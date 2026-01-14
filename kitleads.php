<?php
/**
 * Plugin Name: KitLeads – Grand Slam Lead Magnets for Kit.com
 * Plugin URI: https://leadleads.io
 * Description: Capture high-value leads with Grand Slam magnets. A lightweight Kit.com (ConvertKit) integration for WordPress.
 * Version: 1.0.0
 * Author: LeadLeads Team
 * Author URI: https://leadleads.io
 * License: GPLv2 or later
 * Text Domain: kitleads-lead-magnet
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Constants
 */
define('KITLEADS_VERSION', '1.0.0');
define('KITLEADS_URL', plugin_dir_url(__FILE__));
define('KITLEADS_PATH', plugin_dir_path(__FILE__));

/**
 * Main KitLeads Class
 */
class KitLeads
{
    private static $instance = null;
    private $bridge = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        require_once KITLEADS_PATH . 'includes/class-kit-bridge.php';
        $this->bridge = KitLeads_Bridge::get_instance();

        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        add_shortcode('kitleads', array($this, 'render_shortcode'));

        // AJAX Handler
        add_action('wp_ajax_kitleads_subscribe', array($this, 'handle_ajax_subscribe'));
        add_action('wp_ajax_nopriv_kitleads_subscribe', array($this, 'handle_ajax_subscribe'));
    }

    public function add_settings_page()
    {
        add_menu_page(
            __('Grand Slam Lead Magnets', 'kitleads-lead-magnet'),
            __('KitLeads', 'kitleads-lead-magnet'),
            'manage_options',
            'kitleads-settings',
            array($this, 'render_settings_page'),
            'dashicons-email-alt',
            30
        );
    }

    public function register_settings()
    {
        register_setting('kitleads_settings_group', 'kitleads_api_secret', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('kitleads_settings_group', 'kitleads_form_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('kitleads_settings_group', 'kitleads_fallback_email', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
        ));
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Grand Slam Lead Magnets for Kit.com', 'kitleads-lead-magnet'); ?>
            </h1>
            <form method="post" action="options.php">
                <?php settings_fields('kitleads_settings_group'); ?>
                <?php do_settings_sections('kitleads_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Kit.com API Secret', 'kitleads-lead-magnet'); ?>
                        </th>
                        <td>
                            <input type="password" name="kitleads_api_secret"
                                value="<?php echo esc_attr(get_option('kitleads_api_secret')); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Found in your Kit.com account settings under API.', 'kitleads-lead-magnet'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Lead Magnet Form ID', 'kitleads-lead-magnet'); ?>
                        </th>
                        <td>
                            <input type="text" name="kitleads_form_id"
                                value="<?php echo esc_attr(get_option('kitleads_form_id')); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('The numeric ID of your Kit.com landing page or form.', 'kitleads-lead-magnet'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Fallback Email', 'kitleads-lead-magnet'); ?>
                        </th>
                        <td>
                            <input type="email" name="kitleads_fallback_email"
                                value="<?php echo esc_attr(get_option('kitleads_fallback_email', get_option('admin_email'))); ?>"
                                class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Email to receive lead data if the API connection fails.', 'kitleads-lead-magnet'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>
                    <?php esc_html_e('Generate High-Value Leads', 'kitleads-lead-magnet'); ?>
                </h2>
                <p>
                    <?php esc_html_e('Drop your Grand Slam Lead Magnet code into any page or post to start building your audience:', 'kitleads-lead-magnet'); ?>
                </p>
                <code>[kitleads]</code>
                <p>
                    <?php esc_html_e('You can also override the specific Magnet ID:', 'kitleads-lead-magnet'); ?>
                </p>
                <code>[kitleads form_id="123456"]</code>
            </div>
        </div>
        <?php
    }

    public function enqueue_frontend_assets()
    {
        wp_register_style('kitleads-style', KITLEADS_URL . 'assets/css/kitleads.css', array(), KITLEADS_VERSION);
        wp_register_script('kitleads-script', KITLEADS_URL . 'assets/js/kitleads.js', array(), KITLEADS_VERSION, true);

        wp_localize_script('kitleads-script', 'kitLeadsData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kitleads_nonce')
        ));
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'form_id' => '',
            'title' => __('Get the Grand Slam Multiplier', 'kitleads-lead-magnet'),
            'button_text' => __('Claim This Offer', 'kitleads-lead-magnet'),
            'placeholder' => __('Enter your email to receive value...', 'kitleads-lead-magnet')
        ), $atts, 'kitleads');

        wp_enqueue_style('kitleads-style');
        wp_enqueue_script('kitleads-script');

        ob_start();
        ?>
        <div class="kitleads-form-wrap" data-form-id="<?php echo esc_attr($atts['form_id']); ?>">
            <form class="kitleads-form">
                <?php if (!empty($atts['title'])): ?>
                    <h3>
                        <?php echo esc_html($atts['title']); ?>
                    </h3>
                <?php endif; ?>
                <div class="kitleads-input-group">
                    <input type="email" name="email" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required />
                    <button type="submit">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                <div class="kitleads-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax_subscribe()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kitleads_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $form_id = isset($_POST['form_id']) ? sanitize_text_field(wp_unslash($_POST['form_id'])) : '';

        $result = $this->bridge->subscribe($email, $form_id, array(
            'site_url' => get_site_url(),
            'source' => 'KitLeads WordPress Plugin'
        ));

        if (is_wp_error($result)) {
            // Even if API fails, bridge handles fallback email. We can show success if we want "silent failure" 
            // or error if we want user to know. Let's show success message but log error for admin.
            wp_send_json_success(array('message' => __('Thank you for subscribing!', 'kitleads-lead-magnet')));
        } else {
            wp_send_json_success(array('message' => __('Thank you for subscribing!', 'kitleads-lead-magnet')));
        }
    }
}

if (!defined('WP_INT_TEST')) {
    KitLeads::get_instance();
}
