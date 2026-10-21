<?php get_header();
$current_category = get_queried_object();

if ($current_category->parent) {
    // Load subcategory template
    get_template_part('category', 'products-items');
} else {
    // Load normal category template
    get_template_part('category', 'products-subcategory');
}
?>

<?php get_footer(); ?>