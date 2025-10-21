<?php
/**
 * Plugin Name: Sistema Médico AvatarMX
 * Plugin URI: https://avatarmexchange.com
 * Description: Sistema de consulta médica automatizada con protocolos específicos basados en medicina natural
 * Version: 2.0.0
 * Author: AvatarMX
 * Author URI: https://avatarmexchange.com
 * Text Domain: avatarmx
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;

// Evitar acceso directo
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('AVATARMX_VERSION', '2.0.0');
define('AVATARMX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AVATARMX_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AVATARMX_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AVATARMX_API_NAMESPACE', 'avatarmx/v1');

// Verificar dependencias
register_activation_hook(__FILE__, 'avatarmx_check_dependencies');
function avatarmx_check_dependencies() {
    $missing_deps = [];

    // Verificar WooCommerce
    if (!class_exists('WooCommerce')) {
        $missing_deps[] = 'WooCommerce';
    }

    // Verificar PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $missing_deps[] = 'PHP 7.4 o superior';
    }

    if (!empty($missing_deps)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                __('El plugin Sistema Médico AvatarMX requiere: %s. Por favor, instala y activa las dependencias necesarias.', 'avatarmx'),
                implode(', ', $missing_deps)
            ),
            __('Dependencias faltantes', 'avatarmx'),
            ['back_link' => true]
        );
    }
}

// Clase principal del plugin
final class SistemaMedicoAvatarMX {

    private static $instance = null;
    private $server_url;
    private $api_key;
    private $version;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->version = AVATARMX_VERSION;
        $this->server_url = get_option('avatarmx_server_url', '');
        $this->api_key = get_option('avatarmx_api_key', '');

        $this->define_hooks();
        $this->init();
    }

    private function define_hooks() {
        // Inicialización
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Shortcodes
        add_shortcode('chat_medico_avatarmx', [$this, 'shortcode_chat_medico']);
        add_shortcode('protocolos_avatarmx', [$this, 'shortcode_protocolos']);

        // REST API
        add_action('rest_api_init', [$this, 'registrar_endpoints_rest']);

        // WooCommerce
        add_action('woocommerce_init', [$this, 'init_woocommerce']);

        // Admin
        add_action('admin_menu', [$this, 'agregar_menu_admin']);
        add_action('admin_init', [$this, 'registrar_configuracion']);
        add_action('add_meta_boxes', [$this, 'agregar_metabox_producto']);
        add_action('save_post_product', [$this, 'guardar_metabox_producto']);

        // Usuario
        add_action('wp_dashboard_setup', [$this, 'agregar_widget_dashboard']);
        add_action('show_user_profile', [$this, 'agregar_campos_perfil']);
        add_action('edit_user_profile', [$this, 'agregar_campos_perfil']);
        add_action('personal_options_update', [$this, 'guardar_campos_perfil']);
        add_action('edit_user_profile_update', [$this, 'guardar_campos_perfil']);

        // AJAX actions for chat proxy
        add_action('wp_ajax_avatarmx_iniciar_conversacion', [$this, 'proxy_iniciar_conversacion']);
        add_action('wp_ajax_avatarmx_procesar_respuesta', [$this, 'proxy_procesar_respuesta']);
    }

    public function init() {
        // Inicialización del plugin
        load_plugin_textdomain('avatarmx', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Registrar tipos de contenido personalizados si es necesario
        $this->registrar_tipos_contenido();
    }

    public function enqueue_scripts() {
        // Solo cargar en páginas que usen el shortcode
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'chat_medico_avatarmx') ||
            has_shortcode($post->post_content, 'protocolos_avatarmx'))) {

            wp_enqueue_script(
                'avatarmx-chat-js',
                AVATARMX_PLUGIN_URL . 'assets/js/chat-medico.js',
                ['jquery', 'wp-api'],
                $this->version,
                true
            );

            wp_enqueue_style(
                'avatarmx-chat-css',
                AVATARMX_PLUGIN_URL . 'assets/css/chat-medico.css',
                [],
                $this->version
            );

            // Localizar script con variables
            wp_localize_script('avatarmx-chat-js', 'avatarmx_vars', [
                'server_url' => $this->server_url,
                'user_id' => get_current_user_id(),
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('avatarmx_nonce'),
                'api_nonce' => wp_create_nonce('wp_rest'),
                'strings' => [
                    'loading' => __('Cargando...', 'avatarmx'),
                    'error' => __('Error de conexión', 'avatarmx'),
                    'success' => __('Completado', 'avatarmx'),
                    'sending' => __('Enviando...', 'avatarmx')
                ]
            ]);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'avatarmx') !== false) {
            wp_enqueue_style(
                'avatarmx-admin-css',
                AVATARMX_PLUGIN_URL . 'assets/css/admin.css',
                [],
                $this->version
            );

            wp_enqueue_script(
                'avatarmx-admin-js',
                AVATARMX_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'wp-api'],
                $this->version,
                true
            );
        }
    }

    public function shortcode_chat_medico($atts) {
        $atts = shortcode_atts([
            'modo' => 'completo',
            'mostrar_protocolo' => 'si',
            'idioma' => 'es'
        ], $atts, 'chat_medico_avatarmx');

        // Verificar si el usuario está logueado
        if (!is_user_logged_in()) {
            return $this->mostrar_mensaje_no_logueado();
        }

        // Verificar si tiene acceso médico
        if (!$this->usuario_tiene_acceso_medico()) {
            return $this->mostrar_mensaje_sin_acceso();
        }

        ob_start();
        include AVATARMX_PLUGIN_PATH . 'templates/chat-medico.php';
        return ob_get_clean();
    }

    public function shortcode_protocolos($atts) {
        $atts = shortcode_atts([
            'categoria' => 'todos',
            'mostrar_dietas' => 'si'
        ], $atts, 'protocolos_avatarmx');

        if (!is_user_logged_in() || !$this->usuario_tiene_acceso_medico()) {
            return '<div class="avatarmx-alerta">' .
                   __('Debes tener acceso médico para ver los protocolos.', 'avatarmx') .
                   '</div>';
        }

        ob_start();
        include AVATARMX_PLUGIN_PATH . 'templates/protocolos.php';
        return ob_get_clean();
    }

    private function mostrar_mensaje_no_logueado() {
        return '
        <div class="avatarmx-alerta avatarmx-alerta-info">
            <h3>🔐 Acceso Requerido</h3>
            <p>Para acceder al sistema médico, debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a>.</p>
            <p>Si no tienes cuenta, <a href="' . wp_registration_url() . '">regístrate aquí</a>.</p>
        </div>';
    }

    private function mostrar_mensaje_sin_acceso() {
        $shop_url = get_permalink(wc_get_page_id('shop'));
        return '
        <div class="avatarmx-alerta avatarmx-alerta-warning">
            <h3>🛒 Acceso Médico Requerido</h3>
            <p>Necesitas haber adquirido nuestro producto de consulta médica para acceder al sistema.</p>
            <p><a href="' . $shop_url . '" class="button primary">Ver Productos Disponibles</a></p>
            <p><small>Si ya compraste el producto y no tienes acceso, contacta con soporte.</small></p>
        </div>';
    }

    private function usuario_tiene_acceso_medico() {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return false;
        }

        // Buscar productos que den acceso médico
        $productos_con_acceso = get_posts([
            'post_type' => 'product',
            'meta_key' => '_avatarmx_acceso_medico',
            'meta_value' => 'yes',
            'fields' => 'ids',
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);

        // Verificar si el usuario ha comprado alguno de estos productos
        foreach ($productos_con_acceso as $producto_id) {
            if (wc_customer_bought_product('', $user_id, $producto_id)) {
                return true;
            }
        }

        // Verificar por roles de usuario (para testing o administradores)
        $user = wp_get_current_user();
        if (in_array('administrator', (array) $user->roles) ||
            in_array('shop_manager', (array) $user->roles)) {
            return true;
        }

        return false;
    }

    public function registrar_endpoints_rest() {
        // Endpoint para enviar emails
        register_rest_route(AVATARMX_API_NAMESPACE, '/send-email', [
            'methods' => 'POST',
            'callback' => [$this, 'enviar_email_paciente'],
            'permission_callback' => [$this, 'verificar_permisos_rest'],
            'args' => [
                'user_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'resumen' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ],
                'session_id' => [
                    'required' => true
                ]
            ]
        ]);

        // Endpoint para verificar acceso
        register_rest_route(AVATARMX_API_NAMESPACE, '/verify-access', [
            'methods' => 'GET',
            'callback' => [$this, 'verificar_acceso_usuario'],
            'permission_callback' => [$this, 'verificar_permisos_rest']
        ]);

        // Endpoint para estadísticas
        register_rest_route(AVATARMX_API_NAMESPACE, '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_estadisticas'],
            'permission_callback' => [$this, 'verificar_permisos_admin']
        ]);
    }

    public function verificar_permisos_rest($request) {
        // Verificar API key o autenticación WordPress
        $api_key = $request->get_header('Authorization');
        $stored_key = 'Bearer ' . $this->api_key;

        if ($api_key === $stored_key) {
            return true;
        }

        // Verificar si el usuario está autenticado en WordPress
        return current_user_can('read');
    }

    public function verificar_permisos_admin($request) {
        return current_user_can('manage_options');
    }

    public function enviar_email_paciente($request) {
        $params = $request->get_json_params();
        $user_id = $params['user_id'] ?? 0;
        $resumen = $params['resumen'] ?? '';
        $session_id = $params['session_id'] ?? '';

        if (!$user_id || empty($resumen)) {
            return new WP_Error('missing_params',
                __('Parámetros requeridos faltantes', 'avatarmx'),
                ['status' => 400]
            );
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found',
                __('Usuario no encontrado', 'avatarmx'),
                ['status' => 404]
            );
        }

        $email = $user->user_email;
        $subject = sprintf(
            __('📄 Resumen de tu Consulta Médica - %s', 'avatarmx'),
            get_bloginfo('name')
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email')
        ];

        $enviado = wp_mail($email, $subject, $resumen, $headers);

        if ($enviado) {
            // Registrar envío en logs
            error_log("AvatarMX: Email de consulta médica enviado a {$email} para sesión {$session_id}");

            return [
                'success' => true,
                'message' => __('Email enviado correctamente', 'avatarmx'),
                'email' => $email,
                'session_id' => $session_id,
                'timestamp' => current_time('mysql')
            ];
        } else {
            return new WP_Error('email_failed',
                __('Error al enviar el email', 'avatarmx'),
                ['status' => 500]
            );
        }
    }

    public function verificar_acceso_usuario($request) {
        $user_id = get_current_user_id();

        return [
            'has_access' => $this->usuario_tiene_acceso_medico(),
            'user_id' => $user_id,
            'products' => $this->obtener_productos_acceso_usuario($user_id)
        ];
    }

    public function obtener_estadisticas($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden',
                __('No tienes permisos para ver estadísticas', 'avatarmx'),
                ['status' => 403]
            );
        }

        // Aquí podrías conectar con el servidor para obtener estadísticas
        return [
            'total_usuarios' => $this->obtener_total_usuarios_con_acceso(),
            'productos_acceso' => $this->obtener_productos_con_acceso(),
            'sesiones_hoy' => 0, // Esto vendría del servidor
            'ingresos_totales' => $this->calcular_ingresos_acceso()
        ];
    }

    private function obtener_total_usuarios_con_acceso() {
        // Implementar lógica para contar usuarios con acceso
        return 0;
    }

    private function obtener_productos_con_acceso() {
        return get_posts([
            'post_type' => 'product',
            'meta_key' => '_avatarmx_acceso_medico',
            'meta_value' => 'yes',
            'fields' => 'ids',
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);
    }

    private function calcular_ingresos_acceso() {
        // Implementar cálculo de ingresos
        return 0;
    }

    private function obtener_productos_acceso_usuario($user_id) {
        $productos = [];
        $productos_con_acceso = $this->obtener_productos_con_acceso();

        foreach ($productos_con_acceso as $producto_id) {
            if (wc_customer_bought_product('', $user_id, $producto_id)) {
                $productos[] = [
                    'id' => $producto_id,
                    'name' => get_the_title($producto_id),
                    'purchase_date' => $this->obtener_fecha_compra($user_id, $producto_id)
                ];
            }
        }

        return $productos;
    }

    private function obtener_fecha_compra($user_id, $product_id) {
        // Implementar obtención de fecha de compra
        return null;
    }

    public function init_woocommerce() {
        // Hooks específicos de WooCommerce
        add_filter('woocommerce_product_data_tabs', [$this, 'agregar_tab_producto']);
        add_action('woocommerce_product_data_panels', [$this, 'mostrar_panel_producto']);
        add_action('woocommerce_process_product_meta', [$this, 'guardar_campos_producto']);

        // Cuando se complete una compra de producto con acceso médico
        add_action('woocommerce_order_status_completed', [$this, 'procesar_compra_acceso']);
    }

    public function agregar_tab_producto($tabs) {
        $tabs['avatarmx'] = [
            'label' => __('AvatarMX Medical', 'avatarmx'),
            'target' => 'avatarmx_product_data',
            'class' => ['show_if_simple', 'show_if_variable'],
            'priority' => 80
        ];
        return $tabs;
    }

    public function mostrar_panel_producto() {
        global $post;
        ?>
        <div id="avatarmx_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox([
                    'id' => '_avatarmx_acceso_medico',
                    'label' => __('✅ Dar acceso al sistema médico', 'avatarmx'),
                    'description' => __('Marcar si este producto da acceso al sistema de consultas médicas AvatarMX', 'avatarmx'),
                    'desc_tip' => true
                ]);

                woocommerce_wp_text_input([
                    'id' => '_avatarmx_duracion_acceso',
                    'label' => __('Duración acceso (días)', 'avatarmx'),
                    'type' => 'number',
                    'description' => __('Duración en días del acceso médico (0 = acceso permanente)', 'avatarmx'),
                    'desc_tip' => true,
                    'default' => '0'
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    public function guardar_campos_producto($post_id) {
        $acceso_medico = isset($_POST['_avatarmx_acceso_medico']) ? 'yes' : 'no';
        $duracion_acceso = isset($_POST['_avatarmx_duracion_acceso']) ? absint($_POST['_avatarmx_duracion_acceso']) : 0;

        update_post_meta($post_id, '_avatarmx_acceso_medico', $acceso_medico);
        update_post_meta($post_id, '_avatarmx_duracion_acceso', $duracion_acceso);
    }

    public function procesar_compra_acceso($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $acceso_medico = get_post_meta($product_id, '_avatarmx_acceso_medico', true);

            if ($acceso_medico === 'yes') {
                $this->otorgar_acceso_medico($user_id, $product_id, $order_id);
            }
        }
    }

    private function otorgar_acceso_medico($user_id, $product_id, $order_id) {
        $duracion = get_post_meta($product_id, '_avatarmx_duracion_acceso', true) ?: 0;
        $fecha_expiracion = $duracion > 0 ?
            date('Y-m-d H:i:s', strtotime("+{$duracion} days")) :
            null;

        update_user_meta($user_id, '_avatarmx_acceso_medico', 'yes');
        update_user_meta($user_id, '_avatarmx_producto_acceso', $product_id);
        update_user_meta($user_id, '_avatarmx_orden_acceso', $order_id);

        if ($fecha_expiracion) {
            update_user_meta($user_id, '_avatarmx_expiracion_acceso', $fecha_expiracion);
        }

        // Registrar en logs
        error_log("AvatarMX: Acceso médico otorgado al usuario {$user_id} mediante producto {$product_id}");
    }

    public function agregar_menu_admin() {
        add_menu_page(
            __('Sistema Médico AvatarMX', 'avatarmx'),
            __('AvatarMX Medical', 'avatarmx'),
            'manage_options',
            'avatarmx-settings',
            [$this, 'mostrar_pagina_configuracion'],
            'dashicons-heart',
            80
        );

        add_submenu_page(
            'avatarmx-settings',
            __('Configuración', 'avatarmx'),
            __('Configuración', 'avatarmx'),
            'manage_options',
            'avatarmx-settings',
            [$this, 'mostrar_pagina_configuracion']
        );

        add_submenu_page(
            'avatarmx-settings',
            __('Estadísticas', 'avatarmx'),
            __('Estadísticas', 'avatarmx'),
            'manage_options',
            'avatarmx-stats',
            [$this, 'mostrar_pagina_estadisticas']
        );

        add_submenu_page(
            'avatarmx-settings',
            __('Logs', 'avatarmx'),
            __('Logs', 'avatarmx'),
            'manage_options',
            'avatarmx-logs',
            [$this, 'mostrar_pagina_logs']
        );
    }

    public function mostrar_pagina_configuracion() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'avatarmx'));
        }

        include AVATARMX_PLUGIN_PATH . 'templates/admin/settings.php';
    }

    public function mostrar_pagina_estadisticas() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'avatarmx'));
        }

        include AVATARMX_PLUGIN_PATH . 'templates/admin/stats.php';
    }

    public function mostrar_pagina_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'avatarmx'));
        }

        include AVATARMX_PLUGIN_PATH . 'templates/admin/logs.php';
    }

    public function registrar_configuracion() {
        register_setting('avatarmx_settings', 'avatarmx_server_url', 'sanitize_url');
        register_setting('avatarmx_settings', 'avatarmx_api_key', 'sanitize_text_field');
    }


    public function agregar_metabox_producto() {
        add_meta_box(
            'avatarmx_acceso_medico',
            __('🎯 Acceso Sistema Médico AvatarMX', 'avatarmx'),
            [$this, 'render_metabox_producto'],
            'product',
            'side',
            'high'
        );
    }

    public function render_metabox_producto($post) {
        wp_nonce_field('avatarmx_meta_box', 'avatarmx_meta_box_nonce');

        $acceso_medico = get_post_meta($post->ID, '_avatarmx_acceso_medico', true);
        $duracion_acceso = get_post_meta($post->ID, '_avatarmx_duracion_acceso', true) ?: 0;
        ?>
        <div class="avatarmx-metabox">
            <p>
                <label for="_avatarmx_acceso_medico">
                    <input type="checkbox" id="_avatarmx_acceso_medico" name="_avatarmx_acceso_medico" value="yes" <?php checked($acceso_medico, 'yes'); ?>>
                    <strong><?php _e('✅ Este producto da acceso al sistema médico AvatarMX', 'avatarmx'); ?></strong>
                </label>
            </p>

            <p>
                <label for="_avatarmx_duracion_acceso">
                    <strong><?php _e('Duración del acceso (días):', 'avatarmx'); ?></strong>
                </label>
                <input type="number" id="_avatarmx_duracion_acceso" name="_avatarmx_duracion_acceso" value="<?php echo esc_attr($duracion_acceso); ?>" min="0" step="1" style="width: 100%;">
                <small><?php _e('0 = acceso permanente', 'avatarmx'); ?></small>
            </p>

            <p class="description">
                <?php _e('Los usuarios que compren este producto podrán acceder al sistema de consulta médica automatizada.', 'avatarmx'); ?>
            </p>
        </div>
        <style>
        .avatarmx-metabox {
            padding: 10px 0;
        }
        .avatarmx-metabox p {
            margin-bottom: 15px;
        }
        </style>
        <?php
    }

    public function guardar_metabox_producto($post_id) {
        // Verificar nonce
        if (!isset($_POST['avatarmx_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['avatarmx_meta_box_nonce'], 'avatarmx_meta_box')) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar valores
        $acceso_medico = isset($_POST['_avatarmx_acceso_medico']) ? 'yes' : 'no';
        $duracion_acceso = isset($_POST['_avatarmx_duracion_acceso']) ? absint($_POST['_avatarmx_duracion_acceso']) : 0;

        update_post_meta($post_id, '_avatarmx_acceso_medico', $acceso_medico);
        update_post_meta($post_id, '_avatarmx_duracion_acceso', $duracion_acceso);
    }

    public function agregar_widget_dashboard() {
        if ($this->usuario_tiene_acceso_medico()) {
            wp_add_dashboard_widget(
                'avatarmx_medical_access',
                __('🏥 Acceso Médico AvatarMX', 'avatarmx'),
                [$this, 'mostrar_widget_dashboard']
            );
        }
    }

    public function mostrar_widget_dashboard() {
        $user_id = get_current_user_id();
        $expiracion = get_user_meta($user_id, '_avatarmx_expiracion_acceso', true);

        echo '<div class="avatarmx-dashboard-widget">';
        echo '<p>' . __('Tienes acceso al sistema médico AvatarMX.', 'avatarmx') . '</p>';

        if ($expiracion) {
            $fecha_expiracion = date_i18n(get_option('date_format'), strtotime($expiracion));
            echo '<p><strong>' . __('Acceso válido hasta:', 'avatarmx') . '</strong> ' . $fecha_expiracion . '</p>';
        } else {
            echo '<p><strong>' . __('✅ Acceso permanente', 'avatarmx') . '</strong></p>';
        }

        $chat_page = $this->obtener_pagina_chat();
        if ($chat_page) {
            echo '<p><a href="' . get_permalink($chat_page) . '" class="button button-primary">' .
                 __('Ir al Sistema Médico', 'avatarmx') .
                 '</a></p>';
        }

        echo '</div>';
    }

    public function agregar_campos_perfil($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $acceso_medico = get_user_meta($user->ID, '_avatarmx_acceso_medico', true);
        $expiracion = get_user_meta($user->ID, '_avatarmx_expiracion_acceso', true);
        ?>
        <h3><?php _e('🎯 Acceso Sistema Médico AvatarMX', 'avatarmx'); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="avatarmx_acceso_medico"><?php _e('Acceso médico', 'avatarmx'); ?></label></th>
                <td>
                    <input type="checkbox" name="avatarmx_acceso_medico" id="avatarmx_acceso_medico" value="yes" <?php checked($acceso_medico, 'yes'); ?>>
                    <label for="avatarmx_acceso_medico"><?php _e('Otorgar acceso al sistema médico', 'avatarmx'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="avatarmx_expiracion_acceso"><?php _e('Fecha de expiración', 'avatarmx'); ?></label></th>
                <td>
                    <input type="datetime-local" name="avatarmx_expiracion_acceso" id="avatarmx_expiracion_acceso" value="<?php echo esc_attr($expiracion); ?>" class="regular-text">
                    <p class="description"><?php _e('Dejar vacío para acceso permanente', 'avatarmx'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function guardar_campos_perfil($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $acceso_medico = isset($_POST['avatarmx_acceso_medico']) ? 'yes' : 'no';
        $expiracion = sanitize_text_field($_POST['avatarmx_expiracion_acceso'] ?? '');

        update_user_meta($user_id, '_avatarmx_acceso_medico', $acceso_medico);

        if (!empty($expiracion)) {
            update_user_meta($user_id, '_avatarmx_expiracion_acceso', $expiracion);
        } else {
            delete_user_meta($user_id, '_avatarmx_expiracion_acceso');
        }
    }

    private function registrar_tipos_contenido() {
        // Aquí podrías registrar CPTs si es necesario
    }

    private function obtener_pagina_chat() {
        $page_id = get_option('avatarmx_chat_page_id');
        return $page_id ? get_post($page_id) : null;
    }

    // Proxy AJAX Handlers
    public function proxy_iniciar_conversacion() {
        check_ajax_referer('avatarmx_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Usuario no autenticado.'], 403);
        }

        $response = $this->make_api_request('/iniciar-conversacion', [
            'user_id' => $user_id
        ]);

        wp_send_json($response);
    }

    public function proxy_procesar_respuesta() {
        check_ajax_referer('avatarmx_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id']);
        $answer = sanitize_text_field($_POST['answer']);

        $response = $this->make_api_request('/procesar-respuesta', [
            'session_id' => $session_id,
            'answer' => $answer
        ]);

        wp_send_json($response);
    }

    private function make_api_request($endpoint, $body) {
        $url = rtrim($this->server_url, '/') . '/api/v1' . $endpoint;

        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => json_encode($body),
            'timeout' => 45,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['error' => true, 'message' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}

// Inicializar el plugin
function avatarmx_init() {
    return SistemaMedicoAvatarMX::get_instance();
}

// Iniciar
add_action('plugins_loaded', 'avatarmx_init');

// Hook de activación
register_activation_hook(__FILE__, 'avatarmx_activate');
function avatarmx_activate() {
    // Crear página de chat médico si no existe
    avatarmx_crear_pagina_chat();

    // Programar limpieza de sesiones expiradas
    if (!wp_next_scheduled('avatarmx_cleanup_sessions')) {
        wp_schedule_event(time(), 'daily', 'avatarmx_cleanup_sessions');
    }

    // Configuración inicial
    if (!get_option('avatarmx_server_url')) {
        update_option('avatarmx_server_url', 'https://tu-servidor.railway.app');
    }

    if (!get_option('avatarmx_api_key')) {
        update_option('avatarmx_api_key', avatarmx_generar_api_key());
    }
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'avatarmx_deactivate');
function avatarmx_deactivate() {
    wp_clear_scheduled_hook('avatarmx_cleanup_sessions');
}

// Generar API key
function avatarmx_generar_api_key($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $key;
}

// Crear página de chat
function avatarmx_crear_pagina_chat() {
    $pagina_existe = get_page_by_path('consulta-medica-avatarmx');

    if (!$pagina_existe) {
        $pagina_id = wp_insert_post([
            'post_title' => __('Consulta Médica AvatarMX', 'avatarmx'),
            'post_name' => 'consulta-medica-avatarmx',
            'post_content' => '[chat_medico_avatarmx]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta_input' => [
                '_wp_page_template' => 'full-width.php'
            ]
        ]);

        if ($pagina_id && !is_wp_error($pagina_id)) {
            update_option('avatarmx_chat_page_id', $pagina_id);
        }
    }
}

// Limpieza de sesiones expiradas
add_action('avatarmx_cleanup_sessions', 'avatarmx_limpiar_sesiones_expiradas');
function avatarmx_limpiar_sesiones_expiradas() {
    // Esta función se conectaría con el servidor para limpiar sesiones antiguas
    error_log('AvatarMX: Ejecutando limpieza de sesiones expiradas');
}
