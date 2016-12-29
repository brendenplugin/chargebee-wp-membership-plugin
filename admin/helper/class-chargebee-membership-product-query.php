<?php
if ( ! class_exists( 'Chargebee_Membership_Product_Query' ) ) {

	/**
	 * Class For chargebee product queries.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Product_Query {

		/**
		 * Function to insert all products imported from API key.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param    array $res_body product list.
		 *
		 * @return bool
		 */
		public static function insert_imported_products( $res_body ) {
			global $wpdb;

			if ( empty( $res_body ) ) {
				return false;
			}

			if ( empty( $res_body->list ) ) {
				return false;
			}

			// Insert into Products table.
			foreach ( $res_body->list as $key => $value ) {
				if ( empty( $value ) && ! is_object( $value ) ) {
					continue;
				}

				$plan                 = ! empty( $value->plan ) ? $value->plan : '';
				$price                = ! empty( $plan->price ) ? $plan->price : '';
				$currency_code        = ! empty( $plan->currency_code ) ? $plan->currency_code : '';
				// List of zero decimal currencies supported/will be supported by Chargebee system.
				$zero_decimal_country = array(
					'KRW',
					'JPY',
					'BIF',
					'DJF',
					'PYG',
					'VND',
					'CLP',
					'GNF',
					'RWF',
					'VUV',
					'XAF',
					'XOF',
					'XPF',
					'MGA',
					'KMF',
					'ALL',
					'BYR',
				);

				// If in USD then convert cent into dollars.
				if ( ! empty( $currency_code ) && ! empty( $price ) && ( ! in_array( $currency_code, $zero_decimal_country, true ) ) ) {
					$price = intval( $price ) / 100;
				}
				
				if(empty( $plan->id )){
					return false;
				}
				$sql = "SELECT id FROM " . CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT . " where product_id='" . $plan->id . "'";
				$PRODUCT_ID=$wpdb->get_var( $sql );

				if(empty($PRODUCT_ID)){
				   $wpdb->insert(
					CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT,
					array(
                                                'id'                => null,
						'product_id'        => ! empty( $plan->id ) ? $plan->id : '',
						'product_name'      => ! empty( $plan->invoice_name ) ? $plan->invoice_name : '',
						'price'				=> ! empty( $price ) ? $price : '',
						'period'            => ! empty( $plan->period ) ? $plan->period : '',
						'period_unit'       => ! empty( $plan->period_unit ) ? $plan->period_unit : '',
						'trial_period'      => ! empty( $plan->trial_period ) ? $plan->trial_period : '',
						'trial_period_unit' => ! empty( $plan->trial_period_unit ) ? $plan->trial_period_unit : '',
						'status'            => ! empty( $plan->status ) ? $plan->status : '',
						'description'       => ! empty( $plan->description ) ? $plan->description : '',
						'currency_code'     => ! empty( $plan->currency_code ) ? $plan->currency_code : '',
						'charge_model'      => ! empty( $plan->charge_model ) ? $plan->charge_model : '',
					),
					array( '%d','%s', '%s', '%f', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
				); 
				}else{
				  $wpdb->replace(
					CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT,
					array(
                                                'id'                => $PRODUCT_ID,
						'product_id'        => ! empty( $plan->id ) ? $plan->id : '',
						'product_name'      => ! empty( $plan->invoice_name ) ? $plan->invoice_name : '',
						'price'				=> ! empty( $price ) ? $price : '',
						'period'            => ! empty( $plan->period ) ? $plan->period : '',
						'period_unit'       => ! empty( $plan->period_unit ) ? $plan->period_unit : '',
						'trial_period'      => ! empty( $plan->trial_period ) ? $plan->trial_period : '',
						'trial_period_unit' => ! empty( $plan->trial_period_unit ) ? $plan->trial_period_unit : '',
						'status'            => ! empty( $plan->status ) ? $plan->status : '',
						'description'       => ! empty( $plan->description ) ? $plan->description : '',
						'currency_code'     => ! empty( $plan->currency_code ) ? $plan->currency_code : '',
						'charge_model'      => ! empty( $plan->charge_model ) ? $plan->charge_model : '',
					),
					array( '%d','%s', '%s', '%f', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
				);
                                }
			}// End foreach().
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Function to return Product data from product id.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int $product_id  Product id to fetch content from custom table.
		 * @return object product data object
		 */
		public static function get_product_data( $product_id ) {
			global $wpdb;
			$str = 'SELECT * FROM ' . CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT . ' WHERE product_id=%s';
			$sql = $wpdb->prepare( $str, $product_id );

			$product_obj = $wpdb->get_row( $sql );

			return $product_obj;
		}

		/**
		 * Function to update product content and Hosted Checkout URL.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param    string $product_id 			product id.
		 * @param    string $content 				content to add.
		 */
		public static function update_product_data( $product_id, $content ) {
			global $wpdb;
			$wpdb->update( CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT,
				array(
					'content' => $content,
				),
				array( 'product_id' => $product_id ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}
}// End if().
