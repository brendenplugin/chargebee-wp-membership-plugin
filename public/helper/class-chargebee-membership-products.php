<?php
if ( ! class_exists( 'Chargebee_Membership_Products' ) ) {
	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition products functionality
	 *
	 * @link       https://www.chargebee.com
	 * @since      1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/public/helper
	 */

	/**
	 * The core plugin class.
	 *
	 * @since      1.0.0
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/public/helper
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership_Products {

		/**
		 * The product page id.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $product_page_id The product page id.
		 */
		protected $product_page_id;

		/**
		 * The product page slug.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $product_page_slug The product page slug.
		 */
		protected $product_page_slug;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			$pages           = get_option( 'cbm_pages' );
			$product_page_id = $pages['cbm_product_page'];

			if ( ! empty( $product_page_id ) ) {
				$page = get_post( $product_page_id );
				if ( ! empty( $page ) && $page instanceof WP_Post ) {
					$this->product_page_id   = $page->ID;
					$this->product_page_slug = $page->post_name;
				}
			}
		}

		/**
		 * Register the filters and actions with WordPress.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function run() {
			add_filter( 'query_vars', array( $this, 'rewrite_rule_query_vars' ) );
			add_action( 'init', array( $this, 'rewrite_handler' ) );
			add_action( 'template_redirect', array( $this, 'product_single_template_redirect' ) );
		}

		/**
		 * Adding _product_slug query var using 'query_vars' filter.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array $vars Array of WP_Query query_vars.
		 *
		 * @return array
		 */
		public function rewrite_rule_query_vars( $vars ) {
			$vars[] = '_product_slug';

			return $vars;
		}

		/**
		 *  Rewrite rule for product single page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function rewrite_handler() {
			add_rewrite_rule( '^' . $this->product_page_slug . '/([^/]*)/?', 'index.php?_product_slug=$matches[1]&page_id=' . $this->product_page_id . '', 'top' );
		}

		/**
		 * Add 'the_content' filter on product page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		function product_single_template_redirect() {
			if ( is_page( $this->product_page_slug ) ) {
				add_filter( 'the_content', array( $this, 'product_page_content' ) );
			}
		}

		/**
		 * Overide the content for product single page.
		 *
		 * @since      1.0.0
		 *
		 * @param string $content content of product page.
		 * @return string
		 */
		function product_page_content( $content ) {
			ob_start();
			require_once CHARGEBEE_MEMBERSHIP_PATH . 'public/partials/chargebee-membership-public-product.php';
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}
	}
}
