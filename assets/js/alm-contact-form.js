/**
 * Contact form JavaScript for ALMGR assets
 *
 * Handles AJAX submission and character counter for the "Contact current owner"
 * form on the asset detail page. The collapsible toggle is handled natively
 * by the <details>/<summary> HTML elements and requires no JavaScript.
 *
 * @package AssetLendingManager
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initContactForm();
	} );

	/**
	 * Initialize the contact form: char counter + AJAX submit.
	 */
	function initContactForm() {
		const form = document.getElementById( 'almgr-contact-form' );
		if ( ! form ) {
			return;
		}

		const textarea    = form.querySelector( '#almgr-contact-message' );
		const counter     = form.querySelector( '#almgr-contact-char-count' );
		const responseDiv = document.getElementById( 'almgr-contact-response' );
		const maxLength   = textarea ? parseInt( textarea.getAttribute( 'maxlength' ), 10 ) : 500;

		if ( textarea && counter ) {
			textarea.addEventListener( 'input', function () {
				const used = textarea.value.length;
				counter.textContent = used + ' / ' + maxLength;
			} );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			submitContactForm( form, textarea, responseDiv );
		} );
	}

	/**
	 * Submit the contact form via fetch() and handle the JSON response.
	 *
	 * @param {HTMLFormElement}  form        The contact form element.
	 * @param {HTMLTextAreaElement} textarea The message textarea.
	 * @param {HTMLElement}      responseDiv Element where success/error is shown.
	 */
	function submitContactForm( form, textarea, responseDiv ) {
		const submitBtn = form.querySelector( '[type="submit"]' );
		const counter   = form.querySelector( '#almgr-contact-char-count' );
		const maxLength = textarea ? parseInt( textarea.getAttribute( 'maxlength' ), 10 ) : 500;

		if ( submitBtn ) {
			submitBtn.disabled = true;
		}

		if ( responseDiv ) {
			responseDiv.style.display = 'none';
			responseDiv.textContent   = '';
			responseDiv.className     = 'almgr-response-message';
		}

		const assetInput = form.querySelector( '[name="asset_id"]' );

		const data = new FormData();
		data.append( 'action',   'almgr_send_contact_message' );
		data.append( 'nonce',    window.almgrContact.nonce );
		data.append( 'asset_id', assetInput ? assetInput.value : '0' );
		data.append( 'message',  textarea ? textarea.value : '' );

		fetch( window.almgrContact.ajaxUrl, {
			method:      'POST',
			body:        data,
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( ! responseDiv ) {
					return;
				}
				const msg = ( json.data && json.data.message ) ? json.data.message : '';
				responseDiv.textContent = msg;

				if ( json.success ) {
					responseDiv.classList.add( 'almgr-response-message--success' );
					if ( textarea ) {
						textarea.value = '';
					}
					if ( counter ) {
						counter.textContent = '0 / ' + maxLength;
					}
					if ( submitBtn ) {
						submitBtn.disabled = false;
					}
				} else {
					responseDiv.classList.add( 'almgr-response-message--error' );
					if ( submitBtn ) {
						submitBtn.disabled = false;
					}
				}

				responseDiv.style.display = 'block';
			} )
			.catch( function () {
				if ( responseDiv ) {
					responseDiv.textContent = window.almgrContact.errorMessage;
					responseDiv.classList.add( 'almgr-response-message--error' );
					responseDiv.style.display = 'block';
				}
				if ( submitBtn ) {
					submitBtn.disabled = false;
				}
			} );
	}
} )();
