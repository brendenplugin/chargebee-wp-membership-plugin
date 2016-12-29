(function( $, wp ) {
	'use strict';

	window.Chargebee_Membership_Admin = {
		// Functions for Document Ready Event
		init: function() {
			this.create_account();
			this.content_restrict_metabox();
			//this.api_key_save();
		},

		// Functions for Window Load Event
		initLoad: function() {

		},

		// Function to create chargebee account without subscription
		create_account: function() {
			$( '.cbm-create-acnt-msg' ).hide();

			// Create chargebee account button click
			$( '.cbm-create-acnt' ).click( function( e ) {
				e.preventDefault();

				// Parameters to create account
				var params = {
					user_id: $( this ).attr( 'user-id' ),
					_cbm_nonce: cbm_create_acnt_nonce
				};

				// Ajax request to create account
				var request = wp.ajax.post( 'cbm_create_customer_acnt', params );

				// Check ajax response
				request.done( function( data ) {
					// Autofill customer id into text field
					$( '#chargebee_user_id' ).val( data.customer_id );

					// Display message of account creation
					$( '.cbm-create-acnt-msg' ).html( data.msg );
					$( '.cbm-create-acnt-msg' ).show().delay( 5000 ).fadeOut();
				}, 'json' );

				request.fail( function( data ) {
					// Display error message
					$( '.cbm-create-acnt-msg' ).html( data.msg );
					$( '.cbm-create-acnt-msg' ).show().delay( 5000 ).fadeOut();
				} );

				request.always( function() {

				} );

				return false;
			});
		},

		// Restrict content metabox hide/show levels on option change event.
		content_restrict_metabox: function() {
			var restrict_option = $( '#cbm-restrict-options' );
			var restrict_levels = $( '#cbm-restrict-level-container' );
			restrict_option.change( function() {
				var restrict_option_val = $( this ).val();
				if ( '3' == restrict_option_val ) {
					restrict_levels.removeClass( 'hidden' );
				} else {
					restrict_levels.addClass( 'hidden' );
				}
			} );
		},

		// WIP
		// api_key_save: function() {
		// 	$( '#cbm_api_key_save' ).click( function ( event ) {
		// 		var key = $( '#cbm_key_present' ).val();
		// 		if( 'yes' === key ){
		// 			var r = confirm("By clicking on OK button all your previous data will be overwritten!");
		// 			if (r == false) {
		// 				return false;
		// 			}
		//
		// 			// Parameters to create account
		// 			var params = {
		// 				api_key: $( '#cbm_site_name' ).val(),
		// 				site_name: $( '#cbm_api_key' ).val()
		// 			};
		//
		// 			// Ajax request to create account
		// 			var request = wp.ajax.post( 'cbm_validate_and_flush_old_data', params );
		//
		// 			// Check ajax response
		// 			request.done( function( data ) {
		//
		// 			}, 'json' );
		//
		// 			request.fail( function( data ) {
		// 				console.log(data.error);
		// 			} );
		//
		// 			request.always( function() {
		//
		// 			} );
		// 		}
		// 		return true;
		// 	} );
		// }
	};

	// Edit product save data.
	$( document ).on( 'submit', 'form#product_edit_form', function( event ) {
		event.preventDefault();
		var val = $('#product_content').val();

		// Parameters for edit product
		var params = {
			product_content: val,
			product_id: $('#product_id').val(),
			_nonce : $( '#cbm_edit_product_nonce').val()
		};

		//Ajax request to edit product
		var request = wp.ajax.post( 'cbm_edit_product', params );

		// Check ajax response
		request.done( function( data ) {
			$('.cbm_edit_messages').show();
			$('.cbm_edit_messages').addClass( 'notice notice-success' );
			$('.cbm_edit_messages p').text("Product edited successfully.");
		}, 'json' );

		request.fail( function( data ) {
			$('.cbm_edit_messages').show();
			$('.cbm_edit_messages').addClass( 'error' );
			$('.cbm_edit_messages p').text(data.errors);
		} );

		request.always( function() {

		} );

		return false;
	});

	// Call init function of window
	$( document ).ready( function() {
		Chargebee_Membership_Admin.init();
	} );

	$( window ).load( function() {
		Chargebee_Membership_Admin.initLoad();
	} );

})( jQuery, window.wp );
