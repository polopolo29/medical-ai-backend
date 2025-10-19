<?php
/**
 * Plugin Name:       Shorts Automator Pro
 * Plugin URI:        https://example.com/
 * Description:       Automatización de publicación de shorts en redes sociales con sistema de limpieza automática.
 * Version:           1.0.0
 * Author:            AI Assistant
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shorts-automator-pro
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SHORTS_AUTOMATOR_PRO_VERSION', '1.0.0' );
define( 'SHORTS_AUTOMATOR_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * El código que se ejecuta durante la activación del plugin.
 */
function activate_shorts_automator_pro() {
	require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-database.php';
	require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-cron-manager.php';
	Shorts_Automator_Pro_Database::create_tables();
	Shorts_Automator_Pro_Cron_Manager::schedule_events();
}
register_activation_hook( __FILE__, 'activate_shorts_automator_pro' );

/**
 * El código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_shorts_automator_pro() {
	require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-cron-manager.php';
	Shorts_Automator_Pro_Cron_Manager::unschedule_events();
}
register_deactivation_hook( __FILE__, 'deactivate_shorts_automator_pro' );


/**
 * Clase principal del plugin.
 */
final class Shorts_Automator_Pro_Core {

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Shorts_Automator_Pro_Core ) ) {
			self::$instance = new Shorts_Automator_Pro_Core();
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->init();
		}
		return self::$instance;
	}

	private function setup_constants() {
		// Constantes del plugin.
	}

	private function includes() {
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-admin.php';
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-profile-manager.php';
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-platform-manager.php';
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-video-processor.php';
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-queue-manager.php';
		require_once SHORTS_AUTOMATOR_PRO_PLUGIN_DIR . 'includes/class-cron-manager.php';
	}

	private function init() {
		if ( is_admin() ) {
			new Shorts_Automator_Pro_Admin();
		}
		Shorts_Automator_Pro_Video_Processor::add_upload_mime_types_filter();

		add_filter( 'cron_schedules', array( 'Shorts_Automator_Pro_Cron_Manager', 'add_custom_cron_interval' ) );
		add_action( Shorts_Automator_Pro_Cron_Manager::CRON_HOOK, array( 'Shorts_Automator_Pro_Cron_Manager', 'process_publication_queue' ) );
	}
}

/**
 * Función para inicializar el plugin.
 */
function shorts_automator_pro() {
	return Shorts_Automator_Pro_Core::instance();
}

// Iniciar el plugin.
shorts_automator_pro();
