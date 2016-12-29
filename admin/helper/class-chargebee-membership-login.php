<?php
if ( ! class_exists( 'Chargebee_Membership_Login' ) ) {

	/**
	 * Class to handle chargebee customer login.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Login {

		/**
		 * Constructor of Chargebee_Membership_Login class.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function __construct() {
		}

		/**
		 * Function to add filters and actions.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function run() {
			// Action to Check login if it's chargebee user.
			add_action( 'wp_login', array( $this, 'login_check' ), 20, 2 );

			// Redirect wp-login.php to custom login page.
			add_action( 'login_form_login', array( $this, 'redirect_login_page' ) );

			// Validate login form.
			add_action( 'wp_login_failed', array( $this, 'login_failed_callback' ) );

			// Action to add forget password link to login form.
			add_action( 'login_form_bottom', array( $this, 'add_lost_password_registration_link' ) );

		}

		/**
		 * Function to handle chargebee user's login criterias.
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param 	int    $user_login user id.
		 * @param 	object $user   user object.
		 */
		public function login_check( $user_login, $user ) {
			$role = $user->roles;

			// Check if chargebee user.
			if ( in_array( 'chargebee_member', $role, true ) ) {
				$user_id = $user->data->ID;

				$customer_id = get_user_meta( $user_id, 'chargebee_user_id', true );

				// If customer id is not present then create a customer.
				if ( empty( $customer_id ) ) {
					$url = 'customers';
					$parameter = array(
						'limit'     => '1',
						'email[is]' => $user->data->user_email,
					);

					$res = Chargebee_Membership_Request::chargebee_api_request( $url, $parameter );
					if( ! empty( $res ) ) {

						// Check respomse.
						$code = wp_remote_retrieve_response_code( $res );

						// Success on request.
						if ( 200 === $code ) {
							$cust_list = array_shift( json_decode( wp_remote_retrieve_body( $res ) )->list );

							$customer_data = $cust_list->customer;
							$customer_id   = $customer_data->id;
							update_user_meta( $user_id, 'chargebee_user_id', $customer_id );
						}
					}
				}
			}
		}

		/**
		 * Redirect to custom login page from wp-login.php which is set in backend settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function redirect_login_page() {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        return true;
                    }
			$account_option = get_option( 'cbm_account' );
			// Check if chargebee custom login is enable or not in settings.
			if ( empty( $account_option['cbm_use_cb_login'] ) || is_admin() ) {
				return true;
			}
			// Store for checking if this page equals wp-login.php.
			$page_requested_basename = basename( $_SERVER['REQUEST_URI'] );

			// Check if basename include wp-login.php.
			$is_wp_login_page = strpos( $page_requested_basename, 'wp-login.php' );

			// Check if current requset page is wp-login.php.
			if ( false !== $is_wp_login_page ) {
				$input_actions  = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
				$action         = ! empty( $input_actions ) ? $input_actions : '';
				$filter_actions = array( 'lostpassword', 'rp' );
				if ( ! in_array( $action, $filter_actions, true ) ) {
					// Custom login page.
					$login_page = get_cbm_page_link( 'login' );
					if ( ! empty( $login_page ) ) {
						// TODO: To remove this login redirection we need to user forgot password in custom login page.
						$input_checkemail = filter_input( INPUT_GET, 'checkemail', FILTER_SANITIZE_STRING );
						$checkmail        = ! empty( $input_checkemail ) ? $input_checkemail : '';
						if ( ! empty( $checkmail ) ) {
							$login_page = add_query_arg( 'checkemail', $checkmail, $login_page );
						}
						if ( ! empty( $action ) && 'resetpass' === $action ) {
							$login_page = add_query_arg( 'resetpass', 'success', $login_page );
						}
						// Redirect to custom login page.
						wp_safe_redirect( $login_page );
                                                exit;
					}
				}
			}
		}

		/**
		 * Login failed hook to redirect with failed parameter on invalid login details.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $username Username entered in login form.
		 */
		public function login_failed_callback( $username ) {
			$referrer = ( isset( $_SERVER['HTTP_REFERER'] ) ) ? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
			$referrer = add_query_arg( 'result', 'failed', $referrer );
			if ( ! empty( $referrer ) && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) ) {
				wp_safe_redirect( $referrer );
				exit;
			}
		}

		/**
		 * Callback function to add lost password link into login form.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return string   lost password link.
		 */
		public function add_lost_password_registration_link() {
			ob_start();
			$pages = get_option( 'cbm_pages' );
			$registration_link = '';

			if ( ! empty( $pages['cbm_registration_page'] ) ) {
				$registration_link = get_permalink( $pages['cbm_registration_page'] );
			}
			?>
			<a href="<?php echo esc_url( $registration_link ) ?>"><?php esc_html_e( 'Register', 'chargebee-membership' ); ?></a> |
			<a href="<?php echo esc_url( wp_lostpassword_url() ) ?>"><?php esc_html_e( 'Forgot Password?', 'chargebee-membership' ); ?></a>
			<?php

			return ob_get_clean();

		}
	}
}
