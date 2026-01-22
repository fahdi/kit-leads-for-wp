=== LeadCrafter - Grand Slam Lead Magnets ===
Contributors: fahdi
Tags: email marketing, convertkit, lead magnets, marketing automation, email marketing tools
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.2.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Craft high-converting lead magnets like a pro! AJAX-powered forms with Kit.com integration for creators and marketers.

== Description ==

**LeadCrafter - Grand Slam Lead Magnets** is the efficient multi-service platform for creators focusing on high-value lead acquisition. Stop building generic "forms" and start crafting **Grand Slam Lead Magnets** that build trust and authority.

Designed for marketers, course creators, and developers who value simplicity and high-conversion logic, LeadCrafter lets you deploy value-first lead captures with multiple email service integrations, starting with Kit.com (ConvertKit).

=== Features ===
*   **🎯 Grand Slam Focus:** Optimized for high-value offers and low friction.
*   **⚡ High-Velocity Submissions:** AJAX-powered capture for an instant, seamless experience.
*   **📱 Modern & Responsive:** Professional design that builds immediate credibility.
*   **🔧 Flexible Shortcodes:** Deploy your lead magnets anywhere with `[leadcrafter]`.
*   **🛡️ Data Resilience:** Automatic fallback system ensures you never lose a lead, even if the API hiccups.

== Installation ==

1. Upload the `leadcrafter-lead-magnets` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > LeadCrafter** to enter your credentials.

== Frequently Asked Questions ==

= Is this an official Kit.com plugin? =
No, this is a lightweight, high-performance bridge specifically designed for high-conversion marketing frameworks.

= What is a "Grand Slam Lead Magnet"? =
It's a high-value offer that solves a core problem for your lead, making it an easy "yes" for them to join your list.

= Where do I find my API Secret? =
In your Kit.com account under Settings > API.

== Screenshots ==

1. **Strategic Settings** - Simplify your lead generation setup.
2. **Value-First Form** - The clean, conversion-focused default magnet.

== External services ==

This plugin connects to the **Kit.com (ConvertKit) API** to subscribe users to your email list. This integration is essential for the plugin's core functionality of capturing leads and adding them to your email marketing platform.

= What data is sent =

When a visitor submits their email address through a LeadCrafter form, the following data is transmitted to Kit.com (ConvertKit):

* **Email address** - The email address entered by the visitor
* **Form ID** - Your Kit.com form identifier (configured in plugin settings)
* **Custom fields** - Any additional fields you have configured (optional)

= When data is sent =

Data is sent to the Kit.com API only when a visitor voluntarily submits their email through a LeadCrafter form on your website. No data is sent without explicit user action.

= Service provider =

This service is provided by **Kit (formerly ConvertKit)**:

* Service website: [https://kit.com](https://kit.com)
* Terms of Service: [https://kit.com/terms](https://kit.com/terms)
* Privacy Policy: [https://kit.com/privacy](https://kit.com/privacy)

== Changelog ==

= 1.2.1 =
* **CRITICAL BUG FIXES**: Fixed fatal error preventing plugin activation
* **Fixed**: Return type hint in LeadCrafter_Bridge class (was still referencing old class name)
* **Fixed**: JavaScript localization timing - moved wp_localize_script to proper location
* **Improved**: Plugin now activates without errors after rebranding

= 1.2.0 =
* **MAJOR REBRANDING**: Now "LeadCrafter - Grand Slam Lead Magnets"
* **Updated Shortcode**: Changed from `[grand_slam_magnets]` to `[leadcrafter]`
* **New Text Domain**: Updated to `leadcrafter-lead-magnets`
* **Enhanced Branding**: Professional "crafter" methodology for lead magnet creation
* **API Improvements**: Updated option names and class structure
* **Repository Update**: New slug `leadcrafter-lead-magnets`

= 1.1.0 =
* **REBRANDING**: Changed plugin name from "KitLeads" to "Grand Slam Lead Magnets"
* **WordPress Plugin Directory Compliance**: Resolved trademark naming issues  
* **Comprehensive Testing**: Added 20 automated tests with 75+ assertions covering security, functionality, and WordPress standards
* **Updated Shortcode**: Changed from `[kitleads]` to `[grand_slam_magnets]`
* **Multi-Service Ready**: Positioned for future email service integrations beyond Kit.com
* **Enhanced Security**: Improved input sanitization and nonce validation
* **Code Quality**: Professional-grade development practices and testing infrastructure

= 1.0.0 =
* Initial release. Optimized for high-value lead acquisition and Grand Slam offers.
