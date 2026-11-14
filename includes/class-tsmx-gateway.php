<?php
/**
 * Método de pago: transferencia SPEI a una CLABE.
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pasarela manual de SPEI.
 */
class TSMX_Gateway extends WC_Payment_Gateway {

	const ID = 'spei_mx';

	/**
	 * Configura el método.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->method_title       = __( 'SPEI transfer', 'spei-mexico-for-woocommerce' );
		$this->method_description = __( 'The customer pays by SPEI transfer to your CLABE using the order number as reference. The order stays “On hold” until you confirm the deposit and is cancelled automatically if it is not paid in time.', 'spei-mexico-for-woocommerce' );
		$this->has_fields         = false;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Ganchos que no dependen de que WooCommerce haya cargado las pasarelas en esa petición
	 * (al volver a abrir «Pedido recibido» o al reenviar un correo desde el administrador).
	 */
	public static function registrar_ganchos() {
		add_action(
			'woocommerce_thankyou_' . self::ID,
			function ( $order_id ) {
				$pasarela = self::instancia();
				if ( $pasarela ) {
					$pasarela->pagina_gracias( $order_id );
				}
			}
		);
		add_action(
			'woocommerce_email_before_order_table',
			function ( $order, $sent_to_admin, $plain_text = false ) {
				$pasarela = self::instancia();
				if ( $pasarela && $order instanceof WC_Order ) {
					$pasarela->instrucciones_correo( $order, $sent_to_admin, $plain_text );
				}
			},
			10,
			3
		);
	}

