(function( $, wp ) {
	'use strict';

	window.Chargebee_Membership = {

		// Functions for Document Ready Event
		init: function() {
			this.register_account();
			this.subscribe_product();
			this.checkout_product();
			this.reactivate_product();
			this.validate_login_form();
			this.validate_registration_form();
			this.user_nofication_dialog();
		},

		// Functions for Window Load Event
		initLoad: function() {
			// Slide down notification dialog box and show the message.
			$( '.cbm-user-notify-dialog' ).slideDown( "slow" );
		},

		// Validation of login form.
		validate_login_form: function () {
			var _this = this;
			$( '#cbm_loginform' ).on( 'submit', function () {
				var error_class = $( '.cbm_errors' );
				var error_class_span = $( '.cbm_errors span.error' );
				$( error_class ).css( 'display', 'none' );

				// Get values of fields into variables
				var username = $( '#cbm_user_login' ).val();
				var password = $( '#cbm_user_pass' ).val();
				var err_msg  = '';
				// Empty error check for user name
				if ( '' === username ) {
					$( error_class ).css( 'display', 'block' );
					err_msg = '';
					if ( 'string' === typeof cbm_validation_msg.error_label ) {
						err_msg += cbm_validation_msg.error_label + ' ';
					}
					if ( 'string' === typeof cbm_validation_msg.empty_username ) {
						err_msg += cbm_validation_msg.empty_username;
					}
					$( error_class_span ).html( err_msg );
					_this.scrollToError();
					return false;
				}else if ( '' === password ) {
					// Empty check for email
					$( error_class ).css( 'display', 'block' );
					err_msg = '';
					if ( 'string' === typeof cbm_validation_msg.error_label ) {
						err_msg += cbm_validation_msg.error_label + ' ';
					}
					if ( 'string' === typeof cbm_validation_msg.empty_password ) {
						err_msg += cbm_validation_msg.empty_password;
					}
					$( error_class_span ).html( err_msg );
					_this.scrollToError();
					return false;
				}
				return true;
			})
		},

		// Auto hide and show on yes and no radio button click
		register_account: function() {
			$( '#cbm_customer_id' ).hide();
			$( '#cbm_description' ).hide();

			$( '#cbm_registration_form input[name=cbm_account]' ).change(function( ) {
				var value = $( 'input[name=cbm_account]:checked', '#cbm_registration_form' ).val();
				if ( 'yes' === value ) {
					$( '#cbm_customer_id' ).show();
					$( '#cbm_description' ).hide();
				}else {
					$( '#cbm_customer_id' ).hide();
					$( '#cbm_description' ).show();
				}
			});
		},

		//Scroll to top while error
		scrollToError: function() {
			var cbm_errors = $( '.cbm_errors' );
			if ( cbm_errors.length > 0 ) {
				var cbm_errors_offset = cbm_errors.offset();
				$( 'html,body' ).animate( {
					                          scrollTop: cbm_errors_offset.top
				                          }, 'slow' );
			}
		},

		// Function to subscribe product.
		subscribe_product: function() {
			var error_class = $( '.cbm_errors' );
			var error_class_span = $( '.cbm_errors span.error' );
			var _this = this;
			
			$( '#cbm-cancel-redirect-payment-method' ).click(function(){
				$( '#cbm-user-payment-popup-container' ).hide();
			});

			$("[data-cb-btn='cbm_subscribe_product']").click(function(){
				// Parameters for ajax call for subscription
				var current_url = window.location.href;
                                var refUrl = $(this).attr('data-cb-reference-url');            
				var product_nonce = $(this).attr('data-cb-subscribe-product-nonce');                                
                                if(!refUrl || !product_nonce){
                                    refUrl = current_url;
                                }
				var params = {
					product_id:   $(this).attr('data-cb-product-id'),
                                        product_nonce:product_nonce,
					reference_url:  refUrl
				};
				// Ajax request for subscription of product.
				var request = wp.ajax.post( 'cbm_subscribe_product', params );

				request.done( function( data ) {
                                        console.log(data);
					var hosted_page_url = '';
					if ( 'undefined' != typeof data.payment_hosted_page_url && '' != data.payment_hosted_page_url ) {
						$( '#cbm-user-payment-popup-container' ).show();
						$( '#cbm-confirm-redirect-payment-method' ).click(function(){
							hosted_page_url = data.payment_hosted_page_url;
							var win = window.open( hosted_page_url, '_blank' );
							win.focus();
							$( '#cbm-user-payment-popup-container' ).hide();
						});
					} else if ( 'undefined' != typeof data.subscribe_product_url && '' != data.subscribe_product_url ) {
						hosted_page_url = data.subscribe_product_url;
						window.location.replace( hosted_page_url );
					}
				}, 'json' );

				request.fail( function( data ) {
                                        console.log(data);
					var err_msg = '';
					if ( 'string' === typeof cbm_validation_msg.error_label ) {
						err_msg += cbm_validation_msg.error_label + ' ';
					}

					if( 'not_logged_in' === data.error ) {
						window.location.href = data.url;
					} else if( '' !== data.error ) {
                                               if($( error_class ).length){
                                                    $( error_class ).css( 'display', 'block' );
                                                    $( error_class_span ).html( err_msg + data.error );
                                                     _this.scrollToError();
                                                 }else{//If the error div is not present.
                                                     alert(data.error);
                                                 }
					}
				} );
			});
		},

		// Checkout product to proceed to payment for selected subscription.
		checkout_product: function () {
			var error_class = $( '.cbm_errors' );
			var error_class_span = $( '.cbm_errors span.error' );
			var _this = this;

			$( '#cbm_subscribe_product_checkout' ).click(function(){

				var params = {
					product_id:     $('#cbm_product_id').val(),
					product_nonce:  $('#cbm_checkout_product_nonce').val(),
				};

				var checkout = wp.ajax.post( 'cbm_checkout_product', params );
				checkout.done( function ( data ) {
					if ( 'undefined' != typeof data.redirect_page_url && '' != data.redirect_page_url ) {
						window.location.href = data.redirect_page_url;
					}
				}, 'json');

				checkout.fail( function ( data ) {
					var err_msg = '';
					var validation_msg = '';
					if ( 'string' === typeof cbm_validation_msg.error_label ) {
						err_msg += cbm_validation_msg.error_label + ' ';
					}
					if ( 'undefined' != typeof data.error && '' != data.error ) {
						// Display error messages if any.
						if ( 'duplicate_entry' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.existing_subscription ) {
								validation_msg = err_msg + cbm_validation_msg.existing_subscription;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'resource_not_found' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.not_exist_product ) {
								validation_msg = err_msg + cbm_validation_msg.not_exist_product;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'payment_processing_failed' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.payment_failed ) {
								validation_msg = err_msg + cbm_validation_msg.payment_failed;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'not_logged_in' === data.error ) {
							window.location.href = data.url;
						} else if ( '' !== data.error ) {
                                                        
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( err_msg + data.error );
							_this.scrollToError();
						}
					}
				});
			});
		},

		// Checkout product to proceed to payment for selected subscription.
		reactivate_product: function () {
			var error_class = $( '.cbm_errors' );
			var error_class_span = $( '.cbm_errors span.error' );
			var _this = this;

			$( '#cbm_product_reactivate_checkout' ).click(function(){

				var params = {
					product_id: $('#cbm_product_id').val(),
					reactivate_nonce: $('#cbm_product_reactivate_nonce').val(),
					subscription_id: $('#cbm_subscription_id').val(),
				};

				var reactivate_checkout = wp.ajax.post( 'cbm_reactivate_product', params );
				reactivate_checkout.done( function ( data ) {
					if ( 'undefined' != typeof data.redirect_page_url && '' != data.redirect_page_url ) {
						window.location.href = data.redirect_page_url;
					}
				}, 'json');

				reactivate_checkout.fail( function ( data ) {
					var err_msg = '';
					var validation_msg = '';
					if ( 'string' === typeof cbm_validation_msg.error_label ) {
						err_msg += cbm_validation_msg.error_label + ' ';
					}
					if ( 'undefined' != typeof data.error && '' != data.error ) {
						// Display error messages if any.
						if ( 'duplicate_entry' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.existing_subscription ) {
								validation_msg = err_msg + cbm_validation_msg.existing_subscription;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'resource_not_found' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.not_exist_product ) {
								validation_msg = err_msg + cbm_validation_msg.not_exist_product;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'payment_processing_failed' === data.error ) {
							if ( 'string' === typeof cbm_validation_msg.payment_failed ) {
								validation_msg = err_msg + cbm_validation_msg.payment_failed;
							}
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( validation_msg );
							_this.scrollToError();
						} else if ( 'not_logged_in' === data.error ) {
							window.location.href = data.url;
						} else if ( '' !== data.error ) {
							$( error_class ).css( 'display', 'block' );
							$( error_class_span ).html( err_msg + data.error );
							_this.scrollToError();
						}
					}
				});
			});
		},

		// Validation of registration form.
		validate_registration_form : function() {
			var _this = this;
			$('#cbm_registration_form').on('submit', function( event ) {
				event.preventDefault();
				var error_class = $( '.cbm_errors' );
				var error_class_span = $( '.cbm_errors span.error' );
				$( error_class ).css( 'display', 'none' );

				// Get values of fields into variables
				var username = $( '#cbm_user_Login' ).val();
				var email = $( '#cbm_user_email' ).val();
				var fname = $( '#cbm_user_first' ).val();
				var lname = $( '#cbm_user_last' ).val();
				var pass1 = $( '#password' ).val();
				var pass2 = $( '#password_again' ).val();
				var err_msg = '';
				var validation_msg = '';
				if ( 'string' === typeof cbm_validation_msg.error_label ) {
					err_msg += cbm_validation_msg.error_label + ' ';
				}

				if ( '' === username ) { // Empty error check for user name
					if ( 'string' === typeof cbm_validation_msg.empty_username ) {
						validation_msg = err_msg + cbm_validation_msg.empty_username;
					}
				} else if ( '' === email ) { // Empty check for email.
					if ( 'string' === typeof cbm_validation_msg.empty_email ) {
						validation_msg = err_msg + cbm_validation_msg.empty_email;
					}
				} else if ( '' === pass1 ) { // Empty check for password.
					if ( 'string' === typeof cbm_validation_msg.empty_password ) {
						validation_msg = err_msg + cbm_validation_msg.empty_password;
					}
				} else if ( '' === pass2 ) { // Empty check for repeat password.
					if ( 'string' === typeof cbm_validation_msg.empty_confirm_password ) {
						validation_msg = err_msg + cbm_validation_msg.empty_confirm_password;
					}
				} else if ( pass1 !== pass2 ) { // Compare both passwords.
					if ( 'string' === typeof cbm_validation_msg.password_not_match ) {
						validation_msg = err_msg + cbm_validation_msg.password_not_match;
					}
				}

				if ( '' !== validation_msg ) {
					$( error_class ).css( 'display', 'block' );
					$( error_class_span ).html( validation_msg );
					_this.scrollToError();
					return false;
				} else {
					$( error_class ).css( 'display', 'none' );
				}

				// Parameters for ajax validation and registration of account
				var params = {
					cbm_user_Login: 		username,
					cbm_register_nonce: 	$( '#cbm_registration_nonce' ).val(),
					cbm_user_email: 		email,
					cbm_user_first: 		fname,
					cbm_user_last: 			lname,
					cbm_user_pass: 			pass1,
					cbm_user_pass_confirm: 	pass2
				};

				// Ajax request for errors and create account if no errors
				var request = wp.ajax.post( 'cbm_validate_registration_data', params );

				request.done( function( data ) {
					// Redirect to url if account is created
					window.location.href = data.url;

				}, 'json' );

				request.fail( function( data ) {
					// Display errors from php validation
					var html_error = '';

					$.each( data.errors, function( key, value ) {
						if ( '' != html_error ) {
							html_error = html_error + '<br />';
						}
						html_error = html_error + '<strong>Error : </strong>' + value;
					});

					// Display all errors into block
					if ( '' != html_error ) {
						$( error_class ).css( 'display', 'block' );
						$( error_class_span ).html( html_error );
					}

				} );

				return false;
			});
		},

		// Close notification dialog.
		user_nofication_dialog : function () {
			$( '.cbm-close-dialog' ).on( 'click', function () {
				var _this = this;
				var params = {
					notifiy_id: $( _this ).data( 'notifiy-id' )
				};

				var delete_notification = wp.ajax.post( 'cbm_delete_notification', params );
				delete_notification.done( function ( data ) {
					var dialog = $( _this ).parent();
					dialog.slideUp( 'slow', function () {
						$( this ).remove();
					} );
				});
			} );
		}
	};

	$( document ).ready( function() {
		Chargebee_Membership.init();
	} );

	$( window ).load( function() {
		Chargebee_Membership.initLoad();
	} );

})( jQuery, window.wp );
