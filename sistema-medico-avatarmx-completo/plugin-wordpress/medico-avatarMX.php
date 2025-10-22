<?php
/*
Plugin Name: Sistema Médico AvatarMX
Plugin URI: https://avatarmx.com/
Description: Plugin para integrar el sistema de consulta médica automatizada AvatarMX con WordPress y WooCommerce.
Version: 2.0.1
Author: AvatarMX
Author URI: https://avatarmx.com/
License: GPLv2 or later
Text Domain: avatarmx
Domain Path: /languages
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('AVATARMX_VERSION', '2.0.1');
define('AVATARMX_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AVATARMX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AVATARMX_API_NAMESPACE', 'avatarmx/v1');

final class SistemaMedicoAvatarMX {
    private static $instance = null;
    public $server_url;
    public $api_key;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->server_url = get_option('avatarmx_server_url', '');
        $this->api_key = get_option('avatarmx_api_key', '');
        $this->define_hooks();
    }

    private function define_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_shortcode('chat_medico_avatarmx', [$this, 'render_chat_shortcode']);
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_pdf_upload']);

        // WooCommerce Hooks
        add_filter('woocommerce_product_data_tabs', [$this, 'add_wc_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'render_wc_product_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_wc_product_meta']);
        add_action('woocommerce_order_status_completed', [$this, 'grant_access_on_purchase']);
    }

    public function load_textdomain() {
        load_plugin_textdomain('avatarmx', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'chat_medico_avatarmx')) {
            wp_enqueue_script('avatarmx-chat-js', AVATARMX_PLUGIN_URL . 'assets/js/chat-medico.js', ['jquery'], AVATARMX_VERSION, true);
            wp_enqueue_style('avatarmx-chat-css', AVATARMX_PLUGIN_URL . 'assets/css/chat-medico.css', [], AVATARMX_VERSION);
            wp_localize_script('avatarmx-chat-js', 'avatarmx_vars', [
                'server_url' => $this->server_url,
                'api_key'    => $this->api_key,
                'user_id'    => get_current_user_id(),
                'nonce'      => wp_create_nonce('wp_rest'),
            ]);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'avatarmx') !== false) {
            wp_enqueue_style('avatarmx-admin-css', AVATARMX_PLUGIN_URL . 'assets/css/admin.css', [], AVATARMX_VERSION);
        }
    }

    public function render_chat_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Debes iniciar sesión para acceder al sistema médico.</p>';
        }
        if (!$this->has_medical_access(get_current_user_id())) {
            return '<p>No tienes acceso al sistema médico. Por favor, adquiere un producto que lo incluya.</p>';
        }
        ob_start();
        include(AVATARMX_PLUGIN_PATH . 'templates/chat-medico.php');
        return ob_get_clean();
    }

    public function has_medical_access($user_id) {
        if (user_can($user_id, 'manage_options')) return true; // Admins always have access
        $product_ids = wc_get_products(['meta_key' => '_avatarmx_acceso_medico', 'meta_value' => 'yes', 'return' => 'ids']);
        foreach ($product_ids as $product_id) {
            if (wc_customer_bought_product('', $user_id, $product_id)) {
                return true;
            }
        }
        return false;
    }

    public function register_rest_endpoints() {
        // This endpoint is not needed as the email is sent from the backend
    }

    public function add_admin_menu() {
        add_menu_page('AvatarMX Medical', 'AvatarMX Medical', 'manage_options', 'avatarmx-settings', [$this, 'render_admin_page'], 'dashicons-heart', 80);
        add_submenu_page('avatarmx-settings', 'Estadísticas', 'Estadísticas', 'manage_options', 'avatarmx-stats', [$this, 'render_stats_page']);
        add_submenu_page('avatarmx-settings', 'Logs', 'Logs', 'manage_options', 'avatarmx-logs', [$this, 'render_logs_page']);
    }

    public function render_admin_page() {
        include_once AVATARMX_PLUGIN_PATH . 'templates/admin/settings.php';
    }
    public function render_stats_page() {
        include_once AVATARMX_PLUGIN_PATH . 'templates/admin/stats.php';
    }
    public function render_logs_page() {
        include_once AVATARMX_PLUGIN_PATH . 'templates/admin/logs.php';
    }

    public function register_settings() {
        register_setting('avatarmx_settings_group', 'avatarmx_server_url');
        register_setting('avatarmx_settings_group', 'avatarmx_api_key');
    }

    public function handle_pdf_upload() {
        if (isset($_FILES['medical_pdf_upload']) && check_admin_referer('avatarmx_pdf_upload_nonce')) {
            if (!current_user_can('manage_options') || empty($_FILES['medical_pdf_upload']['tmp_name'])) return;
            if ($_FILES['medical_pdf_upload']['type'] !== 'application/pdf') {
                add_action('admin_notices', fn() => print('<div class="notice notice-error"><p>Error: El archivo debe ser un PDF.</p></div>'));
                return;
            }

            $url = rtrim($this->server_url, '/') . '/api/v1/pdf/cargar-pdf';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($_FILES['medical_pdf_upload']['tmp_name'], 'application/pdf', 'conocimiento_medico.pdf')]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->api_key]);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $notice_type = ($httpcode >= 200 && $httpcode < 300) ? 'success' : 'error';
            $message = ($httpcode >= 200 && $httpcode < 300) ? 'PDF subido exitosamente.' : 'Error al subir PDF: ' . esc_html($response);
            add_action('admin_notices', fn() => print('<div class="notice notice-'.$notice_type.'"><p>'.$message.'</p></div>'));
        }
    }

    public function add_wc_product_tab($tabs) {
        $tabs['avatarmx'] = ['label' => 'AvatarMX Medical', 'target' => 'avatarmx_product_data', 'class' => ['show_if_simple']];
        return $tabs;
    }

    public function render_wc_product_panel() {
        echo '<div id="avatarmx_product_data" class="panel woocommerce_options_panel hidden">';
        woocommerce_wp_checkbox(['id' => '_avatarmx_acceso_medico', 'label' => 'Da acceso al sistema médico', 'desc_tip' => true, 'description' => 'Marcar si este producto otorga acceso al sistema de consultas.']);
        echo '</div>';
    }

    public function save_wc_product_meta($post_id) {
        update_post_meta($post_id, '_avatarmx_acceso_medico', isset($_POST['_avatarmx_acceso_medico']) ? 'yes' : 'no');
    }

    public function grant_access_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        foreach ($order->get_items() as $item) {
            if ('yes' === get_post_meta($item->get_product_id(), '_avatarmx_acceso_medico', true)) {
                // Here you can add a user meta to grant access permanently or for a limited time
                update_user_meta($user_id, 'has_avatarmx_medical_access', 'true');
                break;
            }
        }
    }
}

// Inicializar el plugin
SistemaMedicoAvatarMX::get_instance();

// Hooks de activación/desactivación
register_activation_hook(__FILE__, function() {
    if (!get_option('avatarmx_api_key')) {
        update_option('avatarmx_api_key', wp_generate_password(32, false));
    }
    if (!get_page_by_path('consulta-medica-avatarmx')) {
        wp_insert_post(['post_title' => 'Consulta Médica AvatarMX', 'post_name' => 'consulta-medica-avatarmx', 'post_content' => '[chat_medico_avatarmx]', 'post_status' => 'publish', 'post_type' => 'page']);
    }
});
