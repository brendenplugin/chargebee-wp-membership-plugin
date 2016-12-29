<?php
if ( ! class_exists( 'Chargebee_Membership_Product_Shortcodes' ) ) {
	/**
	 * Class to Create Shortcodes.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Product_Shortcodes {

		/**
		 * Constructor of Chargebee_Membership_Product_Shortcodes class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
		}

		/**
		 * To create shortcodes.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function run() {
			// Create shortcode for Login Form.
			add_shortcode( 'cb_login_form', array( $this, 'render_login_form' ) );

			// Create shortcode for Registration form.
			add_shortcode( 'cb_registration_form', array( $this, 'render_registration_form' ) );

			// Ajax call to validate and create user account.
			add_action( 'wp_ajax_cbm_validate_registration_data', array( $this, 'cbm_validate_registration_data' ) );
			add_action( 'wp_ajax_nopriv_cbm_validate_registration_data', array( $this, 'cbm_validate_registration_data' ) );

			// Create shortcode for account link.
			add_shortcode( 'cb_account_link', array( $this, 'render_account_link' ) );

			// Create shortcode for update payment method form.
			add_shortcode( 'cb_update_payment_method_form', array( $this, 'render_update_payment_method_form' ) );

			// Create shortcode for update payment method form.
			add_shortcode( 'cb_login_logout_link', array( $this, 'render_login_logout_link' ) );

			// Create shortcode to display subscriptions of customer.
			add_shortcode( 'cb_display_subscription', array( $this, 'render_display_subscription' ) );

			// Create shortcode to display content to only visitors.
			add_shortcode( 'cb_not_logged_in', array( $this, 'render_not_logged_in' ) );

			// Create shortcode to display content logged-in users that have an active subscription.
			add_shortcode( 'cb_paid_subscription', array( $this, 'render_paid_subscription' ) );

			// Create shortcode to display content logged-in users that do not have an active subscription.
			add_shortcode( 'cb_free_subscription', array( $this, 'render_free_subscription' ) );

			// Shortcode for restrict a portion of content other than allowed level id in this shortcode.
			add_shortcode( 'cb_content_show', array( $this, 'render_content_show_hide' ) );

			// Shortcode for restrict a portion of content for given level id in this shortcode.
			add_shortcode( 'cb_content_hide', array( $this, 'render_content_show_hide' ) );
                        add_shortcode('cb_product_subscribe',array($this,'product_subscribe'));
		}


		/**
		 * Callback function to create shortcode for login form.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attribute array.
		 * @param string $content content string.
		 *
		 * @return string
		 */
		public function render_login_form( $atts = array(), $content = '' ) {
			// Output buffer to store login form.
			ob_start();
			$account_option = get_option( 'cbm_account' );

			if ( ! is_user_logged_in() ) {

				// Check page referer to redirect.
                            
                                if(! empty( $account_option['cbm_login_redirect'])){
                                    $referer= site_url( '/' . $account_option['cbm_login_redirect'] . '/' ); 
                                }
                                if (empty( $referer )){
                                    $referer = wp_get_referer();
                                }
				// Check if refer url is not empty and not from reset password.
				if ( ! empty( $referer ) && false === strpos( $referer, 'action=rp' ) ) {
					$redirect = $referer;
				} else {
					$redirect = ( ! empty( $account_option['cbm_login_redirect'] ) ? site_url( '/' . $account_option['cbm_login_redirect'] . '/' ) : site_url() );
				}
				// Arguments for login form.
				$args = array(
					'echo'           => true,
					'remember'       => true,
					'redirect'       => $redirect,
					'form_id'        => 'cbm_loginform',
					'id_username'    => 'cbm_user_login',
					'id_password'    => 'cbm_user_pass',
					'id_remember'    => 'cbm_rememberme',
					'id_submit'      => 'cbm_submit',
					'label_username' => __( 'Username' ),
					'label_password' => __( 'Password' ),
					'label_remember' => __( 'Remember Me' ),
					'label_log_in'   => __( 'Log In' ),
					'value_username' => '',
					'value_remember' => false,
				);
				$input_result = filter_input( INPUT_GET, 'result', FILTER_SANITIZE_STRING );
				$get_result   = ! empty( $input_result ) ? $input_result : '';
				$error_msg    = '';
				if ( 'failed' === $get_result ) {
					$error_msg = __( 'Error : Incorrect login details. Please try again.', 'chargebee-membership' );
				}
				$input_checkemail = filter_input( INPUT_GET, 'checkemail', FILTER_SANITIZE_STRING );
				$get_checkemail   = ! empty( $input_checkemail ) ? $input_checkemail : '';
				if ( 'confirm' === $get_checkemail ) {
					$error_msg = __( 'Check your email for the confirmation link.', 'chargebee-membership' );
				}
				$input_resetpass = filter_input( INPUT_GET, 'resetpass', FILTER_SANITIZE_STRING );
				$get_resetpass   = ! empty( $input_resetpass ) ? $input_resetpass : '';
				if ( 'success' === $get_resetpass ) {
					$error_msg = __( 'Your password has been reset.', 'chargebee-membership' );
				}
				?>
				<!-- show any error messages after form submission -->
				<div class="cbm_errors" <?php echo empty( $error_msg ) ? 'style="display:none;"' : '' ?>>
					<span class="error"><strong><?php echo empty( $error_msg ) ? esc_html__( 'Error', 'chargebee-membership' ) : esc_html( $error_msg ); ?></strong></span><br/>
				</div>
				<?php
				// Get login form.
				wp_login_form( $args );

			} else {
				$valid_tags = array(
					'a' => array(
						'href' => array(),
					),
				);
				?>
				<div class="mepr-already-logged-in">
					<?php
					printf( wp_kses( __( 'You\'re already logged in. <a href="%s">Logout</a>', 'chargebee-membership' ), $valid_tags ), esc_url( wp_logout_url( site_url( '/' . $account_option['cbm_logout_redirect'] . '/' ) ) ) );
					?>
				</div>
				<?php
			}// End if().

			return ob_get_clean();
		}


		/**
		 * Callback function to create Shortcode for registration form.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return bool|string|void registration form if its enable from settings.
		 */
		public function render_registration_form() {

			if ( ! is_user_logged_in() ) {
				// check to make sure user registration is enabled.
				$registration_enabled = get_option( 'users_can_register' );

				// only show the registration form if allowed.
				if ( $registration_enabled ) {
					$output = self::cbm_registration_form_fields();
				} else {
					$output = __( 'User registration is not enabled', 'chargebee-memberhip' );
				}

				return $output;
			}

			return false;
		}

		/**
		 * Function for html of registration.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return mixed html of registration form.
		 */
		public function cbm_registration_form_fields() {
			// Output buffer for registration form.
			ob_start();

			include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-registration-form.php';

			// Return data of output buffer.
			return ob_get_clean();
		}

		/**
		 * Function to validate input and create account if no errors.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function cbm_validate_registration_data() {
			$arr = array(
				'cbm_user_Login'        => FILTER_SANITIZE_STRING,
				'cbm_register_nonce'    => FILTER_SANITIZE_STRING,
				'cbm_user_email'        => FILTER_SANITIZE_EMAIL,
				'cbm_user_first'        => FILTER_SANITIZE_STRING,
				'cbm_user_last'         => FILTER_SANITIZE_STRING,
				'cbm_user_pass'         => FILTER_SANITIZE_STRING,
				'cbm_user_pass_confirm' => FILTER_SANITIZE_STRING,
			);

			// Get input from post request.
			$values = filter_input_array( INPUT_POST, $arr );
			$errors = array();

			// this is required for username checks.
			require_once( ABSPATH . WPINC . '/registration.php' );

			if ( username_exists( $values['cbm_user_Login'] ) ) {
				// Username already registered.
				$errors[] = __( 'Username already taken', 'chargebee-membership' );
			}

			if ( ! validate_username( $values['cbm_user_Login'] ) ) {
				// invalid username.
				$errors[] = __( 'Invalid username', 'chargebee-membership' );
			}

			if ( ! is_email( $values['cbm_user_email'] ) ) {
				// invalid email.
				$errors[] = __( 'Invalid email', 'chargebee-membership' );
			}

			if ( email_exists( $values['cbm_user_email'] ) ) {
				// Email address already registered.
				$errors[] = __( 'Email already registered', 'chargebee-membership' );
			}

			// Create user account if empty errors and send response according to it.
			if ( empty( $errors ) ) {
				$url = self::cbm_add_new_member( $values );
				wp_send_json_success( array( 'url' => $url ) );
			} else {
				wp_send_json_error( array( 'errors' => $errors ) );
			}

		}

		/**
		 * Callback function to create shortcode for login form.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array $values values array from post.
		 *
		 * @return string|void      Redirect url after successfull registration.
		 */
		public function cbm_add_new_member( $values ) {

			// Verify nonce to create account.
			if ( ! empty( $values['cbm_user_Login'] ) && wp_verify_nonce( $values['cbm_register_nonce'], 'cbm-register-nonce' ) ) {

				// only create the user in if there are no errors.
				$new_user_id = wp_insert_user(
					array(
						'user_login'      => $values['cbm_user_Login'],
						'user_pass'       => $values['cbm_user_pass'],
					    'user_email'      => $values['cbm_user_email'],
						'first_name'      => $values['cbm_user_first'],
						'last_name'       => $values['cbm_user_last'],
						'user_registered' => date( 'Y-m-d H:i:s' ),
						'role'            => 'chargebee_member',
					)
				);

				// Check if user created and if yes then save chargebee account id.
				if ( $new_user_id ) {
					// send an email to the admin alerting them of the registration.
					wp_new_user_notification( $new_user_id );

					// TODO: test wp_set_auth_cookie and remove wp_setcookie after that.
					// wp_setcookie( $values['cbm_user_Login'], $values['cbm_user_pass'], true );.
					// log the new user in.
					wp_set_auth_cookie( $new_user_id, $values['cbm_user_pass'], true );
					wp_set_current_user( $new_user_id, $values['cbm_user_Login'] );
					do_action( 'wp_login', $values['cbm_user_Login'] );

					// Chargebee Account Id add, if not exists then create account.
					$url       = 'customers';
					$parameters = array(
						'first_name'                  => $values['cbm_user_first'],
						'last_name'                   => $values['cbm_user_last'],
						'email'                       => $values['cbm_user_email'],
						'billing_address[first_name]' => $values['cbm_user_first'],
						'billing_address[last_name]'  => $values['cbm_user_last'],
					);
					$customer_id = '';

					$cbm_request_obj = new Chargebee_Membership_Request();
					$res = $cbm_request_obj->chargebee_api_request( $url, $parameters, 'post' );

					if ( ! empty( $res ) ) {

						$res_code = wp_remote_retrieve_response_code( $res );

						// Check code of response.
						if ( 200 === $res_code ) {
							$res_data      = json_decode( wp_remote_retrieve_body( $res ) );
							$customer_data = $res_data->customer;
							$customer_id   = $customer_data->id;
							// Update usermeta for customer id.
							update_user_meta( $new_user_id, 'chargebee_user_id', $customer_id );
						}
					}// End if().

					if ( ! empty( $customer_id ) ) {
						$options = get_option( 'cbm_general' );
						$plan_id = '';
						if ( ! empty( $options ) ) {
							$plan_id = ! empty( $options['cbm_default_level'] ) ? $options['cbm_default_level'] : '';
						}
						if ( ! empty( $plan_id ) ) {
							// Create subscription if default product is selected from settings.
							$created_subscription = $cbm_request_obj->create_subscription_for_customer( $customer_id, $plan_id , $new_user_id );
						}
					}

					// send the newly created user to the home page after logging them in.
					$site_url = site_url();
					return $site_url;
				}// End if().
			}// End if().
		}

		/**
		 * Callback function to create shortcode for customer account page link.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  shortcode link to account page.
		 */
		public function render_account_link( $atts = array(), $content = '' ) {
			ob_start();
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// Create customer portal link.
				self::generate_account_link( $user_id, $customer_id, $content );
			}

			$data = ob_get_clean();
			return str_replace( '\n', null, $data );
		}

		/**
		 * Callback function to create link to customer account page.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int    $user_id       Current user id.
		 * @param string $customer_id   CB customer id.
		 * @param string $content       Content to link account page.
		 */
		public function generate_account_link( $user_id, $customer_id, $content ) {

			if ( ! empty( $customer_id ) ) {
				// create portal session.
				$url = 'portal_sessions';
				$parameters = array(
					'customer[id]' => $customer_id,
					'redirect_url' => esc_url( site_url() ),
				);
				$method = 'post';

				$res = Chargebee_Membership_Request::chargebee_api_request( $url, $parameters, $method );

				$account_url = '';
				// Check for empty response.
				if ( ! empty( $res ) ) {
					$res_code     = wp_remote_retrieve_response_code( $res );
					$res_data_obj = json_decode( wp_remote_retrieve_body( $res ) );
					if ( 200 === $res_code ) {
						if ( ! empty( $res_data_obj ) ) {
							$portal_session = ! empty( $res_data_obj->portal_session ) ? $res_data_obj->portal_session : '';
							$account_url = ! empty( $portal_session->access_url ) ? $portal_session->access_url : '';
						}
					}
				}

				if ( ! empty( $account_url ) ) {
					?>
					<a href="<?php echo esc_url( $account_url ); ?>" target="_blank"><?php echo esc_html( $content ); ?></a>
					<?php
				} else {
					echo '<a href="#" >' . esc_html( $content ) . '</a>';
				}
			}
		}
		/**
		 * Callback function to create shortcode for customer update payment method form.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  update payment method form.
		 */
		public function render_update_payment_method_form( $atts = array(), $content = '' ) {
			ob_start();
			if ( is_user_logged_in() ) {
				$user_id     = get_current_user_id();
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// Check if chargebee customer.
				if ( ! empty( $customer_id ) ) {

					// Parameters for chargebee api request.
					$url       = 'hosted_pages/update_payment_method';
					$parameter = array(
						'customer[id]' => $customer_id,
					);
					$method    = 'post';
					// TODO : Need to use get_update_payment_hosted_page_url function from Chargebee_Membership_Request Class.
					$res       = Chargebee_Membership_Request::chargebee_api_request( $url, $parameter, $method );

					if ( ! empty( $res ) ) {

						$res_code = wp_remote_retrieve_response_code( $res );

						// Check if response is ok.
						if ( 200 === $res_code ) {
							$res_body        = json_decode( wp_remote_retrieve_body( $res ) );
							$hosted_page     = $res_body->hosted_page;
							$hosted_page_url = $hosted_page->url;
							?>
							<a href="<?php echo esc_url( $hosted_page_url ) ?>" target="_blank" id="cbm_update_payment_link"><?php echo ! empty( $content ) ? esc_html( $content ) : esc_html__( 'Update Payment Method' ); ?></a>
							<?php
						}
					}
				} else {
					esc_html_e( 'Customer Id is not added. Please Add Customer ID to Access Payment Form.', 'chargebee-membership' );
				}
			} else {
				$options = get_option( 'cbm_pages' );

				if ( ! empty( $options['cbm_login_page'] ) ) {
					$url = get_permalink( $options['cbm_login_page'] );
				}

				$valid_tags = array(
					'a' => array(
						'href' => array(),
					),
				);

				printf(
					wp_kses(
						__( 'Please <a href="%s">login</a> to update your payment method.', 'chargebee-membership' ),
						$valid_tags
					),
					esc_url( $url )
				);
			}// End if().

			$data = ob_get_clean();
			return str_replace( '\n', null, $data );
		}

		/**
		 * Callback function to display login or logout link respective to user's status.
		 * If user is already logged in then logout link will display otherwise login link.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  login or logout link.
		 */
		public function render_login_logout_link( $atts = array(), $content = '' ) {
			ob_start();

			// Check if user is logged in or not.
			if ( is_user_logged_in() ) {
				$account_option = get_option( 'cbm_account' );
				// Generate logout link.
				if ( ! empty( $account_option['cbm_logout_redirect'] ) ) {
					$link = wp_logout_url( site_url( '/' . $account_option['cbm_logout_redirect'] . '/' ) );
				} else {
					$link = wp_logout_url( site_url() );
				}
				$text = __( 'Logout', 'chargebee-membership' );
			} else {
				$options = get_option( 'cbm_pages' );

				if ( ! empty( $options['cbm_login_page'] ) ) {
					$link = get_permalink( $options['cbm_login_page'] );
				}
				$text = __( 'Login', 'chargebee-membership' );
			}

			// Display login or logout link.
			?>
			<a href="<?php echo esc_url( $link ) ?>"><?php echo esc_html( $text ) ?></a>
			<?php

			$data = ob_get_clean();
			return str_replace( '\n', null, $data );
		}

		/**
		 * Callback function to display subscriptions of the customer.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  subscription table of customer.
		 */
		public function render_display_subscription( $atts = array(), $content = '' ) {
			ob_start();
			?>
			<br/>
			<?php
			// Check if user is logged in.
			if ( is_user_logged_in() ) {
				$user_id     = get_current_user_id();
				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				if ( ! empty( $customer_id ) ) {
					// Get subscriptions from usermeta.
					$subscriptions = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );

					if ( ! empty( $subscriptions ) ) {
						include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-subscriptions-display.php';

						// Display account page link.
						self::generate_account_link( $user_id, $customer_id, 'Your Account' );
					} else {
						esc_html_e( 'You don\'t have any subscriptions to display.', 'chargebee-membership' );
					}
				} else {
					esc_html_e( 'Either you are not a member or your Customer Id is not added.', 'chargebee-membership' );
				}
			} else {
				$options = get_option( 'cbm_pages' );

				if ( ! empty( $options['cbm_login_page'] ) ) {
					$url = get_permalink( $options['cbm_login_page'] );
				}

				$valid_tags = array(
					'a' => array(
						'href' => array(),
					),
				);

				printf(
					wp_kses(
						__( 'Please <a href="%s">login</a> to see your subscriptions.', 'chargebee-membership' ),
						$valid_tags
					),
					esc_url( $url )
				);
			}// End if().

			return ob_get_clean();
		}

		/**
		 * Callback function to display content to only visitor i.e. not logged-in users.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  content to display non logged-in user.
		 */
		public function render_not_logged_in( $atts = array(), $content = '' ) {

			$return_content = '';
			if ( ! is_user_logged_in() ) {
				// Display content if user is not logged in.
				$return_content = wp_kses_post( $content );
			}

			return $return_content;
		}

		/**
		 * Callback function to display content logged-in users that have an active subscription.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  content to display to active user.
		 */
		public function render_paid_subscription( $atts = array(), $content = '' ) {
			global $current_user;
			$active = self::has_active_subscription();
			$return_content = '';

			// Display content to only active users.
			if ( is_user_logged_in() && $active && in_array( 'chargebee_member', $current_user->roles, true ) ) {
				$return_content = wp_kses_post( $content );
			}

			return $return_content;
		}

		/**
		 * Callback function to display content logged-in users that do not have an active subscription.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $atts attributes of shortcode.
		 * @param string $content shortcode contents.
		 *
		 * @return mix|string  content to display to non-active users.
		 */
		public function render_free_subscription( $atts = array(), $content = '' ) {
			global $current_user;
			$active = self::has_active_subscription();
			$return_content = '';

			// Display content to only inactive users.
			if ( is_user_logged_in() && ! $active && in_array( 'chargebee_member', $current_user->roles, true ) ) {
				$return_content = wp_kses_post( $content );
			}

			return $return_content;
		}

		/**
		 * Function to find if current user has any active subscriptions.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return bool  user has any active subscription or not.
		 */
		public function has_active_subscription() {
			$user       = wp_get_current_user();
			if ( ! is_object( $user ) ) {
				return false;
			}
			$user_id    = ( isset( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = ( isset( $user->roles ) ) ? $user->roles : array();

			if ( ! in_array( 'chargebee_member', $user_roles, true ) ) {
				return false;
			}
			if ( ! empty( $user_id ) ) {
				$subscriptions = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
				$status        = array_column( $subscriptions, 'status' );

				return in_array( 'active', $status, true );
			} else {
				return false;
			}
		}

		/**
		 * Restrict a portion of content by level id attribute.
		 * Hide for cb_content_hide shortcode, And Show for cb_content_show shortcode.
		 *
		 * @param array  $attr {
		 *      Attributes of the cb_content_show / cb_content_hide shortcode.
		 *
		 *      @type int $level Level id for restrict content.
		 * }
		 * @param string $content Shortcode content.
		 * @param string $shortcode_name Shortcode name.
		 *
		 * @return string $content Return content within shortcode if user has access.
		 */
		public function render_content_show_hide( $attr, $content = null, $shortcode_name = '' ) {
			$args = shortcode_atts( array(
				                        'level' => 0,
			), $attr );

			// TODO : Add this to one function as it is used many places.
			$user       = wp_get_current_user();
			if ( ! is_object( $user ) ) {
				return $content;
			}
			$user_id    = ( isset( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = ( isset( $user->roles ) ) ? $user->roles : array();

			if ( ! in_array( 'chargebee_member', $user_roles, true ) ) {
				return $content;
			}

			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : 0;

			if ( $this->is_restrict_content_shortcode_enable( $post_id ) ) {
				$level_id = absint( $args['level'] );
				if ( ! empty( $level_id ) ) {
					$cbm_level_product_data  = get_level_product_data();
					if ( empty( $cbm_level_product_data ) ) {
						return $content;
					}
					$cbm_restrict_level_arr    = isset( $cbm_level_product_data[ $level_id ] ) ? $cbm_level_product_data[ $level_id ] : array();
					$cbm_restrict_level_name   = isset( $cbm_restrict_level_arr['level_name'] ) ? $cbm_restrict_level_arr['level_name'] : '';
					$cbm_restrict_products_arr = isset( $cbm_restrict_level_arr['products'] ) ? $cbm_restrict_level_arr['products'] : array();
					$cbm_restrict_products     = is_array( $cbm_restrict_products_arr ) ? array_column( $cbm_restrict_products_arr, 'product_id' ) : array();
					$user_level_restrict_msg   = '';

					if ( ! empty( $cbm_restrict_level_name ) ) {
						if ( 'cb_content_show' === $shortcode_name ) {
							// TODO : This message will show from general settings.
							$user_level_restrict_msg = '<strong>This content can be accessed by ' . esc_attr( $cbm_restrict_level_name ) . ' users.</strong>';
						}
						if ( 'cb_content_hide' === $shortcode_name ) {
							// TODO : This message will show from general settings.
							$user_level_restrict_msg = '<strong>This content is restricted for ' . esc_attr( $cbm_restrict_level_name ) . ' users.</strong>';
						}
					}
					if ( ! empty( $cbm_restrict_products ) ) {
						$user_subscriptions   = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
						$user_active_products = array();
						if ( empty( $user_subscriptions ) ) {
							return $user_level_restrict_msg;
						}
						foreach ( $user_subscriptions as $user_subscription ) {
							 if ( 'active' === $user_subscription['status'] || 'in_trial' === $user_subscription['status'] || 'non_renewing' === $user_subscription['status'] ) {
								$user_active_products[] = $user_subscription['product_id'];
							}
						}
						if ( empty( $user_active_products ) ) {
							return $user_level_restrict_msg;
						}

						$user_access_can_access_level = array_intersect( $cbm_restrict_products, $user_active_products );

						/**
						 * If $user_access_can_access_level is empty user does not have level assign in the shortcode.
						 * So for cb_content_show shortcode this content is restrict for current user.
						 */
						if ( empty( $user_access_can_access_level ) && 'cb_content_show' === $shortcode_name ) {
							return $user_level_restrict_msg;
						}

						/**
						 * If $user_access_can_access_level is not empty user have level assign in the shortcode.
						 * So for cb_content_show shortcode this content is not restrict for current user.
						 */
						if ( ! empty( $user_access_can_access_level ) && 'cb_content_show' === $shortcode_name ) {
							return $content;
						}

						/**
						 * If $user_access_can_access_level is empty user does not have level assign in the shortcode.
						 * So for cb_content_hide shortcode this content is not restrict for current user.
						 */
						if ( empty( $user_access_can_access_level ) && 'cb_content_hide' === $shortcode_name ) {
							return $content;
						}

						/**
						 * If $user_access_can_access_level is not empty user have level assign in the shortcode.
						 * So for cb_content_hide shortcode this content is restrict for current user.
						 */
						if ( ! empty( $user_access_can_access_level ) && 'cb_content_hide' === $shortcode_name ) {
							return $user_level_restrict_msg;
						}
					}// End if().
				}// End if().
			}// End if().
			if ( 'cb_content_show' === $shortcode_name ) {
				return $content;
			} else {
				return '';
			}
		}

		/**
		 * Check if content restrict by shortcode is enable or not for the post.
		 *
		 * @param int $post_id Post id.
		 *
		 * @return bool
		 */
		public function is_restrict_content_shortcode_enable( $post_id ) {
			$cbm_restrict_option = get_post_meta( $post_id, 'cbm_restrict_option', true );
			if ( '4' === $cbm_restrict_option ) {
				return true;
			}
			return false;
		}
                
                public function product_subscribe($atts = array(), $content = ''){
                    extract(shortcode_atts(array('product_id' => ''), $atts));
                    $subscribe_product_nonce='';
                    $product_slug=$product_id;
                    if ( is_user_logged_in() && ! empty( $product_slug )) {
                        $user_id = get_current_user_id();
                        $subscribe_product_nonce = wp_create_nonce( 'cbm_subscribe_product_' . $product_slug . '_' . $user_id );
                    }                         
                    $product_page_url        = get_cbm_page_link( 'pricing' );
		    $product_single_page_url = $product_page_url . $product_slug;
                    ob_start();
                    ?>
		    <input type="button" class="button" data-cb-btn="cbm_subscribe_product" value="<?php esc_attr_e( 'Subscribe', 'chargebee-membership' ); ?>"
                                    data-cb-product-id="<?php echo esc_attr( $product_slug ) ?>"
                                    data-cb-subscribe-product-nonce="<?php echo esc_html( $subscribe_product_nonce ); ?>"
                                    data-cb-reference-url="<?php echo esc_html($product_single_page_url ); ?>"
                                  />
                    <?php
                    // Return data of output buffer.
                    return ob_get_clean();
                    
                }
	}
}// End if().
