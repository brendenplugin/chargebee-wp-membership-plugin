<?php
if ( ! class_exists( 'Chargebee_Membership_User_Notification' ) ) {

	/**
	 * Class For chargebee user notification table queries.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_User_Notification {

		/**
		 * Insert User notification.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int    $user_id wp user id to add notification msg.
		 * @param string $msg notification message.
		 * @param string $subscription_id subscription id.
		 *
		 * @return bool
		 */
		public static function insert_notification( $user_id, $msg, $subscription_id = '' ) {
			global $wpdb;

			$wpdb->insert( CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION, array(
				'user_id'         => ! empty( $user_id ) ? esc_sql( $user_id ) : '',
				'user_notify_msg' => ! empty( $msg ) ? esc_sql( $msg ) : '',
				'subscription_id' => ! empty( $subscription_id ) ? esc_sql( $subscription_id ) : '',
			), array( '%d', '%s', '%s' ) );

			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Select all notification by user id.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int $user_id wp user id to select all notification messages.
		 *
		 * @return array|null|object
		 */
		public static function get_notifications( $user_id ) {
			global $wpdb;
			$query         = $wpdb->prepare( 'select * from ' . CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION . ' where user_id=%d', esc_sql( $user_id ) );
			$notifications = $wpdb->get_results( $query );

			return $notifications;
		}

		/**
		 * Delete notification by notification id.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int $id notification id.
		 *
		 * @return bool
		 */
		public static function delete_notification( $id ) {
			global $wpdb;
			$wpdb->delete( CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION, array( 'id' => esc_sql( $id ) ), array( '%d' ) );
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Delete notification by notification id.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int $subscription_id Subscription id.
		 *
		 * @return bool
		 */
		public static function delete_notification_by_subscription_id( $subscription_id ) {
			global $wpdb;
			$wpdb->delete( CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION, array( 'subscription_id' => esc_sql( $subscription_id ) ), array( '%s' ) );
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			return true;
		}
	}
}// End if().
