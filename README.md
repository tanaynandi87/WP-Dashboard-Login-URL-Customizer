# Dashboard Login URL Customizer

Change the WordPress login URL from `/wp-admin` / `wp-login.php` to a custom, admin-defined slug.

## Quick start
1. Copy `wp-dashboard-login-customizer/` into `wp-content/plugins/`.
2. Activate the plugin in wp-admin → Plugins.
3. Go to Settings → Login URL and set your slug (e.g., `portal`).
4. Ensure Pretty Permalinks are enabled (Settings → Permalinks).

## What it does
- Serves the login at `https://yoursite.com/<slug>/`.
- Blocks direct access to `wp-login.php` with a 404.
- Redirects core login flows (login, logout, lost password, register) to the slug.
- Flushes rewrite rules on activation/deactivation.
- Uninstall removes plugin options.

## Recovery
If you’re locked out due to a bad slug, rename the plugin folder via FTP to disable it.

## License
GPL-2.0-or-later. See `readme.txt` for details.
