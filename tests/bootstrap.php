<?php
/**
 * PHPUnit Bootstrap
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

// Define test environment constant
define('WP_INT_TEST', true);

// Define plugin constants normally defined by WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return 'http://example.com/wp-content/plugins/kit-leads-for-wp/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return __DIR__ . '/../';
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback)
    {
        \WP_Mock::onAction('add_shortcode')->react(array($tag, $callback));
    }
}

if (!function_exists('register_setting')) {
    function register_setting($group, $name, $args = array())
    {
    }
}

if (!function_exists('add_options_page')) {
    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $function = '')
    {
    }
}

// Load the plugin files
// We manualy require the main class since it is a singleton and not fully PSR-4 autoladable yet
require_once plugin_dir_path(__FILE__) . 'kitleads.php';
