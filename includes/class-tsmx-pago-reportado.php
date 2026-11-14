<?php
/**
 * El cliente reporta su transferencia (clave de rastreo) y la tienda la valida en el CEP de Banxico.
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formulario «Ya pagué» y caja en la pantalla del pedido.
 */
final class TSMX_Pago_Reportado {

	const URL_CEP = 'https://www.banxico.org.mx/cep/';

	/**
	 * Registra los ganchos.
	 */
	public static function init() {
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'formulario' ) );
		add_action( 'woocommerce_view_order', array( __CLASS__, 'instrucciones_en_mi_cuenta' ), 5 );
		add_action( 'template_redirect', array( __CLASS__, 'procesar' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'recursos' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'caja_admin' ) );
	}

	/**
	 * Pasarela configurada.
	 *
	 * @return TSMX_Gateway|null
	 */
	private static function pasarela() {
		return TSMX_Gateway::instancia();
	}

	/**
	 * Indica si el pedido es SPEI y sigue pendiente de confirmar.
	 *
	 * @param WC_Order|false $order Pedido.
	 * @return bool
	 */
	private static function pendiente( $order ) {
		return $order instanceof WC_Order
			&& TSMX_Gateway::ID === $order->get_payment_method()
			&& $order->has_status( 'on-hold' );
	}

	/**
	 * Carga estilos y el botón de copiar solo donde hace falta.
	 */
	public static function recursos() {
		if ( ! function_exists( 'is_order_received_page' ) ) {
			return;
		}
		if ( is_order_received_page() || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_style( 'tsmx', TSMX_URL . 'assets/css/tsmx.css', array(), TSMX_VERSION );
			wp_enqueue_script( 'tsmx-copiar', TSMX_URL . 'assets/js/copiar.js', array(), TSMX_VERSION, true );
		}
	}

	/**
	 * En «Mi cuenta → Pedidos» también se muestran los datos para pagar.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public static function instrucciones_en_mi_cuenta( $order_id ) {
		$order    = wc_get_order( $order_id );
		$pasarela = self::pasarela();
		if ( $pasarela && self::pendiente( $order ) && '' === (string) $order->get_meta( '_tsmx_rastreo' ) ) {
			$pasarela->imprimir_instrucciones( $order );
		}
	}

	/**
	 * Formulario para reportar la transferencia.
	 *
	 * @param WC_Order $order   Pedido.
	 * @param bool     $directo Si se llama desde la página de «Pedido recibido».
	 */
	public static function formulario( $order, $directo = false ) {
		if ( ! self::pendiente( $order ) ) {
			return;
		}
		// En «Pedido recibido» lo imprime la pasarela junto a los datos de pago.
		if ( ! $directo && is_order_received_page() ) {
			return;
		}

		// Los temas de bloques no muestran avisos en esta página: se imprimen aquí.
		echo '<div class="tsmx-avisos">';
		wc_print_notices();
		echo '</div>';

		$rastreo = (string) $order->get_meta( '_tsmx_rastreo' );
		if ( '' !== $rastreo ) {
			echo '<div class="tsmx-reportado woocommerce-info">' . esc_html(
				sprintf(
					/* translators: %s: clave de rastreo */
					__( 'We received your payment notice (tracking key %s). We will confirm your order as soon as we verify the transfer.', 'spei-mexico-for-woocommerce' ),
					$rastreo
				)
			) . '</div>';
			return;
		}

		$hoy = wp_date( 'Y-m-d' );
		?>
		<form class="tsmx-formulario" method="post">
			<h2><?php esc_html_e( 'Already made the transfer?', 'spei-mexico-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Enter the tracking key shown on your bank receipt. It helps us confirm your payment faster.', 'spei-mexico-for-woocommerce' ); ?></p>
			<p class="form-row form-row-wide">
				<label for="tsmx_rastreo"><?php esc_html_e( 'Tracking key', 'spei-mexico-for-woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="tsmx_rastreo" name="tsmx_rastreo" maxlength="30" required autocomplete="off" pattern="[A-Za-z0-9]{6,30}" title="<?php esc_attr_e( 'Between 6 and 30 letters or numbers, no spaces.', 'spei-mexico-for-woocommerce' ); ?>">
			</p>
			<p class="form-row form-row-first">
				<label for="tsmx_fecha"><?php esc_html_e( 'Transfer date', 'spei-mexico-for-woocommerce' ); ?> <span class="required">*</span></label>
				<input type="date" class="input-text" id="tsmx_fecha" name="tsmx_fecha" max="<?php echo esc_attr( $hoy ); ?>" value="<?php echo esc_attr( $hoy ); ?>" required>
			</p>
			<p class="form-row form-row-last">
				<label for="tsmx_banco"><?php esc_html_e( 'Bank you paid from', 'spei-mexico-for-woocommerce' ); ?> <span class="required">*</span></label>
				<select id="tsmx_banco" name="tsmx_banco" required>
					<option value=""><?php esc_html_e( 'Choose your bank', 'spei-mexico-for-woocommerce' ); ?></option>
					<?php foreach ( TSMX_Bancos::todas() as $clave_banco => $nombre_banco ) : ?>
						<option value="<?php echo esc_attr( $clave_banco ); ?>"><?php echo esc_html( $nombre_banco ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<input type="hidden" name="tsmx_pedido" value="<?php echo esc_attr( $order->get_id() ); ?>">
			<input type="hidden" name="tsmx_llave" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
			<?php wp_nonce_field( 'tsmx_reportar_' . $order->get_id(), 'tsmx_nonce' ); ?>
			<p class="tsmx-enviar"><button type="submit" class="button wp-element-button"><?php esc_html_e( 'Let the store know I paid', 'spei-mexico-for-woocommerce' ); ?></button></p>
		</form>
		<?php
	}

	/**
	 * Guarda el aviso de pago.
	 */
	public static function procesar() {
		if ( ! isset( $_POST['tsmx_pedido'], $_POST['tsmx_nonce'] ) ) {
			return;
		}
		$order_id = absint( wp_unslash( $_POST['tsmx_pedido'] ) );
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tsmx_nonce'] ) ), 'tsmx_reportar_' . $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$llave = isset( $_POST['tsmx_llave'] ) ? sanitize_text_field( wp_unslash( $_POST['tsmx_llave'] ) ) : '';
		if ( ! self::pendiente( $order ) || ! hash_equals( $order->get_order_key(), $llave ) ) {
			return;
		}
		// Si el pedido es de un cliente con cuenta, solo ese cliente puede reportarlo.
		if ( $order->get_customer_id() && get_current_user_id() !== $order->get_customer_id() ) {
			return;
		}
		if ( '' !== (string) $order->get_meta( '_tsmx_rastreo' ) ) {
			return;
		}

		$rastreo = isset( $_POST['tsmx_rastreo'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['tsmx_rastreo'] ) ) ) : '';
		$fecha   = isset( $_POST['tsmx_fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['tsmx_fecha'] ) ) : '';
		$clave   = isset( $_POST['tsmx_banco'] ) ? sanitize_text_field( wp_unslash( $_POST['tsmx_banco'] ) ) : '';
		$banco   = TSMX_Bancos::nombre( $clave );

		$errores = array();
		if ( ! preg_match( '/^[A-Z0-9]{6,30}$/', $rastreo ) ) {
			$errores[] = __( 'The tracking key must have between 6 and 30 letters or numbers, without spaces.', 'spei-mexico-for-woocommerce' );
		}
		$f = DateTime::createFromFormat( '!Y-m-d', $fecha, wp_timezone() );
		if ( ! $f || $f->format( 'Y-m-d' ) !== $fecha || $fecha > wp_date( 'Y-m-d' ) ) {
			$errores[] = __( 'Check the transfer date.', 'spei-mexico-for-woocommerce' );
		}
		if ( '' === $banco ) {
			$errores[] = __( 'Enter the bank you paid from.', 'spei-mexico-for-woocommerce' );
		}

		if ( $errores ) {
			foreach ( $errores as $error ) {
				wc_add_notice( $error, 'error' );
			}
			return;
		}

		$order->update_meta_data( '_tsmx_rastreo', $rastreo );
		$order->update_meta_data( '_tsmx_fecha', $fecha );
		$order->update_meta_data( '_tsmx_banco_emisor', $banco );
		$order->update_meta_data( '_tsmx_banco_emisor_clave', $clave );
		$order->update_meta_data( '_tsmx_reportado', time() );
		$order->add_order_note(
			sprintf(
				/* translators: 1: clave de rastreo, 2: fecha, 3: banco */
				__( 'The customer reported their SPEI transfer. Tracking key: %1$s · Date: %2$s · Sending bank: %3$s. Verify it in Banxico\'s CEP before marking the order as paid.', 'spei-mexico-for-woocommerce' ),
				$rastreo,
				$fecha,
				$banco
			)
		);
		$order->save();

		self::avisar_tienda( $order, $rastreo, $fecha, $banco );

		wc_add_notice( __( 'Thank you. We received your payment notice and will confirm your order as soon as we verify the transfer.', 'spei-mexico-for-woocommerce' ) );

		// Regresa a la misma página: «Pedido recibido» (sirve a invitados) o «Mi cuenta → Pedido».
		$destino = is_order_received_page() ? $order->get_checkout_order_received_url() : $order->get_view_order_url();
		wp_safe_redirect( $destino );
		exit;
	}

	/**
	 * Correo breve a la tienda.
	 *
	 * @param WC_Order $order   Pedido.
	 * @param string   $rastreo Clave de rastreo.
	 * @param string   $fecha   Fecha.
	 * @param string   $banco   Banco emisor.
	 */
	private static function avisar_tienda( $order, $rastreo, $fecha, $banco ) {
		$para = get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) );
		if ( ! is_email( $para ) ) {
			return;
		}
		$asunto = sprintf(
			/* translators: %s: número de pedido */
			__( 'SPEI payment reported for order %s', 'spei-mexico-for-woocommerce' ),
			$order->get_order_number()
		);
		$cuerpo = implode(
			"\n",
			array(
				$asunto,
				'',
				__( 'Tracking key', 'spei-mexico-for-woocommerce' ) . ': ' . $rastreo,
				__( 'Date', 'spei-mexico-for-woocommerce' ) . ': ' . $fecha,
				__( 'Sending bank', 'spei-mexico-for-woocommerce' ) . ': ' . $banco,
				__( 'Expected amount', 'spei-mexico-for-woocommerce' ) . ': ' . wc_format_decimal( $order->get_total(), 2 ) . ' ' . $order->get_currency(),
				'',
				__( 'Verify the transfer in Banxico\'s CEP:', 'spei-mexico-for-woocommerce' ) . ' ' . self::URL_CEP,
				__( 'Order:', 'spei-mexico-for-woocommerce' ) . ' ' . $order->get_edit_order_url(),
			)
		);
		wp_mail( $para, $asunto, $cuerpo );
	}

	/**
	 * Caja en la pantalla del pedido (compatible con HPOS).
	 */
	public static function caja_admin() {
		$pantalla = 'shop_order';
		if ( class_exists( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )
			&& wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$pantalla = wc_get_page_screen_id( 'shop-order' );
		}
		add_meta_box( 'tsmx-pago', __( 'SPEI transfer', 'spei-mexico-for-woocommerce' ), array( __CLASS__, 'contenido_caja' ), $pantalla, 'side', 'high' );
	}

	/**
	 * Contenido de la caja.
	 *
	 * @param WP_Post|WC_Order $objeto Pedido o entrada.
	 */
	public static function contenido_caja( $objeto ) {
		$order = ( $objeto instanceof WP_Post ) ? wc_get_order( $objeto->ID ) : $objeto;
		if ( ! $order instanceof WC_Order || TSMX_Gateway::ID !== $order->get_payment_method() ) {
			echo '<p>' . esc_html__( 'This order was not paid by SPEI.', 'spei-mexico-for-woocommerce' ) . '</p>';
			return;
		}

		$rastreo = (string) $order->get_meta( '_tsmx_rastreo' );
		$vence   = (int) $order->get_meta( '_tsmx_vence' );

		if ( '' === $rastreo ) {
			echo '<p>' . esc_html__( 'The customer has not reported the payment yet.', 'spei-mexico-for-woocommerce' ) . '</p>';
			if ( $vence > 0 && $order->has_status( 'on-hold' ) ) {
				echo '<p>' . esc_html(
					sprintf(
						/* translators: %s: fecha límite */
						__( 'It will be cancelled automatically on %s if no payment is reported.', 'spei-mexico-for-woocommerce' ),
						TSMX_Gateway::fecha_legible( $vence )
					)
				) . '</p>';
			}
			return;
		}

		$pasarela = self::pasarela();
		$clabe    = $pasarela ? TSMX_Clabe::normalizar( $pasarela->get_option( 'clabe' ) ) : '';
		$datos    = array(
			__( 'Tracking key', 'spei-mexico-for-woocommerce' ) => $rastreo,
			__( 'Date', 'spei-mexico-for-woocommerce' )            => (string) $order->get_meta( '_tsmx_fecha' ),
			__( 'Sending bank', 'spei-mexico-for-woocommerce' )     => (string) $order->get_meta( '_tsmx_banco_emisor' ),
			__( 'Your CLABE', 'spei-mexico-for-woocommerce' )         => TSMX_Clabe::formatear( $clabe ),
			__( 'Amount', 'spei-mexico-for-woocommerce' )            => wc_format_decimal( $order->get_total(), 2 ),
		);
		echo '<ul>';
		foreach ( $datos as $etiqueta => $valor ) {
			echo '<li><strong>' . esc_html( $etiqueta ) . ':</strong> ' . esc_html( $valor ) . '</li>';
		}
		echo '</ul>';
		echo '<p>' . esc_html__( 'Enter these details in Banxico\'s CEP. If the receipt is found, change the order to “Processing”.', 'spei-mexico-for-woocommerce' ) . '</p>';
		echo '<p><a class="button" href="' . esc_url( self::URL_CEP ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Banxico CEP', 'spei-mexico-for-woocommerce' ) . '</a></p>';
	}
}
