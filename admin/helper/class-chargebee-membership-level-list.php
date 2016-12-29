<?php
if ( ! class_exists( 'Chargebee_Membership_Level_List' ) ) {
	/**
	 * Class to List Chargebee Levels
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Level_List extends WP_List_Table {

		/**
		 * Chargebee custom table : level name.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $table_name The table name of Custom table level.
		 */
		private $table_name;

		/**
		 * Chargebee custom table : level-product relationship name.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $table_name The table name of Custom table level.
		 */
		private $level_product_rel_table_name;


		/**
		 * Chargebee custom table : product name.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $table_name The table name of Custom table product.
		 */
		private $product_table_name;

		/**
		 * Constructor of Chargebee_Membership_Level_List class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			parent::__construct( array(
				                     'singular' => __( 'Level', 'chargebee-membership' ),
				                     'plural'   => __( 'Levels', 'chargebee-membership' ),
				                     'ajax'     => false,
			) );
			$this->table_name                   = CHARGEBEE_MEMBERSHIP_TABLE_LEVEL;
			$this->level_product_rel_table_name = CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION;
			$this->product_table_name           = CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT;
		}

		/**
		 * Retrieve levels’s data from the database
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param   int  $per_page      Show levels per page in listing.
		 * @param   int  $page_number   Current page number for pagination.
		 * @param   bool $all           Check if need all levels or not.
		 *
		 * @return array    levels array.
		 */
		public function get_levels( $per_page = 5, $page_number = 1, $all = false ) {
			global $wpdb;
			$result         = array();
			$sql            = "SELECT id,level_name,level_description FROM {$this->table_name}";
			$input_order_by = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
			$input_order    = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
			$order_by       = ! empty( $input_order_by ) ? $input_order_by : '';
			$order          = ! empty( $input_order ) ? $input_order : 'ASC';

			if ( ! empty( $order_by ) && ! empty( $order ) ) {
				$sql .= ' ORDER BY ' . sanitize_sql_orderby( $order_by . ' ' . $order );
			}

			// Check if user needs all records.
			if ( ! $all ) {
				$sql .= " LIMIT $per_page";
				$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
			}

			$levels = $wpdb->get_results( $sql, 'ARRAY_A' );

			foreach ( $levels as $key => $level ) {
				$level_id = ! empty( $level['id'] ) ? esc_sql( $level['id'] ) : 0;
				if ( ! empty( $level_id ) ) {
					$products_query          = $wpdb->prepare( "SELECT wcp.product_id from {$this->level_product_rel_table_name} wclr LEFT JOIN {$this->product_table_name}  wcp on ( wclr.product_id = wcp.id ) WHERE wclr.level_id = %d", $level_id );
					$products                = $wpdb->get_col( $products_query );
					$level['level_products'] = implode( ',', $products );
					$result[]                = $level;
				}
			}

			return $result;
		}

		/**
		 * Retrieve level’s data from id from the database
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param int $id level's id.
		 *
		 * @return object   single level object.
		 */
		public function get_level( $id ) {
			global $wpdb;
			$sql    = $wpdb->prepare( "SELECT id,level_name,level_description FROM {$this->table_name} where id=%d", $id );
			$result = $wpdb->get_row( $sql );

			return $result;
		}

		/**
		 * Retrieve product ids by level id from the database
		 *
		 * @since   1.0.0
		 * @access  public
		 *
		 * @param int $id level's id.
		 *
		 * @return object   product_id object by level's id.
		 */
		public function get_products_of_level( $id ) {
			global $wpdb;
			$sql    = $wpdb->prepare( "SELECT product_id FROM {$this->level_product_rel_table_name} where level_id=%d", $id );
			$result = $wpdb->get_col( $sql );

			return $result;
		}

		/**
		 * Delete a level record.
		 *
		 * @since    1.0.0
		 *
		 * @access  public
		 *
		 * @param int $id level ID.
		 */
		public function delete_level( $id ) {
			global $wpdb;
			/**
			 * Before delete level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_before_delete_level' );

			$wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

			$wpdb->delete( $this->level_product_rel_table_name, array( 'level_id' => $id ), array( '%d' ) );

			/**
			 * After delete level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_after_delete_level' );
		}

		/**
		 * Returns the count of records in the database.
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @return null|string  total record count in level table.
		 */
		public function record_count() {
			global $wpdb;

			$sql = "SELECT COUNT(*) FROM {$this->table_name}";

			return $wpdb->get_var( $sql );
		}

		/**
		 * Text displayed when no level data is available.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function no_items() {
			echo esc_html__( 'No Levels avaliable.', 'chargebee-membership' );
		}

		/**
		 * Method for level id column
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param array $item an array of DB data.
		 *
		 * @return string
		 */
		public function column_level_name( $item ) {
			// create a nonce for delete level.
			if ( is_array( $item ) && is_user_logged_in() ) {
				$item_id = ! empty( $item['id'] ) ? absint( $item['id'] ) : 0;
				if ( ! empty( $item_id ) ) {
					$user_id      = get_current_user_id();
					$delete_nonce = wp_create_nonce( 'cbm_delete_level_' . $user_id . '_' . $item_id );
					$edit_nonce   = wp_create_nonce( 'cbm_edit_level_' . $user_id . '_' . $item_id );

					$title                = ! empty( $item['level_name'] ) ? '<strong>' . $item['level_name'] . '</strong>' : '';
					$input_requested_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
					$requested_page       = ! empty( $input_requested_page ) ? $input_requested_page : '';
					$delete_link          = sprintf( '?page=%s&action=%s&level=%s&_wpnonce=%s', urlencode( $requested_page ), 'delete', urlencode( $item_id ), urlencode( $delete_nonce ) );
					$edit_link            = sprintf( '?page=%s&action=%s&level=%s&_wpnonce=%s', urlencode( $requested_page ), 'edit', urlencode( $item_id ), urlencode( $edit_nonce ) );
					$actions              = array(
						'delete' => sprintf( '<a href="%s">Delete</a>', esc_url( $delete_link ) ),
						'Edit'   => sprintf( '<a href="%s">Edit</a>', esc_url( $edit_link ) ),
					);

					return $title . $this->row_actions( $actions );
				}
			}

			return false;
		}

		/**
		 * Render a column when no column specific method exists.
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param array  $item level column value.
		 * @param string $column_name level column key.
		 *
		 * @return mixed
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'id':
				case 'level_name':
				case 'level_description':
				case 'level_products':
				default:
					return $item[ $column_name ];
			}
		}

		/**
		 * Render the bulk delete checkbox.
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @param array $item level object.
		 *
		 * @return string   checkbox markup.
		 */
		public function column_cb( $item ) {
			if ( ! is_array( $item ) ) {
				return false;
			}

			return sprintf( '<input type="checkbox" name="bulk-delete[]" value="%s" />', esc_attr( $item['id'] ) );
		}

		/**
		 * Associative array of columns
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @return array    columns.
		 */
		public function get_columns() {
			$columns = array(
				'cb'                => '<input type="checkbox" />',
				'level_name'        => __( 'Name', 'chargebee-membership' ),
				'id'                => __( 'Level ID', 'chargebee-membership' ),
				'level_description' => __( 'Description', 'chargebee-membership' ),
				'level_products'    => __( 'Products', 'chargebee-membership' ),
			);

			return $columns;
		}

		/**
		 * Columns to make sortable.
		 *
		 * @since    1.0.0
		 * @access  public
		 *
		 * @return array    columns to make it sortable.
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'id'         => array( 'id', true ),
				'level_name' => array( 'level_name', true ),
			);

			return $sortable_columns;
		}

		/**
		 * Returns an associative array containing the bulk action
		 *
		 * @since    1.0.0
		 *
		 * @return array    array of actions for levels.
		 */
		public function get_bulk_actions() {
			$actions = array(
				'bulk-delete' => 'Delete',
			);

			return $actions;
		}

		/**
		 * Handles data query and filter, sorting, and pagination.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			/** Process bulk action */
			$this->process_bulk_action();

			$per_page     = $this->get_items_per_page( 'levels_per_page', 5 );
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args( array(
				                            'total_items' => $total_items,
				                            'per_page'    => $per_page,
			) );

			$this->items = self::get_levels( $per_page, $current_page );
		}

		/**
		 * Levels bulk delete actions.
		 *
		 * @since    1.0.0
		 * @access  public
		 */
		public function process_bulk_action() {

			// Detect when a bulk action is being triggered...
			if ( 'delete' === $this->current_action() && is_user_logged_in() ) {
				$user_id       = get_current_user_id();
				$input_wpnonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
				$input_level   = filter_input( INPUT_GET, 'level', FILTER_SANITIZE_NUMBER_INT );
				$delete_nonce  = ! empty( $input_wpnonce ) ? $input_wpnonce : '';
				$level_id      = ! empty( $input_level ) ? $input_level : '';
				if ( ! empty( $delete_nonce ) ) {
					// In our file that handles the request, verify the nonce.
					if ( ! wp_verify_nonce( $delete_nonce, 'cbm_delete_level_' . $user_id . '_' . $level_id ) ) {
						wp_die( esc_html__( 'Go get a life script kiddies', 'chargebee-membership' ) );
					} else {
						self::delete_level( $level_id );
					}
				}
			}

			// If the delete bulk action is triggered.
			if ( 'bulk-delete' === $this->current_action() ) {
				$input_bulk_delete = filter_input( INPUT_POST, 'bulk-delete', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
				$delete_ids        = ! empty( $input_bulk_delete ) ? $input_bulk_delete : '';
				if ( ! empty( $delete_ids ) ) {
					// loop over the array of record IDs and delete them.
					foreach ( $delete_ids as $id ) {
						self::delete_level( $id );
					}
				}
			}

		}
	}

}// End if().
