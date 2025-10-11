<?php
function modern_shop_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    register_nav_menus([
        'primary' => __('Menu Principal', 'modern-shop'),
    ]);
}
add_action('after_setup_theme', 'modern_shop_setup');

function modern_shop_scripts() {
    wp_enqueue_style('modern-shop-style', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_script('modern-shop-scripts', get_template_directory_uri() . '/assets/js/theme.js', ['jquery'], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'modern_shop_scripts');
