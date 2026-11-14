( function () {
	'use strict';
	var registro = window.wc && window.wc.wcBlocksRegistry;
	var ajustes = window.wc && window.wc.wcSettings;
	if ( ! registro || ! ajustes ) {
		return;
	}
	var el = window.wp.element.createElement;
	var decodificar = window.wp.htmlEntities.decodeEntities;
	var datos = ajustes.getSetting( 'spei_mx_data', {} );
	var titulo = decodificar( datos.title || 'SPEI transfer' );

	function Contenido() {
		return el( 'div', null, decodificar( datos.description || '' ) );
	}

	registro.registerPaymentMethod( {
		name: 'spei_mx',
		label: el( 'span', null, titulo ),
		ariaLabel: titulo,
		content: el( Contenido ),
		edit: el( Contenido ),
		canMakePayment: function () {
			return true;
		},
		supports: { features: datos.supports || [ 'products' ] }
	} );
} )();
