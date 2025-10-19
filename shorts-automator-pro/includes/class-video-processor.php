<?php
/**
 * Clase para procesar y validar los videos subidos.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Video_Processor
 *
 * Se encarga de validar los archivos de video subidos a la biblioteca de medios.
 */
class Shorts_Automator_Pro_Video_Processor {

	/**
	 * Tamaño máximo de archivo permitido en bytes (500MB).
	 */
	const MAX_FILE_SIZE = 500 * 1024 * 1024;

	/**
	 * Formatos de video permitidos.
	 *
	 * @var string[]
	 */
	private static $allowed_formats = array( 'mp4', 'mov', 'avi' );

	/**
	 * Valida un archivo adjunto de la biblioteca de medios de WordPress.
	 *
	 * @param int $attachment_id El ID del archivo adjunto.
	 * @return true|WP_Error True si el video es válido, o un objeto WP_Error en caso de fallo.
	 */
	public static function validate_attachment( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$file_path     = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'El archivo de video no se encontró en el servidor.', 'shorts-automator-pro' ) );
		}

		// Validar formato del archivo
		$file_extension = pathinfo( $file_path, PATHINFO_EXTENSION );
		if ( ! in_array( strtolower( $file_extension ), self::$allowed_formats, true ) ) {
			return new WP_Error( 'invalid_format', __( 'Formato de video no válido. Permitidos: MP4, MOV, AVI.', 'shorts-automator-pro' ) );
		}

		// Validar tamaño del archivo
		if ( filesize( $file_path ) > self::MAX_FILE_SIZE ) {
			return new WP_Error( 'file_too_large', __( 'El video excede el tamaño máximo de 500MB.', 'shorts-automator-pro' ) );
		}

		return true;
	}

	/**
	 * Añade un filtro para permitir la subida de nuestros tipos de video personalizados.
	 */
	public static function add_upload_mime_types_filter() {
		add_filter( 'upload_mimes', array( __CLASS__, 'allow_custom_mime_types' ) );
	}

	/**
	 * Añade los tipos MIME para los formatos de video soportados.
	 *
	 * @param array $mimes Array de tipos MIME existentes.
	 * @return array Array de tipos MIME modificado.
	 */
	public static function allow_custom_mime_types( $mimes ) {
		$mimes['mp4'] = 'video/mp4';
		$mimes['mov'] = 'video/quicktime';
		$mimes['avi'] = 'video/x-msvideo';
		return $mimes;
	}
}
