<?php
/**
 * Security and Validation Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class SecurityTest extends TestCase
{

    private $instance;

    public function setUp(): void
    {
        WP_Mock::setUp();
        
        // Reset singleton
        $reflection = new ReflectionClass('KitLeads');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->instance = KitLeads::get_instance();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
        $_POST = [];
    }

    public function test_nonce_creation_in_script_localization()
    {
        WP_Mock::userFunction('wp_create_nonce')
            ->once()
            ->with('kitleads_nonce')
            ->andReturn('generated-nonce-123');

        WP_Mock::userFunction('admin_url')
            ->once()
            ->with('admin-ajax.php')
            ->andReturn('https://example.com/wp-admin/admin-ajax.php');

        WP_Mock::userFunction('wp_localize_script')
            ->once()
            ->with(
                'kitleads-script',
                'kitLeadsData',
                [
                    'ajaxUrl' => 'https://example.com/wp-admin/admin-ajax.php',
                    'nonce' => 'generated-nonce-123'
                ]
            );

        $this->instance->enqueue_frontend_assets();
    }

    public function test_settings_sanitization()
    {
        // Test API secret sanitization
        WP_Mock::userFunction('sanitize_text_field')
            ->times(3)
            ->andReturnUsing(function($input) {
                // Simulate WordPress sanitize_text_field behavior
                return trim(strip_tags($input));
            });

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->andReturnUsing(function($input) {
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            });

        WP_Mock::userFunction('register_setting')
            ->times(3)
            ->andReturnUsing(function($group, $name, $args) {
                $this->assertEquals('kitleads_settings_group', $group);
                $this->assertArrayHasKey('sanitize_callback', $args);
                return true;
            });

        $this->instance->register_settings();
    }

    public function test_malicious_input_sanitization()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = '<script>alert("xss")</script>test@example.com';
        $_POST['form_id'] = '<script>alert("xss")</script>123456';

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_text_field')
            ->twice()
            ->andReturnUsing(function($input) {
                return strip_tags($input);
            });

        WP_Mock::userFunction('wp_unslash')
            ->times(3)
            ->andReturnUsing(function($input) { return $input; });

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->with('<script>alert("xss")</script>test@example.com')
            ->andReturn('test@example.com'); // WordPress would clean this

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock successful subscription
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $mock_bridge->method('subscribe')
            ->with('test@example.com', '123456', \PHPUnit\Framework\Assert::isType('array'))
            ->willReturn(['success' => true]);

        $reflection = new ReflectionClass($this->instance);
        $property = $reflection->getProperty('bridge');
        $property->setAccessible(true);
        $property->setValue($this->instance, $mock_bridge);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('__')
            ->once()
            ->andReturn('Thank you for subscribing!');

        WP_Mock::userFunction('wp_send_json_success')
            ->once();

        $this->instance->handle_ajax_subscribe();
    }

    public function test_sql_injection_attempt()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = "test'; DROP TABLE wp_users; --@example.com";
        $_POST['form_id'] = "123456'; DROP TABLE wp_posts; --";

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_text_field')
            ->twice()
            ->andReturnUsing(function($input) {
                // WordPress sanitize_text_field removes dangerous characters
                return preg_replace('/[^a-zA-Z0-9@._-]/', '', $input);
            });

        WP_Mock::userFunction('wp_unslash')
            ->times(3)
            ->andReturnUsing(function($input) { return $input; });

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->andReturnUsing(function($input) {
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            });

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $mock_bridge->method('subscribe')
            ->willReturn(['success' => true]);

        $reflection = new ReflectionClass($this->instance);
        $property = $reflection->getProperty('bridge');
        $property->setAccessible(true);
        $property->setValue($this->instance, $mock_bridge);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('__')
            ->once()
            ->andReturn('Thank you for subscribing!');

        WP_Mock::userFunction('wp_send_json_success')
            ->once();

        $this->instance->handle_ajax_subscribe();
    }

    public function test_nonce_replay_attack_protection()
    {
        // First request with nonce
        $_POST['nonce'] = 'used-nonce';
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('sanitize_text_field')
            ->once()
            ->andReturn('used-nonce');

        WP_Mock::userFunction('wp_unslash')
            ->once()
            ->andReturn('used-nonce');

        // Simulate nonce already used (WordPress would invalidate)
        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->with('used-nonce', 'kitleads_nonce')
            ->andReturn(false);

        WP_Mock::userFunction('wp_send_json_error')
            ->once()
            ->with('Security check failed');

        $this->instance->handle_ajax_subscribe();
    }

    public function test_capability_check_for_settings()
    {
        WP_Mock::userFunction('add_menu_page')
            ->once()
            ->andReturnUsing(function($page_title, $menu_title, $capability, $menu_slug, $callback, $icon, $position) {
                $this->assertEquals('manage_options', $capability);
                return 'menu-hook';
            });

        WP_Mock::userFunction('__')
            ->twice()
            ->andReturnFirstArg();

        $this->instance->add_settings_page();
    }

    public function test_direct_file_access_protection()
    {
        // Test that ABSPATH check is present in main file
        $main_file_content = file_get_contents(__DIR__ . '/../kitleads.php');
        $this->assertStringContainsString("if (!defined('ABSPATH'))", $main_file_content);
        $this->assertStringContainsString('exit;', $main_file_content);

        // Test that ABSPATH check is present in bridge file
        $bridge_file_content = file_get_contents(__DIR__ . '/../includes/class-kit-bridge.php');
        $this->assertStringContainsString("if (!defined('ABSPATH'))", $bridge_file_content);
        $this->assertStringContainsString('exit;', $bridge_file_content);
    }

    public function test_options_validation_callback()
    {
        // Test API secret validation
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_api_secret', \WP_Mock\Functions::type('array'))
            ->once()
            ->andReturnUsing(function($group, $name, $args) {
                $this->assertArrayHasKey('sanitize_callback', $args);
                $this->assertEquals('sanitize_text_field', $args['sanitize_callback']);
                return true;
            });

        // Test form ID validation  
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_form_id', \WP_Mock\Functions::type('array'))
            ->once()
            ->andReturnUsing(function($group, $name, $args) {
                $this->assertEquals('sanitize_text_field', $args['sanitize_callback']);
                return true;
            });

        // Test email validation
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_fallback_email', \WP_Mock\Functions::type('array'))
            ->once()
            ->andReturnUsing(function($group, $name, $args) {
                $this->assertEquals('sanitize_email', $args['sanitize_callback']);
                return true;
            });

        $this->instance->register_settings();
    }

    public function test_csrf_protection_on_settings_form()
    {
        WP_Mock::userFunction('settings_fields')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('do_settings_sections')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('get_option')
            ->times(3)
            ->andReturnUsing(function($option, $default = '') {
                return $default;
            });

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->times(6)
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        // Check that WordPress nonce fields are included
        $this->assertStringContainsString('settings_fields', $output);
    }

    public function test_output_escaping_in_shortcode()
    {
        $atts = [
            'title' => '<script>alert("xss")</script>Dangerous Title',
            'button_text' => '<script>alert("xss")</script>Click Me',
            'placeholder' => '<script>alert("xss")</script>Enter email'
        ];

        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn($atts);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnUsing(function($input) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            });

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnUsing(function($input) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            });

        $output = $this->instance->render_shortcode($atts);

        // Verify dangerous scripts are escaped
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}