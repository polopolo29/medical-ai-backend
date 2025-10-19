<?php
/**
 * Clase para manejar el área de administración del plugin.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class Shorts_Automator_Pro_Admin
 *
 * Se encarga de la lógica del panel de administración, incluyendo la creación
 * del menú, el encolado de scripts y el manejo de peticiones AJAX.
 */
class Shorts_Automator_Pro_Admin {

    /**
     * Inicializa los hooks del área de administración.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_sap_create_profile', array( $this, 'ajax_create_profile' ) );
        add_action( 'wp_ajax_sap_delete_profile', array( $this, 'ajax_delete_profile' ) );
        add_action( 'wp_ajax_sap_update_profile', array( $this, 'ajax_update_profile' ) );
        add_action( 'wp_ajax_sap_get_connections', array( $this, 'ajax_get_connections' ) );
        add_action( 'wp_ajax_sap_save_connection', array( $this, 'ajax_save_connection' ) );
        add_action( 'wp_ajax_sap_disconnect_platform', array( $this, 'ajax_disconnect_platform' ) );
        add_action( 'wp_ajax_sap_schedule_short', array( $this, 'ajax_schedule_short' ) );
    }

    /**
     * Añade el menú del plugin al panel de administración de WordPress.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Shorts Automator Pro', 'shorts-automator-pro' ),
            __( 'Shorts Automator', 'shorts-automator-pro' ),
            'manage_options',
            'shorts-automator-pro',
            array( $this, 'render_dashboard_page' ),
            'dashicons-video-alt3',
            25
        );
    }

    /**
     * Renderiza la página del dashboard principal del plugin.
     */
    public function render_dashboard_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin-dashboard.php';
    }

    /**
     * Encola los scripts y estilos del panel de administración.
     */
    public function enqueue_scripts( $hook ) {
        // Solo cargar en la página del plugin
        if ( 'toplevel_page_shorts-automator-pro' !== $hook ) {
            return;
        }

        wp_enqueue_media(); // Añadir scripts de la biblioteca de medios

        wp_enqueue_style(
            'sap-admin-style',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.css',
            array(),
            SHORTS_AUTOMATOR_PRO_VERSION
        );

        wp_enqueue_script(
            'sap-admin-script',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.js',
            array( 'jquery' ),
            SHORTS_AUTOMATOR_PRO_VERSION,
            true
        );

        // Pasar datos de PHP a JavaScript de forma segura
        wp_localize_script(
            'sap-admin-script',
            'sap_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sap_ajax_nonce' )
            )
        );
    }

    /**
     * Maneja la petición AJAX para crear un nuevo perfil.
     */
    public function ajax_create_profile() {
        check_ajax_referer( 'sap_create_profile_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'shorts-automator-pro' ) ) );
        }

        if ( ! isset( $_POST['name'] ) || empty( $_POST['name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'El nombre del perfil no puede estar vacío.', 'shorts-automator-pro' ) ) );
        }

        $profile_name = sanitize_text_field( $_POST['name'] );

        $profile_id = Shorts_Automator_Pro_Profile_Manager::create_profile( $profile_name );

        if ( $profile_id ) {
            wp_send_json_success( array( 'profile_id' => $profile_id ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo crear el perfil en la base de datos.', 'shorts-automator-pro' ) ) );
        }
    }

    /**
     * Maneja la petición AJAX para eliminar un perfil.
     */
    public function ajax_delete_profile() {
        check_ajax_referer( 'sap_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'shorts-automator-pro' ) ) );
        }

        if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'ID de perfil no válido.', 'shorts-automator-pro' ) ) );
        }

        $profile_id = absint( $_POST['id'] );

        $deleted = Shorts_Automator_Pro_Profile_Manager::delete_profile( $profile_id );

        if ( $deleted ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo eliminar el perfil de la base de datos.', 'shorts-automator-pro' ) ) );
        }
    }

    /**
     * Maneja la petición AJAX para actualizar un perfil.
     */
    public function ajax_update_profile() {
        check_ajax_referer( 'sap_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'shorts-automator-pro' ) ) );
        }

        if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) || ! isset( $_POST['name'] ) || empty( $_POST['name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Datos de perfil no válidos.', 'shorts-automator-pro' ) ) );
        }

        $profile_id = absint( $_POST['id'] );
        $profile_name = sanitize_text_field( $_POST['name'] );

        $updated = Shorts_Automator_Pro_Profile_Manager::update_profile( $profile_id, $profile_name );

        if ( $updated ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo actualizar el perfil en la base de datos.', 'shorts-automator-pro' ) ) );
        }
    }

    /**
     * Maneja la petición AJAX para obtener las conexiones de un perfil.
     */
    public function ajax_get_connections() {
        check_ajax_referer( 'sap_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        if ( ! isset( $_POST['profile_id'] ) ) {
            wp_send_json_error();
        }

        $profile_id = absint( $_POST['profile_id'] );
        $connections = Shorts_Automator_Pro_Platform_Manager::get_platform_connections( $profile_id );
        $platforms = array( 'facebook', 'instagram', 'tiktok', 'youtube' );

        ob_start();
        foreach ( $platforms as $platform ) {
            $is_connected = isset( $connections[ $platform ] );
            ?>
            <div class="platform-connection-item">
                <h4><?php echo ucfirst( $platform ); ?></h4>
                <div class="connection-status">
                    <strong><?php _e( 'Estado:', 'shorts-automator-pro' ); ?></strong>
                    <span class="<?php echo $is_connected ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $is_connected ? __( 'Conectado', 'shorts-automator-pro' ) : __( 'Desconectado', 'shorts-automator-pro' ); ?>
                    </span>
                </div>
                <button class="button button-secondary toggle-connection-form"><?php echo $is_connected ? __( 'Editar', 'shorts-automator-pro' ) : __( 'Conectar', 'shorts-automator-pro' ); ?></button>
                <?php if ( $is_connected ) : ?>
                    <button class="button button-danger disconnect-platform" data-platform="<?php echo esc_attr( $platform ); ?>"><?php _e( 'Desconectar', 'shorts-automator-pro' ); ?></button>
                <?php endif; ?>
                <div class="connection-form" style="display:none;">
                    <?php $this->render_platform_form( $platform, $connections ); ?>
                </div>
            </div>
            <?php
        }
        wp_send_json_success( array( 'html' => ob_get_clean() ) );
    }

    /**
     * Renderiza el formulario específico para una plataforma.
     */
    private function render_platform_form( $platform, $connections ) {
        $values = isset( $connections[ $platform ] ) ? (array) $connections[ $platform ] : array();
        ?>
        <form class="save-connection-form" data-platform="<?php echo esc_attr( $platform ); ?>">
            <?php
            switch ( $platform ) {
                case 'facebook':
                case 'instagram':
                    ?>
                    <div class="form-field">
                        <label><?php _e( 'App ID', 'shorts-automator-pro' ); ?></label>
                        <input type="text" name="app_id" value="<?php echo esc_attr( $values['app_id'] ?? '' ); ?>" required>
                    </div>
                    <div class="form-field">
                        <label><?php _e( 'App Secret', 'shorts-automator-pro' ); ?></label>
                        <input type="password" name="app_secret" value="<?php echo esc_attr( $values['app_secret'] ?? '' ); ?>" required>
                    </div>
                    <div class="form-field">
                        <label><?php _e( 'Access Token', 'shorts-automator-pro' ); ?></label>
                        <textarea name="access_token" required><?php echo esc_textarea( $values['access_token'] ?? '' ); ?></textarea>
                    </div>
                    <?php
                    break;
                case 'tiktok':
                    ?>
                    <div class="form-field">
                        <label><?php _e( 'Access Token', 'shorts-automator-pro' ); ?></label>
                        <textarea name="access_token" required><?php echo esc_textarea( $values['access_token'] ?? '' ); ?></textarea>
                    </div>
                    <?php
                    break;
                case 'youtube':
                    ?>
                    <div class="form-field">
                        <label><?php _e( 'API Key', 'shorts-automator-pro' ); ?></label>
                        <input type="text" name="api_key" value="<?php echo esc_attr( $values['api_key'] ?? '' ); ?>" required>
                    </div>
                    <div class="form-field">
                        <label><?php _e( 'Channel ID', 'shorts-automator-pro' ); ?></label>
                        <input type="text" name="channel_id" value="<?php echo esc_attr( $values['channel_id'] ?? '' ); ?>" required>
                    </div>
                    <?php
                    break;
            }
            ?>
            <button type="submit" class="button button-primary"><?php _e( 'Guardar Conexión', 'shorts-automator-pro' ); ?></button>
        </form>
        <?php
    }

    /**
     * Maneja la petición AJAX para guardar una conexión.
     */
    public function ajax_save_connection() {
        check_ajax_referer( 'sap_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'shorts-automator-pro' ) ) );
        }

        $required = array( 'profile_id', 'platform', 'credentials' );
        foreach ( $required as $key ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Faltan datos.', 'shorts-automator-pro' ) ) );
            }
        }

        $profile_id = absint( $_POST['profile_id'] );
        $platform = sanitize_key( $_POST['platform'] );
        parse_str( $_POST['credentials'], $credentials );

        $updated = Shorts_Automator_Pro_Platform_Manager::update_connection( $profile_id, $platform, $credentials );

        if ( $updated ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo guardar la conexión.', 'shorts-automator-pro' ) ) );
        }
    }

    /**
     * Maneja la petición AJAX para desconectar una plataforma.
     */
    public function ajax_disconnect_platform() {
        check_ajax_referer( 'sap_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'shorts-automator-pro' ) ) );
        }

        if ( ! isset( $_POST['profile_id'] ) || ! isset( $_POST['platform'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Faltan datos.', 'shorts-automator-pro' ) ) );
        }

        $profile_id = absint( $_POST['profile_id'] );
        $platform = sanitize_key( $_POST['platform'] );

        $deleted = Shorts_Automator_Pro_Platform_Manager::delete_connection( $profile_id, $platform );

        if ( $deleted ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo desconectar la plataforma.', 'shorts-automator-pro' ) ) );
        }
    }

    /**
     * Maneja la petición AJAX para programar un short.
     */
    public function ajax_schedule_short() {
        check_ajax_referer( 'sap_schedule_short_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'shorts-automator-pro' ) ) );
        }

        // Validación de datos (simplificada, se puede mejorar)
        $video_id = isset( $_POST['video_id'] ) ? absint( $_POST['video_id'] ) : 0;
        $validation = Shorts_Automator_Pro_Video_Processor::validate_attachment( $video_id );
        if ( is_wp_error( $validation ) ) {
            wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
        }

        $data = array(
            'profile_id'   => isset( $_POST['profile_id'] ) ? absint( $_POST['profile_id'] ) : 0,
            'video_path'   => get_attached_file( $video_id ),
            'platforms'    => isset( $_POST['platforms'] ) ? (array) $_POST['platforms'] : array(),
            'metadata'     => array(
                'title' => isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '',
                'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            ),
            'publish_time' => isset( $_POST['publish_time'] ) ? sanitize_text_field( $_POST['publish_time'] ) : '',
        );

        $queue_id = Shorts_Automator_Pro_Queue_Manager::add_to_queue( $data );

        if ( $queue_id ) {
            wp_send_json_success( array( 'message' => __( '¡Short programado con éxito!', 'shorts-automator-pro' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'No se pudo programar el short.', 'shorts-automator-pro' ) ) );
        }
    }
}
