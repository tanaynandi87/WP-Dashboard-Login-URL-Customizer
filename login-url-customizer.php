<?php
/**
 * Plugin Name: Dashboard Login URL Customizer
 * Description: Change the WordPress login URL from /wp-admin or /wp-login.php to a custom, admin-defined slug.
 * Version: 1.0.0
 * Author: Tanay Nandi
 * Author URI: mailto:tanay.nandi87@gmail.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.5
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CDLC_Login_URL_Customizer {
    const OPTION_SLUG = 'cdlc_login_slug';
    const OPTION_VERSION = 'cdlc_plugin_version';
    const QUERY_VAR = 'cdlc_login';

    private static $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Block admin area for logged-out users with 404 (before core auth_redirect)
        add_action('init', [$this, 'maybe_block_admin_when_logged_out'], 0);

        add_action('init', [$this, 'add_rewrite_rules'], 1);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'maybe_render_login_from_slug']);

        add_action('login_init', [$this, 'maybe_block_wp_login_direct_access']);

        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
        add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
        add_filter('lostpassword_url', [$this, 'filter_lostpassword_url'], 10, 2);
        add_filter('register_url', [$this, 'filter_register_url'], 10);
        add_filter('site_url', [$this, 'filter_site_url'], 10, 4);
        add_filter('network_site_url', [$this, 'filter_site_url'], 10, 3);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_settings_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_notices', [$this, 'maybe_show_permalinks_notice']);
        }

        register_activation_hook(__FILE__, [__CLASS__, 'on_activation']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'on_deactivation']);
    }

    public static function on_activation(): void {
        if (get_option(self::OPTION_SLUG, null) === null) {
            add_option(self::OPTION_SLUG, 'login');
        }
        update_option(self::OPTION_VERSION, '1.0.0');
        self::get_instance()->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function on_deactivation(): void {
        flush_rewrite_rules();
    }

    public function get_slug(): string {
        $raw = (string) get_option(self::OPTION_SLUG, 'login');
        $slug = sanitize_title_with_dashes($raw);
        if ($slug === '' || $this->is_reserved_slug($slug)) {
            $slug = 'login';
        }
        return $slug;
    }

    private function is_reserved_slug(string $slug): bool {
        $reserved = [
            'wp-admin',
            'wp-login.php',
            'wp-login',
            'admin',
            'admin-ajax.php',
            'xmlrpc.php',
        ];
        return in_array($slug, $reserved, true);
    }

    private function build_slug_url(array $extra_query_args = []): string {
        $base = home_url('/' . $this->get_slug() . '/');
        if (!empty($extra_query_args)) {
            $base = add_query_arg($extra_query_args, $base);
        }
        return $base;
    }

    private function replace_base_with_slug_url(string $url): string {
        $parsed = wp_parse_url($url);
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        return $this->build_slug_url($query);
    }

    public function add_rewrite_rules(): void {
        $slug = $this->get_slug();
        // More specific rewrite rule
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
        // Also handle with trailing slash variations
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
    }

    public function register_query_vars(array $vars): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public function maybe_render_login_from_slug(): void {
        if ((string) get_query_var(self::QUERY_VAR) !== '1') {
            return;
        }
        
        // Set the marker for wp-login.php
        $_GET['cdlc'] = '1'; // phpcs:ignore WordPress.Security.NonceVerification
        
        // Load the login template
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    public function maybe_block_wp_login_direct_access(): void {
        if (isset($_GET['cdlc']) && $_GET['cdlc'] == '1') { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        if ($this->is_request_from_wp_cli_or_cron()) {
            return;
        }
        status_header(404);
        nocache_headers();
        $template = get_404_template();
        if ($template && file_exists($template)) {
            include $template;
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>404 Not Found</title><h1>Not Found</h1>';
        }
        exit;
    }

    public function maybe_block_admin_when_logged_out(): void {
        // Only for admin area requests, excluding AJAX/cron/CLI, when not logged in
        if (!is_admin()) {
            return;
        }
        if (is_user_logged_in()) {
            return;
        }
        if ($this->is_request_from_wp_cli_or_cron()) {
            return;
        }
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return;
        }
        status_header(404);
        nocache_headers();
        $template = get_404_template();
        if ($template && file_exists($template)) {
            include $template;
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>404 Not Found</title><h1>Not Found</h1>';
        }
        exit;
    }

    private function is_request_from_wp_cli_or_cron(): bool {
        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }
        return false;
    }

    public function filter_login_url(string $login_url, string $redirect, bool $force_reauth): string {
        $args = [];
        if (!empty($redirect)) {
            $args['redirect_to'] = $redirect;
        }
        $login_url = $this->replace_base_with_slug_url(add_query_arg($args, $login_url));
        return $login_url;
    }

    public function filter_logout_url(string $logout_url, string $redirect): string {
        $logout_url = $this->replace_base_with_slug_url($logout_url);
        if (!empty($redirect)) {
            $logout_url = add_query_arg('redirect_to', $redirect, $logout_url);
        }
        return $logout_url;
    }

    public function filter_lostpassword_url(string $lostpassword_url, string $redirect): string {
        $lostpassword_url = $this->replace_base_with_slug_url($lostpassword_url);
        if (!empty($redirect)) {
            $lostpassword_url = add_query_arg('redirect_to', $redirect, $lostpassword_url);
        }
        return $lostpassword_url;
    }

    public function filter_register_url(string $register_url): string {
        return $this->replace_base_with_slug_url($register_url);
    }

    public function filter_site_url(string $url, string $path, $scheme, $blog_id = null): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        $path = (string) $path;
        if ($path === 'wp-login.php' || strpos($path, 'wp-login.php?') === 0) {
            return $this->replace_base_with_slug_url($url);
        }
        return $url;
    }

    public function add_settings_page(): void {
        add_options_page(
            __('Login URL', 'cdlc'),
            __('Login URL', 'cdlc'),
            'manage_options',
            'cdlc-login-url',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting('cdlc_settings_group', self::OPTION_SLUG, [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                $value = (string) $value;
                $value = sanitize_title_with_dashes($value);
                if ($value === '' || $this->is_reserved_slug($value)) {
                    add_settings_error(self::OPTION_SLUG, 'cdlc_invalid_slug', __('Invalid or reserved slug. Please choose a different value.', 'cdlc'));
                    return $this->get_slug();
                }
                $old = $this->get_slug();
                if ($old !== $value) {
                    // Force rewrite flush after option update
                    add_action('updated_option', function ($option, $old_value, $new_value) {
                        if ($option === self::OPTION_SLUG) {
                            // Remove old rules and add new ones
                            $this->remove_old_rewrite_rules();
                            $this->add_rewrite_rules();
                            flush_rewrite_rules();
                        }
                    }, 10, 3);
                }
                return $value;
            },
            'default' => 'login',
        ]);

        add_settings_section(
            'cdlc_section_main',
            __('Custom Login URL', 'cdlc'),
            function () {
                echo '<p>' . esc_html__('Set a custom slug for your login page. Example: entering "portal" makes your login URL https://example.com/portal/', 'cdlc') . '</p>';
            },
            'cdlc_settings_page'
        );

        add_settings_field(
            self::OPTION_SLUG,
            __('Login slug', 'cdlc'),
            function () {
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" pattern="[a-z0-9\\-]+" />',
                    esc_attr(self::OPTION_SLUG),
                    esc_attr($this->get_slug())
                );
                echo '<p class="description">' . esc_html__('Use only lowercase letters, numbers, and dashes. Avoid reserved words like wp-admin or wp-login.', 'cdlc') . '</p>';
            },
            'cdlc_settings_page',
            'cdlc_section_main'
        );
    }

    private function remove_old_rewrite_rules(): void {
        // This is a simple approach - in production you might want to track and remove specific rules
        // For now, we'll rely on flush_rewrite_rules() to rebuild everything
    }

    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle manual flush request
        if (isset($_POST['cdlc_flush_rules']) && wp_verify_nonce($_POST['_wpnonce'], 'cdlc_flush_rules')) {
            $this->add_rewrite_rules();
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>' . esc_html__('Rewrite rules flushed successfully!', 'cdlc') . '</p></div>';
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Login URL', 'cdlc') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('cdlc_settings_group');
        do_settings_sections('cdlc_settings_page');
        submit_button(__('Save Changes', 'cdlc'));
        echo '</form>';
        
        // Manual flush button
        echo '<hr />';
        echo '<h3>' . esc_html__('Troubleshooting', 'cdlc') . '</h3>';
        echo '<form method="post" action="">';
        wp_nonce_field('cdlc_flush_rules');
        echo '<p>' . esc_html__('If your custom login URL is giving a 404 error, try flushing the rewrite rules:', 'cdlc') . '</p>';
        echo '<input type="submit" name="cdlc_flush_rules" class="button button-secondary" value="' . esc_attr__('Flush Rewrite Rules', 'cdlc') . '" />';
        echo '</form>';
        
        echo '<hr />';
        echo '<p>' . esc_html__('Current login URL:', 'cdlc') . ' <code>' . esc_html($this->build_slug_url()) . '</code></p>';
        
        // Simple status info
        if (current_user_can('manage_options')) {
            echo '<h3>' . esc_html__('Status Information', 'cdlc') . '</h3>';
            echo '<p><strong>' . esc_html__('Current slug:', 'cdlc') . '</strong> ' . esc_html($this->get_slug()) . '</p>';
            echo '<p><strong>' . esc_html__('Pretty permalinks enabled:', 'cdlc') . '</strong> ' . (get_option('permalink_structure') ? 'Yes' : 'No') . '</p>';
            
            // Check if rewrite rules exist (simplified)
            $rewrite_rules = get_option('rewrite_rules');
            $slug = $this->get_slug();
            $rule_found = false;
            
            if ($rewrite_rules && is_array($rewrite_rules)) {
                foreach ($rewrite_rules as $pattern => $replacement) {
                    if (is_string($pattern) && strpos($pattern, $slug) !== false && strpos($replacement, self::QUERY_VAR) !== false) {
                        $rule_found = true;
                        break;
                    }
                }
            }
            
            echo '<p><strong>' . esc_html__('Rewrite rule found:', 'cdlc') . '</strong> ' . ($rule_found ? 'Yes' : 'No') . '</p>';
            
            if (!$rule_found) {
                echo '<p style="color: #d63638;"><strong>' . esc_html__('Note:', 'cdlc') . '</strong> ' . esc_html__('No rewrite rule found. Click "Flush Rewrite Rules" above to fix this.', 'cdlc') . '</p>';
            }
        }
        
        echo '</div>';
    }

    public function maybe_show_permalinks_notice(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (get_option('permalink_structure')) {
            return;
        }
        echo '<div class="notice notice-warning"><p>' . esc_html__(
            'For the custom login URL to work reliably, please enable Pretty Permalinks in Settings → Permalinks.',
            'cdlc'
        ) . '</p></div>';
    }
}

CDLC_Login_URL_Customizer::get_instance();
