<?php
/**
 * The file that defines the custom functions of the core plugin
 *
 * @link       https://www.chargebee.com
 * @since      1.0.0
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/public/helper
 */
if ( ! function_exists( 'db' ) ) {

	/**
	 * TODO: Function for debuging will remove before releasing plugin.
	 *
	 * @since      1.0.0
	 *
	 * @param type   $val value to print.
	 * @param type   $exit exit.
	 * @param string $method print method.
	 */
	function db( $val, $exit = null, $method = 'pre' ) {

		if ( isset( $_REQUEST['db'] ) && ! empty( $_REQUEST['db'] ) ) {

			if ( 'pre' === $method ) {

				echo '<pre>';
				print_r( $val );
				echo '</pre>';

			} elseif ( $method ) {

				var_dump( $val );

			}

			if ( $exit ) {
				exit;
			}
		}
	}
}

if ( ! function_exists( 'cbm_pages_template_redirect' ) ) {
	/**
	 * Function to redirect Thank you page.
	 *
	 * @since      1.0.0
	 */
	function cbm_pages_template_redirect() {
		$pages = get_option( 'cbm_pages' );

		// Set thank you page content.
		$cbm_thankyou_page = ! empty( $pages['cbm_thankyou_page'] ) ? $pages['cbm_thankyou_page'] : '';
		if ( ! empty( $cbm_thankyou_page ) && is_page( $cbm_thankyou_page ) ) {
			add_filter( 'the_content', 'thank_you_page_content' );
		}
	}
	add_action( 'template_redirect', 'cbm_pages_template_redirect' );
}

if ( ! function_exists( 'thank_you_page_content' ) ) {

	/**
	 * Function to display Thank You page content.
	 *
	 * @since      1.0.0
	 *
	 * @param string $content content of page.
	 *
	 * @return string
	 */
	function thank_you_page_content( $content ) {
		ob_start();
		require_once CHARGEBEE_MEMBERSHIP_PATH . 'public/partials/chargebee-membership-public-thank-you.php';
		$thank_you_page_content = ob_get_contents();
		ob_end_clean();
		$content = $thank_you_page_content . $content;
		return $content;
	}
}

if ( ! function_exists( 'cbm_subscribe_product_callback' ) ) {
	/**
	 * Callback Function of ajax to subscribe product.
	 *
	 * @since      1.0.0
	 */
	function cbm_subscribe_product_callback() {
		// Check if user is logged in.
		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_id    = ( ! empty( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = ( ! empty( $user->roles ) ) ? $user->roles : array();

			// parameters for api request.
			$product_id          = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING );
			$product_nonce       = filter_input( INPUT_POST, 'product_nonce', FILTER_SANITIZE_STRING );
			$input_reference_url = filter_input( INPUT_POST, 'reference_url', FILTER_SANITIZE_STRING );
			$reference_url       = ! empty( $input_reference_url ) ? $input_reference_url : '';
			$reference_url       = ( ! empty( $product_nonce ) && ! empty( $input_reference_url ) ) ? $reference_url . '?cbm_product_nonce=' . $product_nonce : '';

			$user_subscriptions   = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
			$existing_active_subscription = array();
			foreach ( $user_subscriptions as $user_subscription ) {
				if ( 'active' === $user_subscription['status'] || 'in_trial' === $user_subscription['status'] ) {
					$existing_active_subscription[] = $user_subscription['product_id'];
				}
			}

			if ( ! empty( $existing_active_subscription ) && in_array( $product_id, $existing_active_subscription, true ) ) {
				wp_send_json_error( array( 'error' => esc_html__( 'You already have subscription.', 'chargebee-membership' ) ) );
			}

			// Check if current user is chargebee user.
			if ( in_array( 'chargebee_member', $user_roles, true ) ) {
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// Check if customer id exists.
				if ( ! empty( $customer_id ) ) {
					$cb_api_request_obj = new Chargebee_Membership_Request();

					$is_card_exist               = $cb_api_request_obj->is_card_exist_for_customer( $customer_id );
					if ( $is_card_exist ) {
						wp_send_json_success( array( 'subscribe_product_url' => $reference_url ) );
					} else {
						// If user does not have credit card sent him/her to update payment method page to add credit card.
						$update_payment_hosted_page_url = $cb_api_request_obj->get_update_payment_hosted_page_url( $customer_id, $reference_url );
						if ( ! empty( $update_payment_hosted_page_url ) ) {
							wp_send_json_success( array( 'payment_hosted_page_url' => $update_payment_hosted_page_url ) );
						}
					}
				} else {
					// Send error if customer id is not set.
					wp_send_json_error( array( 'error' => esc_html__( 'Customer id not found.', 'chargebee-membership' ) ) );
				}
			} else {
				// Not a chargebee user.
				wp_send_json_error( array( 'error' => esc_html__( 'You aren\'t a member yet, please register to continue.', 'chargebee-membership' ) ) );
			}
		} else {
			$options = get_option( 'cbm_pages' );
			$url     = wp_login_url();
			if ( ! empty( $options['cbm_login_page'] ) ) {
				$url = get_permalink( $options['cbm_login_page'] );
			}

			// send url to redirect for login.
			wp_send_json_error( array( 'error' => 'not_logged_in', 'url' => $url ) );
		}// End if().
	}
	add_filter( 'wp_ajax_cbm_subscribe_product', 'cbm_subscribe_product_callback' );
	add_filter( 'wp_ajax_nopriv_cbm_subscribe_product', 'cbm_subscribe_product_callback' );
}// End if().

