<?php
/**
 * Integración con el checkout por bloques de WooCommerce.
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Método SPEI para el checkout por bloques.
 */
final class TSMX_Blocks extends AbstractPaymentMethodType {

	/**
	 * Identificador del método.
	 *
	 * @var string
	 */
	protected $name = 'spei_mx';

	/**
	 * Lee los ajustes.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_spei_mx_settings', array() );
	}

	/**
	 * Activo si está habilitado y bien configurado.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'yes' === $this->get_setting( 'enabled' )
			&& class_exists( 'TSMX_Gateway' )
			&& TSMX_Gateway::configuracion_completa( $this->settings );
	}

	/**
	 * Script que registra el método.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'tsmx-blocks',
			TSMX_URL . 'assets/js/blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
			TSMX_VERSION,
			true
		);
		return array( 'tsmx-blocks' );
	}

	/**
	 * Datos para el script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title', __( 'SPEI transfer', 'spei-mexico-for-woocommerce' ) ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array( 'products' ),
		);
	}
}
