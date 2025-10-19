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
}
