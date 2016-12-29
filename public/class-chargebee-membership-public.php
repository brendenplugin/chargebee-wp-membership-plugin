<?php
if ( ! class_exists( 'Chargebee_Membership_Public' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * @link       https://www.chargebee.com
	 * @since      1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/public
	 */

	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the admin-specific stylesheet and JavaScript.
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/public
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership_Public {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string $plugin_name       The name of the plugin.
		 * @param      string $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;

		}

		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Chargebee_Membership_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Chargebee_Membership_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/chargebee-membership-public.css', array(), $this->version, 'all' );

		}

		/**
		 * Register the JavaScript for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Chargebee_Membership_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Chargebee_Membership_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/chargebee-membership-public.js', array( 'jquery', 'wp-util' ), $this->version, false );
			$valid_tags = array(
				'strong' => array(),
				'b'      => array(),
			);
			// Localize the script with validation messages with translation support.
			$translation_array = array(
				'error_label'              => wp_kses( __( '<strong>Error :</strong>', 'chargebee-membership' ), $valid_tags ),
				'empty_username'           => esc_html__( 'Please enter a username', 'chargebee-membership' ),
				'empty_email'              => esc_html__( 'Please enter a email', 'chargebee-membership' ),
				'empty_password'           => esc_html__( 'Please enter password', 'chargebee-membership' ),
				'empty_confirm_password'   => esc_html__( 'Please fill up confirm password', 'chargebee-membership' ),
				'password_not_match'       => esc_html__( 'Passwords do not match', 'chargebee-membership' ),
				'existing_subscription'    => esc_html__( 'You already have subscription.', 'chargebee-membership' ),
				'not_exist_product'        => esc_html__( 'This product doesn\'t exists.', 'chargebee-membership' ),
				'payment_failed'           => esc_html__( 'Sorry, Payment failed. Please try again.', 'chargebee-membership' ),
				'confirm_redirect_payment' => esc_html__( 'You don\'t have credit card details available. You will be redirected to update payment method page. Click OK to continue.', 'chargebee-membership' ),
			);
			wp_localize_script( $this->plugin_name, 'cbm_validation_msg', $translation_array );
		}

	}

}
