<?php
/**
 * Widget Logic Parser - Safe translator from Widget Logic PHP snippets to structured rules.
 *
 * Widget Logic stored visibility conditions as PHP expression strings that
 * were evaluated with eval() (5.x) or against a whitelist (6.x+). This parser
 * never executes PHP. It tokenizes the expression, builds a tiny AST limited
 * to function calls, boolean literals, `!`, `&&` and `||`, plus the handful of
 * comparisons people used to test the current URL, and translates recognised
 * patterns into the structured rule format used by AyudaWP_WVC_Data_Manager.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse Widget Logic snippets into structured visibility rules.
 *
 * @since 1.3.0
 */
class AyudaWP_WVC_Widget_Logic_Parser {

	/**
	 * Maximum number of values accepted in a list argument.
	 *
	 * Each value becomes a stored rule evaluated on every request, so a huge
	 * list is refused rather than imported.
	 *
	 * @since 1.4.0
	 */
	const MAX_LIST_ITEMS = 100;

	/**
	 * Maximum length of the text a URL check looks for.
	 *
	 * @since 1.5.0
	 */
	const MAX_URL_NEEDLE_LENGTH = 200;

	/**
	 * Functions that return the position of a text inside another, or false.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	private static $position_functions = array( 'strpos', 'stripos', 'strrpos', 'strripos', 'mb_strpos', 'mb_stripos', 'mb_strrpos', 'mb_strripos' );

	/**
	 * The ones among them that look for the first occurrence.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	private static $first_position_functions = array( 'strpos', 'stripos', 'mb_strpos', 'mb_stripos' );

	/**
	 * Functions that return the rest of the text from the match on, or false.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	private static $substring_functions = array( 'strstr', 'stristr', 'mb_strstr', 'mb_stristr' );

	/**
	 * Public entry point.
	 *
	 * @param string $logic Raw Widget Logic expression as stored in the database.
	 * @return array {
	 *     @type string $status     One of 'ok', 'unsupported', 'empty', 'always_show', 'always_hide'.
	 *     @type string $action     'show' or 'hide'. Only meaningful when status is 'ok' or 'always_hide'.
	 *     @type string $match_all  '0' for OR, '1' for AND. Only meaningful when status is 'ok'.
	 *     @type array  $rules      Array of rules for save_conditions().
	 *     @type string $reason     Human-readable reason when status is not 'ok'.
	 *     @type string $original   Echoed input (after light normalization) for UI display.
	 * }
	 */
	public static function parse( $logic ) {
		$result = array(
			'status'    => 'unsupported',
			'action'    => 'show',
			'match_all' => '0',
			'rules'     => array(),
			'reason'    => '',
			'original'  => is_string( $logic ) ? $logic : '',
		);

		if ( ! is_string( $logic ) ) {
			$result['reason'] = __( 'Logic is not a string.', 'widget-visibility-control' );
			return $result;
		}

		$normalized = self::normalize_input( $logic );

		if ( '' === $normalized ) {
			$result['status'] = 'always_show';
			return $result;
		}

		// Boolean / integer literals — treat as always_show / always_hide.
		$lower = strtolower( $normalized );
		if ( in_array( $lower, array( 'true', '1' ), true ) ) {
			$result['status'] = 'always_show';
			return $result;
		}
		if ( in_array( $lower, array( 'false', '0' ), true ) ) {
			$result['status']    = 'always_hide';
			$result['action']    = 'hide';
			$result['rules']     = array(
				array(
					'major'        => 'always',
					'minor'        => '',
					'has_children' => '',
				),
			);
			$result['match_all'] = '0';
			return $result;
		}

		try {
			$tokens = self::tokenize( $normalized );
			$pos    = 0;
			$ast    = self::parse_expr( $tokens, $pos );

			if ( $pos < count( $tokens ) ) {
				$result['reason'] = sprintf(
					/* translators: %s: unexpected token value. */
					__( 'Unexpected token after expression: %s', 'widget-visibility-control' ),
					$tokens[ $pos ]['value']
				);
				return $result;
			}

			$resolved = self::ast_to_rules( $ast );

			if ( 'ok' !== $resolved['status'] ) {
				$result['status'] = $resolved['status'];
				$result['reason'] = $resolved['reason'];
				return $result;
			}

			$result['status']    = 'ok';
			$result['action']    = $resolved['action'];
			$result['match_all'] = $resolved['match_all'];
			$result['rules']     = $resolved['rules'];
			return $result;
		} catch ( Exception $e ) {
			$result['reason'] = $e->getMessage();
			return $result;
		}
	}

	/**
	 * Trim whitespace, strip PHP wrappers, trailing semicolons and a leading return.
	 *
	 * @param string $logic Raw input.
	 * @return string Normalized input ready to tokenize.
	 */
	private static function normalize_input( $logic ) {
		$out = trim( $logic );

		// Strip PHP open/close tags if present.
		$out = preg_replace( '/^<\?(php)?/i', '', $out );
		$out = preg_replace( '/\?>\s*$/', '', $out );
		$out = trim( $out );

		// Strip trailing semicolons.
		$out = rtrim( $out, "; \t\n\r\0\x0B" );
		$out = trim( $out );

		// Strip a single leading `return`.
		if ( preg_match( '/^return\s+(.*)$/is', $out, $m ) ) {
			$out = trim( $m[1] );
			$out = rtrim( $out, "; \t\n\r\0\x0B" );
			$out = trim( $out );
		}

		return $out;
	}

