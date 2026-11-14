<?php
/**
 * Plugin Name:          SPEI Mexico for WooCommerce
 * Plugin URI:           https://www.lionsystems.com.mx/
 * Description:          Accept SPEI bank transfers to your Mexican CLABE: clear instructions with copy buttons, order-number reference, automatic cancellation of unpaid orders and tracking-key reports to verify in Banxico's CEP.
 * Version:              0.1.1
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Lion Systems
 * Author URI:           https://www.lionsystems.com.mx/
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          spei-mexico-for-woocommerce
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'TSMX_VERSION', '0.1.1' );
define( 'TSMX_FILE', __FILE__ );
define( 'TSMX_DIR', plugin_dir_path( __FILE__ ) );
define( 'TSMX_URL', plugin_dir_url( __FILE__ ) );

require_once TSMX_DIR . 'includes/class-tsmx-clabe.php';
require_once TSMX_DIR . 'includes/class-tsmx-bancos.php';

/**
 * Carga la traducción incluida (es_MX y es_ES) mientras translate.wordpress.org publica la suya.
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'spei-mexico-for-woocommerce', false, dirname( plugin_basename( TSMX_FILE ) ) . '/languages' );
	}
);

/**
 * Declara compatibilidad con pedidos en tablas propias (HPOS) y con el checkout por bloques.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', TSMX_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', TSMX_FILE, true );
		}
	}
);

/**
 * Carga el método de pago cuando WooCommerce está activo.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		require_once TSMX_DIR . 'includes/class-tsmx-gateway.php';
		require_once TSMX_DIR . 'includes/class-tsmx-vencimiento.php';
		require_once TSMX_DIR . 'includes/class-tsmx-pago-reportado.php';

		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				$gateways[] = 'TSMX_Gateway';
				return $gateways;
			}
		);

		TSMX_Gateway::registrar_ganchos();
		TSMX_Vencimiento::init();
		TSMX_Pago_Reportado::init();
	}
);

/**
 * Registra el método en el checkout por bloques.
 */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		if ( ! class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
			return;
		}
		require_once TSMX_DIR . 'includes/class-tsmx-blocks.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( $registry ) {
				$registry->register( new TSMX_Blocks() );
			}
		);
	}
);

/**
 * Enlace directo a los ajustes desde la lista de plugins.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( TSMX_FILE ),
	function ( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=spei_mx' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'spei-mexico-for-woocommerce' ) . '</a>' );
		return $links;
	}
);
