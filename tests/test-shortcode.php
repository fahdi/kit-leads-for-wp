<?php
/**
 * Shortcode Rendering Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class ShortcodeTest extends TestCase
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

    public function test_shortcode_registration()
    {
        WP_Mock::expectAction('wp_enqueue_scripts', [$this->instance, 'enqueue_frontend_assets']);
        WP_Mock::expectAction('admin_menu', [$this->instance, 'add_settings_page']);
        WP_Mock::expectAction('admin_init', [$this->instance, 'register_settings']);

        // Test shortcode registration
        WP_Mock::userFunction('add_shortcode')
            ->once()
            ->with('grand_slam_magnets', [$this->instance, 'render_shortcode']);

        // Re-initialize to trigger constructor
        $reflection = new ReflectionClass('KitLeads');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        KitLeads::get_instance();
    }

    public function test_shortcode_default_attributes()
    {
        $expected_defaults = [
            'form_id' => '',
            'title' => 'Get the Grand Slam Multiplier',
            'button_text' => 'Claim This Offer',
            'placeholder' => 'Enter your email to receive value...'
        ];

        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->with($expected_defaults, [], 'grand_slam_magnets')
            ->andReturn($expected_defaults);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once()
            ->with('kitleads-style');

        WP_Mock::userFunction('wp_enqueue_script')
            ->once()
            ->with('kitleads-script');

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $output = $this->instance->render_shortcode([]);

        $this->assertStringContainsString('kitleads-form-wrap', $output);
        $this->assertStringContainsString('Get the Grand Slam Multiplier', $output);
        $this->assertStringContainsString('Claim This Offer', $output);
    }

    public function test_shortcode_custom_attributes()
    {
        $custom_atts = [
            'form_id' => '999999',
            'title' => 'Custom Lead Magnet',
            'button_text' => 'Get It Now',
            'placeholder' => 'Your email address'
        ];

        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn($custom_atts);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $output = $this->instance->render_shortcode($custom_atts);

        $this->assertStringContainsString('data-form-id="999999"', $output);
        $this->assertStringContainsString('Custom Lead Magnet', $output);
        $this->assertStringContainsString('Get It Now', $output);
        $this->assertStringContainsString('placeholder="Your email address"', $output);
    }

    public function test_shortcode_empty_title_handling()
    {
        $atts_no_title = [
            'form_id' => '123456',
            'title' => '',
            'button_text' => 'Subscribe',
            'placeholder' => 'Email'
        ];

        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn($atts_no_title);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->once() // Only button text, no title
            ->andReturnFirstArg();

        $output = $this->instance->render_shortcode($atts_no_title);

        // Should not contain h3 tag when title is empty
        $this->assertStringNotContainsString('<h3>', $output);
        $this->assertStringContainsString('Subscribe', $output);
    }

    public function test_shortcode_html_structure()
    {
        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn([
                'form_id' => '123456',
                'title' => 'Test Title',
                'button_text' => 'Test Button',
                'placeholder' => 'Test Placeholder'
            ]);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $output = $this->instance->render_shortcode([]);

        // Test HTML structure
        $this->assertStringContainsString('<div class="kitleads-form-wrap"', $output);
        $this->assertStringContainsString('<form class="kitleads-form">', $output);
        $this->assertStringContainsString('<div class="kitleads-input-group">', $output);
        $this->assertStringContainsString('<input type="email"', $output);
        $this->assertStringContainsString('<button type="submit">', $output);
        $this->assertStringContainsString('<div class="kitleads-message"></div>', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('required', $output);
    }

    public function test_shortcode_assets_enqueued()
    {
        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn([
                'form_id' => '',
                'title' => 'Test',
                'button_text' => 'Test',
                'placeholder' => 'Test'
            ]);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        // Test that assets are properly enqueued
        WP_Mock::userFunction('wp_enqueue_style')
            ->once()
            ->with('kitleads-style');

        WP_Mock::userFunction('wp_enqueue_script')
            ->once()
            ->with('kitleads-script');

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $this->instance->render_shortcode([]);
    }

    public function test_shortcode_form_id_attribute_handling()
    {
        // Test with form_id
        $with_form_id = ['form_id' => '555555'];
        
        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn(array_merge([
                'form_id' => '555555',
                'title' => 'Test',
                'button_text' => 'Test',
                'placeholder' => 'Test'
            ], $with_form_id));

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $output = $this->instance->render_shortcode($with_form_id);
        
        $this->assertStringContainsString('data-form-id="555555"', $output);
    }

    public function test_shortcode_output_buffering()
    {
        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn([
                'form_id' => '',
                'title' => 'Buffer Test',
                'button_text' => 'Test',
                'placeholder' => 'Test'
            ]);

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        // Test that output is returned, not echoed
        ob_start();
        $result = $this->instance->render_shortcode([]);
        $buffered = ob_get_clean();

        // No output should be echoed
        $this->assertEmpty($buffered);
        // But result should contain the form
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Buffer Test', $result);
    }

    public function test_shortcode_special_characters_in_attributes()
    {
        $special_atts = [
            'title' => 'Title with "quotes" & <tags>',
            'button_text' => 'Button & "text"',
            'placeholder' => 'Email & more'
        ];

        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn(array_merge([
                'form_id' => '',
            ], $special_atts));

        WP_Mock::userFunction('__')
            ->times(3)
            ->andReturnFirstArg();

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnUsing(function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            });

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnUsing(function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            });

        $output = $this->instance->render_shortcode($special_atts);

        // Special characters should be escaped
        $this->assertStringContainsString('&quot;', $output);
        $this->assertStringContainsString('&amp;', $output);
        $this->assertStringContainsString('&lt;', $output);
        $this->assertStringContainsString('&gt;', $output);
    }

    public function test_translation_ready_strings()
    {
        WP_Mock::userFunction('shortcode_atts')
            ->once()
            ->andReturn([
                'form_id' => '',
                'title' => 'Test',
                'button_text' => 'Test',
                'placeholder' => 'Test'
            ]);

        // Test that translatable strings use proper text domain
        WP_Mock::userFunction('__')
            ->with('Get the Grand Slam Multiplier', 'grand-slam-lead-magnets')
            ->once()
            ->andReturn('Get the Grand Slam Multiplier');

        WP_Mock::userFunction('__')
            ->with('Claim This Offer', 'grand-slam-lead-magnets')
            ->once()
            ->andReturn('Claim This Offer');

        WP_Mock::userFunction('__')
            ->with('Enter your email to receive value...', 'grand-slam-lead-magnets')
            ->once()
            ->andReturn('Enter your email to receive value...');

        WP_Mock::userFunction('wp_enqueue_style')
            ->once();

        WP_Mock::userFunction('wp_enqueue_script')
            ->once();

        WP_Mock::userFunction('esc_attr')
            ->twice()
            ->andReturnFirstArg();

        WP_Mock::userFunction('esc_html')
            ->twice()
            ->andReturnFirstArg();

        $this->instance->render_shortcode([]);
    }
}