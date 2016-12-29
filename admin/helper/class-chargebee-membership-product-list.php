<?php
if ( ! class_exists( 'Chargebee_Membership_Product_List' ) ) {
	/**
	 * Class to List Chargebee Products
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Product_List extends WP_List_Table {

		/**
		 * Chargebee custom table product name.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $table_name The table name of Custom table level.
		 */
		private $table_name;

		/**
		 * Constructor of Chargebee_Membership_Product_List class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			$this->table_name = CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT;
			parent::__construct( array(
				                     'singular' => __( 'Product', 'chargebee-membership' ),
				                     'plural'   => __( 'Products', 'chargebee-membership' ),
				                     'ajax'     => false,
			) );
		}

		/**
		 * Retrieve products’s data from the database
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int  $per_page    Product per page to display.
		 * @param int  $page_number Current page number.
		 * @param bool $all         Check if need all product or not.
		 *
		 * @return mixed products to display.
		 */
		public function get_products( $per_page = 5, $page_number = 1, $all = false ) {
			global $wpdb;
			$sql            = "SELECT id, product_id, product_name, price, status, currency_code, period, period_unit FROM {$this->table_name}";
			$input_order_by = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
			$input_order    = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
			$order_by       = ! empty( $input_order_by ) ? $input_order_by : '';
			$order          = ! empty( $input_order ) ? $input_order : 'ASC';

			if ( ! $all ) {

				if ( ! empty( $orderby ) ) {
					$sql .= ' ORDER BY ' . sanitize_sql_orderby( $order_by . ' ' . $order );
				}

				$sql .= " LIMIT $per_page";

				$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
			}

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );

			return $result;
		}

		/**
		 * Get Free Products.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return array|null|object
		 */
		public function get_free_products() {
			global $wpdb;
			$sql = "SELECT id, product_id, product_name, price, status, currency_code, period, period_unit FROM {$this->table_name} WHERE price=0";

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );

			return $result;
		}

		/**
		 * Returns the count of records in the database.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return null|string total number of products.
		 */
		public function record_count() {
			global $wpdb;

			$sql = "SELECT COUNT(*) FROM {$this->table_name}";

			return $wpdb->get_var( $sql );
		}

		/** Text displayed when no Product data is available */
		public function no_items() {
			echo esc_html__( 'No Products avaliable.', 'chargebee-membership' );
		}

		/**
		 * Method for product id column
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array $item an array of DB data.
		 *
		 * @return string
		 */
		public function column_product_id( $item ) {

			$product_id = $item['product_id'];

			$input_requested_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
			$requested_page       = ! empty( $input_requested_page ) ? $input_requested_page : '';
			$title                = '<strong>' . esc_html( $product_id ) . '</strong>';

			$options = get_option( 'cbm_pages' );

			$product_slug = '';
			if( ! empty( $options['cbm_product_page'] ) ) {
				$product_page = get_post( $options['cbm_product_page'] );
				if ( ! empty( $product_page ) && $product_page instanceof WP_Post ) {
					$product_slug = $product_page->post_name;
				}
			}

			$view_link = site_url() . '/' . $product_slug . '/' . $item['product_id'];
			$edit_link = admin_url() . 'admin.php?page=' . $requested_page . '&action=edit&product=' . $product_id;

			$actions = array(
				'Edit' => sprintf( '<a href="%s">Edit</a>', esc_url( $edit_link ) ),
				'View' => sprintf( '<a href="%s">View</a>', esc_url( $view_link ) ),
			);

			return $title . $this->row_actions( $actions );
		}

		/**
		 * Render a column when no column specific method exists.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array  $item column values.
		 * @param string $column_name column name.
		 *
		 * @return mixed    column values.
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'product_id':
				case 'status':
				case 'product_name':
					return $item[ $column_name ];
				case 'price':
					// format 20USD/1 Month.
					return $item[ $column_name ] . ' ' . $item['currency_code'] . ' / ' . $item['period'] . ' ' . $item['period_unit'];
			}
		}

		/**
		 *  Associative array of columns
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return array    array of columns to display.
		 */
		public function get_columns() {
			$columns = array(
				'product_id'   => __( 'Product ID', 'chargebee-membership' ),
				'product_name' => __( 'Name', 'chargebee-membership' ),
				'price'        => __( 'Price', 'chargebee-membership' ),
				'status'       => __( 'Status', 'chargebee-membership' ),
			);

			return $columns;
		}

		/**
		 * Columns to make sortable.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @return array    sortable columns.
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'product_id' => array( 'product_id', true ),
				'price'      => array( 'price', true ),
			);

			return $sortable_columns;
		}

		/**
		 * Handles data query and filter, sorting, and pagination.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			$per_page     = $this->get_items_per_page( 'products_per_page', 9 );
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args( array(
				                            'total_items' => $total_items,
				                            'per_page'    => $per_page,
			) );

			$this->items = self::get_products( $per_page, $current_page );
		}
	}
}// End if().
