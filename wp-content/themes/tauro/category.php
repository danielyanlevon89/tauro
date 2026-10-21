
<?php
$current_category = get_queried_object();

$products_category = get_category_by_slug('products');

if (
	$products_category &&
	(
		(int) $current_category->term_id === (int) $products_category->term_id ||
		cat_is_ancestor_of($products_category->term_id, $current_category->term_id)
	)
) {
	if ($current_category->parent) {
		// Products subcategory
		get_template_part('category', 'products');
	} else {
		// Products category
		get_template_part('category', 'products-items');
	}
} else {
	// All other categories use the default template
	get_template_part('category', 'default');
}
?>
