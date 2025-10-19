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

// Si el archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * El código que se ejecuta durante la activación del plugin.
 */
function activate_shorts_automator_pro() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
    Shorts_Automator_Pro_Database::create_tables();
}
register_activation_hook( __FILE__, 'activate_shorts_automator_pro' );

/**
 * El código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_shorts_automator_pro() {
    // El código de desactivación irá aquí.
}
register_deactivation_hook( __FILE__, 'deactivate_shorts_automator_pro' );
