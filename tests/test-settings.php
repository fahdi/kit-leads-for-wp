<?php
/**
 * Settings Validation Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class SettingsTest extends TestCase
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
    }

    public function test_settings_registration()
    {
        // Test API secret registration
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_api_secret', [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ])
            ->once();

        // Test form ID registration  
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_form_id', [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ])
            ->once();

        // Test fallback email registration
        WP_Mock::userFunction('register_setting')
            ->with('kitleads_settings_group', 'kitleads_fallback_email', [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email'
            ])
            ->once();

        $this->instance->register_settings();
    }

    public function test_settings_page_creation()
    {
        WP_Mock::userFunction('__')
            ->twice()
            ->with('Grand Slam Lead Magnets', 'grand-slam-lead-magnets')
            ->andReturn('Grand Slam Lead Magnets');

        WP_Mock::userFunction('add_menu_page')
            ->once()
            ->with(
                'Grand Slam Lead Magnets',
                'Grand Slam Lead Magnets', 
                'manage_options',
                'kitleads-settings',
                [$this->instance, 'render_settings_page'],
                'dashicons-email-alt',
                30
            );

        $this->instance->add_settings_page();
    }

    public function test_settings_page_rendering()
    {
        WP_Mock::userFunction('settings_fields')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('do_settings_sections')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('test-api-secret');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('123456');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_fallback_email', \WP_Mock\Functions::type('string'))
            ->once()
            ->andReturn('admin@example.com');

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->times(6)
            ->andReturnUsing(function($text) {
                echo htmlspecialchars($text);
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('Grand Slam Lead Magnets', $output);
        $this->assertStringContainsString('Kit.com API Secret', $output);
        $this->assertStringContainsString('Lead Magnet Form ID', $output);
        $this->assertStringContainsString('Fallback Email', $output);
        $this->assertStringContainsString('test-api-secret', $output);
        $this->assertStringContainsString('123456', $output);
        $this->assertStringContainsString('admin@example.com', $output);
    }

    public function test_frontend_asset_registration()
    {
        WP_Mock::userFunction('wp_register_style')
            ->once()
            ->with('kitleads-style', \WP_Mock\Functions::type('string'), [], \WP_Mock\Functions::type('string'));

        WP_Mock::userFunction('wp_register_script')
            ->once()
            ->with('kitleads-script', \WP_Mock\Functions::type('string'), [], \WP_Mock\Functions::type('string'), true);

        WP_Mock::userFunction('admin_url')
            ->once()
            ->with('admin-ajax.php')
            ->andReturn('https://example.com/wp-admin/admin-ajax.php');

        WP_Mock::userFunction('wp_create_nonce')
            ->once()
            ->with('kitleads_nonce')
            ->andReturn('test-nonce');

        WP_Mock::userFunction('wp_localize_script')
            ->once()
            ->with('kitleads-script', 'kitLeadsData', [
                'ajaxUrl' => 'https://example.com/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce'
            ]);

        $this->instance->enqueue_frontend_assets();
    }

    public function test_sanitization_callbacks()
    {
        // Test that proper sanitization callbacks are used
        $expected_settings = [
            'kitleads_api_secret' => 'sanitize_text_field',
            'kitleads_form_id' => 'sanitize_text_field', 
            'kitleads_fallback_email' => 'sanitize_email'
        ];

        foreach ($expected_settings as $setting => $callback) {
            WP_Mock::userFunction('register_setting')
                ->with('kitleads_settings_group', $setting, \WP_Mock\Functions::type('array'))
                ->once()
                ->andReturnUsing(function($group, $name, $args) use ($callback) {
                    $this->assertEquals($callback, $args['sanitize_callback']);
                    return true;
                });
        }

        $this->instance->register_settings();
    }

    public function test_default_fallback_email()
    {
        WP_Mock::userFunction('settings_fields')
            ->once();

        WP_Mock::userFunction('do_settings_sections')
            ->once();

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('');

        // Test default fallback to admin_email
        WP_Mock::userFunction('get_option')
            ->with('admin_email')
            ->once()
            ->andReturn('admin@wordpress.test');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_fallback_email', 'admin@wordpress.test')
            ->once()
            ->andReturn('admin@wordpress.test');

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->times(6)
            ->andReturnUsing(function($text) {
                echo htmlspecialchars($text);
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('admin@wordpress.test', $output);
    }

    public function test_settings_page_security()
    {
        WP_Mock::userFunction('settings_fields')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('do_settings_sections')
            ->once()
            ->with('kitleads_settings_group');

        WP_Mock::userFunction('get_option')
            ->times(3)
            ->andReturn('');

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->times(6)
            ->andReturnUsing(function($text) {
                echo htmlspecialchars($text);
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        // Check that form uses POST and proper action
        $this->assertStringContainsString('method="post"', $output);
        $this->assertStringContainsString('action="options.php"', $output);
        
        // Check that password field is used for API secret
        $this->assertStringContainsString('type="password"', $output);
    }

    public function test_help_text_display()
    {
        WP_Mock::userFunction('settings_fields')
            ->once();

        WP_Mock::userFunction('do_settings_sections')
            ->once();

        WP_Mock::userFunction('get_option')
            ->times(3)
            ->andReturn('');

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->with('Grand Slam Lead Magnets', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Kit.com API Secret', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Found in your Kit.com account settings under API.', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Lead Magnet Form ID', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('The numeric ID of your Kit.com landing page or form.', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Fallback Email', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Email to receive lead data if the API connection fails.', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Generate High-Value Leads', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('Drop your Grand Slam Lead Magnet code into any page or post to start building your audience:', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('esc_html_e')
            ->with('You can also override the specific Magnet ID:', 'grand-slam-lead-magnets')
            ->once()
            ->andReturnUsing(function($text) {
                echo $text;
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('[grand_slam_magnets]', $output);
        $this->assertStringContainsString('[grand_slam_magnets form_id="123456"]', $output);
        $this->assertStringContainsString('Found in your Kit.com account settings under API.', $output);
        $this->assertStringContainsString('The numeric ID of your Kit.com landing page or form.', $output);
        $this->assertStringContainsString('Email to receive lead data if the API connection fails.', $output);
    }

    public function test_css_classes_and_styling()
    {
        WP_Mock::userFunction('settings_fields')
            ->once();

        WP_Mock::userFunction('do_settings_sections')
            ->once();

        WP_Mock::userFunction('get_option')
            ->times(3)
            ->andReturn('');

        WP_Mock::userFunction('esc_attr')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html_e')
            ->times(6)
            ->andReturnUsing(function($text) {
                echo htmlspecialchars($text);
            });

        WP_Mock::userFunction('submit_button')
            ->once();

        ob_start();
        $this->instance->render_settings_page();
        $output = ob_get_clean();

        // Check for proper WordPress admin styling
        $this->assertStringContainsString('class="wrap"', $output);
        $this->assertStringContainsString('class="form-table"', $output);
        $this->assertStringContainsString('class="regular-text"', $output);
        $this->assertStringContainsString('class="description"', $output);
        $this->assertStringContainsString('class="card"', $output);
    }
}