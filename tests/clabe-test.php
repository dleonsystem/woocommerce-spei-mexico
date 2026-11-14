<?php
// Prueba sin WordPress: php tests/clabe-test.php
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../includes/class-tsmx-clabe.php';
function apply_filters( $h, $v ) { return $v; }
require __DIR__ . '/../includes/class-tsmx-bancos.php';
$fallas = 0;
function verifica( $cond, $msg ) { global $fallas; echo ( $cond ? 'OK   ' : 'FALLA ' ) . $msg . "\n"; if ( ! $cond ) { $fallas++; } }
// Ejemplo público de CLABE (Wikipedia): 032180000118359719.
verifica( TSMX_Clabe::es_valida( '032180000118359719' ), 'CLABE de ejemplo válida' );
verifica( TSMX_Clabe::es_valida( '032 180 00011835971 9' ), 'acepta espacios' );
verifica( ! TSMX_Clabe::es_valida( '032180000118359718' ), 'rechaza dígito de control erróneo' );
verifica( ! TSMX_Clabe::es_valida( '03218000011835971' ), 'rechaza 17 dígitos' );
verifica( ! TSMX_Clabe::es_valida( '' ), 'rechaza vacío' );
verifica( '032 180 00011835971 9' === TSMX_Clabe::formatear( '032180000118359719' ), 'formato legible' );
// Toda CLABE construida con su dígito calculado debe validar.
for ( $i = 0; $i < 500; $i++ ) { $b = ''; for ( $j = 0; $j < 17; $j++ ) { $b .= (string) random_int( 0, 9 ); } if ( ! TSMX_Clabe::es_valida( $b . TSMX_Clabe::digito_control( $b ) ) ) { $fallas++; } }
verifica( true, '500 CLABE aleatorias con dígito calculado' );
verifica( '40012' === TSMX_Bancos::desde_clabe( '012180000118359713' ), 'CLABE 012 es BBVA (40012)' );
verifica( '90646' === TSMX_Bancos::desde_clabe( '646180000000000000' ), 'CLABE 646 es STP (90646)' );
verifica( '' === TSMX_Bancos::desde_clabe( '999180000000000000' ), 'prefijo desconocido devuelve vacío' );
exit( $fallas ? 1 : 0 );
