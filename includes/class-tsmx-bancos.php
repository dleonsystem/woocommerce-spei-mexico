<?php
/**
 * Instituciones participantes en SPEI con su clave oficial.
 *
 * Fuente: Banco de México, https://www.banxico.org.mx/cep-scl/listaInstituciones.do (consultada el 9-nov-2026).
 *
 * @package SpeiMexicoForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catálogo de instituciones SPEI.
 */
final class TSMX_Bancos {

	/**
	 * Clave de 5 dígitos => nombre.
	 *
	 * @var array
	 */
	const LISTA = array(
		'40133' => 'ACTINVER',
		'40062' => 'AFIRME',
		'90721' => 'albo',
		'90660' => 'Altor',
		'90706' => 'ARCUS FI',
		'90659' => 'ASP INTEGRA OPC',
		'40127' => 'AZTECA',
		'37166' => 'BaBien',
		'40030' => 'BAJIO',
		'40002' => 'BANAMEX',
		'40154' => 'BANCO COVALTO',
		'40160' => 'BANCO S3',
		'37006' => 'BANCOMEXT',
		'40137' => 'BANCOPPEL',
		'40152' => 'BANCREA',
		'37019' => 'BANJERCITO',
		'40106' => 'BANK OF AMERICA',
		'40159' => 'BANK OF CHINA',
		'40147' => 'BANKAOOL',
		'37009' => 'BANOBRAS',
		'40072' => 'BANORTE',
		'40058' => 'BANREGIO',
		'40060' => 'BANSI',
		'40129' => 'BARCLAYS',
		'40145' => 'BBASE',
		'40012' => 'BBVA MEXICO',
		'40112' => 'BMONEX',
		'90677' => 'CAJA POP MEXICA',
		'90683' => 'CAJA TELEFONIST',
		'90715' => 'CASHI CUENTA',
		'40124' => 'CITI MEXICO',
		'90730' => 'Clip',
		'90901' => 'CLS',
		'90903' => 'CoDi Valida',
		'40130' => 'COMPARTAMOS',
		'40140' => 'CONSUBANCO',
		'90725' => 'COOPDESARROLLO',
		'90652' => 'CREDICAPITAL',
		'90688' => 'CREDICLUB',
		'90680' => 'CRISTOBAL COLON',
		'90723' => 'Cuenca',
		'90729' => 'Dep y Pag Dig',
		'40151' => 'DONDE',
		'90616' => 'FINAMEX',
		'90734' => 'FINCO PAY',
		'90634' => 'FINCOMUN',
		'90738' => 'FINTOC',
		'90699' => 'FONDEADORA',
		'90685' => 'FONDO (FIRA)',
		'90601' => 'GBM',
		'40167' => 'HEY BANCO',
		'37168' => 'HIPOTECARIA FED',
		'40021' => 'HSBC',
		'40155' => 'ICBC',
		'40036' => 'INBURSA',
		'90902' => 'INDEVAL',
		'40150' => 'INMOBILIARIO',
		'40136' => 'INTERCAM BANCO',
		'40059' => 'INVEX',
		'40110' => 'JP MORGAN',
		'40128' => 'KAPITAL',
		'90661' => 'KLAR',
		'90653' => 'KUSPIT',
		'90670' => 'LIBERTAD',
		'90602' => 'MASARI',
		'90722' => 'Mercado Pago W',
		'90720' => 'MexPago',
		'40042' => 'MIFEL',
		'40158' => 'MIZUHO BANK',
		'90600' => 'MONEXCB',
		'40108' => 'MUFG',
		'40132' => 'MULTIVA BANCO',
		'37135' => 'NAFIN',
		'40638' => 'NUBANK',
		'90710' => 'NVIO',
		'40148' => 'PAGATODO',
		'90732' => 'Peibo',
		'90714' => 'PPBALANCEMX',
		'90620' => 'PROFUTURO',
		'40170' => 'REVOLUT BANK',
		'40156' => 'SABADELL',
		'40014' => 'SANTANDER',
		'40044' => 'SCOTIABANK',
		'40157' => 'SHINHAN',
		'90728' => 'SPIN BY OXXO',
		'90646' => 'STP',
		'90703' => 'TESORED',
		'90684' => 'TRANSFER',
		'90727' => 'TRANSFER DIRECT',
		'90631' => 'TRF',
		'40138' => 'UALA',
		'90656' => 'UNAGRA',
		'90617' => 'VALMEX',
		'90605' => 'VALUE',
		'40113' => 'VE POR MAS',
		'40141' => 'VOLKSWAGEN',
	);

	/**
	 * Lista filtrable (por si Banxico agrega instituciones antes de actualizar el plugin).
	 *
	 * @return array
	 */
	public static function todas() {
		return apply_filters( 'tsmx_bancos', self::LISTA );
	}

	/**
	 * Nombre de una institución por su clave.
	 *
	 * @param string $clave Clave de 5 dígitos.
	 * @return string
	 */
	public static function nombre( $clave ) {
		$lista = self::todas();
		return isset( $lista[ $clave ] ) ? $lista[ $clave ] : '';
	}

	/**
	 * Clave de la institución a partir de la CLABE: sus 3 primeros dígitos son los 3 últimos de la clave.
	 *
	 * @param string $clabe CLABE.
	 * @return string Clave de 5 dígitos o cadena vacía.
	 */
	public static function desde_clabe( $clabe ) {
		$prefijo = substr( TSMX_Clabe::normalizar( $clabe ), 0, 3 );
		if ( 3 !== strlen( $prefijo ) ) {
			return '';
		}
		foreach ( array_keys( self::todas() ) as $clave ) {
			if ( substr( (string) $clave, 2 ) === $prefijo ) {
				return (string) $clave;
			}
		}
		return '';
	}
}
