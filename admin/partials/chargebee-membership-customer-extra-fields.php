<?php
/**
 * Extra User fields for chargebee customer.
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/admin
 */

$cust_id = get_user_meta( $user->ID, 'chargebee_user_id', true );
?>
<h3><?php esc_html_e( 'Chargebee Customer Information', 'chargebee-membership' ); ?></h3>

	<table class="form-table">

		<tr>
			<th><label for="chargebee_user_id"><?php esc_html_e( 'Customer ID:', 'chargebee-membership' ); ?></label></th>

			<td>
				<input type="text" name="chargebee_user_id" id="chargebee_user_id" value="<?php echo esc_attr( $cust_id ); ?>" class="regular-text" <?php echo empty( $cust_id ) ? null : esc_attr( 'disabled' ) ?> /><br />
				<?php if ( empty( $cust_id ) ) { ?>
					<span class="description"><?php esc_html_e( 'Please enter Chargebee Customer Id for this user.', 'chargebee-membership' ); ?></span>
				<?php } ?>
			</td>
		</tr>
		<?php

		// Display button if customer id is empty.
		if ( empty( $cust_id ) ) {
		?>
			<tr>
				<td class="cbm_separator"><?php esc_html_e( 'OR', 'chargebee-membership' ); ?></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Create Chargebee User without Subscription:', 'chargebee-membership' ); ?></label></th>

				<td>
					<a class="button button-primary cbm-create-acnt" href="#" user-id="<?php echo esc_attr( $user->ID ); ?>"><?php esc_html_e( 'Create CB Account', 'chargebee-membership' ); ?></a>

					<span class="cbm-create-acnt-msg description"></span>
				</td>
			</tr>
		<?php
		}
		?>
	</table>

	<?php
	// If customer id is present then display user's subscriptions.
	if ( ! empty( $cust_id ) ) {

		// Get subscriptions from usermeta.
		$subscriptions = get_user_meta( $user->ID, 'chargebee_user_subscriptions', true );

		// If empty subscription then get subscriptions.
		if ( empty( $subscriptions ) ) {
			$url = 'subscriptions';
			$parameters = array(
				'customer_id[is]' => $cust_id,
			);

			$res = Chargebee_Membership_Request::chargebee_api_request( $url, $parameters );
			if ( ! empty( $res ) ) {

				$res_code = wp_remote_retrieve_response_code( $res );
				if ( 200 === $res_code ) {
					$list = json_Decode( wp_remote_retrieve_body( $res ) )->list;

					$new_subscriptions = array();

					// Add all subscription details into array.
					foreach ( $list as $key => $value ) {
						$sub = $value->subscription;
						$product = Chargebee_Membership_Product_Query::get_product_data( $sub->plan_id );
						$price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
						$new_subscriptions[] = array(
							'subscription_id'	=> $sub->id,
							'product_id'		=> $sub->plan_id,
							'product_name'      => $product->product_name,
							'product_decs'      => $product->description,
							'status'			=> $sub->status,
							'product_price'     => $price,
							'trail_start'       => ! empty( $sub->trial_start ) ? date( 'd/m/Y', $sub->trial_start ) : '',
							'trial_end'         => ! empty( $sub->trial_end ) ? date( 'd/m/Y', $sub->trial_end ) : '',
						);
					}

					// Add subscriptions into usermeta.
					update_user_meta( $user->ID, 'chargebee_user_subscriptions', $new_subscriptions );
					$subscriptions = $new_subscriptions;
				}
			}
		}

		include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-membership-subscriptions-display.php';
	}
	?>
<?php