if ( ! function_exists( 'cbm_checkout_product_callback' ) ) {
	/**
	 * Callback Function of ajax to checkout product.
	 *
	 * @since      1.0.0
	 */
	function cbm_checkout_product_callback() {
		// Check if user is logged in.
		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_id    = ( ! empty( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = ( ! empty( $user->roles ) ) ? $user->roles : array();

			// Check if current user is chargebee user.
			if ( in_array( 'chargebee_member', $user_roles, true ) ) {
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// parameters for api request.
				$product_id    = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING );
				$product_nonce = filter_input( INPUT_POST, 'product_nonce', FILTER_SANITIZE_STRING );
				if ( ! empty( $product_nonce ) && ! empty( $user_id ) && ! empty( $product_id ) ) {
					// In our file that handles the request, verify the nonce.
					if ( ! wp_verify_nonce( $product_nonce, 'cbm_checkout_product_' . $product_id . '_' . $user_id ) ) {
						wp_send_json_error( array( 'error' => esc_html__( 'Invalid Request.', 'chargebee-membership' ) ) );
					}
				}
				// Check if customer id exists.
				if ( ! empty( $customer_id ) ) {
					$cb_api_request_obj          = new Chargebee_Membership_Request();
					$pages                       = get_option( 'cbm_pages' );
					$cbm_thankyou_page           = ! empty( $pages['cbm_thankyou_page'] ) ? $pages['cbm_thankyou_page'] : '';
					$cbm_thankyou_page_permalink = get_permalink( $cbm_thankyou_page );
					if ( ! empty( $cbm_thankyou_page_permalink ) ) {
						$checkout_nonce = ! empty( $user_id )? wp_create_nonce( 'cbm_checkout_' . $user_id ) : '';
						$cbm_thankyou_page_permalink .= '?cbm_checkout_nonce=' . $checkout_nonce;
					}
					$create_subscrition_response = $cb_api_request_obj->create_subscription_for_customer( $customer_id, $product_id, $user_id );
					if ( true === $create_subscrition_response ) {
						wp_send_json_success( array( 'redirect_page_url' => $cbm_thankyou_page_permalink ) );
					} else {
						wp_send_json_error( array( 'error' => $create_subscrition_response ) );
					}
				} else {
					// Send error if customer id is not set.
					wp_send_json_error( array( 'error' => esc_html__( 'Chargebee Customer id not found.', 'chargebee-membership' ) ) );
				}// End if().
			} else {
				// Not a chargebee user.
				wp_send_json_error( array( 'error' => esc_html__( 'You aren\'t a member yet, please register to continue.', 'chargebee-membership' ) ) );
			}// End if().
		} else {
			$options = get_option( 'cbm_pages' );
			$url     = wp_login_url();
			if ( ! empty( $options['cbm_login_page'] ) ) {
				$url = get_permalink( $options['cbm_login_page'] );
			}

			// send url to redirect for login.
			wp_send_json_error( array( 'error' => 'not_logged_in', 'url' => $url ) );
		}// End if().
	}
	add_filter( 'wp_ajax_cbm_checkout_product', 'cbm_checkout_product_callback' );
	add_filter( 'wp_ajax_nopriv_cbm_checkout_product', 'cbm_checkout_product_callback' );
}// End if().

