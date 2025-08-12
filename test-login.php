<?php
/**
 * Test file for Dashboard Login URL Customizer
 * Place this in your WordPress root directory to test the custom login URL
 */

// Load WordPress
require_once('wp-load.php');

// Check if plugin is active
if (!class_exists('CDLC_Login_URL_Customizer')) {
    die('Plugin not active');
}

$plugin = CDLC_Login_URL_Customizer::get_instance();

echo '<h1>Dashboard Login URL Customizer - Test</h1>';
echo '<h2>Current Settings</h2>';
echo '<p><strong>Custom Slug:</strong> ' . esc_html($plugin->get_slug()) . '</p>';
echo '<p><strong>Custom Login URL:</strong> <a href="' . esc_url($plugin->build_slug_url()) . '">' . esc_html($plugin->build_slug_url()) . '</a></p>';
echo '<p><strong>Default Login URL:</strong> <a href="' . esc_url(wp_login_url()) . '">' . esc_html(wp_login_url()) . '</a></p>';

echo '<h2>Rewrite Rules Check</h2>';
$rewrite_rules = get_option('rewrite_rules');
$slug = $plugin->get_slug();
$rule_found = false;

foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, $slug) !== false) {
        echo '<p><strong>Found rule:</strong> ' . esc_html($pattern) . ' => ' . esc_html($replacement) . '</p>';
        $rule_found = true;
    }
}

if (!$rule_found) {
    echo '<p style="color: red;"><strong>No rewrite rule found for slug:</strong> ' . esc_html($slug) . '</p>';
    echo '<p>Try going to Settings → Login URL and clicking "Flush Rewrite Rules"</p>';
}

echo '<h2>Test Links</h2>';
echo '<p><a href="' . esc_url($plugin->build_slug_url()) . '" target="_blank">Test Custom Login URL</a></p>';
echo '<p><a href="' . esc_url(home_url('/wp-admin/')) . '" target="_blank">Test wp-admin (should 404 if not logged in)</a></p>';
echo '<p><a href="' . esc_url(home_url('/wp-login.php')) . '" target="_blank">Test wp-login.php (should 404)</a></p>';

echo '<h2>Debug Info</h2>';
echo '<p><strong>Pretty Permalinks:</strong> ' . (get_option('permalink_structure') ? 'Enabled' : 'Disabled') . '</p>';
echo '<p><strong>Query Vars:</strong> ' . esc_html(implode(', ', get_query_var_names())) . '</p>';
echo '<p><strong>Plugin Query Var:</strong> ' . esc_html(CDLC_Login_URL_Customizer::QUERY_VAR) . '</p>';
