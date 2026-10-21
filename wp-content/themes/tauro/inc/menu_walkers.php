<?php

class Header_Menu_Walker extends Walker_Nav_Menu
{
    /**
     * Start a submenu.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '<ul class="dropdown-menu">';
    }

    /**
     * End a submenu.
     */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul>';
    }

    /**
     * Start menu item.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $has_children = in_array('menu-item-has-children', $item->classes, true);

        if ($depth === 0) {

            $li_class = $has_children
                ? 'nav-item dropdown'
                : 'nav-item';

            $output .= '<li class="' . esc_attr($li_class) . '">';

            $classes = $has_children
                ? 'nav-link dropdown-toggle'
                : 'nav-link';

            $attributes = '';

            if (!empty($item->url)) {
                $attributes .= ' href="' . esc_url($item->url) . '"';
            }

            if ($has_children) {
                $attributes .= ' data-bs-toggle="dropdown"';
            }

            $output .= '<a class="' . esc_attr($classes) . '"' . $attributes . '>';
            $output .= esc_html($item->title);
            $output .= '</a>';

        } else {

            $output .= '<li>';

            $output .= '<a class="dropdown-item" href="' .
                esc_url($item->url) .
                '">';

            $output .= esc_html($item->title);

            $output .= '</a>';
        }
    }

    /**
     * End menu item.
     */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= '</li>';
    }
}
class Footer_Menu_Bottom_Walker extends Walker_Nav_Menu
{
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        // Don't output <ul>
    }

    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        // Don't output </ul>
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $attributes = '';

        if (!empty($item->attr_title)) {
            $attributes .= ' title="' . esc_attr($item->attr_title) . '"';
        }

        if (!empty($item->target)) {
            $attributes .= ' target="' . esc_attr($item->target) . '"';
        }

        if (!empty($item->xfn)) {
            $attributes .= ' rel="' . esc_attr($item->xfn) . '"';
        }

        if (!empty($item->url)) {
            $attributes .= ' href="' . esc_attr($item->url) . '"';
        }

        $output .= '<a' . $attributes . '>'
            . ($args->link_before ?? '')
            . apply_filters('the_title', $item->title, $item->ID)
            . ($args->link_after ?? '')
            . "</a>\n";
    }

    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        // Don't output </li>
    }
}

class Footer_Menu_Walker extends Walker_Nav_Menu
{
    /**
     * Start first-level menu.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '<ul class="footer-links">';
        }
    }

    /**
     * End first-level menu.
     */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</ul>';
        }
    }

    /**
     * Start menu item.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        if ($depth === 0) {

            // First-level item = column heading
            $output .= '<div class="col-6 col-lg-2">';
            $output .= '<div class="footer-heading">'
                . esc_html($item->title)
                . '</div>';

        } else {

            // Child item = link
            $attributes = '';

            if (!empty($item->url)) {
                $attributes .= ' href="' . esc_url($item->url) . '"';
            }

            $output .= '<li>';
            $output .= '<a' . $attributes . '>';
            $output .= esc_html($item->title);
            $output .= '</a>';
        }
    }

    /**
     * End menu item.
     */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</div>';
        } else {
            $output .= '</li>';
        }
    }
}

