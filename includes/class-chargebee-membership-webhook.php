<?php
if ( ! class_exists( 'Chargebee_Membership_Webhook' ) ) {

	/**
	 * This class is use to handle Chargebee webhook events.
	 *
	 * @since      1.0.0
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership_Webhook {

		/**
		 * The Chargebee_Membership_User_Notification object for insert/delete/select
		 * user notifications.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_User_Notification    $user_notification  User notification custom table queries.
		 */
		protected $user_notification;

		/**
		 * Chargebee_Membership_Webhook constructor.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			$this->user_notification = new Chargebee_Membership_User_Notification();
			add_action( 'rest_api_init', array( $this, 'webhook_register_routes' ) );
		}

		/**
		 * Register the /wp-json/cbm/v2/webhook route
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function webhook_register_routes() {
			register_rest_route( 'cbm/v2/', 'webhook', array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'cbm_webhook_serve_route' ),
			) );
		}

		/**
		 * Generate results for the /wp-json/cbm/v2/webhook route.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error The response for the request.
		 */
		public function cbm_webhook_serve_route( WP_REST_Request $request ) {
			// Do something with the $request.
			$event_type = $request->get_param( 'event_type' );
			if ( empty( $event_type ) ) {
				$invalid_request = new WP_Error( 'empty_event', esc_html__( 'Invalid Request.', 'chargebee-membership' ) );
				return $invalid_request;
			}

                        if (isset($_SERVER['PHP_AUTH_USER'])) {
                            $cbm_options = get_option( 'cbm_site_settings' );
                            $WORDPRESS_WEBHOOK_PW=$cbm_options["webhook_password"];
                            $CB_REQUEST_USER=$_SERVER['PHP_AUTH_USER'];
                            $CB_REQUEST_PASS=$_SERVER['PHP_AUTH_PW'];
                            if( $CB_REQUEST_USER === "cb_wp_membership" && $CB_REQUEST_PASS === "$WORDPRESS_WEBHOOK_PW"){
                                $request_params = $request->get_params();
                            }
                            else{
                                $invalid_request = new WP_Error('empty_event', esc_html__( 'Invalid Request.', 'chargebee-membership' ), array( 'status' => 403 ) );
                                return $invalid_request;
                            }
                        }
                        else{
                            $invalid_request = new WP_Error('empty_event', esc_html__( 'Invalid Request.', 'chargebee-membership' ), array( 'status' => 401 ) );
                            return $invalid_request;                            
                        }
                        
                        //$request_params = $request->get_params();
			$handle_events  = array(
				'subscription' => array(
					'subscription_created',
					'subscription_changed',
					'subscription_deleted',
					'subscription_cancelled',
					'subscription_trial_end_reminder',
				),
				'customer'     => array(
					'customer_created',
					'customer_changed',
					'customer_deleted',
				),
				'payment'     => array(
					'payment_failed',
				),
                                'plan'        => array(
					'plan_created',
                                        'plan_deleted',
				),
			);
			if ( in_array( $event_type, $handle_events['subscription'], true ) ) {
				$response = $this->cbm_subscription( $event_type, $request_params );
			} elseif ( in_array( $event_type, $handle_events['customer'], true ) ) {
				$response = $this->cbm_customer( $event_type, $request_params );
			} elseif ( in_array( $event_type, $handle_events['payment'], true ) ) {
				$response = $this->cbm_payment( $event_type, $request_params );
                        } elseif ( in_array( $event_type, $handle_events['plan'], true ) ) {
				$response = $this->cbm_plan( $event_type, $request_params );
			} else {
				$response = new WP_REST_Response( 'Success' );
			}

			// Return either a WP_REST_Response or WP_Error object.
			return $response;
		}

		/**
		 * Handle customer subscription webhook event.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $event subscription webhook event.
		 * @param string $request_params Request parameters from webhooks.
		 *
		 * @return WP_REST_Response
		 */
		public function cbm_subscription( $event, $request_params ) {
			$response = new WP_REST_Response( 'Success', 400 );
			$content  = ! empty( $request_params['content'] ) ? $request_params['content'] : array();
			if ( empty( $content ) ) {
				$response->set_data( esc_html__( 'Subscription response content is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$subscription = ! empty( $content['subscription'] ) ? $content['subscription'] : array();

			if ( empty( $subscription ) ) {
				$response->set_data( esc_html__( 'Subscription response is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$subscription_id = ! empty( $subscription['id'] ) ? $subscription['id'] : '';

			if ( empty( $subscription_id ) ) {
				$response->set_data( esc_html__( 'Subscription id is not available.', 'chargebee-membership' ) );

				return $response;
			}

			$customer_id = ! empty( $subscription['customer_id'] ) ? $subscription['customer_id'] : '';

			if ( empty( $customer_id ) ) {
				$response->set_data( esc_html__( 'Customer id for subscription is not available.', 'chargebee-membership' ) );

				return $response;
			}

			$wp_user_id = $this->get_customer_wp_user_id( $customer_id );

			if ( empty( $wp_user_id ) ) {
				$response->set_data( esc_html__( 'WordPress user id not found for this customer id.', 'chargebee-membership' ) );

				return $response;
			}
			$existing_subscriptions = get_user_meta( $wp_user_id, 'chargebee_user_subscriptions', true );
			$updated_subscriptions  = array();
			switch ( $event ) {
				case 'subscription_created':
					$updated_subscriptions     = $existing_subscriptions;
					$plan_id                   = ! empty( $subscription['plan_id'] ) ? $subscription['plan_id'] : '';
					$product                   = Chargebee_Membership_Product_Query::get_product_data( $plan_id );
					$existing_subscription_ids = array_column( $existing_subscriptions, 'subscription_id' );
					if ( ! in_array( $subscription_id, $existing_subscription_ids, true ) ) {
						if ( ! empty( $product ) ) {
							if ( ! empty( $product->price ) && ! empty( $product->currency_code ) && ! empty( $product->period ) && ! empty( $product->period_unit ) ) {
								$price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
							} else {
								$price = '';
							}

							$updated_subscriptions[] = array(
								'subscription_id' => ! empty( $subscription_id ) ? $subscription_id : '',
								'product_id'      => ! empty( $plan_id ) ? $plan_id : '',
								'product_name'    => ! empty( $product->product_name ) ? $product->product_name : '',
								'product_decs'    => ! empty( $product->description ) ? $product->description : '',
								'status'          => ! empty( $subscription['status'] ) ? $subscription['status'] : '',
								'product_price'   => ! empty( $price ) ? $price : '',
								'trail_start'     => ! empty( $subscription['trial_start'] ) ? date( 'd/m/Y', $subscription['trial_start'] ) : '',
								'trial_end'       => ! empty( $subscription['trial_end'] ) ? date( 'd/m/Y', $subscription['trial_end'] ) : '',
							);
						}
						if ( ! empty( $updated_subscriptions ) ) {
							update_user_meta( $wp_user_id, 'chargebee_user_subscriptions', $updated_subscriptions, $subscription_id );
						}
						/* translators: %s: chargebee_plan_id */
						$notification_msg = sprintf( esc_html__( 'You have subscribe to %s plan.', 'chargebee-membership' ), $plan_id );
						$inserted_notification = $this->user_notification->insert_notification( $wp_user_id, $notification_msg, $subscription_id );
						if ( false === $inserted_notification ) {
							$response->set_data( esc_html__( 'User notification is not added.', 'chargebee-membership' ) );
						}
						$response->set_status( 200 );
					}
					break;
				case 'subscription_cancelled':
				case 'subscription_changed':
				case 'subscription_deleted':
					$plan_id = ! empty( $subscription['plan_id'] ) ? $subscription['plan_id'] : '';
					$product = Chargebee_Membership_Product_Query::get_product_data( $plan_id );
					foreach ( $existing_subscriptions as $existing_subscription ) {
						if ( $subscription_id === $existing_subscription['subscription_id'] ) {
							if ( ! empty( $product ) ) {
								if ( ! empty( $product->price ) && ! empty( $product->currency_code ) && ! empty( $product->period ) && ! empty( $product->period_unit ) ) {
									$price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
								} else {
									$price = '';
								}
								$existing_subscription = array(
									'subscription_id' => ! empty( $subscription_id ) ? $subscription_id : '',
									'product_id'      => ! empty( $plan_id ) ? $plan_id : '',
									'product_name'    => ! empty( $product->product_name ) ? $product->product_name : '',
									'product_decs'    => ! empty( $product->description ) ? $product->description : '',
									'status'          => ! empty( $subscription['status'] ) ? $subscription['status'] : '',
									'product_price'   => ! empty( $price ) ? $price : '',
									'trail_start'     => ! empty( $subscription['trial_start'] ) ? date( 'd/m/Y', $subscription['trial_start'] ) : '',
									'trial_end'       => ! empty( $subscription['trial_end'] ) ? date( 'd/m/Y', $subscription['trial_end'] ) : '',
								);
							}
						}
						$updated_subscriptions[] = $existing_subscription;
					}
					if ( ! empty( $updated_subscriptions ) ) {
						update_user_meta( $wp_user_id, 'chargebee_user_subscriptions', $updated_subscriptions );
					}
					if ( 'subscription_cancelled' === $event ) {
						$product_page_url        = get_cbm_page_link( 'pricing' );
						$product_single_page_url = $product_page_url . $plan_id;
						$product_reactivate_url  = $product_single_page_url . '?subscription_id=' . esc_attr( $subscription_id );
						$reactivate_subscription = '<a class="cbm-reactivate-subscription" href="' . esc_attr( $product_reactivate_url ) . '" title="' . esc_html__( 'Reactivate', 'chargebee-membership' ) . '">' . esc_html__( 'Reactivate', 'chargebee-membership' ) . '</a>';
						/* translators: %s: chargebee_plan_id */
						$notification_msg = sprintf( esc_html__( 'Your subscription for %s plan is cancelled.', 'chargebee-membership' ), $plan_id );
						$notification_msg .= $reactivate_subscription;
					} elseif ( 'subscription_changed' === $event ) {
						/* translators: %s: chargebee_plan_id */
						$notification_msg = sprintf( esc_html__( 'Your subscription to %s plan is updated.', 'chargebee-membership' ), $plan_id );
					} elseif ( 'subscription_deleted' === $event ) {
						/* translators: %s: chargebee_plan_id */
						$notification_msg = sprintf( esc_html__( 'Your subscription to %s plan is deleted.', 'chargebee-membership' ), $plan_id );
					} else {
						$notification_msg = '';
					}
					if ( ! empty( $notification_msg ) ) {
						$inserted_notification = $this->user_notification->insert_notification( $wp_user_id, $notification_msg, $subscription_id );
						if ( false === $inserted_notification ) {
							$response->set_data( esc_html__( 'User notification is not added.', 'chargebee-membership' ) );
						}
					}
					$response->set_status( 200 );
					break;
				case 'subscription_trial_end_reminder':
					break;
				default:
					$response->set_data( 'Success' );
					break;
			}// End switch().

			return $response;
		}


		/**
		 * Handle customer webhook event.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $event customer event.
		 * @param string $request_params Request parameters from webhooks.
		 *
		 * @return WP_REST_Response
		 */
		public function cbm_customer( $event, $request_params ) {
			$response = new WP_REST_Response( 'Success', 400 );

			$content = ! empty( $request_params['content'] ) ? $request_params['content'] : array();
			if ( empty( $content ) ) {
				$response->set_data( esc_html__( 'Customer response content is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$customer = ! empty( $content['customer'] ) ? $content['customer'] : array();

			if ( empty( $customer ) || empty( $customer['email'] ) ) {
				$response->set_data( esc_html__( 'Customer response is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$customer_id = ! empty( $customer['id'] ) ? $customer['id'] : '';

			if ( empty( $customer_id ) ) {
				$response->set_data( esc_html__( 'Customer id is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$user_args = array(
				'user_login'      => ! empty( $customer['email'] ) ? $customer['email'] : '',
				'user_pass'       => ! empty( $customer['cbm_user_pass'] ) ? $customer['cbm_user_pass'] : '',
				'user_email'      => ! empty( $customer['email'] ) ? $customer['email'] : '',
				'first_name'      => ! empty( $customer['first_name'] ) ? $customer['first_name'] : '',
				'last_name'       => ! empty( $customer['last_name'] ) ? $customer['last_name'] : '',
				'user_registered' => ! empty( $customer['created_at'] ) ? date( 'Y-m-d H:i:s', $customer['created_at'] ) : date( 'Y-m-d H:i:s' ),
				'role'            => 'chargebee_member',
			);

			if ( 'customer_created' === $event ) {
				$wp_user_id = new WP_Error( 'empty_user_id', esc_html__( 'Empty user id.', 'chargebee-membership' ) );
			} else {
				$wp_user_id = $this->get_customer_wp_user_id( $customer_id );
				if ( empty( $wp_user_id ) ) {
					$response->set_data( esc_html__( 'Invalid Customer id.', 'chargebee-membership' ) );

					return $response;
				}
			}

			switch ( $event ) {
				case 'customer_created':
					$wp_user_id = wp_insert_user( $user_args );
					break;
				case 'customer_changed':
					$user_args['ID'] = $wp_user_id;
					$wp_user_id      = wp_update_user( $user_args );
					break;
				case 'customer_deleted':
					wp_delete_user( $wp_user_id );
					$response->set_data( esc_html__( 'Customer deleted successfully.', 'chargebee-membership' ) );
					$response->set_status( 200 );

					return $response;
					break;
				default:
					$wp_user_id = new WP_Error( 'empty_user_id', esc_html__( 'Empty user id.', 'chargebee-membership' ) );
					break;
			}
			if ( is_wp_error( $wp_user_id ) ) {
				$response->set_data( esc_html__( 'Customer data is not changed due to some invalid data.', 'chargebee-membership' ) );
			} else {
				// Success!
				$response->set_data( esc_html__( 'Customer data changed successfully.', 'chargebee-membership' ) );
				update_user_meta( $wp_user_id, 'chargebee_user_id', $customer_id );
				$response->set_status( 200 );
			}

			return $response;
		}


		/**
		 * Handle customer payment webhook event.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $event Payment event.
		 * @param string $request_params Request parameters from webhooks.
		 *
		 * @return WP_REST_Response
		 */
		public function cbm_payment( $event, $request_params ) {
			$response = new WP_REST_Response( 'Success', 400 );

			$content = ! empty( $request_params['content'] ) ? $request_params['content'] : array();
			if ( empty( $content ) ) {
				$response->set_data( esc_html__( 'Customer response content is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$customer = ! empty( $content['customer'] ) ? $content['customer'] : array();

			if ( empty( $customer ) || empty( $customer['email'] ) ) {
				$response->set_data( esc_html__( 'Customer response is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$customer_id = ! empty( $customer['id'] ) ? $customer['id'] : '';

			if ( empty( $customer_id ) ) {
				$response->set_data( esc_html__( 'Customer id is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$wp_user_id = $this->get_customer_wp_user_id( $customer_id );

			if ( empty( $wp_user_id ) ) {
				$response->set_data( esc_html__( 'Invalid Customer id.', 'chargebee-membership' ) );

				return $response;
			}

			switch ( $event ) {
				case 'payment_failed':
					$notification_msg = esc_html__( 'Your last payment is failed due payment method issue, Please check and update your payment method.', 'chargebee-membership' );
					$notification_msg .= esc_html( '[cb_update_payment_method_form]' );
					if ( ! empty( $notification_msg ) ) {
						$inserted_notification = $this->user_notification->insert_notification( $wp_user_id, $notification_msg );
						if ( false === $inserted_notification ) {
							$response->set_data( esc_html__( 'User notification is not added.', 'chargebee-membership' ) );
						}
					}
					$response->set_status( 200 );
					break;
			}

			return $response;
		}

		/**
		 * Handle Plan webhook event.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $event plan event.
		 * @param string $request_params Request parameters from webhooks.
		 *
		 * @return WP_REST_Response
		 */
		public function cbm_plan( $event, $request_params ) {
			$response = new WP_REST_Response( 'Success', 400 );

			$content = ! empty( $request_params['content'] ) ? $request_params['content'] : array();
			if ( empty( $content ) ) {
				$response->set_data( esc_html__( 'Plan response content is empty.', 'chargebee-membership' ) );

				return $response;
			}

			$plan = ! empty( $content['plan'] ) ? $content['plan'] : array();
			if ( empty( $plan ) || empty( $plan['id'] ) ) {
				$response->set_data( esc_html__( 'Plan response is empty.', 'chargebee-membership' ) );
				return $response;
			}

			switch ( $event ) {
				case 'plan_created':
					$product_inserted = $this->insert_webhook_products( $plan );
					if ($product_inserted ) {
                                            $response->set_data( esc_html__( 'Plan created successfully.', 'chargebee-membership' ) );
                                            $response->set_status( 200 );
                                            return $response;
					} else {
                                            $response->set_data( esc_html__( 'Issue during plan creation webhook.', 'chargebee-membership' ) );
                                            return $response;
					}
					break;
				case 'plan_deleted':
                                        $product_deleted = $this->delete_webhook_products( $plan );
					if ($product_deleted ) {
                                            $response->set_data( esc_html__( 'Plan deleted successfully.', 'chargebee-membership' ) );
                                            $response->set_status( 200 );
                                            return $response;
                                        }
                                        else{
                                            $response->set_data( esc_html__( 'Issue during plan deletion webhook.', 'chargebee-membership' ) );
                                            return $response;
                                        }
					break;
				default:
					$response->set_data( esc_html__( 'Issue during plan deletion webhook.', 'chargebee-membership' ) );
                                        return $response;
			}
                        $response->set_data( esc_html__( 'Plan Sync Successful.', 'chargebee-membership' ) );
                        $response->set_status( 200 );
			return $response;
		}                
                
                
		/**
		 * Get user id from chargebee customer id.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $customer_id Chargebee customer id.
		 *
		 * @return int WP user id.
		 */
		public function get_customer_wp_user_id( $customer_id ) {
			global $wpdb;
			$user_id_sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'chargebee_user_id' AND meta_value = %s", esc_sql( $customer_id ) );
			$user_id     = $wpdb->get_var( $user_id_sql );

			return $user_id;
		}

		/**
		 * Function to Delete plan details triggered from webhooks.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param    array plan.
		 *
		 * @return bool
		 */
		public function delete_webhook_products( $plan ) {
			global $wpdb;

			if ( empty( $plan ) ) {
				return false;
			}
                        $exec_return = $wpdb->delete(
                             CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT,
                             array(
                                     'product_id'        => ! empty( $plan['id'] ) ? $plan['id'] : '',
                             ),
                             array('%s')
                     );
                     return $exec_return;
		}
                
		/**
		 * Function to insert all products triggered from webhooks.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param    array $res_body product list.
		 *
		 * @return bool
		 */
		public function insert_webhook_products( $plan ) {
			global $wpdb;

			if ( empty( $plan ) ) {
				return false;
			}

                        if(! empty($plan['price'])){
                            $plan_price = intval( $plan['price'] ) / 100;
                        }
                        if(empty($plan_price)){
                            return false;
                        }
                        
                        $wpdb->insert(
                             CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT,
                             array(
                                     'id'                => null,
                                     'product_id'        => ! empty( $plan['id'] ) ? $plan['id'] : '',
                                     'product_name'      => ! empty( $plan['name'] ) ? $plan['name'] : '',
                                     'price'		 => ! empty( $plan_price ) ? $plan_price : '',
                                     'period'            => ! empty( $plan['period'] ) ? $plan['period'] : '',
                                     'period_unit'       => ! empty( $plan['period_unit'] ) ? $plan['period_unit'] : '',
                                     'trial_period'      => ! empty( $plan['trial_period'] ) ? $plan['trial_period'] : '',
                                     'trial_period_unit' => ! empty( $plan['trial_period_unit'] ) ? $plan['trial_period_unit'] : '',
                                     'status'            => ! empty( $plan['status'] ) ? $plan['status'] : '',
                                     'description'       => ! empty( $plan['description'] ) ? $plan['description'] : '',
                                     'currency_code'     => ! empty( $plan['currency_code'] ) ? $plan['currency_code'] : '',
                                     'charge_model'      => ! empty( $plan['charge_model'] ) ? $plan['charge_model'] : '',
                             ),
                             array( '%d','%s', '%s', '%f', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
                     ); 
		return true;
		}
	}
}// End if().
