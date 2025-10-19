<?php
/**
 * Clase para manejar la integración con WooCommerce.
 *
 * @package Shorts_Automator_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Shorts_Automator_Pro_WooCommerce_Integration
 *
 * Gestiona el acceso premium basado en compras de WooCommerce.
 */
class Shorts_Automator_Pro_WooCommerce_Integration {

	/**
	 * El SKU del producto que da acceso al plugin.
	 */
	const PRODUCT_SKU = 'SHORTS-AUTOMATOR-PRO';

	/**
	 * El nombre del meta campo que controla el acceso.
	 */
	const META_KEY_HAS_ACCESS = 'shorts_automator_pro_access';

	/**
	 * Verifica si un usuario tiene acceso al plugin.
	 *
	 * @param int|null $user_id ID del usuario. Si es null, usa el usuario actual.
	 * @return bool
	 */
	public static function has_access( $user_id = null ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		// Los administradores siempre tienen acceso para facilitar la configuración.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		return 'true' === get_user_meta( $user_id, self::META_KEY_HAS_ACCESS, true );
	}

	/**
	 * Concede acceso al plugin a un usuario.
	 *
	 * @param int $user_id ID del usuario.
	 */
	public static function grant_access( $user_id ) {
		update_user_meta( $user_id, self::META_KEY_HAS_ACCESS, 'true' );
	}

	/**
	 * Revoca el acceso al plugin a un usuario.
	 *
	 * @param int $user_id ID del usuario.
	 */
	public static function revoke_access( $user_id ) {
		delete_user_meta( $user_id, self::META_KEY_HAS_ACCESS );
	}

	/**
	 * Se activa cuando un pedido se marca como completado.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public static function on_order_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_customer_id();
		if ( ! $user_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && $product->get_sku() === self::PRODUCT_SKU ) {
				self::grant_access( $user_id );
				return; // Salir en cuanto se encuentre el producto.
			}
		}
	}

	/**
	 * Se activa cuando un pedido se reembolsa o cancela.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public static function on_order_revoked( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_customer_id();
		if ( ! $user_id ) {
			return;
		}

		// Como un usuario puede tener varias compras, una lógica más avanzada
		// verificaría si todavía tiene alguna otra compra activa del producto.
		// Por simplicidad, aquí revocamos el acceso directamente.
		self::revoke_access( $user_id );
	}
}
