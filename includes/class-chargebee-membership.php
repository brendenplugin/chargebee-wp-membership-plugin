<?php
if ( ! class_exists( 'Chargebee_Membership' ) ) {
	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition that includes attributes and functions used across both the
	 * public-facing side of the site and the admin area.
	 *
	 * @link       https://www.chargebee.com
	 * @since      1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * Products that's responsible for maintaining and registering all product related functionality of
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Products    $products    Maintaining and registering all product related functionality for the plugin.
		 */
		protected $products;

		/**
		 * Shortcodes creation and maintainance.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Product_Shortcodes    $shortcode    Create shortcodes and maintain them
		 */
		protected $shortcodes;

		/**
		 * Class instance to handle login request and chargebee customer login.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Login    $shortcode    login request handle
		 */
		protected $cbm_login;

		/**
		 * Class instance to Add Extra fields and save its data.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Customer_Extra_Fields    $customer_extra_fields    add extra fields to user profile
		 */
		protected $customer_extra_fields;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;

		/**
		 * Chargebee_Membership_Restrict_Content Class Object to restrict content by user subscription level.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Restrict_Content    $restrict_content    Object of Chargebee_Membership_Restrict_Content Class.
		 */
		protected $restrict_content;

		/**
		 * Chargebee_Membership_Webhook Class Object to handle webhook from Chargebee Events.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Chargebee_Membership_Webhook    $webhook    Object of Chargebee_Membership_Webhook Class.
		 */
		protected $webhook;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

			$this->plugin_name = 'chargebee-membership';
			$this->version = '1.0.0';

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->define_settings();

			// Display the admin notification for api key.
			add_action( 'admin_notices', array( $this, 'plugin_activation' ) );

			// Set admin bar and dashboard permission for chargebee user.
			add_action( 'init',  array( $this, 'permissions_settings' ) );
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Chargebee_Membership_Loader. Orchestrates the hooks of the plugin.
		 * - Chargebee_Membership_i18n. Defines internationalization functionality.
		 * - Chargebee_Membership_Admin. Defines all hooks for the admin area.
		 * - Chargebee_Membership_Public. Defines all hooks for the public side of the site.
		 * - Chargebee_Membership_Webhook. Handle all webhooks from Chargebee events.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-chargebee-membership-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-chargebee-membership-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-chargebee-membership-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-chargebee-membership-public.php';

			/**
			 * The class responsible for handle Chargebee webhook events.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-chargebee-membership-webhook.php';

			// include class for list table of products.
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			}

			/**
			 * Directory array for include files.
			 */
			$include_files = array(
				CHARGEBEE_MEMBERSHIP_PATH . 'public/helper/*.php',
				CHARGEBEE_MEMBERSHIP_PATH . 'admin/helper/*.php',
			);

			foreach ( $include_files as $include_file ) {
				foreach ( glob( $include_file ) as $filename ) {
					require_once $filename;
				}
			}

			$this->loader = new Chargebee_Membership_Loader();

			/**
			 * Initialize Product Class
			 */
			$this->products = new Chargebee_Membership_Products();

			/**
			 * Initialize Shortcode Class
			 */
			$this->shortcodes = new Chargebee_Membership_Product_Shortcodes();

			/**
			 * Initialize Login Class
			 */
			$this->cbm_login = new Chargebee_Membership_Login();

			/**
			 * Initialize Customer Extra Fields Class.
			 */
			$this->customer_extra_fields = new Chargebee_Membership_Customer_Extra_Fields();

			/**
			 * Initialize restrict content Class.
			 */
			$this->restrict_content = new Chargebee_Membership_Restrict_Content();

			/**
			 * Initialize webhook handler class.
			 */
			$this->webhook = new Chargebee_Membership_Webhook();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Chargebee_Membership_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale() {

			$plugin_i18n = new Chargebee_Membership_i18n();

			$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {

			$plugin_admin = new Chargebee_Membership_Admin( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		}

		/**
		 * Register all of the hooks related to the settings.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_settings() {

			// Construct Chargebee_Membership_Request for settings.
			$cbs = new Chargebee_Membership_Settings;
		}
		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks() {

			$plugin_public = new Chargebee_Membership_Public( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function run() {
			$this->loader->run();
			$this->products->run();
			$this->shortcodes->run();
			$this->cbm_login->run();
			$this->customer_extra_fields->run();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return    Chargebee_Membership_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return    string    The version number of the plugin.
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Custom message when plugin is activated.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		function plugin_activation() {
			$api_key = get_option( 'cbm_api_key' );
			// Check if API key is present in option.
			if ( false === $api_key || empty( $api_key ) ) {
				// Admin url.
				$url = admin_url( 'admin.php?page=chargebee-membership-settings&tab=integration' );
				$valid_tags = array(
					'a' => array(
		             	'href' => array(),
				 	),
				);
				?>
				<div class="notice notice-warning">
					<p>
						<?php
						printf(
			             	wp_kses(
		 	        			__( 'Chargebee needs to be setup. Click <a href="%s">here</a> to complete the setup.', 'chargebee-membership' ),
								$valid_tags
							),
			             	esc_url( $url )
		             	);
						?>
					</p>
				</div><!-- /.updated -->
				<?php
			}
		}

		/**
		 * Permission settings for Chargebee members like admin bar and dashboard access.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function permissions_settings() {
			$value = get_option( 'cbm_account' );

			if ( true === is_user_logged_in() ) {
				// Get current user's role.
				global $current_user;

				// Admin bar accessibility to Chargebee Member.
				if ( ! empty( $value['cbm_adminbar_display'] ) && '1' === $value['cbm_adminbar_display'] && in_array( 'chargebee_member', $current_user->roles, true ) ) {
					show_admin_bar( false );
				}

				// Dashboard accessibility to Chargebee Member.
				if ( ! empty( $value['cbm_dashboard_access'] ) && '1' === $value['cbm_dashboard_access'] && is_admin() && ! defined( 'DOING_AJAX' ) && in_array( 'chargebee_member', $current_user->roles, true ) ) {
					 wp_redirect( home_url() );
					 exit;
				}
			}
		}
	}

}
