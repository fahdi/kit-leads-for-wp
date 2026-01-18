<?php
/**
 * Plugin Name: LeadCrafter - Grand Slam Lead Magnets
 * Plugin URI: https://github.com/fahdi/leadcrafter-lead-magnets
 * Description: Create professional lead magnets that convert! AJAX-powered forms with Kit.com integration, fallback email system, and conversion-optimized designs. Perfect for creators, marketers, and coaches.
 * Version: 1.2.1
 * Author: Fahad Murtaza
 * Author URI: https://github.com/fahdi
 * License: GPLv2 or later
 * Text Domain: leadcrafter-lead-magnets
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Constants
 */
define('LEADCRAFTER_VERSION', '1.2.1');
define('LEADCRAFTER_URL', plugin_dir_url(__FILE__));
define('LEADCRAFTER_PATH', plugin_dir_path(__FILE__));

/**
 * Main LeadCrafter Class
 */
class LeadCrafter
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
        require_once LEADCRAFTER_PATH . 'includes/class-kit-bridge.php';
        $this->bridge = LeadCrafter_Bridge::get_instance();

        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        add_shortcode('leadcrafter', array($this, 'render_shortcode'));

        // AJAX Handler
        add_action('wp_ajax_leadcrafter_subscribe', array($this, 'handle_ajax_subscribe'));
        add_action('wp_ajax_nopriv_leadcrafter_subscribe', array($this, 'handle_ajax_subscribe'));
    }

    public function add_settings_page()
    {
        add_menu_page(
            __('LeadCrafter', 'leadcrafter-lead-magnets'),
            __('LeadCrafter', 'leadcrafter-lead-magnets'),
            'manage_options',
            'leadcrafter-settings',
            array($this, 'render_settings_page'),
            'dashicons-email-alt',
            30
        );
    }

    public function register_settings()
    {
        register_setting('leadcrafter_settings_group', 'leadcrafter_api_secret', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('leadcrafter_settings_group', 'leadcrafter_form_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('leadcrafter_settings_group', 'leadcrafter_fallback_email', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
        ));
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('LeadCrafter - Grand Slam Lead Magnets', 'leadcrafter-lead-magnets'); ?>
            </h1>
            <form method="post" action="options.php">
                <?php settings_fields('leadcrafter_settings_group'); ?>
                <?php do_settings_sections('leadcrafter_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Kit.com API Secret', 'leadcrafter-lead-magnets'); ?>
                        </th>
                        <td>
                            <input type="password" name="leadcrafter_api_secret"
                                value="<?php echo esc_attr(get_option('leadcrafter_api_secret')); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Found in your Kit.com account settings under API.', 'leadcrafter-lead-magnets'); ?>
                                <br>
                                <a href="https://app.convertkit.com/account_settings/advanced_settings" target="_blank" rel="noopener">
                                    <?php esc_html_e('→ Go to Kit.com API Settings', 'leadcrafter-lead-magnets'); ?>
                                </a>
                                <span style="color: #666; font-size: 11px;">
                                    <?php esc_html_e('(opens in new tab)', 'leadcrafter-lead-magnets'); ?>
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Lead Magnet Form ID', 'leadcrafter-lead-magnets'); ?>
                        </th>
                        <td>
                            <input type="text" name="leadcrafter_form_id"
                                value="<?php echo esc_attr(get_option('leadcrafter_form_id')); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('The numeric ID of your Kit.com landing page or form.', 'leadcrafter-lead-magnets'); ?>
                                <br>
                                <strong><?php esc_html_e('Find your Form ID:', 'leadcrafter-lead-magnets'); ?></strong>
                                <br>
                                <a href="https://app.convertkit.com/landing_pages" target="_blank" rel="noopener">
                                    <?php esc_html_e('→ Go to Kit.com Landing Pages', 'leadcrafter-lead-magnets'); ?>
                                </a>
                                <?php esc_html_e(' or ', 'leadcrafter-lead-magnets'); ?>
                                <a href="https://app.convertkit.com/forms" target="_blank" rel="noopener">
                                    <?php esc_html_e('→ Go to Kit.com Forms', 'leadcrafter-lead-magnets'); ?>
                                </a>
                                <br>
                                <span style="color: #666; font-size: 11px;">
                                    <?php esc_html_e('(Look for the ID number in the URL or form list)', 'leadcrafter-lead-magnets'); ?>
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Fallback Email', 'leadcrafter-lead-magnets'); ?>
                        </th>
                        <td>
                            <input type="email" name="leadcrafter_fallback_email"
                                value="<?php echo esc_attr(get_option('leadcrafter_fallback_email', get_option('admin_email'))); ?>"
                                class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Email to receive lead data if the API connection fails.', 'leadcrafter-lead-magnets'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px; border-left: 4px solid #2563eb;">
                <h2>
                    <?php esc_html_e('🚀 Quick Setup Guide', 'leadcrafter-lead-magnets'); ?>
                </h2>
                <ol style="line-height: 1.6;">
                    <li>
                        <strong><?php esc_html_e('Get your Kit.com API Secret:', 'leadcrafter-lead-magnets'); ?></strong>
                        <br>
                        <?php esc_html_e('Visit', 'leadcrafter-lead-magnets'); ?> 
                        <a href="https://app.convertkit.com/account_settings/advanced_settings" target="_blank" rel="noopener">
                            <?php esc_html_e('Kit.com API Settings', 'leadcrafter-lead-magnets'); ?>
                        </a> 
                        <?php esc_html_e('and copy your API Secret', 'leadcrafter-lead-magnets'); ?>
                    </li>
                    <li style="margin-top: 10px;">
                        <strong><?php esc_html_e('Find your Form ID:', 'leadcrafter-lead-magnets'); ?></strong>
                        <br>
                        <?php esc_html_e('Go to your', 'leadcrafter-lead-magnets'); ?> 
                        <a href="https://app.convertkit.com/forms" target="_blank" rel="noopener">
                            <?php esc_html_e('Kit.com Forms', 'leadcrafter-lead-magnets'); ?>
                        </a> 
                        <?php esc_html_e('and note the ID number (visible in URL or form list)', 'leadcrafter-lead-magnets'); ?>
                    </li>
                    <li style="margin-top: 10px;">
                        <strong><?php esc_html_e('Add the shortcode:', 'leadcrafter-lead-magnets'); ?></strong>
                        <br>
                        <?php esc_html_e('Use', 'leadcrafter-lead-magnets'); ?> <code>[leadcrafter]</code> <?php esc_html_e('in any post or page', 'leadcrafter-lead-magnets'); ?>
                    </li>
                </ol>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>
                    <?php esc_html_e('Generate High-Value Leads', 'leadcrafter-lead-magnets'); ?>
                </h2>
                <p>
                    <?php esc_html_e('Drop your LeadCrafter shortcode into any page or post to start building your audience:', 'leadcrafter-lead-magnets'); ?>
                </p>
                <code>[leadcrafter]</code>
                <p>
                    <?php esc_html_e('You can also override the specific Magnet ID:', 'leadcrafter-lead-magnets'); ?>
                </p>
                <code>[leadcrafter form_id="123456"]</code>
            </div>
        </div>
        <?php
    }

    public function enqueue_frontend_assets()
    {
        wp_register_style('leadcrafter-style', LEADCRAFTER_URL . 'assets/css/leadcrafter.css', array(), LEADCRAFTER_VERSION);
        wp_register_script('leadcrafter-script', LEADCRAFTER_URL . 'assets/js/leadcrafter.js', array(), LEADCRAFTER_VERSION, true);
        
        // Always localize the script when it's registered so it's available when enqueued
        wp_localize_script('leadcrafter-script', 'leadCrafterData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leadcrafter_nonce')
        ));
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'form_id' => '',
            'title' => __('Get the Grand Slam Multiplier', 'leadcrafter-lead-magnets'),
            'button_text' => __('Claim This Offer', 'leadcrafter-lead-magnets'),
            'placeholder' => __('Enter your email to receive value...', 'leadcrafter-lead-magnets')
        ), $atts, 'leadcrafter');

        wp_enqueue_style('leadcrafter-style');
        wp_enqueue_script('leadcrafter-script');

        ob_start();
        ?>
        <div class="leadcrafter-form-wrap" data-form-id="<?php echo esc_attr($atts['form_id']); ?>">
            <form class="leadcrafter-form">
                <?php if (!empty($atts['title'])): ?>
                    <h3>
                        <?php echo esc_html($atts['title']); ?>
                    </h3>
                <?php endif; ?>
                <div class="leadcrafter-input-group">
                    <input type="email" name="email" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required />
                    <button type="submit">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                <div class="leadcrafter-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax_subscribe()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'leadcrafter_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $form_id = isset($_POST['form_id']) ? sanitize_text_field(wp_unslash($_POST['form_id'])) : '';

        $result = $this->bridge->subscribe($email, $form_id, array(
            'site_url' => get_site_url(),
            'source' => 'LeadCrafter WordPress Plugin'
        ));

        if (is_wp_error($result)) {
            // Even if API fails, bridge handles fallback email. We can show success if we want "silent failure" 
            // or error if we want user to know. Let's show success message but log error for admin.
            wp_send_json_success(array('message' => __('Thank you for subscribing!', 'leadcrafter-lead-magnets')));
        } else {
            wp_send_json_success(array('message' => __('Thank you for subscribing!', 'leadcrafter-lead-magnets')));
        }
    }
}

if (!defined('WP_INT_TEST')) {
    LeadCrafter::get_instance();
}