if ( ! function_exists( 'cbm_reactivate_product_callback' ) ) {
	/**
	 * Callback Function of ajax to reactivate product.
	 *
	 * @since      1.0.0
	 */
	function cbm_reactivate_product_callback() {
		// Check if user is logged in.
		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_id    = ( ! empty( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = ( ! empty( $user->roles ) ) ? $user->roles : array();

			// Check if current user is chargebee user.
			if ( in_array( 'chargebee_member', $user_roles, true ) ) {
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// parameters for api request.
				$product_id    = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING );
				$subscription_id = filter_input( INPUT_POST, 'subscription_id', FILTER_SANITIZE_STRING );
				$reactivate_nonce = filter_input( INPUT_POST, 'reactivate_nonce', FILTER_SANITIZE_STRING );
				if ( ! empty( $reactivate_nonce ) && ! empty( $user_id ) && ! empty( $product_id ) ) {
					// In our file that handles the request, verify the nonce.
					if ( ! wp_verify_nonce( $reactivate_nonce, 'cbm_product_reactivate_' . $subscription_id . '_' . $user_id ) ) {
						wp_send_json_error( array( 'error' => esc_html__( 'Invalid Request.', 'chargebee-membership' ) ) );
					}
				}
				// Check if customer id exists.
				if ( ! empty( $customer_id ) && !empty($subscription_id) ) {
					$cb_api_request_obj          = new Chargebee_Membership_Request();
					$cbm_thankyou_page_permalink = get_cbm_page_link( 'thank_you' );
					if ( ! empty( $cbm_thankyou_page_permalink ) ) {
						$checkout_nonce = ! empty( $user_id ) ? wp_create_nonce( 'cbm_checkout_' . $user_id ) : '';
						$cbm_thankyou_page_permalink .= '?cbm_checkout_nonce=' . $checkout_nonce;
					}
					
					$check_reactivate_subscrition_response = $cb_api_request_obj->check_subscription_for_customer( $subscription_id, $customer_id);
					if ( true === $check_reactivate_subscrition_response ) {
						$reactivate_subscrition_response = $cb_api_request_obj->reactivate_subscription_for_customer( $subscription_id, $product_id, $user_id );
						if ( true === $reactivate_subscrition_response ) {
							$delete_notifications = Chargebee_Membership_User_Notification::delete_notification_by_subscription_id( $subscription_id );
							wp_send_json_success( array( 'redirect_page_url' => $cbm_thankyou_page_permalink ) );
						} else {
							wp_send_json_error( array( 'error' => $reactivate_subscrition_response ) );
						}
					}
					else{
						wp_send_json_error( array( 'error' => esc_html__( 'Subscription ID not found', 'chargebee-membership' ) ) );
					}
				} else {
					// Send error if customer id is not set.
					wp_send_json_error( array( 'error' => esc_html__( 'Chargebee Customer id not found.', 'chargebee-membership' ) ) );
				}// End if().
			} else {
				// Not a chargebee user.
				wp_send_json_error( array( 'error' => esc_html__( 'You aren\'t a member yet, please register to continue.', 'chargebee-membership' ) ) );
			}// End if().
		} else {
			$options = get_option( 'cbm_pages' );
			$url     = wp_login_url();
			if ( ! empty( $options['cbm_login_page'] ) ) {
				$url = get_permalink( $options['cbm_login_page'] );
			}

			// send url to redirect for login.
			wp_send_json_error( array( 'error' => 'not_logged_in', 'url' => $url ) );
		}// End if().
	}
	add_filter( 'wp_ajax_cbm_reactivate_product', 'cbm_reactivate_product_callback' );
	add_filter( 'wp_ajax_nopriv_cbm_reactivate_product', 'cbm_reactivate_product_callback' );
}// End if().

/**
 * Get level product data in one array.
 *
 * @since      1.0.0
 *
 * @param bool $update_transient For forcefully update transient cache.
 * Default false.
 *
 * @return array Return Level product data in array.
 */
