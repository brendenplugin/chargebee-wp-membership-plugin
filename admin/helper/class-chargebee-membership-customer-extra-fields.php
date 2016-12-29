<?php
if ( ! class_exists( 'Chargebee_Membership_Customer_Extra_Fields' ) ) {
	/**
	 * Class to Add and Save Extra Fields at customer profile page
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Customer_Extra_Fields {

		/**
		 * Function to add actions and filters.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function run() {

			// actions to add extra fields.
			add_action( 'show_user_profile', array( $this, 'customer_add_extra_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'customer_add_extra_fields' ) );

			// Save extra fields.
			add_action( 'personal_options_update', array( $this, 'customer_id_extra_fields_save' ) );
			add_action( 'edit_user_profile_update', array( $this, 'customer_id_extra_fields_save' ) );

			// Ajax call to create chargebee customer account.
			add_action( 'wp_ajax_cbm_create_customer_acnt', array( $this, 'create_chargebee_acnt_callback' ) );
		}

		/**
		 * Function to Add Chargebee customer extra fields
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param object $user Object of user currently editing.
		 */
		public static function customer_add_extra_fields( $user ) {
			if ( in_array( 'chargebee_member', $user->roles, true ) ) {
				include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-customer-extra-fields.php';
			}
		}

		/**
		 * Function to Save Chargebee customer extra fields
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param	int	$user_id    user id currently editing.
		 */
		public static function customer_id_extra_fields_save( $user_id ) {
			$chargebee_user_id = filter_input( INPUT_POST, 'chargebee_user_id', FILTER_SANITIZE_STRING );
			if ( ! empty( $chargebee_user_id ) ) {
				update_user_meta( $user_id, 'chargebee_user_id', $chargebee_user_id );
			}
		}

		/**
		 * Function to Create Chargebee customer without subscription
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public static function create_chargebee_acnt_callback() {

			// Verify Nonce for create account.
			check_ajax_referer( 'cbm-create-account', '_cbm_nonce' );

			// Get user id.
			$user_id = filter_input( INPUT_POST, 'user_id', FILTER_VALIDATE_INT );

			$url = 'customers';
			$user_data = get_userdata( $user_id );
			if ( is_object( $user_data ) && ! empty( $user_data ) ) {

				$parameters = array(
					'first_name'                  => $user_data->first_name,
					'last_name'                   => $user_data->last_name,
					'email'                       => $user_data->data->user_email,
					'billing_address[first_name]' => $user_data->first_name,
					'billing_address[last_name]'  => $user_data->last_name,
				);

				$res = Chargebee_Membership_Request::chargebee_api_request( $url, $parameters, 'post' );

				if ( ! empty( $res ) ) {

					$res_code = wp_remote_retrieve_response_code( $res );
					$res_data = json_decode( wp_remote_retrieve_body( $res ) );

					// Check code of response.
					if ( 200 !== $res_code ) {
						// Send error message.
						wp_send_json_error( array( 'msg' => $res_data->message ) );
					} else {
						$customer_data = $res_data->customer;
						$customer_id   = $customer_data->id;
						update_user_meta( $user_id, 'chargebee_user_id', $customer_id );

						// Send customer id to display in text filed of customer id.
						wp_send_json_success( array( 'customer_id' => $customer_id, 'msg' => esc_html__( 'Chargebee Account Created Successfully.', 'chargebee-membership' ) ) );
					}
				}
			}
		}
	}
}
