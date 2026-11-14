( function () {
	'use strict';
	function copiar( texto ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( texto );
		}
		var area = document.createElement( 'textarea' );
		area.value = texto;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'absolute';
		area.style.left = '-9999px';
		document.body.appendChild( area );
		area.select();
		try {
			document.execCommand( 'copy' );
		} finally {
			document.body.removeChild( area );
		}
		return Promise.resolve();
	}
	document.addEventListener( 'click', function ( evento ) {
		var boton = evento.target.closest( '.tsmx-copiar' );
		if ( ! boton ) {
			return;
		}
		var original = boton.textContent;
		copiar( boton.getAttribute( 'data-valor' ) || '' ).then( function () {
			boton.textContent = boton.getAttribute( 'data-copiado' ) || '✓';
			setTimeout( function () {
				boton.textContent = original;
			}, 2000 );
		} );
	} );
} )();
