<?php
/**
 * Validación y formato de la CLABE interbancaria (18 dígitos).
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Utilidades de CLABE.
 */
final class TSMX_Clabe {

	/**
	 * Deja solo los dígitos.
	 *
	 * @param string $valor Texto capturado.
	 * @return string
	 */
	public static function normalizar( $valor ) {
		return preg_replace( '/\D+/', '', (string) $valor );
	}

	/**
	 * Calcula el dígito de control a partir de los primeros 17 dígitos.
	 * Pesos 3, 7, 1 repetidos; se suma cada producto módulo 10.
	 *
	 * @param string $diecisiete Primeros 17 dígitos.
	 * @return int
	 */
	public static function digito_control( $diecisiete ) {
		$pesos = array( 3, 7, 1 );
		$suma  = 0;
		for ( $i = 0; $i < 17; $i++ ) {
			$suma += ( (int) $diecisiete[ $i ] * $pesos[ $i % 3 ] ) % 10;
		}
		return ( 10 - ( $suma % 10 ) ) % 10;
	}

	/**
	 * Indica si la CLABE tiene 18 dígitos y el dígito de control correcto.
	 *
	 * @param string $clabe CLABE con o sin espacios.
	 * @return bool
	 */
	public static function es_valida( $clabe ) {
		$clabe = self::normalizar( $clabe );
		if ( 18 !== strlen( $clabe ) ) {
			return false;
		}
		return self::digito_control( substr( $clabe, 0, 17 ) ) === (int) $clabe[17];
	}

	/**
	 * Formato legible: banco (3) plaza (3) cuenta (11) control (1).
	 *
	 * @param string $clabe CLABE.
	 * @return string
	 */
	public static function formatear( $clabe ) {
		$clabe = self::normalizar( $clabe );
		if ( 18 !== strlen( $clabe ) ) {
			return $clabe;
		}
		return substr( $clabe, 0, 3 ) . ' ' . substr( $clabe, 3, 3 ) . ' ' . substr( $clabe, 6, 11 ) . ' ' . substr( $clabe, 17, 1 );
	}
}
