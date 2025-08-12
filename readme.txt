=== Dashboard Login URL Customizer ===
Contributors: your-name
Tags: login, security, admin, customization
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Change the WordPress login URL from /wp-admin or /wp-login.php to a custom, admin-defined slug.

== Description ==
This plugin lets administrators define a custom login URL slug, e.g. https://example.com/portal/.

Features:
- Custom login slug with live URL display
- Blocks direct access to wp-login.php (404)
- Redirects all login flows (login, logout, lost password, register) to your slug
- Activation/deactivation flushes rewrite rules
- Settings page under Settings → Login URL

WordPress already redirects non-logged-in users trying to access /wp-admin to the login page. With this plugin active, those redirects go to your custom login URL.

Recovery: If you misconfigure the slug and get locked out, rename the plugin folder via FTP to disable it temporarily.

== Installation ==
1. Upload the `wp-dashboard-login-customizer` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → Login URL, set your desired slug (e.g., `portal`) and save.
4. Ensure Pretty Permalinks are enabled in Settings → Permalinks.

== Frequently Asked Questions ==
= Does this affect admin access? =
When not logged in, visiting /wp-admin redirects to the login URL, which now uses your custom slug.

= What if my theme/plugins link to wp-login.php directly? =
The plugin filters core login URL helpers and most links are rewritten to the custom slug while preserving query args.

= Is wp-login.php disabled? =
Direct access is blocked with a 404, but the login still renders via your custom slug.

== Changelog ==
= 1.0.0 =
- Initial release: custom login slug, URL filtering, rewrite rules, settings page, uninstall cleanup.
