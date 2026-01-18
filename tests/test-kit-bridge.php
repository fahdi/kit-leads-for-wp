<?php
/**
 * Kit Bridge API Integration Tests
 *
 * @package GrandSlamLeadMagnets
 */

use WP_Mock\Tools\TestCase;

class KitBridgeTest extends TestCase
{

    private $bridge;

    public function setUp(): void
    {
        WP_Mock::setUp();
        
        // Reset singleton
        $reflection = new ReflectionClass('KitLeads_Bridge');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->bridge = KitLeads_Bridge::get_instance();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    public function test_singleton_pattern()
    {
        $instance1 = KitLeads_Bridge::get_instance();
        $instance2 = KitLeads_Bridge::get_instance();
        
        $this->assertInstanceOf('KitLeads_Bridge', $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function test_subscribe_invalid_email_returns_error()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('invalid-email')
            ->andReturn(false);

        $result = $this->bridge->subscribe('invalid-email');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_email', $result->get_error_code());
    }

    public function test_subscribe_missing_api_secret_returns_error()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('123456');

        $result = $this->bridge->subscribe('test@example.com');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('missing_config', $result->get_error_code());
    }

    public function test_subscribe_missing_form_id_returns_error()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('valid-api-secret');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('');

        $result = $this->bridge->subscribe('test@example.com');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('missing_config', $result->get_error_code());
    }

    public function test_subscribe_successful_api_call()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('valid-api-secret');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('123456');

        // Mock successful API response
        $mock_response = [
            'body' => json_encode([
                'subscription' => [
                    'id' => 789,
                    'email' => 'test@example.com',
                    'state' => 'active'
                ]
            ])
        ];

        WP_Mock::userFunction('wp_json_encode')
            ->once()
            ->andReturn('{"api_secret":"valid-api-secret","email":"test@example.com","fields":[]}');

        WP_Mock::userFunction('wp_remote_post')
            ->once()
            ->with('https://api.convertkit.com/v3/forms/123456/subscribe', \WP_Mock\Functions::type('array'))
            ->andReturn($mock_response);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->with($mock_response)
            ->andReturn(false);

        WP_Mock::userFunction('wp_remote_retrieve_body')
            ->once()
            ->with($mock_response)
            ->andReturn(json_encode([
                'subscription' => [
                    'id' => 789,
                    'email' => 'test@example.com',
                    'state' => 'active'
                ]
            ]));

        $result = $this->bridge->subscribe('test@example.com');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Subscription successful', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_subscribe_api_error_calls_fallback()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('valid-api-secret');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('123456');

        // Mock WP_Error response
        $error_response = new WP_Error('http_request_failed', 'API connection failed');

        WP_Mock::userFunction('wp_json_encode')
            ->once()
            ->andReturn('{"api_secret":"valid-api-secret","email":"test@example.com","fields":[]}');

        WP_Mock::userFunction('wp_remote_post')
            ->once()
            ->andReturn($error_response);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->with($error_response)
            ->andReturn(true);

        // Mock fallback email call
        WP_Mock::userFunction('get_option')
            ->with('kitleads_fallback_email', \WP_Mock\Functions::type('string'))
            ->once()
            ->andReturn('admin@example.com');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        WP_Mock::userFunction('wp_mail')
            ->once()
            ->with(
                'admin@example.com',
                \WP_Mock\Functions::type('string'),
                \WP_Mock\Functions::type('string')
            );

        $result = $this->bridge->subscribe('test@example.com');
        
        $this->assertInstanceOf('WP_Error', $result);
    }

    public function test_subscribe_with_custom_form_id()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('valid-api-secret');

        // Should use custom form_id, not default
        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->never();

        WP_Mock::userFunction('wp_json_encode')
            ->once()
            ->with([
                'api_secret' => 'valid-api-secret',
                'email' => 'test@example.com',
                'fields' => ['custom' => 'field']
            ])
            ->andReturn('{"api_secret":"valid-api-secret","email":"test@example.com","fields":{"custom":"field"}}');

        $mock_response = [
            'body' => json_encode(['subscription' => ['id' => 789]])
        ];

        WP_Mock::userFunction('wp_remote_post')
            ->once()
            ->with('https://api.convertkit.com/v3/forms/999999/subscribe', \WP_Mock\Functions::type('array'))
            ->andReturn($mock_response);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode(['subscription' => ['id' => 789]]));

        $result = $this->bridge->subscribe('test@example.com', '999999', ['custom' => 'field']);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function test_subscribe_malformed_api_response()
    {
        WP_Mock::userFunction('is_email')
            ->once()
            ->with('test@example.com')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->with('kitleads_api_secret')
            ->once()
            ->andReturn('valid-api-secret');

        WP_Mock::userFunction('get_option')
            ->with('kitleads_form_id')
            ->once()
            ->andReturn('123456');

        // Mock malformed API response (missing 'subscription' key)
        $mock_response = [
            'body' => json_encode(['error' => 'Invalid form ID'])
        ];

        WP_Mock::userFunction('wp_json_encode')
            ->once()
            ->andReturn('{"api_secret":"valid-api-secret","email":"test@example.com","fields":[]}');

        WP_Mock::userFunction('wp_remote_post')
            ->once()
            ->andReturn($mock_response);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode(['error' => 'Invalid form ID']));

        // Mock fallback email for error case
        WP_Mock::userFunction('get_option')
            ->with('kitleads_fallback_email', \WP_Mock\Functions::type('string'))
            ->once()
            ->andReturn('admin@example.com');

        WP_Mock::userFunction('get_site_url')
            ->once()
            ->andReturn('https://example.com');

        WP_Mock::userFunction('wp_mail')
            ->once();

        $result = $this->bridge->subscribe('test@example.com');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('api_error', $result->get_error_code());
    }
}