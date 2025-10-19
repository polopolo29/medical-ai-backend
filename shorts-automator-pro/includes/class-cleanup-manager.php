<?php
/**
 * Clase para manejar la limpieza automática de videos.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Cleanup_Manager
 *
 * Gestiona la eliminación de archivos de video después de la publicación.
 */
class Shorts_Automator_Pro_Cleanup_Manager {

	/**
	 * Se engancha a la acción de publicación exitosa para iniciar la limpieza.
	 *
	 * @param int    $short_id   ID del short en la tabla de la cola.
	 * @param string $video_path Ruta del archivo de video (obsoleto, usaremos el ID del adjunto).
	 */
	public static function handle_successful_publication( $short_id, $video_path ) {
		global $wpdb;

		$queue_table = $wpdb->prefix . 'shorts_automator_queue';
		$short = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $queue_table WHERE id = %d", $short_id ) );

		if ( ! $short || 'published' !== $short->status || empty( $short->attachment_id ) ) {
			// Verificación de seguridad: no hacer nada si el short no está publicado o no tiene un ID de adjunto.
			return;
		}

		// Eliminar el adjunto de la biblioteca de medios (y el archivo físico del servidor)
		$deleted = wp_delete_attachment( $short->attachment_id, true );

		if ( $deleted ) {
			// Registrar en el log de limpieza si la eliminación fue exitosa.
			self::log_cleanup( $short_id, $short->video_path );
		}
	}

	/**
	 * Registra la operación de limpieza en la base de datos.
	 *
	 * @param int    $short_id   ID del short en la cola.
	 * @param string $video_path Ruta del archivo que fue eliminado.
	 */
	private static function log_cleanup( $short_id, $video_path ) {
		global $wpdb;
		$log_table = $wpdb->prefix . 'shorts_automator_cleanup_log';

		$wpdb->insert(
			$log_table,
			array(
				'short_id'   => $short_id,
				'video_path' => $video_path,
				'cleaned_at' => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Placeholder para la limpieza diaria de archivos temporales.
	 */
	public static function daily_cleanup_task() {
		// Esta función se implementará en el futuro para limpiar archivos temporales >24h.
		// Por ahora, es un placeholder.
	}
}
