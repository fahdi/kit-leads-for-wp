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

// Define WP_Error class for testing
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $errors = [];
        private $error_data = [];

        public function __construct($code = '', $message = '', $data = '')
        {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
        }

        public function add($code, $message, $data = '')
        {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_code()
        {
            $codes = array_keys($this->errors);
            return !empty($codes) ? $codes[0] : '';
        }

        public function get_error_message($code = '')
        {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->errors[$code]) ? $this->errors[$code][0] : '';
        }
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return 'http://example.com/wp-content/plugins/grand-slam-lead-magnets/';
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

if (!function_exists('sanitize_email')) {
    function sanitize_email($email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return trim(strip_tags($str));
    }
}

if (!function_exists('is_email')) {
    function is_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return is_string($value) ? stripslashes($value) : $value;
    }
}

// Load the plugin files
// We manualy require the main class since it is a singleton and not fully PSR-4 autoladable yet
require_once plugin_dir_path(__FILE__) . 'kitleads.php';
