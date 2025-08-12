<?php
/**
 * Uninstall cleanup for Dashboard Login URL Customizer
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('cdlc_login_slug');
delete_option('cdlc_plugin_version');
