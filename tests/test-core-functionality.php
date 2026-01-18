<?php
/**
 * Core Functionality Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class CoreFunctionalityTest extends TestCase
{

    public function setUp(): void
    {
        WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    public function test_plugin_class_exists()
    {
        $this->assertTrue(class_exists('KitLeads'));
    }

    public function test_bridge_class_exists()
    {
        // Bridge class is loaded when KitLeads singleton is instantiated
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $this->assertFileExists($bridge_file);
        
        // Check that the file contains the class definition
        $content = file_get_contents($bridge_file);
        $this->assertStringContainsString('class KitLeads_Bridge', $content);
    }

    public function test_main_plugin_file_structure()
    {
        $main_file = __DIR__ . '/../kitleads.php';
        $this->assertFileExists($main_file);
        
        $content = file_get_contents($main_file);
        
        // Test security
        $this->assertStringContainsString("if (!defined('ABSPATH'))", $content);
        
        // Test plugin header
        $this->assertStringContainsString('Plugin Name: Grand Slam Lead Magnets', $content);
        $this->assertStringContainsString('Text Domain: grand-slam-lead-magnets', $content);
        
        // Test shortcode registration
        $this->assertStringContainsString('grand_slam_magnets', $content);
        
        // Test AJAX handlers
        $this->assertStringContainsString('wp_ajax_kitleads_subscribe', $content);
        $this->assertStringContainsString('wp_ajax_nopriv_kitleads_subscribe', $content);
    }

    public function test_bridge_file_structure()
    {
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $this->assertFileExists($bridge_file);
        
        $content = file_get_contents($bridge_file);
        
        // Test security
        $this->assertStringContainsString("if (!defined('ABSPATH'))", $content);
        
        // Test API endpoint
        $this->assertStringContainsString('api.convertkit.com/v3/forms', $content);
        
        // Test fallback mechanism
        $this->assertStringContainsString('send_fallback_email', $content);
        $this->assertStringContainsString('wp_mail', $content);
    }

    public function test_assets_exist()
    {
        $css_file = __DIR__ . '/../assets/css/kitleads.css';
        $js_file = __DIR__ . '/../assets/js/kitleads.js';
        
        $this->assertFileExists($css_file);
        $this->assertFileExists($js_file);
        
        $css_content = file_get_contents($css_file);
        $js_content = file_get_contents($js_file);
        
        // Test CSS contains form styles
        $this->assertStringContainsString('.kitleads-form-wrap', $css_content);
        $this->assertStringContainsString('.kitleads-input-group', $css_content);
        
        // Test JS contains AJAX functionality
        $this->assertStringContainsString('kitleads_subscribe', $js_content);
        $this->assertStringContainsString('fetch(kitLeadsData.ajaxUrl', $js_content);
    }

    public function test_readme_updated()
    {
        $readme_file = __DIR__ . '/../readme.txt';
        $this->assertFileExists($readme_file);
        
        $content = file_get_contents($readme_file);
        
        // Test rebranding
        $this->assertStringContainsString('=== Grand Slam Lead Magnets ===', $content);
        $this->assertStringContainsString('[grand_slam_magnets]', $content);
        $this->assertStringNotContainsString('KitLeads', $content);
        
        // Test tags include trending keywords
        $this->assertStringContainsString('email marketing', $content);
        $this->assertStringContainsString('convertkit', $content);
        $this->assertStringContainsString('lead magnets', $content);
        $this->assertStringContainsString('marketing automation', $content);
        $this->assertStringContainsString('email marketing tools', $content);
    }

    public function test_composer_configuration()
    {
        $composer_file = __DIR__ . '/../composer.json';
        $this->assertFileExists($composer_file);
        
        $content = json_decode(file_get_contents($composer_file), true);
        
        $this->assertEquals('fahdi/grand-slam-lead-magnets', $content['name']);
        $this->assertArrayHasKey('scripts', $content);
        $this->assertArrayHasKey('test', $content['scripts']);
        $this->assertEquals('phpunit', $content['scripts']['test']);
    }

    public function test_security_measures()
    {
        // Test that sensitive files have proper protection
        $files_to_check = [
            __DIR__ . '/../kitleads.php',
            __DIR__ . '/../includes/class-kit-bridge.php'
        ];
        
        foreach ($files_to_check as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('ABSPATH', $content, "File $file missing ABSPATH check");
            $this->assertStringContainsString('exit', $content, "File $file missing exit statement");
        }
    }

    public function test_sanitization_functions_used()
    {
        $main_file = __DIR__ . '/../kitleads.php';
        $content = file_get_contents($main_file);
        
        // Test input sanitization
        $this->assertStringContainsString('sanitize_email', $content);
        $this->assertStringContainsString('sanitize_text_field', $content);
        
        // Test output escaping
        $this->assertStringContainsString('esc_attr', $content);
        $this->assertStringContainsString('esc_html', $content);
        
        // Test nonce verification
        $this->assertStringContainsString('wp_verify_nonce', $content);
        $this->assertStringContainsString('wp_create_nonce', $content);
    }

    public function test_internationalization_ready()
    {
        $main_file = __DIR__ . '/../kitleads.php';
        $content = file_get_contents($main_file);
        
        // Test translation functions
        $this->assertStringContainsString("__('", $content);
        $this->assertStringContainsString("esc_html_e('", $content);
        
        // Test text domain usage
        $this->assertStringContainsString("'grand-slam-lead-magnets'", $content);
    }

    public function test_wp_error_handling()
    {
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $content = file_get_contents($bridge_file);
        
        // Test error handling
        $this->assertStringContainsString('WP_Error', $content);
        $this->assertStringContainsString('is_wp_error', $content);
    }

    public function test_no_hardcoded_secrets()
    {
        $files_to_check = [
            __DIR__ . '/../kitleads.php',
            __DIR__ . '/../includes/class-kit-bridge.php'
        ];
        
        foreach ($files_to_check as $file) {
            $content = file_get_contents($file);
            
            // Test no hardcoded API keys or secrets
            $this->assertStringNotContainsString('sk_', $content, "Potential hardcoded API key in $file");
            $this->assertStringNotContainsString('secret_key', $content, "Potential hardcoded secret in $file");
            
            // Verify options are used instead
            $this->assertStringContainsString('get_option', $content, "Missing get_option usage in $file");
        }
    }

    public function test_proper_wp_hooks()
    {
        $main_file = __DIR__ . '/../kitleads.php';
        $content = file_get_contents($main_file);
        
        // Test WordPress hooks are used properly
        $this->assertStringContainsString('add_action', $content);
        $this->assertStringContainsString('add_shortcode', $content);
        
        // Test proper hook names
        $this->assertStringContainsString('admin_menu', $content);
        $this->assertStringContainsString('admin_init', $content);
        $this->assertStringContainsString('wp_enqueue_scripts', $content);
    }

    public function test_capability_checks()
    {
        $main_file = __DIR__ . '/../kitleads.php';
        $content = file_get_contents($main_file);
        
        // Test capability checks for admin functions
        $this->assertStringContainsString('manage_options', $content);
    }

    public function test_api_timeout_set()
    {
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $content = file_get_contents($bridge_file);
        
        // Test API timeout is set
        $this->assertStringContainsString('timeout', $content);
    }

    public function test_proper_json_handling()
    {
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $content = file_get_contents($bridge_file);
        
        // Test JSON encoding/decoding
        $this->assertStringContainsString('wp_json_encode', $content);
        $this->assertStringContainsString('json_decode', $content);
        
        // Test content type header
        $this->assertStringContainsString('application/json', $content);
    }

    public function test_fallback_email_mechanism()
    {
        $bridge_file = __DIR__ . '/../includes/class-kit-bridge.php';
        $content = file_get_contents($bridge_file);
        
        // Test fallback email functionality
        $this->assertStringContainsString('fallback_email', $content);
        $this->assertStringContainsString('wp_mail', $content);
        $this->assertStringContainsString('admin_email', $content);
    }
}