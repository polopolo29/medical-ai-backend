<?php
/**
 * Clase para manejar la lógica de los perfiles.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Profile_Manager
 *
 * Se encarga de la lógica de negocio para la gestión de perfiles (CRUD).
 */
class Shorts_Automator_Pro_Profile_Manager {

	/**
	 * Obtiene el nombre de la tabla de perfiles.
	 *
	 * @return string Nombre de la tabla.
	 */
	private static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'shorts_automator_profiles';
	}

	/**
	 * Crea un nuevo perfil en la base de datos.
	 *
	 * @param string $name El nombre del perfil.
	 * @return int|false El ID del nuevo perfil insertado o false en caso de error.
	 */
	public static function create_profile( $name ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return false;
		}

		$name = sanitize_text_field( $name );

		$result = $wpdb->insert(
			self::get_table_name(),
			array(
				'name' => $name,
			),
			array(
				'%s',
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Obtiene todos los perfiles de la base de datos.
	 *
	 * @return array Un array de objetos, donde cada objeto es un perfil.
	 */
	public static function get_profiles() {
		global $wpdb;
		$table_name = self::get_table_name();
		return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
	}

	/**
	 * Elimina un perfil de la base de datos.
	 *
	 * @param int $id El ID del perfil a eliminar.
	 * @return bool True si la eliminación fue exitosa, false en caso contrario.
	 */
	public static function delete_profile( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$result = $wpdb->delete(
			self::get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Actualiza el nombre de un perfil existente.
	 *
	 * @param int    $id   El ID del perfil a actualizar.
	 * @param string $name El nuevo nombre para el perfil.
	 * @return bool True si la actualización fue exitosa, false en caso contrario.
	 */
	public static function update_profile( $id, $name ) {
		global $wpdb;

		$id   = absint( $id );
		$name = sanitize_text_field( $name );

		if ( ! $id || empty( $name ) ) {
			return false;
		}

		$result = $wpdb->update(
			self::get_table_name(),
			array( 'name' => $name ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}
}
