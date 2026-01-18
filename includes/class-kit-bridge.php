<?php
/**
 * Grand Slam Lead Magnets - Kit Bridge
 * 
 * Handles interactions with the Kit.com (ConvertKit) API using plugin settings.
 * 
 * @package GrandSlamLeadMagnets
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class KitLeads_Bridge
{
    /**
     * Singleton instance.
     * @var KitLeads_Bridge|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     * 
     * @return KitLeads_Bridge
     */
    public static function get_instance(): KitLeads_Bridge
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Subscribe an email to a Kit.com form.
     * 
     * @param string $email
     * @param string $form_id Optional form ID override
     * @param array $fields Additional custom fields
     * @return array|WP_Error Success message or WP_Error
     */
    public function subscribe(string $email, string $form_id = '', array $fields = [])
    {
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address');
        }

        $api_secret = get_option('kitleads_api_secret');
        $final_form_id = !empty($form_id) ? $form_id : get_option('kitleads_form_id');

        if (empty($api_secret) || empty($final_form_id)) {
            return new WP_Error('missing_config', 'Kit.com API Secret or Form ID is missing from settings.');
        }

        $response = wp_remote_post("https://api.convertkit.com/v3/forms/{$final_form_id}/subscribe", array(
            'body' => wp_json_encode(array(
                'api_secret' => $api_secret,
                'email' => $email,
                'fields' => $fields
            )),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            $this->send_fallback_email($email, $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['subscription'])) {
            $error_msg = isset($body['message']) ? $body['message'] : 'Unknown API error';
            $this->send_fallback_email($email, $error_msg);
            return new WP_Error('api_error', $error_msg);
        }

        return array(
            'success' => true,
            'message' => 'Subscription successful',
            'data' => $body
        );
    }

    /**
     * Send a fallback email if the API fails.
     * 
     * @param string $email
     * @param string $error
     * @return void
     */
    private function send_fallback_email(string $email, string $error): void
    {
        $fallback_email = get_option('kitleads_fallback_email', get_option('admin_email'));

        if (empty($fallback_email)) {
            return;
        }

        wp_mail(
            $fallback_email,
            'Grand Slam Lead Magnets Alert (API Failed): ' . $email,
            sprintf(
                "Kit.com API failed to process a new lead.\n\nEmail: %s\nSite: %s\nError: %s",
                $email,
                get_site_url(),
                $error
            )
        );
    }
}
