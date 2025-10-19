<?php
/**
 * Clase para manejar los eventos de cron de WordPress.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_Cron_Manager
 *
 * Gestiona la programación y ejecución de tareas en segundo plano.
 */
class Shorts_Automator_Pro_Cron_Manager {

	/**
	 * Hooks para nuestros eventos de cron.
	 */
	const PUBLISH_CRON_HOOK = 'shorts_automator_cron';
	const CLEANUP_CRON_HOOK = 'shorts_automator_daily_cleanup';

	/**
	 * Programa todos los eventos de cron.
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( self::PUBLISH_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'ten_minutes', self::PUBLISH_CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::CLEANUP_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CLEANUP_CRON_HOOK );
		}
	}

	/**
	 * Elimina todos los eventos de cron programados.
	 */
	public static function unschedule_events() {
		wp_clear_scheduled_hook( self::PUBLISH_CRON_HOOK );
		wp_clear_scheduled_hook( self::CLEANUP_CRON_HOOK );
	}

	/**
	 * Añade un intervalo de tiempo personalizado de 10 minutos.
	 *
	 * @param array $schedules Los intervalos existentes.
	 * @return array Los intervalos con el nuevo añadido.
	 */
	public static function add_custom_cron_interval( $schedules ) {
		$schedules['ten_minutes'] = array(
			'interval' => 600, // 10 minutos en segundos
			'display'  => esc_html__( 'Cada Diez Minutos' ),
		);
		return $schedules;
	}

	/**
	 * Método que se ejecuta con el cron para procesar la cola.
	 */
	public static function process_publication_queue() {
		$pending_shorts = Shorts_Automator_Pro_Queue_Manager::get_pending_shorts();

		foreach ( $pending_shorts as $short ) {
			// Marcar como 'publishing' para evitar procesamiento duplicado
			Shorts_Automator_Pro_Queue_Manager::update_short_status( $short->id, 'publishing' );

			// La lógica de publicación real (simulada) irá en el siguiente paso.
			// Por ahora, simplemente lo marcamos como publicado para probar el flujo.
			$published_successfully = true;

			if ( $published_successfully ) {
				Shorts_Automator_Pro_Queue_Manager::update_short_status( $short->id, 'published' );

				/**
				 * Dispara una acción después de una publicación exitosa.
				 * Es utilizado por el sistema de limpieza.
				 *
				 * @param int    $short->id       ID del short en la cola.
				 * @param string $short->video_path Ruta del archivo de video.
				 */
				do_action( 'shorts_automator_after_publish', $short->id, $short->video_path );

			} else {
				Shorts_Automator_Pro_Queue_Manager::update_short_status( $short->id, 'failed' );
			}
		}
	}
}
