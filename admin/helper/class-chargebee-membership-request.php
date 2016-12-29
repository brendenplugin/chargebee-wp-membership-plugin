<?php
if ( ! class_exists( 'Chargebee_Membership_Request' ) ) {

	/**
	 * Class to handle chargebee requests for CURL data.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Request {

		/**
		 * Function to send curl request and get response.
		 *
		 * @since    1.0.0
		 */
		public static function send() {

		}

		/**
		 * Function to Authenticate key and site name and Import Current Plans/Products.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $api   API key of chargebee.
		 * @param string $site  Site name of chargebee.
		 *
		 * @return array|WP_Error   Response object from CB API
		 */
		public static function authorize_key( $api, $site ) {
			global $CB_PLUGIN_VERSION;
			global $wp_version;
			$password = '';
			$url      = "https://{$site}.chargebee.com/api/v2/plans";
			$args     = array(
				'headers'   => array(
					'Authorization' => 'Basic ' . base64_encode( "$api:$password" ),
				),
				'body'      => array(
					'limit' => '100',
				),
				'sslverify' => true,
				'user-agent' => "CB/$CB_PLUGIN_VERSION/WP/$wp_version",
			);
			$response = wp_remote_get( $url, $args );

			return $response;
		}

		/**
		 * Function for Chargebee API request.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $url       Request url.
		 * @param array  $parameter body parameters for request.
		 * @param string $method    get or post method for request.
		 *
		 * @return mixed response of request.
		 */
		public static function chargebee_api_request( $url = '', $parameter = array(), $method = 'get' ) {
			global $CB_PLUGIN_VERSION;
                        global $wp_version;
			$api      = self::get_key();
			$site     = self::get_site();
			$password = '';
			if ( ! empty( $api ) && ! empty( $site ) ) {

				$url      = "https://{$site}.chargebee.com/api/v2/" . $url;
				$args     = array(
					'headers'   => array(
						'Authorization' => 'Basic ' . base64_encode( "$api:$password" ),
					),
					'sslverify' => false,
					'user-agent' => "CB/$CB_PLUGIN_VERSION/WP/$wp_version",
				);
				if ( ! empty( $parameter ) ) {
					$args['body'] = $parameter;
				}

				// Check method for request.
				if ( 'get' === $method ) {
					$response = wp_remote_get( $url, $args );
				} else {
					$response = wp_remote_post( $url, $args );
				}

				return $response;
			} else {
				return false;
			}
		}

		/**
		 * Function to get chargebee API key.
		 *
		 * @since    1.0.0
		 * @access   private
		 *
		 * @return mixed|void   Chargebee API key.
		 */
		private static function get_key() {
			return get_option( 'cbm_api_key', true );
		}

		/**
		 * Function to get chargebee Site name.
		 *
		 * @since    1.0.0
		 * @access   private
		 *
		 * @return mixed|void   Chargebee site name.
		 */
		private static function get_site() {
			return get_option( 'cbm_site_name', true );
		}


		/**
		 * Create subscription for customer.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $customer_id Chargebee customer id.
		 * @param string $plan_id Chargebee plan id.
		 * @param int    $user_id WordPress Customer/user id.
		 *
		 * @return bool|string
		 */
		public function create_subscription_for_customer( $customer_id, $plan_id, $user_id ) {
			$url = 'customers/' . $customer_id . '/subscriptions';
			$data = array(
				'plan_id' => $plan_id,
			);
			// API request to create subscription for customer using plan/product id.
			$subscription_response = self::chargebee_api_request( $url, $data, 'post' );
			if ( ! empty( $subscription_response ) ) {

				$res_code     = wp_remote_retrieve_response_code( $subscription_response );
				$res_data_obj = json_decode( wp_remote_retrieve_body( $subscription_response ) );

				// Check code of response.
				if ( 200 === $res_code ) {
					$created_subscription_obj = ! empty( $res_data_obj->subscription ) ? $res_data_obj->subscription : '';
					if ( ! empty( $created_subscription_obj ) && ! empty( $created_subscription_obj->id ) ) {
						$product = Chargebee_Membership_Product_Query::get_product_data( $plan_id );
						if ( ! empty( $product ) ) {
							if ( ! empty( $product->price ) && ! empty( $product->currency_code ) && ! empty( $product->period ) && ! empty( $product->period_unit ) ) {
								$price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
							} else {
								$price = '';
							}
							// TODO : Need to test usermeta if its exist, will uncomment once verify the o/p.
							$new_subscriptions   = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
							if ( empty( $new_subscriptions ) ) {
								$new_subscriptions   = array();
							}
							$new_subscriptions[] = array(
								'subscription_id' => $created_subscription_obj->id,
								'product_id'      => ! empty( $created_subscription_obj->plan_id ) ? $created_subscription_obj->plan_id : '',
								'product_name'    => ! empty( $product->product_name ) ? $product->product_name : '',
								'product_decs'    => ! empty( $product->description ) ? $product->description : '',
								'status'          => ! empty( $created_subscription_obj->status ) ? $created_subscription_obj->status : '',
								'product_price'   => ! empty( $price ) ? $price : '',
								'trail_start'     => ! empty( $created_subscription_obj->trial_start ) ? date( 'd/m/Y', $created_subscription_obj->trial_start ) : '',
								'trial_end'       => ! empty( $created_subscription_obj->trial_end ) ? date( 'd/m/Y', $created_subscription_obj->trial_end ) : '',
							);
							update_user_meta( $user_id, 'chargebee_user_subscriptions', $new_subscriptions );
						}
					}
					return true;
				} else {
					$api_error_code = ! empty( $res_data_obj->api_error_code ) ? $res_data_obj->api_error_code : '';
					return $api_error_code;
				}
				// End if().
			}// End if().
		}


		/**
		 * Updating meta data for a plan to avoid getting replaced eachtime during sync.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $plan_id Chargebee plan id.
		 * @param int    $plan_meta_id id that was inserted in the database for product.
		 *
		 * @return bool|string
		 */
		public function update_plan_meta_id( $plan_id, $plan_meta_id) {
                        if($plan_id == null || $plan_meta_id == null ){
                            return false;
                        }
			$url = 'plans/' . $plan_id;
                        $data = array(
				'meta_data' => "{'cb_wp_plan_id': $plan_meta_id}",
			);
			$plan_meta_response = self::chargebee_api_request( $url, $data, 'post' );
			if ( ! empty( $plan_meta_response ) ) {
				$res_code     = wp_remote_retrieve_response_code( $plan_meta_response );
				$res_data_obj = json_decode( wp_remote_retrieve_body( $plan_meta_response ) );
				if ( 200 === $res_code ) {
					return true;
				} else {
					$api_error_code = ! empty( $res_data_obj->api_error_code ) ? $res_data_obj->api_error_code : '';
					return $api_error_code;
				}
			}
		}
                

		public function check_subscription_for_customer( $subscription_id, $customer_id){
			$url = 'subscriptions/' . $subscription_id;
			$subscription_response = self::chargebee_api_request( $url, array(), 'get' );
			if ( ! empty( $subscription_response ) ) {

				$res_code     = wp_remote_retrieve_response_code( $subscription_response );
				$res_data_obj = json_decode( wp_remote_retrieve_body( $subscription_response ) );

				// Check code of response.
				if ( 200 === $res_code ) {
					$reactivated_customer_obj = ! empty( $res_data_obj->customer) ? $res_data_obj->customer : '';
					if ( ! empty( $reactivated_customer_obj ) && ! empty( $reactivated_customer_obj->id ) ) {
						if($reactivated_customer_obj->id == $customer_id){
							return true;
						}
					}
				}
			}
			return false;
		}

		/**
		 * Reactivate subscription for customer.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $subscription_id Chargebee subscription id.
		 * @param string $plan_id Chargebee plan id.
		 * @param int    $user_id WordPress Customer/user id.
		 *
		 * @return bool|string
		 */
		public function reactivate_subscription_for_customer( $subscription_id, $plan_id, $user_id ) {
			$url = 'subscriptions/' . $subscription_id . '/reactivate';
			// API request to reactivate subscription for customer using subscription id.
			$subscription_response = self::chargebee_api_request( $url, array(), 'post' );
			if ( ! empty( $subscription_response ) ) {

				$res_code     = wp_remote_retrieve_response_code( $subscription_response );
				$res_data_obj = json_decode( wp_remote_retrieve_body( $subscription_response ) );

				// Check code of response.
				if ( 200 === $res_code ) {
					$reactivated_subscription_obj = ! empty( $res_data_obj->subscription ) ? $res_data_obj->subscription : '';
					if ( ! empty( $reactivated_subscription_obj ) && ! empty( $reactivated_subscription_obj->id ) ) {
						$product = Chargebee_Membership_Product_Query::get_product_data( $plan_id );
						if ( ! empty( $product ) ) {
							if ( ! empty( $product->price ) && ! empty( $product->currency_code ) && ! empty( $product->period ) && ! empty( $product->period_unit ) ) {
								$price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
							} else {
								$price = '';
							}
							$updated_subscriptions  = array();
							$existing_subscriptions = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
							foreach ( $existing_subscriptions as $existing_subscription ) {
								if ( $subscription_id === $existing_subscription['subscription_id'] ) {
									$existing_subscription = array(
										'subscription_id' => $reactivated_subscription_obj->id,
										'product_id'      => ! empty( $reactivated_subscription_obj->plan_id ) ? $reactivated_subscription_obj->plan_id : '',
										'product_name'    => ! empty( $product->product_name ) ? $product->product_name : '',
										'product_decs'    => ! empty( $product->description ) ? $product->description : '',
										'status'          => ! empty( $reactivated_subscription_obj->status ) ? $reactivated_subscription_obj->status : '',
										'product_price'   => ! empty( $price ) ? $price : '',
										'trail_start'     => ! empty( $reactivated_subscription_obj->trial_start ) ? date( 'd/m/Y', $reactivated_subscription_obj->trial_start ) : '',
										'trial_end'       => ! empty( $reactivated_subscription_obj->trial_end ) ? date( 'd/m/Y', $reactivated_subscription_obj->trial_end ) : '',
									);
								}
								$updated_subscriptions[] = $existing_subscription;
							}
							if ( ! empty( $updated_subscriptions ) ) {
								update_user_meta( $user_id, 'chargebee_user_subscriptions', $updated_subscriptions );
							}
						}
					}
					return true;
				} else {
					$api_error_code = ! empty( $res_data_obj->api_error_code ) ? $res_data_obj->api_error_code : '';
					return $api_error_code;
				}// End if().
			}// End if().
		}


		/**
		 * Get update payment hosted page url from customer id.
		 *
		 * @since    1.0.0
		 * @access   static
		 *
		 * @param string $customer_id Chargebee customer id.
		 * @param string $redirect_url Redirect url from hosted page.
		 *
		 * @return bool|string Return url if get we get in response OR false.
		 */
		public function get_update_payment_hosted_page_url( $customer_id, $redirect_url = '' ) {
			if ( empty( $customer_id ) ) {
				return false;
			}
			$url       = 'hosted_pages/update_payment_method';
			$data = array(
				'customer[id]' => $customer_id,
			);

			if ( ! empty( $redirect_url ) ) {
				$data['redirect_url'] = $redirect_url;
			}
			// API request to crete subscription for customer using plan/product id.
			$update_payment_response = self::chargebee_api_request( $url, $data, 'post' );
			if ( ! empty( $update_payment_response ) ) {

				$res_code = wp_remote_retrieve_response_code( $update_payment_response );

				// Check code of response.
				if ( 200 === $res_code ) {

					$res_body        = json_decode( wp_remote_retrieve_body( $update_payment_response ) );
					if ( ! is_object( $res_body ) ) {
						return false;
					}
					$hosted_page     = ! empty( $res_body->hosted_page ) ? $res_body->hosted_page : '';
					$hosted_page_url = ! empty( $hosted_page ) && ! empty( $hosted_page->url ) ? $hosted_page->url : '';
					return $hosted_page_url;
				}// End if().

			}// End if().
			return false;
		}


		/**
		 * Check if customer have any credit card available.
		 *
		 * @since    1.0.0
		 * @access   static
		 *
		 * @param string $customer_id Chargebee customer id.
		 *
		 * @api-ref : https://apidocs.chargebee.com/docs/api/cards
		 *
		 * @return bool
		 */
		public function is_card_exist_for_customer( $customer_id ) {
			if ( empty( $customer_id ) ) {
				return false;
			}
			$url  = 'cards/' . $customer_id;
			// API request to crete subscription for customer using plan/product id.
			$card_detail_response = self::chargebee_api_request( $url );
			if ( ! empty( $card_detail_response ) ) {

				$res_code = wp_remote_retrieve_response_code( $card_detail_response );

				// Check code of response.
				if ( 200 === $res_code ) {
					return true;
				}// End if().

			}// End if().
			return false;
		}
	}
}// End if().