function get_level_product_data( $update_transient = false ) {
	$level_product_data = get_transient( 'cbm_level_product_data' );

	if ( false === $level_product_data || true === $update_transient ) {
		global $wpdb;
		$level_data_sql = 'SELECT wcl.id level_id, wcl.level_name, wcp.id product_id, wcp.product_id cb_product_id, wcp.product_name
FROM ' . CHARGEBEE_MEMBERSHIP_TABLE_LEVEL . ' wcl JOIN ' . CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION . ' wclr ON wcl.id = wclr.level_id
JOIN ' . CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT . ' wcp ON wclr.product_id = wcp.id ORDER BY wcl.id ASC';
		$level_data     = $wpdb->get_results( $level_data_sql );

		$data = array();
		foreach ( $level_data as $item ) {
			if ( ! is_object( $item ) ) {
				continue;
			}
			$level_id = isset( $item->level_id ) ? $item->level_id : 0;
			if ( empty( $level_id ) ) {
				continue;
			}
			$data[ $level_id ]['id']                                            = $level_id;
			$data[ $level_id ]['level_name']                                    = $item->level_name;
			$data[ $level_id ]['products'][ $item->product_id ]['id']           = $item->product_id;
			$data[ $level_id ]['products'][ $item->product_id ]['product_id']   = $item->cb_product_id;
			$data[ $level_id ]['products'][ $item->product_id ]['product_name'] = $item->product_name;
		}
		$level_product_data = $data;
		// Put the results in a transient. Expire after 6 hours.
		set_transient( 'cbm_level_product_data', $level_product_data, 6 * HOUR_IN_SECONDS );
	}

	return $level_product_data;
}

if ( ! function_exists( 'cbm_level_product_clear_cache' ) ) {
	/**
	 * Clear transient cache on level add/update actions.
	 *
	 * @since      1.0.0
	 */
	function cbm_level_product_clear_cache() {
		get_level_product_data( true );
	}

	add_action( 'cbm_after_update_level', 'cbm_level_product_clear_cache' );
	add_action( 'cbm_after_insert_level', 'cbm_level_product_clear_cache' );
	add_action( 'cbm_after_delete_level', 'cbm_level_product_clear_cache' );
}

// TODO : Add into custom functions of admin.
if ( ! function_exists( 'cbm_edit_product' ) ) {
	/**
	 * Callback function of ajax to edit product data.
	 *
	 * @since      1.0.0
	 */
	function cbm_edit_product() {
		// Save Content of Product.
		$nonce = filter_input( INPUT_POST, '_nonce', FILTER_SANITIZE_STRING );
		$product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING );

		// Verify nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce,'cbm_edit_product_nonce' ) ) {
			// Nonce is not verified.
			wp_send_json_error( array( 'errors' => esc_html__( 'Something went wrong. Try again later.', 'chargebee-membership' ) ) );
		} else {
			$content = filter_input( INPUT_POST, 'product_content', FILTER_SANITIZE_STRING );

			// Save content of product.
			Chargebee_Membership_Product_Query::update_product_data( $product_id, $content );
			wp_send_json_success( array( 'product' => $product_id ) );
		}
	}

	add_filter( 'wp_ajax_cbm_edit_product', 'cbm_edit_product' );
}

/**
 * Get permalink for pages set in settings.
 *
 * @since      1.0.0
 *
 * @param string $page Page name to fetch url.
 *
 * @return false|string
 */
function get_cbm_page_link( $page ) {
	$options = get_option( 'cbm_pages' );
	$url     = '';
	switch ( $page ) {
		case 'login' :
			if ( ! empty( $options['cbm_login_page'] ) ) {
				$url = get_permalink( $options['cbm_login_page'] );
			} else {
				$url = wp_login_url();
			}
			break;
		case 'registration' :
			if ( ! empty( $options['cbm_registration_page'] ) ) {
				$url = get_permalink( $options['cbm_registration_page'] );
			}
			break;
		case 'pricing' :
			if ( ! empty( $options['cbm_product_page'] ) ) {
				$url = get_permalink( $options['cbm_product_page'] );
			}
			break;
		case 'thank_you' :
			if ( ! empty( $options['cbm_thankyou_page'] ) ) {
				$url = get_permalink( $options['cbm_thankyou_page'] );
			}
			break;

	}
	return $url;
}

