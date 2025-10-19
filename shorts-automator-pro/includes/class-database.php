<?php
/**
 * Clase para manejar la base de datos del plugin.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Database
 *
 * Se encarga de la creación y gestión de las tablas de la base de datos.
 */
class Shorts_Automator_Pro_Database {

	/**
	 * Crea las tablas personalizadas del plugin en la base de datos.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Tabla de Perfiles
		$table_name_profiles = $wpdb->prefix . 'shorts_automator_profiles';
		$sql_profiles = "CREATE TABLE $table_name_profiles (
			id INT NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_profiles );

		// Tabla de Plataformas
		$table_name_platforms = $wpdb->prefix . 'shorts_automator_platforms';
		$sql_platforms = "CREATE TABLE $table_name_platforms (
			id INT NOT NULL AUTO_INCREMENT,
			profile_id INT NOT NULL,
			platform ENUM('facebook','instagram','tiktok','youtube') NOT NULL,
			app_id VARCHAR(255) NULL,
			app_secret VARCHAR(255) NULL,
			access_token TEXT NULL,
			api_key VARCHAR(255) NULL,
			channel_id VARCHAR(255) NULL,
			status ENUM('connected','disconnected') DEFAULT 'disconnected' NOT NULL,
			connected_at DATETIME NULL,
			PRIMARY KEY  (id),
			FOREIGN KEY (profile_id) REFERENCES {$wpdb->prefix}shorts_automator_profiles(id) ON DELETE CASCADE
		) $charset_collate;";
		dbDelta( $sql_platforms );

		// Tabla de Cola de Publicaciones
		$table_name_queue = $wpdb->prefix . 'shorts_automator_queue';
		$sql_queue = "CREATE TABLE $table_name_queue (
			id INT NOT NULL AUTO_INCREMENT,
			profile_id INT NOT NULL,
			attachment_id BIGINT(20) UNSIGNED NOT NULL,
			video_path VARCHAR(255) NOT NULL,
			platforms TEXT NOT NULL,
			metadata TEXT NOT NULL,
			status ENUM('pending','scheduled','publishing','published','failed') DEFAULT 'pending' NOT NULL,
			publish_time DATETIME NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			FOREIGN KEY (profile_id) REFERENCES {$wpdb->prefix}shorts_automator_profiles(id) ON DELETE CASCADE
		) $charset_collate;";
		dbDelta( $sql_queue );

		// Tabla de Log de Limpieza
		$table_name_cleanup_log = $wpdb->prefix . 'shorts_automator_cleanup_log';
		$sql_cleanup_log = "CREATE TABLE $table_name_cleanup_log (
			id INT NOT NULL AUTO_INCREMENT,
			short_id INT NOT NULL,
			video_path VARCHAR(255) NOT NULL,
			cleaned_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			FOREIGN KEY (short_id) REFERENCES {$wpdb->prefix}shorts_automator_queue(id) ON DELETE CASCADE
		) $charset_collate;";
		dbDelta( $sql_cleanup_log );
	}
}
