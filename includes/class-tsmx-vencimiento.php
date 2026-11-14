<?php
/**
 * Cancela pedidos SPEI que no se pagaron a tiempo.
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vencimiento con Action Scheduler (incluido en WooCommerce).
 */
final class TSMX_Vencimiento {

	const HOOK  = 'tsmx_cancelar_si_no_pagado';
	const GRUPO = 'spei-mexico-for-woocommerce';

	/**
	 * Registra el manejador.
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'cancelar_si_no_pagado' ) );
	}

	/**
	 * Programa la revisión del pedido.
	 *
	 * @param int $order_id ID del pedido.
	 * @param int $cuando   Marca de tiempo.
	 */
	public static function programar( $order_id, $cuando ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $cuando, self::HOOK, array( 'order_id' => (int) $order_id ), self::GRUPO );
		}
	}

	/**
	 * Cancela solo si sigue «En espera», es SPEI y el cliente no reportó pago.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public static function cancelar_si_no_pagado( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || TSMX_Gateway::ID !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) ) {
			return;
		}

		if ( '' !== (string) $order->get_meta( '_tsmx_rastreo' ) ) {
			$order->add_order_note( __( 'Not cancelled automatically because the customer reported an SPEI payment. Verify it in Banxico\'s CEP.', 'spei-mexico-for-woocommerce' ) );
			return;
		}

		$order->update_status( 'cancelled', __( 'Cancelled automatically: the SPEI transfer was not received in time.', 'spei-mexico-for-woocommerce' ) );
	}
}
