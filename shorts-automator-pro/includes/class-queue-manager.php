<?php
/**
 * Clase para manejar la cola de publicaciones.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Queue_Manager
 *
 * Se encarga de añadir y gestionar los elementos en la cola de publicación.
 */
class Shorts_Automator_Pro_Queue_Manager {

	/**
	 * Obtiene el nombre de la tabla de la cola.
	 *
	 * @return string Nombre de la tabla.
	 */
	private static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'shorts_automator_queue';
	}

	/**
	 * Añade un nuevo video a la cola de publicación.
	 *
	 * @param array $data Los datos del video a programar.
	 * @return int|false El ID del nuevo elemento en la cola o false en caso de error.
	 */
	public static function add_to_queue( $data ) {
		global $wpdb;

		$defaults = array(
			'profile_id'   => 0,
			'video_path'   => '',
			'platforms'    => '[]',
			'metadata'     => '{}',
			'status'       => 'scheduled',
			'publish_time' => '',
		);
		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['profile_id'] ) || empty( $data['video_path'] ) || empty( $data['publish_time'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			self::get_table_name(),
			array(
				'profile_id'   => absint( $data['profile_id'] ),
				'video_path'   => sanitize_text_field( $data['video_path'] ),
				'platforms'    => wp_json_encode( $data['platforms'] ),
				'metadata'     => wp_json_encode( $data['metadata'] ),
				'status'       => $data['status'],
				'publish_time' => $data['publish_time'],
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return $result ? $wpdb->insert_id : false;
	}
}