	/**
	 * Instancia configurada de esta pasarela.
	 *
	 * @return TSMX_Gateway|null
	 */
	public static function instancia() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return null;
		}
		$todas = WC()->payment_gateways()->payment_gateways();
		return isset( $todas[ self::ID ] ) ? $todas[ self::ID ] : null;
	}

	/**
	 * Campos de ajustes.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable', 'spei-mexico-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Accept SPEI bank transfer payments', 'spei-mexico-for-woocommerce' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'   => __( 'Checkout title', 'spei-mexico-for-woocommerce' ),
				'type'    => 'text',
				'default' => __( 'SPEI transfer', 'spei-mexico-for-woocommerce' ),
			),
			'description'  => array(
				'title'   => __( 'Checkout description', 'spei-mexico-for-woocommerce' ),
				'type'    => 'textarea',
				'default' => __( 'After placing your order you will see the CLABE and the reference to transfer from your banking app. We ship your order as soon as we confirm the deposit.', 'spei-mexico-for-woocommerce' ),
			),
			'banco'        => array(
				'title'       => __( 'Bank', 'spei-mexico-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'For example: BBVA, Banorte, Santander. If empty, it is taken from your CLABE.', 'spei-mexico-for-woocommerce' ),
				'default'     => '',
			),
			'beneficiario' => array(
				'title'       => __( 'Beneficiary', 'spei-mexico-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Name or business name of the account holder, exactly as the bank shows it.', 'spei-mexico-for-woocommerce' ),
				'default'     => '',
			),
			'clabe'        => array(
				'title'       => __( 'CLABE (18 digits)', 'spei-mexico-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The check digit is validated to prevent typing mistakes.', 'spei-mexico-for-woocommerce' ),
				'default'     => '',
			),
			'rfc'          => array(
				'title'       => __( 'RFC (optional)', 'spei-mexico-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Some banks ask for it when the customer adds your account as a recipient.', 'spei-mexico-for-woocommerce' ),
				'default'     => '',
			),
			'horas_limite' => array(
				'title'             => __( 'Hours to pay', 'spei-mexico-for-woocommerce' ),
				'type'              => 'number',
				'description'       => __( 'If no payment is reported within this time, the order is cancelled and stock is released. 0 = never cancel.', 'spei-mexico-for-woocommerce' ),
				'default'           => '48',
				'custom_attributes' => array(
					'min'  => '0',
					'max'  => '720',
					'step' => '1',
				),
			),
			'instrucciones' => array(
				'title'       => __( 'Additional instructions (optional)', 'spei-mexico-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on the order received page and in the order email.', 'spei-mexico-for-woocommerce' ),
				'default'     => '',
			),
		);
		if ( ! class_exists( 'TSMXP_Lotes' ) ) {
			$this->form_fields['tsmx_pro'] = array(
				'title'       => __( 'Many SPEI orders?', 'spei-mexico-for-woocommerce' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: URL of the SPEI Mexico Pro page. */
					__( 'SPEI Mexico Pro validates the reported payments in batch with Banxico’s CEP and marks the confirmed orders as paid. <a href="%s" target="_blank" rel="noopener noreferrer">Learn more</a>', 'spei-mexico-for-woocommerce' ),
					esc_url( 'https://www.lionsystems.com.mx/plugins/spei-woocommerce?utm_source=plugin&utm_medium=ajustes&utm_campaign=spei-pro' )
				),
			);
		}
	}

	/**
	 * Valida la CLABE al guardar ajustes.
	 *
	 * @param string $key   Clave del campo.
	 * @param string $value Valor capturado.
	 * @return string
	 */
	public function validate_clabe_field( $key, $value ) {
		$clabe = TSMX_Clabe::normalizar( $value );
		if ( '' !== $clabe && ! TSMX_Clabe::es_valida( $clabe ) ) {
			WC_Admin_Settings::add_error( __( 'The CLABE is not valid: check that it has exactly 18 digits. The CLABE change was not saved.', 'spei-mexico-for-woocommerce' ) );
			return $this->get_option( 'clabe' );
		}
		return $clabe;
	}

	/**
	 * Valida las horas límite.
	 *
	 * @param string $key   Clave del campo.
	 * @param string $value Valor capturado.
	 * @return string
	 */
	public function validate_horas_limite_field( $key, $value ) {
		return (string) min( 720, max( 0, absint( $value ) ) );
	}

	/**
	 * Solo se ofrece con pesos mexicanos y con CLABE válida.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! parent::is_available() ) {
			return false;
		}
		return self::configuracion_completa( $this->settings );
	}

	/**
	 * Revisa moneda, CLABE y beneficiario.
	 *
	 * @param array $ajustes Ajustes guardados.
	 * @return bool
	 */
	public static function configuracion_completa( $ajustes ) {
		return 'MXN' === get_woocommerce_currency()
			&& ! empty( $ajustes['clabe'] )
			&& TSMX_Clabe::es_valida( $ajustes['clabe'] )
			&& ! empty( $ajustes['beneficiario'] );
	}

	/**
	 * Deja el pedido «En espera» y programa la cancelación automática.
	 *
	 * @param int $order_id ID del pedido.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$horas = absint( $this->get_option( 'horas_limite', 48 ) );

		if ( $horas > 0 ) {
			$vence = time() + ( $horas * HOUR_IN_SECONDS );
			$order->update_meta_data( '_tsmx_vence', $vence );
			$order->save();
			TSMX_Vencimiento::programar( $order->get_id(), $vence );
		}

		// Al pasar a «En espera» WooCommerce aparta el inventario.
		$order->update_status( 'on-hold', __( 'Awaiting SPEI transfer.', 'spei-mexico-for-woocommerce' ) );

		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Datos que el cliente necesita para transferir.
	 *
	 * @param WC_Order $order Pedido.
	 * @return array
	 */
	public function datos_pago( $order ) {
		$numero = $order->get_order_number();
		$datos  = array(
			'banco'        => '' !== trim( (string) $this->get_option( 'banco' ) ) ? $this->get_option( 'banco' ) : TSMX_Bancos::nombre( TSMX_Bancos::desde_clabe( $this->get_option( 'clabe' ) ) ),
			'banco_clave'  => TSMX_Bancos::desde_clabe( $this->get_option( 'clabe' ) ),
			'beneficiario' => $this->get_option( 'beneficiario' ),
			'clabe'        => TSMX_Clabe::normalizar( $this->get_option( 'clabe' ) ),
			'rfc'          => $this->get_option( 'rfc' ),
			'monto'        => $order->get_total(),
			'concepto'     => sprintf(
				/* translators: %s: número de pedido */
				__( 'Order %s', 'spei-mexico-for-woocommerce' ),
				$numero
			),
			'referencia'   => '',
			'vence'        => (int) $order->get_meta( '_tsmx_vence' ),
		);

		// La referencia numérica de SPEI admite hasta 7 dígitos.
		if ( ctype_digit( (string) $numero ) && strlen( (string) $numero ) <= 7 ) {
			$datos['referencia'] = (string) $numero;
		}

		return $datos;
	}

	/**
	 * Fecha límite legible en la zona horaria del sitio.
	 *
	 * @param int $timestamp Marca de tiempo.
	 * @return string
	 */
	public static function fecha_legible( $timestamp ) {
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Página de «Pedido recibido».
	 *
	 * @param int $order_id ID del pedido.
	 */
	public function pagina_gracias( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->has_status( 'on-hold' ) ) {
			return;
		}
		// Datos y formulario juntos, en ese orden, en temas clásicos y de bloques.
		if ( '' === (string) $order->get_meta( '_tsmx_rastreo' ) ) {
			$this->imprimir_instrucciones( $order );
		}
		TSMX_Pago_Reportado::formulario( $order, true );
	}

	/**
	 * Bloque HTML con los datos para transferir.
	 *
	 * @param WC_Order $order Pedido.
	 */
	public function imprimir_instrucciones( $order ) {
		$d     = $this->datos_pago( $order );
		$filas = array(
			array( __( 'Bank', 'spei-mexico-for-woocommerce' ), $d['banco'], '' ),
			array( __( 'Beneficiary', 'spei-mexico-for-woocommerce' ), $d['beneficiario'], $d['beneficiario'] ),
			array( __( 'CLABE', 'spei-mexico-for-woocommerce' ), TSMX_Clabe::formatear( $d['clabe'] ), $d['clabe'] ),
			array( __( 'Exact amount', 'spei-mexico-for-woocommerce' ), wc_price( $d['monto'], array( 'currency' => $order->get_currency() ) ), wc_format_decimal( $d['monto'], 2 ) ),
			array( __( 'Payment concept', 'spei-mexico-for-woocommerce' ), $d['concepto'], $d['concepto'] ),
		);
		if ( '' !== $d['referencia'] ) {
			$filas[] = array( __( 'Numeric reference', 'spei-mexico-for-woocommerce' ), $d['referencia'], $d['referencia'] );
		}
		if ( '' !== $d['rfc'] ) {
			$filas[] = array( __( 'RFC', 'spei-mexico-for-woocommerce' ), $d['rfc'], '' );
		}

		echo '<section class="tsmx-instrucciones">';
		echo '<h2>' . esc_html__( 'SPEI transfer details', 'spei-mexico-for-woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'From your banking app, transfer the exact amount to this CLABE and type the payment concept exactly as shown. That is how we identify your payment.', 'spei-mexico-for-woocommerce' ) . '</p>';
		echo '<table class="tsmx-datos"><tbody>';
		foreach ( $filas as $fila ) {
			if ( '' === (string) $fila[1] ) {
				continue;
			}
			echo '<tr><th scope="row">' . esc_html( $fila[0] ) . '</th><td>' . wp_kses_post( $fila[1] );
			if ( '' !== $fila[2] ) {
				echo ' <button type="button" class="button tsmx-copiar" data-valor="' . esc_attr( $fila[2] ) . '" data-copiado="' . esc_attr__( 'Copied', 'spei-mexico-for-woocommerce' ) . '">' . esc_html__( 'Copy', 'spei-mexico-for-woocommerce' ) . '</button>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		if ( $d['vence'] > 0 ) {
			echo '<p class="tsmx-vence"><strong>' . esc_html(
				sprintf(
					/* translators: %s: fecha y hora límite */
					__( 'Pay before %s. After that the order is cancelled automatically.', 'spei-mexico-for-woocommerce' ),
					self::fecha_legible( $d['vence'] )
				)
			) . '</strong></p>';
		}

		$extra = $this->get_option( 'instrucciones' );
		if ( '' !== trim( (string) $extra ) ) {
			echo wp_kses_post( wpautop( wptexturize( $extra ) ) );
		}
		echo '</section>';
	}

	/**
	 * Instrucciones en el correo de «Pedido en espera».
	 *
	 * @param WC_Order $order         Pedido.
	 * @param bool     $sent_to_admin Si el correo va al administrador.
	 * @param bool     $plain_text    Si el correo es de texto plano.
	 */
	public function instrucciones_correo( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || self::ID !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) ) {
			return;
		}
		$d = $this->datos_pago( $order );

		$lineas = array(
			__( 'SPEI transfer details', 'spei-mexico-for-woocommerce' ),
			__( 'Bank', 'spei-mexico-for-woocommerce' ) . ': ' . $d['banco'],
			__( 'Beneficiary', 'spei-mexico-for-woocommerce' ) . ': ' . $d['beneficiario'],
			__( 'CLABE', 'spei-mexico-for-woocommerce' ) . ': ' . TSMX_Clabe::formatear( $d['clabe'] ),
			__( 'Exact amount', 'spei-mexico-for-woocommerce' ) . ': ' . html_entity_decode( wp_strip_all_tags( wc_price( $d['monto'], array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ),
			__( 'Payment concept', 'spei-mexico-for-woocommerce' ) . ': ' . $d['concepto'],
		);
		if ( '' !== $d['referencia'] ) {
			$lineas[] = __( 'Numeric reference', 'spei-mexico-for-woocommerce' ) . ': ' . $d['referencia'];
		}
		if ( $d['vence'] > 0 ) {
			$lineas[] = sprintf(
				/* translators: %s: fecha y hora límite */
				__( 'Pay before %s or the order will be cancelled.', 'spei-mexico-for-woocommerce' ),
				self::fecha_legible( $d['vence'] )
			);
		}
		$aviso = __( 'After paying, open your order and enter the transfer tracking key so we can confirm it faster.', 'spei-mexico-for-woocommerce' );
		$url   = $order->get_checkout_order_received_url();

		if ( $plain_text ) {
			echo esc_html( implode( "\n", $lineas ) ) . "\n" . esc_html( $aviso ) . "\n" . esc_url_raw( $url ) . "\n\n";
			return;
		}

		$titulo = array_shift( $lineas );
		echo '<h2>' . esc_html( $titulo ) . '</h2><p>';
		echo implode( '<br>', array_map( 'esc_html', $lineas ) );
		echo '</p><p>' . esc_html( $aviso ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Report my payment', 'spei-mexico-for-woocommerce' ) . '</a></p>';
	}
}
