/**
 * AI Feed Digest Admin JavaScript
 *
 * @package AIFeedDigest
 */

( function( $ ) {
	'use strict';

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		initRangeInputs();
		initPromptEditor();
		initFeedActions();
		initDigestPreview();
		initApiTest();
	} );

	/**
	 * Initialize range inputs to show their value.
	 */
	function initRangeInputs() {
		$( 'input[type="range"]' ).on( 'input', function() {
			$( this ).next( '.afd-range-value' ).text( $( this ).val() );
		} );
	}

	/**
	 * Initialize prompt editor functionality.
	 */
	function initPromptEditor() {
		// Variable insertion.
		$( '.afd-insert-variable' ).on( 'click', function() {
			const variable = $( this ).data( 'variable' );
			const textarea = $( '#afd_prompt_template' );
			const cursorPos = textarea[ 0 ].selectionStart;
			const textBefore = textarea.val().substring( 0, cursorPos );
			const textAfter = textarea.val().substring( cursorPos );

			textarea.val( textBefore + variable + textAfter );
			textarea.focus();
			textarea[ 0 ].selectionStart = cursorPos + variable.length;
			textarea[ 0 ].selectionEnd = cursorPos + variable.length;
		} );

		// Reset to default.
		$( '.afd-reset-prompt' ).on( 'click', function() {
			if ( confirm( afdAdmin.i18n.confirmReset || 'Are you sure you want to reset to the default template?' ) ) {
				$( '#afd_prompt_template' ).val( $( this ).data( 'default' ) );
			}
		} );

		// Test prompt.
		$( '.afd-test-prompt-btn' ).on( 'click', function() {
			const feedId = $( '#afd-test-feed' ).val();
			const nonce = $( this ).data( 'nonce' );
			const $button = $( this );
			const $result = $( '.afd-test-result' );

			if ( ! feedId ) {
				alert( afdAdmin.i18n.selectFeed || 'Please select a feed.' );
				return;
			}

			$button.prop( 'disabled', true ).text( afdAdmin.i18n.testingPrompt );
			$result.hide();

			$.ajax( {
				url: afdAdmin.ajaxUrl,
				method: 'POST',
				data: {
					action: 'afd_test_prompt',
					nonce: nonce,
					feed_id: feedId
				},
				success: function( response ) {
					if ( response.success ) {
						$result.find( '.afd-test-content' ).html( response.data.content );
						$result.show();
					} else {
						alert( response.data.message || 'An error occurred.' );
					}
				},
				error: function() {
					alert( 'An error occurred while testing the prompt.' );
				},
				complete: function() {
					$button.prop( 'disabled', false ).text( 'Test Prompt' );
				}
			} );
		} );
	}

	/**
	 * Initialize feed action buttons.
	 */
	function initFeedActions() {
		// Fetch feed.
		$( document ).on( 'click', '.afd-fetch-feed', function( e ) {
			e.preventDefault();

			const $link = $( this );
			const linkId = $link.data( 'link-id' );
			const originalText = $link.text();

			if ( $link.hasClass( 'afd-loading' ) ) {
				return;
			}

			$link.addClass( 'afd-loading' ).text( afdAdmin.i18n.fetchingFeed );

			$.ajax( {
				url: $link.attr( 'href' ),
				method: 'GET',
				dataType: 'json',
				success: function( response ) {
					if ( response.success ) {
						alert( response.data.message );
						location.reload();
					} else {
						alert( response.data.message || afdAdmin.i18n.fetchError );
					}
				},
				error: function() {
					alert( afdAdmin.i18n.fetchError );
				},
				complete: function() {
					$link.removeClass( 'afd-loading' ).text( originalText );
				}
			} );
		} );

		// Generate digest.
		$( document ).on( 'click', '.afd-generate-digest', function( e ) {
			e.preventDefault();

			const $link = $( this );
			const originalText = $link.text();

			if ( $link.hasClass( 'afd-loading' ) ) {
				return;
			}

			$link.addClass( 'afd-loading' ).text( afdAdmin.i18n.generatingDigest );

			$.ajax( {
				url: $link.attr( 'href' ),
				method: 'GET',
				dataType: 'json',
				success: function( response ) {
					if ( response.success ) {
						alert( response.data.message );
						location.reload();
					} else {
						alert( response.data.message || afdAdmin.i18n.digestError );
					}
				},
				error: function() {
					alert( afdAdmin.i18n.digestError );
				},
				complete: function() {
					$link.removeClass( 'afd-loading' ).text( originalText );
				}
			} );
		} );
	}

	/**
	 * Initialize digest preview page.
	 */
	function initDigestPreview() {
		$( '.afd-resend-digest' ).on( 'click', function() {
			const digestId = $( this ).data( 'digest-id' );
			const nonce = $( this ).data( 'nonce' );
			const $button = $( this );
			const originalText = $button.text();

			if ( $button.hasClass( 'afd-loading' ) ) {
				return;
			}

			if ( ! confirm( 'Are you sure you want to resend this digest email?' ) ) {
				return;
			}

			$button.addClass( 'afd-loading' ).prop( 'disabled', true ).text( 'Sending...' );

			$.ajax( {
				url: afdAdmin.ajaxUrl,
				method: 'POST',
				data: {
					action: 'afd_resend_digest',
					nonce: nonce,
					digest_id: digestId
				},
				success: function( response ) {
					if ( response.success ) {
						alert( response.data.message );
					} else {
						alert( response.data.message || 'An error occurred.' );
					}
				},
				error: function() {
					alert( 'An error occurred while resending the digest.' );
				},
				complete: function() {
					$button.removeClass( 'afd-loading' ).prop( 'disabled', false ).text( originalText );
				}
			} );
		} );
	}

	/**
	 * Initialize API test button.
	 */
	function initApiTest() {
		$( '.afd-test-api' ).on( 'click', function() {
			const $button = $( this );
			const nonce = $button.data( 'nonce' );
			const $result = $button.siblings( '.afd-test-result' );
			const originalText = $button.text();

			$button.prop( 'disabled', true ).text( 'Testing...' );
			$result.removeClass( 'success error' ).text( '' );

			$.ajax( {
				url: afdAdmin.ajaxUrl,
				method: 'POST',
				data: {
					action: 'afd_test_api',
					nonce: nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$result.addClass( 'success' ).text( 'Connection successful!' );
					} else {
						$result.addClass( 'error' ).text( response.data.message || 'Connection failed.' );
					}
				},
				error: function() {
					$result.addClass( 'error' ).text( 'Connection test failed.' );
				},
				complete: function() {
					$button.prop( 'disabled', false ).text( originalText );
				}
			} );
		} );
	}

} )( jQuery );