	/**
	 * Tokenize an expression. Returns a list of tokens.
	 *
	 * Each token is array('type' => string, 'value' => string).
	 * Token types: IDENT, NUMBER, STRING, VARIABLE, AND, OR, NOT, CMP, LPAREN,
	 * RPAREN, LBRACKET, RBRACKET, COMMA, TRUE, FALSE.
	 *
	 * @param string $input Normalized expression.
	 * @return array
	 * @throws Exception When an unrecognised character appears.
	 */
	private static function tokenize( $input ) {
		$tokens = array();
		$len    = strlen( $input );
		$i      = 0;

		while ( $i < $len ) {
			$ch = $input[ $i ];

			if ( ctype_space( $ch ) ) {
				$i++;
				continue;
			}

			if ( '&' === $ch && $i + 1 < $len && '&' === $input[ $i + 1 ] ) {
				$tokens[] = array(
					'type'  => 'AND',
					'value' => '&&',
				);
				$i       += 2;
				continue;
			}

			if ( '|' === $ch && $i + 1 < $len && '|' === $input[ $i + 1 ] ) {
				$tokens[] = array(
					'type'  => 'OR',
					'value' => '||',
				);
				$i       += 2;
				continue;
			}

			// Equality comparisons: ==, ===, != and !==.
			if ( ( '=' === $ch || '!' === $ch ) && $i + 1 < $len && '=' === $input[ $i + 1 ] ) {
				$operator = ( $i + 2 < $len && '=' === $input[ $i + 2 ] ) ? $ch . '==' : $ch . '=';
				$tokens[] = array(
					'type'  => 'CMP',
					'value' => $operator,
				);
				$i       += strlen( $operator );
				continue;
			}

			if ( '!' === $ch ) {
				$tokens[] = array(
					'type'  => 'NOT',
					'value' => '!',
				);
				$i++;
				continue;
			}

			if ( '(' === $ch ) {
				$tokens[] = array(
					'type'  => 'LPAREN',
					'value' => '(',
				);
				$i++;
				continue;
			}

			if ( ')' === $ch ) {
				$tokens[] = array(
					'type'  => 'RPAREN',
					'value' => ')',
				);
				$i++;
				continue;
			}

			if ( ',' === $ch ) {
				$tokens[] = array(
					'type'  => 'COMMA',
					'value' => ',',
				);
				$i++;
				continue;
			}

			if ( '[' === $ch ) {
				$tokens[] = array(
					'type'  => 'LBRACKET',
					'value' => '[',
				);
				$i++;
				continue;
			}

			if ( ']' === $ch ) {
				$tokens[] = array(
					'type'  => 'RBRACKET',
					'value' => ']',
				);
				$i++;
				continue;
			}

			// Strings.
			if ( '"' === $ch || "'" === $ch ) {
				$quote = $ch;
				$j     = $i + 1;
				$buf   = '';
				while ( $j < $len ) {
					$cj = $input[ $j ];
					if ( '\\' === $cj && $j + 1 < $len ) {
						$buf .= $input[ $j + 1 ];
						$j   += 2;
						continue;
					}
					if ( $cj === $quote ) {
						break;
					}
					$buf .= $cj;
					$j++;
				}
				if ( $j >= $len || $input[ $j ] !== $quote ) {
					throw new Exception( esc_html__( 'Unterminated string literal.', 'widget-visibility-control' ) );
				}
				$tokens[] = array(
					'type'  => 'STRING',
					'value' => $buf,
				);
				$i        = $j + 1;
				continue;
			}

			// Variables. Only the name is kept: the parser decides what it accepts.
			if ( '$' === $ch ) {
				$j = $i + 1;
				while ( $j < $len && ( ctype_alnum( $input[ $j ] ) || '_' === $input[ $j ] ) ) {
					$j++;
				}
				if ( $j > $i + 1 ) {
					$tokens[] = array(
						'type'  => 'VARIABLE',
						'value' => substr( $input, $i + 1, $j - $i - 1 ),
					);
					$i        = $j;
					continue;
				}
			}

			// Numbers (positive integers; signed handled by NOT/operators in parser).
			if ( ctype_digit( $ch ) ) {
				$j = $i;
				while ( $j < $len && ctype_digit( $input[ $j ] ) ) {
					$j++;
				}
				$tokens[] = array(
					'type'  => 'NUMBER',
					'value' => substr( $input, $i, $j - $i ),
				);
				$i        = $j;
				continue;
			}

			// Identifiers.
			if ( ctype_alpha( $ch ) || '_' === $ch ) {
				$j = $i;
				while ( $j < $len && ( ctype_alnum( $input[ $j ] ) || '_' === $input[ $j ] ) ) {
					$j++;
				}
				$word  = substr( $input, $i, $j - $i );
				$lower = strtolower( $word );
				if ( 'true' === $lower ) {
					$tokens[] = array(
						'type'  => 'TRUE',
						'value' => 'true',
					);
				} elseif ( 'false' === $lower ) {
					$tokens[] = array(
						'type'  => 'FALSE',
						'value' => 'false',
					);
				} else {
					$tokens[] = array(
						'type'  => 'IDENT',
						'value' => $word,
					);
				}
				$i = $j;
				continue;
			}

			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: unrecognised character in the Widget Logic expression. */
						__( 'Unrecognised character: %s', 'widget-visibility-control' ),
						$ch
					)
				)
			);
		}

		return $tokens;
	}

	/**
	 * Recursive-descent parser entry point. Returns AST node.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 */
	private static function parse_expr( $tokens, &$pos ) {
		return self::parse_or( $tokens, $pos );
	}

	/**
	 * OR expressions.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 */
	private static function parse_or( $tokens, &$pos ) {
		$left = self::parse_and( $tokens, $pos );

		if ( $pos >= count( $tokens ) || 'OR' !== $tokens[ $pos ]['type'] ) {
			return $left;
		}

		$children = array( $left );
		while ( $pos < count( $tokens ) && 'OR' === $tokens[ $pos ]['type'] ) {
			$pos++;
			$children[] = self::parse_and( $tokens, $pos );
		}

		return array(
			'type'     => 'or',
			'children' => $children,
		);
	}

	/**
	 * AND expressions.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 */
	private static function parse_and( $tokens, &$pos ) {
		$left = self::parse_comparison( $tokens, $pos );

		if ( $pos >= count( $tokens ) || 'AND' !== $tokens[ $pos ]['type'] ) {
			return $left;
		}

		$children = array( $left );
		while ( $pos < count( $tokens ) && 'AND' === $tokens[ $pos ]['type'] ) {
			$pos++;
			$children[] = self::parse_comparison( $tokens, $pos );
		}

		return array(
			'type'     => 'and',
			'children' => $children,
		);
	}

	/**
	 * Comparison: an operand, optionally compared with another one.
	 *
	 * Comparisons are not kept in the tree. The only ones understood are the
	 * idioms that test the current URL, and those are folded straight into a
	 * `urltest` leaf (or its negation); anything else is refused.
	 *
	 * @since 1.5.0
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 * @throws Exception When the comparison is not a URL check, or a value stands alone.
	 */
	private static function parse_comparison( $tokens, &$pos ) {
		$left = self::parse_operand( $tokens, $pos );

		if ( $pos < count( $tokens ) && 'CMP' === $tokens[ $pos ]['type'] ) {
			$operator = $tokens[ $pos ]['value'];
			$pos++;
			$right = self::parse_operand( $tokens, $pos );

			return self::comparison_to_node( $left, $operator, $right );
		}

		if ( 'value' === $left['type'] ) {
			throw new Exception( esc_html__( 'A value on its own is not a condition.', 'widget-visibility-control' ) );
		}

		return $left;
	}

	/**
	 * One side of a comparison: a number, a string, the request URI, or an expression.
	 *
	 * @since 1.5.0
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node, of type `value` for the literal operands.
	 */
	private static function parse_operand( $tokens, &$pos ) {
		if ( $pos < count( $tokens ) ) {
			$tok = $tokens[ $pos ];

			if ( 'NUMBER' === $tok['type'] ) {
				$pos++;
				return array(
					'type'  => 'value',
					'kind'  => 'number',
					'value' => (int) $tok['value'],
				);
			}

			if ( 'STRING' === $tok['type'] ) {
				$pos++;
				return array(
					'type'  => 'value',
					'kind'  => 'string',
					'value' => $tok['value'],
				);
			}

			if ( 'VARIABLE' === $tok['type'] ) {
				self::parse_request_uri( $tokens, $pos );
				return array(
					'type'  => 'value',
					'kind'  => 'request_uri',
					'value' => '',
				);
			}
		}

		return self::parse_unary( $tokens, $pos );
	}

	/**
	 * Consume `$_SERVER['REQUEST_URI']`, the only variable the parser accepts.
	 *
	 * @since 1.5.0
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference), on the variable token.
	 * @return void
	 * @throws Exception For any other variable.
	 */
	private static function parse_request_uri( $tokens, &$pos ) {
		$name = $tokens[ $pos ]['value'];

		if (
			'_SERVER' === $name
			&& isset( $tokens[ $pos + 3 ] )
			&& 'LBRACKET' === $tokens[ $pos + 1 ]['type']
			&& 'STRING' === $tokens[ $pos + 2 ]['type']
			&& 'REQUEST_URI' === $tokens[ $pos + 2 ]['value']
			&& 'RBRACKET' === $tokens[ $pos + 3 ]['type']
		) {
			$pos += 4;
			return;
		}

		throw new Exception(
			esc_html(
				sprintf(
					/* translators: %s: name of a PHP variable found in the Widget Logic code. */
					__( 'This rule reads a PHP variable, which cannot be translated automatically: %s', 'widget-visibility-control' ),
					'$' . $name
				)
			)
		);
	}

	/**
	 * Turn a comparison into a URL check, the only kind that is understood.
	 *
	 * Covers `strpos( $_SERVER['REQUEST_URI'], 'x' ) !== false` and its
	 * relatives, `strpos( ... ) === 0` for "starts with", and the request URI
	 * compared with a string, with the operands in either order.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $left     Left operand node.
	 * @param string $operator One of ==, ===, != and !==.
	 * @param array  $right    Right operand node.
	 * @return array AST node: a `urltest` leaf, or a `not` wrapping one.
	 * @throws Exception When the comparison is anything else.
	 */
	private static function comparison_to_node( $left, $operator, $right ) {
		// `false !== strpos( ... )` reads the same as `strpos( ... ) !== false`.
		if ( in_array( $left['type'], array( 'bool', 'value' ), true ) && 'call' === $right['type'] ) {
			$swap  = $left;
			$left  = $right;
			$right = $swap;
		}

		$strict  = ( 3 === strlen( $operator ) );
		$differs = ( '!' === $operator[0] );
		$test    = null;
		$negate  = false;

		if ( 'call' === $left['type'] ) {
			$fn     = strtolower( $left['fn'] );
			$needle = self::url_needle( $left['args'] );

			if ( null !== $needle && 'bool' === $right['type'] && false === $right['value'] ) {
				if ( in_array( $fn, self::$position_functions, true ) ) {
					// Loosely compared with false, position 0 counts as "not found",
					// and a text that starts with a slash can sit exactly there.
					if ( $strict || '/' !== $needle[0] ) {
						$test   = self::url_test( 'contains', $needle );
						$negate = ! $differs;
					}
				} elseif ( in_array( $fn, self::$substring_functions, true ) && ( $strict || '0' !== $needle ) ) {
					$test   = self::url_test( 'contains', $needle );
					$negate = ! $differs;
				}
			} elseif (
				null !== $needle
				&& $strict
				&& 'value' === $right['type'] && 'number' === $right['kind'] && 0 === $right['value']
				&& in_array( $fn, self::$first_position_functions, true )
			) {
				$test   = self::url_test( 'starts', $needle );
				$negate = $differs;
			}
		} elseif ( 'value' === $left['type'] && 'value' === $right['type'] ) {
			// The request URI and a string, in either order.
			$kinds = array( $left['kind'], $right['kind'] );
			sort( $kinds );

			if ( array( 'request_uri', 'string' ) === $kinds ) {
				$test   = self::url_test( 'equals', 'string' === $left['kind'] ? $left['value'] : $right['value'] );
				$negate = $differs;
			}
		}

		if ( null === $test ) {
			throw new Exception( esc_html__( 'This comparison cannot be translated automatically. Only checks on the current URL are understood.', 'widget-visibility-control' ) );
		}

		if ( $negate ) {
			return array(
				'type'  => 'not',
				'child' => $test,
			);
		}

		return $test;
	}

	/**
	 * Build the leaf node for a URL check.
	 *
	 * @since 1.5.0
	 *
	 * @param string $mode   One of contains, starts and equals.
	 * @param string $needle Text the check looks for.
	 * @return array AST node.
	 */
	private static function url_test( $mode, $needle ) {
		return array(
			'type'   => 'urltest',
			'mode'   => $mode,
			'needle' => (string) $needle,
		);
	}

	/**
	 * Read the text a URL function looks for, when it is called on the request URI.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Argument descriptors of the call.
	 * @return string|null The text, or null when the call is not `fn( $_SERVER['REQUEST_URI'], 'text' )`.
	 */
	private static function url_needle( $args ) {
		if (
			2 === count( $args )
			&& 'request_uri' === $args[0]['type']
			&& 'string' === $args[1]['type']
			&& '' !== $args[1]['value']
		) {
			return (string) $args[1]['value'];
		}

		return null;
	}

	/**
	 * Unary `!` prefix.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 */
	private static function parse_unary( $tokens, &$pos ) {
		if ( $pos < count( $tokens ) && 'NOT' === $tokens[ $pos ]['type'] ) {
			$pos++;
			return array(
				'type'  => 'not',
				'child' => self::parse_unary( $tokens, $pos ),
			);
		}
		return self::parse_primary( $tokens, $pos );
	}

	/**
	 * Primary: parenthesised expression, function call, or bool literal.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array AST node.
	 * @throws Exception On unexpected end of input or invalid call syntax.
	 */
	private static function parse_primary( $tokens, &$pos ) {
		if ( $pos >= count( $tokens ) ) {
			throw new Exception( esc_html__( 'Unexpected end of expression.', 'widget-visibility-control' ) );
		}

		$tok = $tokens[ $pos ];

		if ( 'LPAREN' === $tok['type'] ) {
			$pos++;
			$inner = self::parse_expr( $tokens, $pos );
			if ( $pos >= count( $tokens ) || 'RPAREN' !== $tokens[ $pos ]['type'] ) {
				throw new Exception( esc_html__( 'Missing closing parenthesis.', 'widget-visibility-control' ) );
			}
			$pos++;
			return $inner;
		}

		if ( 'TRUE' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'bool',
				'value' => true,
			);
		}

		if ( 'FALSE' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'bool',
				'value' => false,
			);
		}

		if ( 'IDENT' === $tok['type'] ) {
			$fn = $tok['value'];
			$pos++;
			if ( $pos >= count( $tokens ) || 'LPAREN' !== $tokens[ $pos ]['type'] ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %s: identifier name that wasn't followed by a function call. */
							__( 'Bare identifier "%s" without call.', 'widget-visibility-control' ),
							$fn
						)
					)
				);
			}
			$pos++;
			$args = array();
			if ( $pos < count( $tokens ) && 'RPAREN' !== $tokens[ $pos ]['type'] ) {
				$args[] = self::parse_arg( $tokens, $pos );
				while ( $pos < count( $tokens ) && 'COMMA' === $tokens[ $pos ]['type'] ) {
					$pos++;
					$args[] = self::parse_arg( $tokens, $pos );
				}
			}
			if ( $pos >= count( $tokens ) || 'RPAREN' !== $tokens[ $pos ]['type'] ) {
				throw new Exception( esc_html__( 'Missing closing parenthesis in function call.', 'widget-visibility-control' ) );
			}
			$pos++;
			return array(
				'type' => 'call',
				'fn'   => $fn,
				'args' => $args,
			);
		}

		throw new Exception(
			esc_html(
				sprintf(
					/* translators: %s: unexpected token value. */
					__( 'Unexpected token: %s', 'widget-visibility-control' ),
					$tok['value']
				)
			)
		);
	}

	/**
	 * Parse a function-call argument: NUMBER, STRING, TRUE, FALSE or a list.
	 *
	 * Lists are accepted in both notations, `array( 1, 2 )` and `[ 1, 2 ]`, and
	 * come back as a single descriptor of type `array` holding the parsed items.
	 *
	 * @since 1.4.0 Lists of literals are supported.
	 *
	 * @param array $tokens Token list.
	 * @param int   $pos    Current position (by reference).
	 * @return array Argument descriptor.
	 * @throws Exception When the argument is not a simple literal or a list of them.
	 */
	private static function parse_arg( $tokens, &$pos ) {
		if ( $pos >= count( $tokens ) ) {
			throw new Exception( esc_html__( 'Unexpected end of expression in argument.', 'widget-visibility-control' ) );
		}
		$tok = $tokens[ $pos ];

		// Long array syntax: array( ... ).
		if ( 'IDENT' === $tok['type'] && 'array' === strtolower( $tok['value'] ) ) {
			$pos++;
			if ( $pos >= count( $tokens ) || 'LPAREN' !== $tokens[ $pos ]['type'] ) {
				throw new Exception( esc_html__( 'Malformed array argument.', 'widget-visibility-control' ) );
			}
			$pos++;
			return self::parse_arg_list( $tokens, $pos, 'RPAREN' );
		}

		// Short array syntax: [ ... ].
		if ( 'LBRACKET' === $tok['type'] ) {
			$pos++;
			return self::parse_arg_list( $tokens, $pos, 'RBRACKET' );
		}
		// The request URI, as the first argument of a URL check.
		if ( 'VARIABLE' === $tok['type'] ) {
			self::parse_request_uri( $tokens, $pos );
			return array(
				'type'  => 'request_uri',
				'value' => '',
			);
		}
		if ( 'NUMBER' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'number',
				'value' => (int) $tok['value'],
			);
		}
		if ( 'STRING' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'string',
				'value' => $tok['value'],
			);
		}
		if ( 'TRUE' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'bool',
				'value' => true,
			);
		}
		if ( 'FALSE' === $tok['type'] ) {
			$pos++;
			return array(
				'type'  => 'bool',
				'value' => false,
			);
		}

		throw new Exception( esc_html__( 'Only literal numbers, strings, true and false are supported as function arguments.', 'widget-visibility-control' ) );
	}

	/**
	 * Parse the items of a list argument up to its closing token.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $tokens  Token list.
	 * @param int    $pos     Current position (by reference).
	 * @param string $closing Token type that closes the list (RPAREN or RBRACKET).
	 * @return array Argument descriptor of type `array`.
	 * @throws Exception When the list is not closed or holds a non-literal item.
	 */
	private static function parse_arg_list( $tokens, &$pos, $closing ) {
		$items = array();

		if ( $pos < count( $tokens ) && $closing !== $tokens[ $pos ]['type'] ) {
			$items[] = self::parse_arg( $tokens, $pos );
			while ( $pos < count( $tokens ) && 'COMMA' === $tokens[ $pos ]['type'] ) {
				$pos++;
				// Tolerate a trailing comma before the closing token.
				if ( $pos < count( $tokens ) && $closing === $tokens[ $pos ]['type'] ) {
					break;
				}
				$items[] = self::parse_arg( $tokens, $pos );
			}
		}

		if ( $pos >= count( $tokens ) || $closing !== $tokens[ $pos ]['type'] ) {
			throw new Exception( esc_html__( 'Malformed array argument.', 'widget-visibility-control' ) );
		}
		$pos++;

		foreach ( $items as $item ) {
			if ( 'array' === $item['type'] ) {
				throw new Exception( esc_html__( 'Nested arrays are not supported.', 'widget-visibility-control' ) );
			}
		}

		// Every value becomes a stored rule that is evaluated on each request,
		// so refuse absurdly long lists instead of importing them.
		if ( count( $items ) > self::MAX_LIST_ITEMS ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %d: maximum number of values allowed in a list. */
						__( 'This list has too many values to import (limit is %d).', 'widget-visibility-control' ),
						self::MAX_LIST_ITEMS
					)
				)
			);
		}

		return array(
			'type'  => 'array',
			'value' => $items,
		);
	}

	/**
	 * Convert an AST to the structured rules array (or report unsupported).
	 *
	 * @param array $ast Root AST node.
	 * @return array
	 */
	private static function ast_to_rules( $ast ) {
		$ast = self::collapse_nots( $ast );

		// Expressions made only of true/false collapse to a constant, which is
		// how Widget Logic users park a widget as always on or always off.
		$constant = self::constant_value( $ast );
		if ( null !== $constant ) {
			$ast = array(
				'type'  => 'bool',
				'value' => $constant,
			);
		}

		// Bool literals at the top.
		if ( 'bool' === $ast['type'] ) {
			if ( $ast['value'] ) {
				return array(
					'status' => 'always_show',
					'reason' => '',
				);
			}
			return array(
				'status'    => 'ok',
				'action'    => 'hide',
				'match_all' => '0',
				'rules'     => array(
					array(
						'major'        => 'always',
						'minor'        => '',
						'has_children' => '',
					),
				),
			);
		}

		// First pass: translate the tree as it is, keeping every condition positive.
		$direct = self::rules_from_tree( $ast, false );
		if ( 'ok' === $direct['status'] ) {
			return $direct;
		}

		// Second pass: if the whole expression is a negation, De Morgan turns it
		// into an equivalent positive tree evaluated with the `hide` action.
		$negated = self::negate_tree( $ast );
		if ( null !== $negated ) {
			$flipped = self::rules_from_tree( $negated, true );
			if ( 'ok' === $flipped['status'] ) {
				return $flipped;
			}
		}

		return $direct;
	}

	/**
	 * Translate an AST whose conditions are all positive into a rules array.
	 *
	 * When $negated is true the caller has already applied De Morgan, so the
	 * widget must be hidden when the conditions match instead of shown.
	 *
	 * @since 1.4.0
	 *
	 * @param array $ast     AST node (call, not-of-call, and, or).
	 * @param bool  $negated Whether the original expression was the negation of this tree.
	 * @return array Result array with status ok or unsupported.
	 */
	private static function rules_from_tree( $ast, $negated ) {
		$action = $negated ? 'hide' : 'show';

		if ( 'and' === $ast['type'] || 'or' === $ast['type'] ) {
			// The conjunction is read straight from this tree: when the caller
			// applied De Morgan, negate_tree() already swapped AND and OR.
			$match_all = ( 'and' === $ast['type'] ) ? '1' : '0';
			$leaves    = $ast['children'];

			foreach ( $leaves as $leaf ) {
				if ( 'and' === $leaf['type'] || 'or' === $leaf['type'] ) {
					return array(
						'status' => 'unsupported',
						'reason' => __( 'This rule mixes AND and OR. A widget can only use one of the two at a time.', 'widget-visibility-control' ),
					);
				}
			}

			$rules = array();
			foreach ( $leaves as $leaf ) {
				$leaf_rules = self::leaf_to_rules( $leaf );
				if ( null === $leaf_rules ) {
					return array(
						'status' => 'unsupported',
						'reason' => self::describe_unsupported_leaf( $leaf ),
					);
				}
				$rules = array_merge( $rules, $leaf_rules );
			}

			// A list of values expands to several rules joined with OR, so it
			// cannot live inside a condition that ends up joined with AND: the
			// stored format keeps a single AND/OR switch for the whole widget.
			if ( '1' === $match_all && count( $rules ) > count( $leaves ) ) {
				return array(
					'status' => 'unsupported',
					'reason' => __( 'This rule uses a list of values inside an AND condition, which cannot be stored yet.', 'widget-visibility-control' ),
				);
			}

			return array(
				'status'    => 'ok',
				'action'    => $action,
				'match_all' => $match_all,
				'rules'     => $rules,
			);
		}

		// Single leaf at root.
		$rules = self::leaf_to_rules( $ast );
		if ( null === $rules ) {
			return array(
				'status' => 'unsupported',
				'reason' => self::describe_unsupported_leaf( $ast ),
			);
		}

		return array(
			'status'    => 'ok',
			'action'    => $action,
			'match_all' => '0',
			'rules'     => $rules,
		);
	}

	/**
	 * Return the positive tree T such that the given AST is equivalent to NOT T.
	 *
	 * Handles a negated expression, and the two De Morgan shapes where every
	 * branch of an AND/OR is negated. Returns null when the expression is not a
	 * whole negation, for instance when a negated condition is mixed with a
	 * positive one: that needs per-rule negation, which the stored format does
	 * not support.
	 *
	 * @since 1.4.0
	 *
	 * @param array $ast AST node.
	 * @return array|null Equivalent positive AST, or null.
	 */
	private static function negate_tree( $ast ) {
		if ( 'not' === $ast['type'] ) {
			return $ast['child'];
		}

		if ( 'and' !== $ast['type'] && 'or' !== $ast['type'] ) {
			return null;
		}

		$children = array();
		foreach ( $ast['children'] as $child ) {
			if ( 'not' !== $child['type'] ) {
				return null;
			}
			$children[] = $child['child'];
		}

		// not(A) and not(B) is not(A or B); not(A) or not(B) is not(A and B).
		return array(
			'type'     => ( 'and' === $ast['type'] ) ? 'or' : 'and',
			'children' => $children,
		);
	}

	/**
	 * Resolve an expression built only from true/false literals.
	 *
	 * Widget Logic accepts things like `true`, `false && true` or `!(true ||
	 * false)`, which people use to park a widget as always visible or always
	 * hidden. They carry no condition, so they fold into a single boolean.
	 *
	 * @since 1.4.0
	 *
	 * @param array $ast AST node.
	 * @return bool|null The constant value, or null if the expression has conditions.
	 */
	private static function constant_value( $ast ) {
		if ( ! isset( $ast['type'] ) ) {
			return null;
		}

		if ( 'bool' === $ast['type'] ) {
			return (bool) $ast['value'];
		}

		if ( 'not' === $ast['type'] ) {
			$child = self::constant_value( $ast['child'] );
			return ( null === $child ) ? null : ! $child;
		}

		if ( 'and' === $ast['type'] || 'or' === $ast['type'] ) {
			$is_and = ( 'and' === $ast['type'] );
			$result = $is_and;
			foreach ( $ast['children'] as $child ) {
				$value = self::constant_value( $child );
				if ( null === $value ) {
					return null;
				}
				$result = $is_and ? ( $result && $value ) : ( $result || $value );
			}
			return $result;
		}

		return null;
	}

	/**
	 * Collapse pairs of negations so that `!!is_home()` behaves as `is_home()`.
	 *
	 * @since 1.4.0
	 *
	 * @param array $ast AST node.
	 * @return array Normalised AST node.
	 */
	private static function collapse_nots( $ast ) {
		if ( ! isset( $ast['type'] ) ) {
			return $ast;
		}

		if ( 'not' === $ast['type'] ) {
			$child = self::collapse_nots( $ast['child'] );
			if ( 'not' === $child['type'] ) {
				return $child['child'];
			}
			return array(
				'type'  => 'not',
				'child' => $child,
			);
		}

		if ( 'and' === $ast['type'] || 'or' === $ast['type'] ) {
			$children = array();
			foreach ( $ast['children'] as $child ) {
				$children[] = self::collapse_nots( $child );
			}
			return array(
				'type'     => $ast['type'],
				'children' => $children,
			);
		}

		return $ast;
	}

	/**
	 * Translate a single leaf node (call or `!call`) to rules, or null if unsupported.
	 *
	 * A single call can produce more than one rule when it takes a list of
	 * values, as in `is_page( array( 1, 2 ) )`.
	 *
	 * @since 1.4.0 Returns a list of rules instead of a single rule.
	 *
	 * @param array $node Leaf AST node.
	 * @return array|null List of rules, or null when the leaf cannot be translated.
	 */
	private static function leaf_to_rules( $node ) {
		if ( 'not' === $node['type'] ) {
			$child = $node['child'];
			if ( 'call' !== $child['type'] ) {
				return null;
			}
			// `!is_user_logged_in()` maps to a positive rule of its own, which is
			// what lets it be mixed with other positive conditions.
			if ( 'is_user_logged_in' === $child['fn'] && empty( $child['args'] ) ) {
				return array(
					array(
						'major'        => 'loggedin',
						'minor'        => 'loggedout',
						'has_children' => '',
					),
				);
			}
			return null;
		}

		if ( 'urltest' === $node['type'] ) {
			return self::rules_from_url_test( $node['mode'], $node['needle'] );
		}

		if ( 'call' !== $node['type'] ) {
			return null;
		}

		return self::map_call_to_rule( $node['fn'], $node['args'] );
	}

	/**
	 * Build a human-readable reason for an unsupported leaf.
	 *
	 * @param array $node Leaf AST node.
	 * @return string Reason text.
	 */
	private static function describe_unsupported_leaf( $node ) {
		if ( 'call' === $node['type'] ) {
			return sprintf(
				/* translators: %s: function name. */
				__( 'This function cannot be translated automatically: %s', 'widget-visibility-control' ),
				$node['fn'] . '()'
			);
		}
		if ( 'not' === $node['type'] && isset( $node['child']['type'] ) && 'call' === $node['child']['type'] ) {
			return sprintf(
				/* translators: %s: function name being negated. */
				__( 'This rule mixes a negated condition with positive ones, which cannot be stored yet. Negated: !%s', 'widget-visibility-control' ),
				$node['child']['fn'] . '()'
			);
		}
		if ( 'urltest' === $node['type'] ) {
			return __( 'This URL check cannot be translated automatically.', 'widget-visibility-control' );
		}
		if ( 'not' === $node['type'] && isset( $node['child']['type'] ) && 'urltest' === $node['child']['type'] ) {
			return __( 'This rule mixes a negated URL check with positive conditions, which cannot be stored yet.', 'widget-visibility-control' );
		}
		return __( 'The shape of this expression cannot be translated automatically.', 'widget-visibility-control' );
	}

	/**
	 * Map a function call to a list of rules, or null if unsupported.
	 *
	 * Calls that take a list of values, such as `is_page( array( 1, 2 ) )`,
	 * expand to one rule per value. Whether that expansion can actually be
	 * stored is decided upstream, in rules_from_tree().
	 *
	 * @since 1.4.0 Returns a list of rules and understands list arguments.
	 *
	 * @param string $fn   Function name.
	 * @param array  $args Argument descriptors.
	 * @return array|null List of rules, or null when the call cannot be translated.
	 */
	private static function map_call_to_rule( $fn, $args ) {
		$fn = strtolower( $fn );

		// An empty list behaves like calling the function with no arguments.
		if ( 1 === count( $args ) && 'array' === $args[0]['type'] && empty( $args[0]['value'] ) ) {
			$args = array();
		}

		switch ( $fn ) {
			case 'is_home':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'posts' );
				}
				return null;

			case 'is_front_page':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'front' );
				}
				return null;

			case 'is_archive':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'archive' );
				}
				return null;

			case 'is_search':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'search' );
				}
				return null;

			case 'is_404':
				if ( empty( $args ) ) {
					return self::rules( 'page', '404' );
				}
				return null;

			case 'is_date':
				if ( empty( $args ) ) {
					return self::rules( 'date', '' );
				}
				return null;

			case 'is_day':
				if ( empty( $args ) ) {
					return self::rules( 'date', 'day' );
				}
				return null;

			case 'is_month':
				if ( empty( $args ) ) {
					return self::rules( 'date', 'month' );
				}
				return null;

			case 'is_year':
				if ( empty( $args ) ) {
					return self::rules( 'date', 'year' );
				}
				return null;

			case 'is_user_logged_in':
				if ( empty( $args ) ) {
					return self::rules( 'loggedin', 'loggedin' );
				}
				return null;

			case 'is_single':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'post_type-post' );
				}
				return null;

			case 'is_sticky':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'sticky' );
				}
				return null;

			case 'is_paged':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'paged' );
				}
				return null;

			case 'current_user_can':
				if ( 1 === count( $args ) && 'string' === $args[0]['type'] ) {
					$cap = sanitize_key( $args[0]['value'] );
					if ( '' !== $cap ) {
						return self::rules( 'capability', $cap );
					}
				}
				return null;

			case 'is_singular':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'singular' );
				}
				if ( 1 === count( $args ) ) {
					$items = self::expand_arg( $args[0] );
					$rules = array();
					foreach ( $items as $item ) {
						if ( 'string' !== $item['type'] ) {
							return null;
						}
						$post_type = sanitize_key( $item['value'] );
						if ( '' === $post_type ) {
							return null;
						}
						$rules[] = self::rule( 'page', 'post_type-' . $post_type );
					}
					return $rules ? $rules : null;
				}
				return null;

			case 'is_post_type_archive':
				if ( 1 === count( $args ) ) {
					$items = self::expand_arg( $args[0] );
					$rules = array();
					foreach ( $items as $item ) {
						if ( 'string' !== $item['type'] ) {
							return null;
						}
						$post_type = sanitize_key( $item['value'] );
						if ( '' === $post_type ) {
							return null;
						}
						$rules[] = self::rule( 'page', 'post_type_archive-' . $post_type );
					}
					return $rules ? $rules : null;
				}
				return null;

			case 'is_page':
				// Without arguments it means "any page", same as is_singular('page').
				if ( empty( $args ) ) {
					return self::rules( 'page', 'post_type-page' );
				}
				if ( 1 === count( $args ) ) {
					return self::rules_from_page_arg( $args[0] );
				}
				return null;

			case 'is_category':
				if ( empty( $args ) ) {
					return self::rules( 'category', '' );
				}
				if ( 1 === count( $args ) ) {
					return self::rules_from_term_arg( $args[0], 'category', 'category' );
				}
				return null;

			case 'in_category':
			case 'has_category':
				if ( 1 === count( $args ) ) {
					return self::rules_from_term_arg( $args[0], 'category', 'category' );
				}
				return null;

			case 'is_attachment':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'post_type-attachment' );
				}
				return null;

			case 'is_privacy_policy':
				if ( empty( $args ) ) {
					$policy = (int) get_option( 'wp_page_for_privacy_policy' );
					if ( $policy > 0 ) {
						return self::rules( 'page', (string) $policy );
					}
				}
				return null;

			case 'is_tree':
				// Widget Logic's is_tree() means "this page or any descendant".
				if ( 1 === count( $args ) ) {
					$rules = self::rules_from_page_arg( $args[0] );
					if ( null === $rules ) {
						return null;
					}
					foreach ( $rules as $i => $rule ) {
						$rules[ $i ]['has_children'] = '1';
					}
					return $rules;
				}
				return null;

			// --- WooCommerce -------------------------------------------------
			case 'is_product':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'post_type-product' );
				}
				return null;

			case 'is_shop':
				if ( empty( $args ) ) {
					return self::rules( 'page', 'post_type_archive-product' );
				}
				return null;

			case 'is_cart':
			case 'is_checkout':
			case 'is_account_page':
				if ( empty( $args ) ) {
					$page_id = self::resolve_woocommerce_page( $fn );
					if ( null !== $page_id ) {
						return self::rules( 'page', (string) $page_id );
					}
				}
				return null;

			case 'is_product_category':
				if ( empty( $args ) ) {
					return self::rules( 'taxonomy', 'product_cat' );
				}
				if ( 1 === count( $args ) ) {
					return self::rules_from_taxonomy_terms( $args[0], 'product_cat' );
				}
				return null;

			case 'is_product_tag':
				if ( empty( $args ) ) {
					return self::rules( 'taxonomy', 'product_tag' );
				}
				if ( 1 === count( $args ) ) {
					return self::rules_from_taxonomy_terms( $args[0], 'product_tag' );
				}
				return null;

			case 'is_woocommerce':
				// Any shop page: the product archive, a single product, or a
				// product taxonomy. Expands to the same set WooCommerce checks.
				if ( empty( $args ) ) {
					return array(
						self::rule( 'page', 'post_type_archive-product' ),
						self::rule( 'page', 'post_type-product' ),
						self::rule( 'taxonomy', 'product_cat' ),
						self::rule( 'taxonomy', 'product_tag' ),
					);
				}
				return null;

			case 'is_tag':
				if ( empty( $args ) ) {
					return self::rules( 'tag', '' );
				}
				if ( 1 === count( $args ) ) {
					return self::rules_from_term_arg( $args[0], 'post_tag', 'tag' );
				}
				return null;

			case 'has_tag':
				if ( 1 === count( $args ) ) {
					return self::rules_from_term_arg( $args[0], 'post_tag', 'tag' );
				}
				return null;

			case 'is_author':
				if ( empty( $args ) ) {
					return self::rules( 'author', '' );
				}
				if ( 1 === count( $args ) ) {
					$items = self::expand_arg( $args[0] );
					$rules = array();
					foreach ( $items as $item ) {
						if ( 'number' === $item['type'] ) {
							$rules[] = self::rule( 'author', (string) $item['value'] );
							continue;
						}
						if ( 'string' === $item['type'] ) {
							$id = self::resolve_user_ref( $item['value'] );
							if ( null === $id ) {
								return null;
							}
							$rules[] = self::rule( 'author', (string) $id );
							continue;
						}
						return null;
					}
					return $rules ? $rules : null;
				}
				return null;

			case 'is_tax':
				if ( empty( $args ) ) {
					return self::rules( 'taxonomy', '' );
				}
				if ( 1 === count( $args ) && 'string' === $args[0]['type'] ) {
					$tax = sanitize_key( $args[0]['value'] );
					if ( '' !== $tax ) {
						return self::rules( 'taxonomy', $tax );
					}
				}
				if ( 2 === count( $args ) && 'string' === $args[0]['type'] ) {
					$tax = sanitize_key( $args[0]['value'] );
					if ( '' === $tax ) {
						return null;
					}
					return self::rules_from_taxonomy_terms( $args[1], $tax );
				}
				return null;

			// --- Current URL ---------------------------------------------------
			case 'str_contains':
				$needle = self::url_needle( $args );
				return ( null === $needle ) ? null : self::rules_from_url_test( 'contains', $needle );

			case 'str_starts_with':
				$needle = self::url_needle( $args );
				return ( null === $needle ) ? null : self::rules_from_url_test( 'starts', $needle );

			case 'strpos':
			case 'stripos':
			case 'strrpos':
			case 'strripos':
			case 'mb_strpos':
			case 'mb_stripos':
			case 'mb_strrpos':
			case 'mb_strripos':
				// Used as a condition on its own, the position counts as true from
				// 1 onwards. The request URI starts with a slash, so that is the
				// same as "contains" unless the text looked for starts with one too.
				$needle = self::url_needle( $args );
				if ( null === $needle || '/' === $needle[0] ) {
					return null;
				}
				return self::rules_from_url_test( 'contains', $needle );

			case 'strstr':
			case 'stristr':
			case 'mb_strstr':
			case 'mb_stristr':
				// The text from the match on is true unless it is exactly "0".
				$needle = self::url_needle( $args );
				if ( null === $needle || '0' === $needle ) {
					return null;
				}
				return self::rules_from_url_test( 'contains', $needle );

			case 'has_term':
				// Note the argument order is the reverse of is_tax(): term first.
				if ( 2 === count( $args ) && 'string' === $args[1]['type'] ) {
					$tax = sanitize_key( $args[1]['value'] );
					if ( '' === $tax ) {
						return null;
					}
					return self::rules_from_taxonomy_terms( $args[0], $tax );
				}
				return null;
		}

		return null;
	}

	/**
	 * Build the URL rule equivalent to a check on the request URI.
	 *
	 * The query string is treated as the PHP did: "contains" and "starts with"
	 * look at the whole address, and "is" asks for no query string at all. What
	 * differs is that the stored pattern is compared without regard to case,
	 * with the address decoded and always closed by a slash, so it also matches
	 * /Shop/ or /shop where the PHP only matched /shop/. In a negated check
	 * that means the widget is hidden on those variants too.
	 *
	 * @since 1.5.0
	 *
	 * @param string $mode   One of contains, starts and equals.
	 * @param string $needle Text the check looks for.
	 * @return array|null List holding one rule, or null when the check cannot be stored faithfully.
	 */
	private static function rules_from_url_test( $mode, $needle ) {
		$needle = (string) $needle;

		// An asterisk, plain or encoded, would turn into a wildcard; a fragment
		// never reaches the server; and a dollar sign is most likely a variable
		// inside a double-quoted string, which PHP would have filled in.
		if (
			'' === $needle
			|| strlen( $needle ) > self::MAX_URL_NEEDLE_LENGTH
			|| 1 === preg_match( '/[*#$\x00-\x1F\x7F]|%2a/i', $needle )
		) {
			return null;
		}

		// `shop?orderby` only matched addresses without a slash before the query
		// string. Patterns are compared with that slash always in place.
		$query_at = strpos( $needle, '?' );
		if ( false !== $query_at && $query_at > 0 && '/' !== $needle[ $query_at - 1 ] ) {
			return null;
		}

		// "Starts with" and "is" only make sense for a text that opens like a path.
		if ( 'contains' !== $mode && '/' !== $needle[0] ) {
			return null;
		}

		// On a site installed in a subdirectory the request URI carries that
		// directory, and URL patterns are written relative to the site home.
		$home = AyudaWP_Widget_Conditions::get_home_path();
		if ( '' !== $home ) {
			$home = '/' . $home;

			if ( 'contains' === $mode ) {
				// Found inside the directory itself it would always be true, and
				// straddling its end it has no equivalent.
				if ( false !== strpos( $home . '/', $needle ) ) {
					return null;
				}
				$longest = min( strlen( $home ) + 1, strlen( $needle ) - 1 );
				for ( $length = 2; $length <= $longest; $length++ ) {
					if ( substr( $home . '/', -$length ) === substr( $needle, 0, $length ) ) {
						return null;
					}
				}
			} elseif ( 0 === strpos( $needle, $home . '/' ) ) {
				$needle = substr( $needle, strlen( $home ) );
			} elseif ( 'equals' === $mode && $needle === $home ) {
				$needle = '/';
			} else {
				return null;
			}
		}

		if ( 'contains' === $mode ) {
			$pattern = '*' . $needle . '*';
		} elseif ( 'starts' === $mode ) {
			$pattern = $needle . '*';
		} elseif ( false === $query_at ) {
			// Compared as a whole, the request URI had no query string: a pattern
			// that ends in `?` asks for exactly that.
			$pattern = $needle . '?';
		} else {
			$pattern = $needle;
		}

		// If cleaning the pattern changes it, the rule would not mean the same.
		if ( AyudaWP_Widget_Conditions::sanitize_url_pattern( $pattern ) !== $pattern ) {
			return null;
		}

		return self::rules( 'url', $pattern );
	}

	/**
	 * Resolve the page id behind a WooCommerce endpoint function.
	 *
	 * Returns null when WooCommerce is not installed or the page is not set, so
	 * the rule is reported as untranslatable instead of pointing nowhere.
	 *
	 * @since 1.4.0
	 *
	 * @param string $fn Function name (is_cart, is_checkout, is_account_page).
	 * @return int|null
	 */
	private static function resolve_woocommerce_page( $fn ) {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return null;
		}

		$map = array(
			'is_cart'         => 'cart',
			'is_checkout'     => 'checkout',
			'is_account_page' => 'myaccount',
		);

		if ( ! isset( $map[ $fn ] ) ) {
			return null;
		}

		$page_id = (int) wc_get_page_id( $map[ $fn ] );

		return $page_id > 0 ? $page_id : null;
	}

	/**
	 * Normalise an argument descriptor to a list of scalar descriptors.
	 *
	 * @since 1.4.0
	 *
	 * @param array $arg Argument descriptor.
	 * @return array List of scalar argument descriptors.
	 */
	private static function expand_arg( $arg ) {
		if ( 'array' === $arg['type'] ) {
			return $arg['value'];
		}
		return array( $arg );
	}

	/**
	 * Build page rules from an argument that may be a single value or a list.
	 *
	 * @since 1.4.0
	 *
	 * @param array $arg Argument descriptor.
	 * @return array|null List of rules, or null when any value cannot be resolved.
	 */
	private static function rules_from_page_arg( $arg ) {
		$items = self::expand_arg( $arg );
		$rules = array();

		foreach ( $items as $item ) {
			if ( 'number' === $item['type'] ) {
				$rules[] = self::rule( 'page', (string) $item['value'] );
				continue;
			}
			if ( 'string' === $item['type'] ) {
				$id = self::resolve_page_ref( $item['value'] );
				if ( null === $id ) {
					// One unresolved reference invalidates the whole call: a
					// partial import would silently change what the user had.
					return null;
				}
				$rules[] = self::rule( 'page', (string) $id );
				continue;
			}
			return null;
		}

		return $rules ? $rules : null;
	}

	/**
	 * Build term rules from an argument that may be a single value or a list.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $arg      Argument descriptor.
	 * @param string $taxonomy Taxonomy the terms belong to.
	 * @param string $major    Rule major to build.
	 * @return array|null List of rules, or null when any value cannot be resolved.
	 */
	private static function rules_from_term_arg( $arg, $taxonomy, $major ) {
		$items = self::expand_arg( $arg );
		$rules = array();

		foreach ( $items as $item ) {
			if ( 'number' === $item['type'] ) {
				$rules[] = self::rule( $major, (string) $item['value'] );
				continue;
			}
			if ( 'string' === $item['type'] ) {
				$id = self::resolve_term_ref( $item['value'], $taxonomy );
				if ( null === $id ) {
					return null;
				}
				$rules[] = self::rule( $major, (string) $id );
				continue;
			}
			return null;
		}

		return $rules ? $rules : null;
	}

	/**
	 * Build taxonomy rules for one or more terms of a given taxonomy.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $arg Argument descriptor holding the term or terms.
	 * @param string $tax Taxonomy name, already sanitised.
	 * @return array|null List of rules, or null when any term cannot be resolved.
	 */
	private static function rules_from_taxonomy_terms( $arg, $tax ) {
		$items = self::expand_arg( $arg );
		$rules = array();

		foreach ( $items as $item ) {
			$term_id = null;
			if ( 'number' === $item['type'] ) {
				$term_id = (int) $item['value'];
			} elseif ( 'string' === $item['type'] ) {
				$term_id = self::resolve_term_ref( $item['value'], $tax );
			}
			if ( null === $term_id ) {
				return null;
			}
			$rules[] = self::rule( 'taxonomy', $tax . '_tax_' . $term_id );
		}

		return $rules ? $rules : null;
	}

	/**
	 * Convenience builder for a single-rule list.
	 *
	 * @since 1.4.0
	 *
	 * @param string $major Rule major.
	 * @param string $minor Rule minor.
	 * @return array List holding one rule.
	 */
	private static function rules( $major, $minor ) {
		return array( self::rule( $major, $minor ) );
	}

	/**
	 * Convenience builder for a rule array.
	 *
	 * @param string $major Rule major.
	 * @param string $minor Rule minor.
	 * @return array
	 */
	private static function rule( $major, $minor ) {
		return array(
			'major'        => $major,
			'minor'        => $minor,
			'has_children' => '',
		);
	}

	/**
	 * Resolve a page reference (slug or title) to its post ID.
	 *
	 * @param string $value Slug or title literal.
	 * @return int|null
	 */
	private static function resolve_page_ref( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return null;
		}
		$page = get_page_by_path( $value );
		if ( $page instanceof WP_Post ) {
			return (int) $page->ID;
		}
		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $value,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);
		if ( ! empty( $query->posts ) ) {
			return (int) $query->posts[0]->ID;
		}
		return null;
	}

	/**
	 * Resolve a term reference (slug or name) to its term ID.
	 *
	 * @param string $value    Slug or name literal.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int|null
	 */
	private static function resolve_term_ref( $value, $taxonomy ) {
		$value = (string) $value;
		if ( '' === $value || '' === $taxonomy ) {
			return null;
		}
		$term = get_term_by( 'slug', $value, $taxonomy );
		if ( $term instanceof WP_Term ) {
			return (int) $term->term_id;
		}
		$term = get_term_by( 'name', $value, $taxonomy );
		if ( $term instanceof WP_Term ) {
			return (int) $term->term_id;
		}
		return null;
	}

	/**
	 * Resolve a user reference (slug or login) to a user ID.
	 *
	 * @param string $value Slug or login literal.
	 * @return int|null
	 */
	private static function resolve_user_ref( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return null;
		}
		$user = get_user_by( 'slug', $value );
		if ( $user instanceof WP_User ) {
			return (int) $user->ID;
		}
		$user = get_user_by( 'login', $value );
		if ( $user instanceof WP_User ) {
			return (int) $user->ID;
		}
		return null;
	}
}