/**
 * Check if any taxonomies are exist for given post type,
 * if exist return taxonomies else return false.
 *
 * @since      1.0.0
 *
 * @param string $post_type post type name to check taxonomies.
 *
 * @return array|bool
 */
function get_cbm_taxonomies_for_post_type( $post_type ) {
	// Get all the registered taxonomies of current post.
	$post_registered_taxonomies = get_object_taxonomies( $post_type );
	// If current post does not have any registered taxonomies return the content as it is.
	if ( empty( $post_registered_taxonomies ) ) {
		return false;
	}

	// Get all taxonomies which has the restriction metaboxes.
	$metabox_obj                = new Chargebee_Membership_Metabox();
	$restricted_taxonomies      = $metabox_obj->get_taxonomies();
	// If restricted taxonomies are empty return content as it is.
	if ( empty( $restricted_taxonomies ) ) {
		return false;
	}

	// Check if current post have any restricted taxonomy registered.
	$restricted_post_taxonomies = array_intersect( $post_registered_taxonomies, $restricted_taxonomies );
	if ( ! empty( $restricted_post_taxonomies ) ) {
		return $restricted_post_taxonomies;
	}
	return false;
}

if ( ! function_exists( 'cbm_user_notification_popup' ) ) {

	/**
	 * User Notification messages from webhook.
	 */
	function cbm_user_notification_popup() {
		$valid_tags = array(
			'a' => array(
				'href' => array(),
				'class' => array(),
				'title' => array(),
			),
		);
		$user_id = get_current_user_id();
		if ( ! empty( $user_id ) ) {
			$user_notification_obj = new Chargebee_Membership_User_Notification();
			$user_notifications    = $user_notification_obj->get_notifications( $user_id );
			if ( ! empty( $user_notifications ) ) {
				echo '<div class="cbm-user-notify-dialog-container">';
				foreach ( $user_notifications as $user_notification ) {
					if ( ! empty( $user_notification->user_notify_msg ) && ! empty( $user_notification->id ) ) {
						echo '<div class="cbm-user-notify-dialog cbm-hide">';
							echo '<p class="cbm-user-notify-msg">';
								echo do_shortcode( wp_kses( $user_notification->user_notify_msg, $valid_tags ) );
							echo '</p>';
							echo '<span class="cbm-close-dialog" data-notifiy-id="' . esc_attr( $user_notification->id ) . '">X</span>';
						echo '</div>';
					}
				}
				echo '</div>';
			}
		}
	}

	add_action( 'wp_footer', 'cbm_user_notification_popup' );
}

if ( ! function_exists( 'cbm_user_payment_popup' ) ) {

	/**
	 * User update payment method redirect dialog/popup box.
	 */
	function cbm_user_payment_popup() {
		?>
		<div class="cbm-user-payment-popup-container cbm-hide" id="cbm-user-payment-popup-container">
			<div class="cbm-user-payment-popup">
				<p>
					<?php
						echo esc_html__( 'You don\'t have credit card details available. You will be redirected to update payment method page. Click OK to continue.', 'chargebee-membership' );
					?>
				</p>
				<button type="button" id='cbm-confirm-redirect-payment-method'><?php echo esc_html__( 'OK', 'chargebee-membership' ); ?></button>
				<button type="button" id='cbm-cancel-redirect-payment-method'><?php echo esc_html__( 'Cancel', 'chargebee-membership' ); ?></button>
			</div>
		</div>
		<?php
	}

	add_action( 'wp_footer', 'cbm_user_payment_popup' );
}

if ( ! function_exists( 'cbm_delete_notification_callback' ) ) {
	/**
	 * Delete user notification on close dialog button click ajax call.
	 */
	function cbm_delete_notification_callback() {
		if ( is_user_logged_in() ) {
			$notifiy_id          = filter_input( INPUT_POST, 'notifiy_id', FILTER_SANITIZE_STRING );
			$delete_notification = Chargebee_Membership_User_Notification::delete_notification( $notifiy_id );
			if ( $delete_notification ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		} else {
			wp_send_json_error();
		}// End if().
	}

	add_filter( 'wp_ajax_cbm_delete_notification', 'cbm_delete_notification_callback' );
	add_filter( 'wp_ajax_nopriv_cbm_delete_notification', 'cbm_delete_notification_callback' );
}
