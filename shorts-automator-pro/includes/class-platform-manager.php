<?php
/**
 * Clase para manejar la lógica de las conexiones a plataformas.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Platform_Manager
 *
 * Se encarga de la lógica de negocio para la gestión de conexiones de plataformas (CRUD).
 */
class Shorts_Automator_Pro_Platform_Manager {

	/**
	 * Obtiene el nombre de la tabla de plataformas.
	 *
	 * @return string Nombre de la tabla.
	 */
	private static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'shorts_automator_platforms';
	}

	/**
	 * Obtiene las conexiones de un perfil específico.
	 *
	 * @param int $profile_id El ID del perfil.
	 * @return array Un array asociativo con las plataformas como clave.
	 */
	public static function get_platform_connections( $profile_id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$profile_id = absint( $profile_id );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE profile_id = %d",
				$profile_id
			)
		);

		$connections = array();
		foreach ( $results as $row ) {
			$connections[ $row->platform ] = $row;
		}
		return $connections;
	}

	/**
	 * Actualiza o crea una conexión para una plataforma.
	 *
	 * @param int    $profile_id  El ID del perfil.
	 * @param string $platform    El nombre de la plataforma (e.g., 'facebook').
	 * @param array  $credentials Un array con las credenciales a guardar.
	 * @return bool True si la operación fue exitosa, false en caso contrario.
	 */
	public static function update_connection( $profile_id, $platform, $credentials ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$profile_id = absint( $profile_id );
		$platform   = sanitize_key( $platform );

		if ( ! $profile_id || empty( $platform ) || ! is_array( $credentials ) ) {
			return false;
		}

		// Sanitizar todas las credenciales antes de usarlas.
		$sanitized_credentials = array_map( 'sanitize_text_field', $credentials );

		// Buscar si ya existe una conexión para este perfil y plataforma.
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE profile_id = %d AND platform = %s",
				$profile_id,
				$platform
			)
		);

		$data = array_merge(
			$sanitized_credentials,
			array(
				'profile_id' => $profile_id,
				'platform'   => $platform,
				'status'     => 'connected',
				'connected_at' => current_time( 'mysql' ),
			)
		);

		if ( $existing ) {
			// Actualizar la conexión existente.
			$result = $wpdb->update( $table_name, $data, array( 'id' => $existing->id ) );
		} else {
			// Insertar una nueva conexión.
			$result = $wpdb->insert( $table_name, $data );
		}

		return $result !== false;
	}

	/**
	 * Elimina la conexión de una plataforma para un perfil.
	 *
	 * @param int    $profile_id El ID del perfil.
	 * @param string $platform   El nombre de la plataforma.
	 * @return bool True si la eliminación fue exitosa, false en caso contrario.
	 */
	public static function delete_connection( $profile_id, $platform ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$profile_id = absint( $profile_id );
		$platform   = sanitize_key( $platform );

		if ( ! $profile_id || empty( $platform ) ) {
			return false;
		}

		$result = $wpdb->delete(
			$table_name,
			array(
				'profile_id' => $profile_id,
				'platform'   => $platform,
			),
			array(
				'%d',
				'%s',
			)
		);

		return $result !== false;
	}
}
