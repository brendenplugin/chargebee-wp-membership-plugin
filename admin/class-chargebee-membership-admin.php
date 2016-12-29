<?php
if ( ! class_exists( 'Chargebee_Membership_Admin' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * @link       https://www.chargebee.com
	 * @since      1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/admin
	 */

	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the admin-specific stylesheet and JavaScript.
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/admin
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership_Admin {

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
		 * Product list object
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $version    Product list object
		 */
		public $products_obj;

		/**
		 * Level list object
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $version    Level list object
		 */
		public $level_obj;

		/**
		 * Level list object
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $version    Level list object
		 */
		public $metabox;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string $plugin_name       The name of this plugin.
		 * @param      string $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;
			$this->metabox     = new Chargebee_Membership_Metabox();

			// Admin Menu creation.
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

			// Screen set for products.
			add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );

		}

		/**
		 * Register the stylesheets for the admin area.
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

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/chargebee-membership-admin.css', array(), $this->version, 'all' );

		}

		/**
		 * Register the JavaScript for the admin area.
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

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/chargebee-membership-admin.js', array( 'jquery', 'wp-util' ), $this->version, false );

			// Localize script for nonce to create customer account.
			$acnt_nonce = wp_create_nonce( 'cbm-create-account' );
			wp_localize_script( $this->plugin_name, 'cbm_create_acnt_nonce', $acnt_nonce );
		}

		/**
		 * Create admin menu for chargebee membership options/settings.
		 *
		 * @since    1.0.0
		 */
		public function add_admin_menu() {
			// Add Parent menu of chargebee.
			add_menu_page( __( 'Chargebee Membership', 'chargebee-membership' ), __( 'Chargebee', 'chargebee-membership' ), 'manage_options', 'chargebee-membership-admin', null, 'dashicons-tickets', 6 );

			// Add Submenu for Products.
			$product_hook = add_submenu_page( 'chargebee-membership-admin', __( 'Products', 'chargebee-membership' ), __( 'Products', 'chargebee-membership' ), 'manage_options', 'chargebee-membership-products', array( $this, 'chargebee_admin_products' ) );

			// Add Submenu for Levels.
			$level_hook = add_submenu_page( 'chargebee-membership-admin', __( 'Levels', 'chargebee-membership' ), __( 'Levels', 'chargebee-membership' ), 'manage_options', 'chargebee-membership-levels', array( $this, 'chargebee_admin_levels' ) );

			// Add Submenu for Settings.
			add_submenu_page( 'chargebee-membership-admin', __( 'Settings', 'chargebee-membership' ), __( 'Settings', 'chargebee-membership' ), 'manage_options', 'chargebee-membership-settings', array( $this, 'chargebee_admin_settings' ) );

			// Remove duplicate chargebee submenu.
			remove_submenu_page( 'chargebee-membership-admin','chargebee-membership-admin' );

			// Actions for list table of product and level.
			add_action( "load-$product_hook", array( $this, 'product_screen_option' ) );
			add_action( "load-$level_hook", array( $this, 'level_screen_option' ) );

		}


		/**
		 * Set screen for products
		 *
		 * @since   1.0.0
		 *
		 * @param 	bool   $status     status of screen.
		 * @param 	string $option 	The option name.
		 * @param 	int    $value  	The number of rows to use.
		 * @return 	int
		 */
		public static function set_screen( $status, $option, $value ) {
			return $value;
		}

		/**
		 * Product Screen options
		 *
		 * @since    1.0.0
		 */
		public function product_screen_option() {

			$option = 'per_page';
			$args   = array(
				'label'   => 'Products',
				'default' => 5,
				'option'  => 'products_per_page',
			);

			add_screen_option( $option, $args );

			$this->products_obj = new Chargebee_Membership_Product_List();
		}

		/**
		 * Product Screen options
		 *
		 * @since    1.0.0
		 */
		public function level_screen_option() {

			$option = 'per_page';

			$args   = array(
				'label'   => 'Levels',
				'default' => 5,
				'option'  => 'levels_per_page',
			);

			add_screen_option( $option, $args );
			$this->level_obj    = new Chargebee_Membership_Level_List();
		}

		/**
		 * Products page for Chargebee.
		 *
		 * @since    1.0.0
		 */
		public function chargebee_admin_products() {
			include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-product-page.php';
		}

		/**
		 * Levels page for Chargebee.
		 *
		 * @since    1.0.0
		 */
		public function chargebee_admin_levels() {
			include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-level-page.php';
		}

		/**
		 * Setting page for Chargebee.
		 *
		 * @since    1.0.0
		 */
		public function chargebee_admin_settings() {
			// include settings page html file.
			include_once plugin_dir_path( __FILE__ ) . '/partials/chargebee-settings.php';
		}
	}

}
