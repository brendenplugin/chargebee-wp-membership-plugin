<?php
if ( ! class_exists( 'Chargebee_Membership_Activator' ) ) {
	/**
	 * Fired during plugin activation
	 *
	 * @link       https://www.chargebee.com
	 * @since      1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */

	/**
	 * Fired during plugin activation.
	 *
	 * This class defines all code necessary to run during the plugin's activation.
	 *
	 * @since      1.0.0
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 * @author     rtcamp <plugin@rtcamp.com>
	 */
	class Chargebee_Membership_Activator {

		/**
		 * Activities to do while plugin get activate.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public static function activate() {
			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}

			$subscriber = $wp_roles->get_role( 'subscriber' );

			// Add New User Role for Chargebee Member.
			$wp_roles->add_role( 'chargebee_member', __( 'Chargebee Member', 'chargebee-membership' ), $subscriber->capabilities );
			self::reserved_pages();
			self::create_custom_tables();
			self::default_options();
		}

		/**
		 * Create custom table at activation time.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public static function create_custom_tables() {
			global $wpdb;
			$charset_collate                = $wpdb->get_charset_collate();
			$products_table_name            = CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT;
			$levels_table_name              = CHARGEBEE_MEMBERSHIP_TABLE_LEVEL;
			$level_relationships_table_name = CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION;
			$user_notification_table_name   = CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION;

			// Query to create table for products/plans.
			$product_table = "CREATE TABLE IF NOT EXISTS $products_table_name (
					`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
					`product_id` VARCHAR(100) NOT NULL UNIQUE,
					`product_name` VARCHAR(50) NOT NULL,
					`description` longtext,
					`content` longtext,
					`price` FLOAT NOT NULL,
					`currency_code` VARCHAR(10) NOT NULL,
					`period` SMALLINT NOT NULL,
					`period_unit` VARCHAR(5) NOT NULL,
					`trial_period` SMALLINT,
					`trial_period_unit` VARCHAR(5),
					`charge_model` VARCHAR(20),
					`status` VARCHAR(10) NOT NULL,
					PRIMARY KEY  (`id`)
				) $charset_collate;";

			$isProdcutTableCreated = $wpdb->query( $product_table );

			// Query to create table for levels.
			$levels_table = "CREATE TABLE IF NOT EXISTS $levels_table_name (
					`id` bigint(9) unsigned NOT NULL AUTO_INCREMENT,
					`level_name` VARCHAR(20) NOT NULL,
					`level_description` longtext,
					PRIMARY KEY  (`id`)
				) $charset_collate;";

			$isLevelTableCreated = $wpdb->query( $levels_table );

			// Query to create table for levels and product relationships.
			$level_relationships_table = "CREATE TABLE IF NOT EXISTS $level_relationships_table_name (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`level_id` bigint(20) unsigned NOT NULL DEFAULT '0',
					`product_id` bigint(20) unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY  (`id`)
				) $charset_collate;";

			$isLevelRelationshipTableCreated = $wpdb->query( $level_relationships_table );

			// Query to create table for user notification.
			$user_notification_table = "CREATE TABLE IF NOT EXISTS $user_notification_table_name (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
					`user_notify_msg` longtext,
					`subscription_id` VARCHAR(20),
					PRIMARY KEY  (`id`)
				) $charset_collate;";

			$isUserNotificationTableCreated = $wpdb->query( $user_notification_table );

		}

		/**
		 * Create reserved pages at activation time.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public static function reserved_pages() {
			// Create Reserved Pages.
			$reserved_pages_option = get_option( 'cbm_pages' );
			$reserved_pages = array();

			// Check if page is already set.
			if ( empty( $reserved_pages_option['cbm_login_page'] ) ) {
				$login_page = array(
					'post_type'    => 'page',
					'post_title'   => 'Login',
					'post_content' => '[cb_login_form]',
					'post_status'  => 'publish',
				);
				$val        = wp_insert_post( $login_page );

				// Check for error.
				if ( is_object( $val ) && is_wp_error( $val ) ) {
					$reserved_pages['cbm_login_page'] = '0';
				} else {
					$reserved_pages['cbm_login_page'] = $val;
				}
			} else {
				$reserved_pages['cbm_login_page'] = $reserved_pages_option['cbm_login_page'];
			}

			// Check if page is already set.
			if ( empty( $reserved_pages_option['cbm_registration_page'] ) ) {
				$registration_page = array(
					'post_type'    => 'page',
					'post_title'   => 'Registration',
					'post_content' => '[cb_registration_form]',
					'post_status'  => 'publish',
				);
				$val               = wp_insert_post( $registration_page );

				// Check for error.
				if ( is_object( $val ) && is_wp_error( $val ) ) {
					$reserved_pages['cbm_registration_page'] = '0';
				} else {
					$reserved_pages['cbm_registration_page'] = $val;
				}
			} else {
				$reserved_pages['cbm_registration_page'] = $reserved_pages_option['cbm_registration_page'];
			}

			// Check if page is already set.
			if ( empty( $reserved_pages_option['cbm_product_page'] ) ) {

				$product_page = array(
					'post_type'    => 'page',
					'post_title'   => 'Pricing',
					'post_content' => '',
					'post_status'  => 'publish',
				);
				$val          = wp_insert_post( $product_page );

				// Check for error.
				if ( is_object( $val ) && is_wp_error( $val ) ) {
					$reserved_pages['cbm_product_page'] = '0';
				} else {
					$reserved_pages['cbm_product_page'] = $val;
				}
			} else {
				$reserved_pages['cbm_product_page'] = $reserved_pages_option['cbm_product_page'];
			}

			// Check if page is already set.
			if ( empty( $reserved_pages_option['cbm_thankyou_page'] ) ) {

				$thankyou_page = array(
					'post_type'    => 'page',
					'post_title'   => 'Thank You',
					'post_content' => '',
					'post_status'  => 'publish',
				);
				$val           = wp_insert_post( $thankyou_page );

				// Check for error.
				if ( is_object( $val ) && is_wp_error( $val ) ) {
					$reserved_pages['cbm_thankyou_page'] = '0';
				} else {
					$reserved_pages['cbm_thankyou_page'] = $val;
				}
			} else {
				$reserved_pages['cbm_thankyou_page'] = $reserved_pages_option['cbm_thankyou_page'];
			}

			// Add ids of pages into option.
			update_option( 'cbm_pages', $reserved_pages );
		}

		/**
		 * Set default option values at activation time.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public static function default_options() {
			$permissions = get_option( 'cbm_account' );

			if ( empty( $permissions['cbm_adminbar_display'] ) || empty( $permissions['cbm_dashboard_access'] ) || empty( $permissions['cbm_logout_redirect'] ) ) {

				$pages = get_option( 'cbm_pages' );

				// set logout redirect url.
				if ( ! empty( $pages['cbm_login_page'] ) ) {
					$logout_url = basename( get_permalink( $pages['cbm_login_page'] ) );
				}

				$arr = array(
					'cbm_adminbar_display' => ! empty( $permissions['cbm_adminbar_display'] ) ? $permissions['cbm_adminbar_display'] : '1',
					'cbm_dashboard_access' => ! empty( $permissions['cbm_dashboard_access'] ) ? $permissions['cbm_dashboard_access'] : '1',
					'cbm_logout_redirect'  => ! empty( $permissions['cbm_logout_redirect'] ) ? $permissions['cbm_logout_redirect'] : $logout_url,
				);
				update_option( 'cbm_account', $arr );
			}

			// Set default value to content restriction message.
			$general = get_option( 'cbm_general' );
			if ( empty( $general['cbm_restriction_message'] ) ) {
				$general['cbm_restriction_message'] = esc_html__( 'This content can be accessed by only {user_level} users.', 'chargebee-membership' );

				update_option( 'cbm_general', $general );
			}
			// Set user registration ability to true.
			update_option( 'users_can_register', '1' );
		}
	}
}
