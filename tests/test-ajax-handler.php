<?php
/**
 * AJAX Handler Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class AjaxHandlerTest extends TestCase
{

    private $instance;

    public function setUp(): void
    {
        WP_Mock::setUp();
        
        // Reset KitLeads singleton
        $reflection = new ReflectionClass('KitLeads');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->instance = KitLeads::get_instance();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
        // Clear superglobals
        $_POST = [];
    }

    public function test_ajax_handler_invalid_nonce()
    {
        $_POST['nonce'] = 'invalid-nonce';
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->with('invalid-nonce', 'kitleads_nonce')
            ->andReturn(false);

        WP_Mock::userFunction('sanitize_text_field')
            ->with('invalid-nonce')
            ->once()
            ->andReturn('invalid-nonce');

        WP_Mock::userFunction('wp_unslash')
            ->with('invalid-nonce')
            ->once()
            ->andReturn('invalid-nonce');

        WP_Mock::userFunction('wp_send_json_error')
            ->once()
            ->with('Security check failed');

        $this->instance->handle_ajax_subscribe();
    }

    public function test_ajax_handler_missing_nonce()
    {
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';
        // No nonce set

        WP_Mock::userFunction('wp_send_json_error')
            ->once()
            ->with('Security check failed');

        $this->instance->handle_ajax_subscribe();
    }

    public function test_ajax_handler_successful_subscription()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('sanitize_text_field')
            ->with('valid-nonce')
            ->once()
            ->andReturn('valid-nonce');

        WP_Mock::userFunction('wp_unslash')
            ->with('valid-nonce')
            ->once()
            ->andReturn('valid-nonce');

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->with('valid-nonce', 'kitleads_nonce')
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->with('test@example.com')
            ->andReturn('test@example.com');

        WP_Mock::userFunction('wp_unslash')
            ->with('test@example.com')
            ->once()
            ->andReturn('test@example.com');

        WP_Mock::userFunction('sanitize_text_field')
            ->with('123456')
            ->once()
            ->andReturn('123456');

        WP_Mock::userFunction('wp_unslash')
            ->with('123456')
            ->once()
            ->andReturn('123456');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock bridge response (success)
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $mock_bridge->method('subscribe')
            ->willReturn(['success' => true, 'message' => 'Subscribed successfully']);

        // Use reflection to inject mock bridge
        $reflection = new ReflectionClass($this->instance);
        $property = $reflection->getProperty('bridge');
        $property->setAccessible(true);
        $property->setValue($this->instance, $mock_bridge);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('__')
            ->once()
            ->with('Thank you for subscribing!', 'grand-slam-lead-magnets')
            ->andReturn('Thank you for subscribing!');

        WP_Mock::userFunction('wp_send_json_success')
            ->once()
            ->with(['message' => 'Thank you for subscribing!']);

        $this->instance->handle_ajax_subscribe();
    }

    public function test_ajax_handler_api_error_still_shows_success()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('sanitize_text_field')
            ->twice()
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_unslash')
            ->times(3)
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->andReturn('test@example.com');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock bridge response (error)
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $api_error = new WP_Error('api_error', 'API connection failed');
        $mock_bridge->method('subscribe')
            ->willReturn($api_error);

        // Use reflection to inject mock bridge
        $reflection = new ReflectionClass($this->instance);
        $property = $reflection->getProperty('bridge');
        $property->setAccessible(true);
        $property->setValue($this->instance, $mock_bridge);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->with($api_error)
            ->andReturn(true);

        WP_Mock::userFunction('__')
            ->once()
            ->with('Thank you for subscribing!', 'grand-slam-lead-magnets')
            ->andReturn('Thank you for subscribing!');

        WP_Mock::userFunction('wp_send_json_success')
            ->once()
            ->with(['message' => 'Thank you for subscribing!']);

        $this->instance->handle_ajax_subscribe();
    }

    public function test_ajax_handler_empty_email()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = '';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('sanitize_text_field')
            ->twice()
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_unslash')
            ->times(3)
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->with('')
            ->andReturn('');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock bridge response for empty email
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $empty_email_error = new WP_Error('invalid_email', 'Invalid email address');
        $mock_bridge->method('subscribe')
            ->with('', '123456', \PHPUnit\Framework\Assert::isType('array'))
            ->willReturn($empty_email_error);

        // Use reflection to inject mock bridge
        $reflection = new ReflectionClass($this->instance);
        $property = $reflection->getProperty('bridge');
        $property->setAccessible(true);
        $property->setValue($this->instance, $mock_bridge);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('__')
            ->once()
            ->with('Thank you for subscribing!', 'grand-slam-lead-magnets')
            ->andReturn('Thank you for subscribing!');

        WP_Mock::userFunction('wp_send_json_success')
            ->once()
            ->with(['message' => 'Thank you for subscribing!']);

        $this->instance->handle_ajax_subscribe();
    }

    public function test_ajax_handler_no_form_id()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = 'test@example.com';
        // No form_id provided

        WP_Mock::userFunction('sanitize_text_field')
            ->once()
            ->with('valid-nonce')
            ->andReturn('valid-nonce');

        WP_Mock::userFunction('wp_unslash')
            ->twice()
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->andReturn('test@example.com');

        WP_Mock::userFunction('sanitize_text_field')
            ->once()
            ->with('')
            ->andReturn('');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock bridge response with empty form_id (should use default)
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $mock_bridge->method('subscribe')
            ->with('test@example.com', '', \PHPUnit\Framework\Assert::isType('array'))
            ->willReturn(['success' => true]);

        // Use reflection to inject mock bridge
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

    public function test_ajax_data_includes_source_attribution()
    {
        $_POST['nonce'] = 'valid-nonce';
        $_POST['email'] = 'test@example.com';
        $_POST['form_id'] = '123456';

        WP_Mock::userFunction('sanitize_text_field')
            ->twice()
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_unslash')
            ->times(3)
            ->andReturnUsing(function($arg) { return $arg; });

        WP_Mock::userFunction('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('sanitize_email')
            ->once()
            ->andReturn('test@example.com');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        // Mock bridge to capture the data passed to it
        $mock_bridge = $this->createMock('KitLeads_Bridge');
        $mock_bridge->expects($this->once())
            ->method('subscribe')
            ->with(
                'test@example.com',
                '123456',
                [
                    'site_url' => 'https://example.com',
                    'source' => 'KitLeads WordPress Plugin'
                ]
            )
            ->willReturn(['success' => true]);

        // Use reflection to inject mock bridge
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
}