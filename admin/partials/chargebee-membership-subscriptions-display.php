<?php
/**
 * File to display subscriptions of customer.
 *
 * @since    1.0.0
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/includes
 */

// If subscriptions not empty then display into table.
if ( ! empty( $subscriptions ) ) {
	?>
	<h3><?php esc_html_e( 'Your Subscriptions', 'chargebee-membership' ) ?></h3>
	<table class="cbm-subscription-table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Product Name', 'chargebee-membership' ); ?></th>
			<th><?php esc_html_e( 'Description', 'chargebee-membership' ); ?></th>
			<th><?php esc_html_e( 'Status', 'chargebee-membership' ); ?></th>
			<th><?php esc_html_e( 'Product Price', 'chargebee-membership' ); ?></th>
			<th><?php esc_html_e( 'Trial Start', 'chargebee-membership' ); ?></th>
			<th><?php esc_html_e( 'Trial End', 'chargebee-membership' ); ?></th>
		</tr>
		</thead>
		<tbody>
		</tbody>
		<?php
		foreach ( $subscriptions as $key => $value ) {
			?>
			<tr>
				<td><?php echo ! empty( $value['product_name'] ) ? esc_html( $value['product_name'] ) : ''; ?></td>
				<td><?php echo ! empty( $value['product_decs'] ) ? esc_html( $value['product_decs'] ) : ''; ?></td>
				<td><?php echo ! empty( $value['status'] ) ? esc_html( $value['status'] ) : '';
				if ( ! empty( $value['status'] ) && 'cancelled' === $value['status'] && ! empty( $value['subscription_id'] ) && ! empty( $value['product_id'] ) ) {
					$subscription_id         = $value['subscription_id'];
					$plan_id                 = $value['product_id'];
					$product_page_url        = get_cbm_page_link( 'pricing' );
					$product_single_page_url = $product_page_url . $plan_id;
					$product_reactivate_url  = $product_single_page_url . '?subscription_id=' . esc_attr( $subscription_id );
					echo '<br/><a class="cbm-reactivate-subscription" href="' . esc_attr( $product_reactivate_url ) . '" title="' . esc_html__( 'Reactivate', 'chargebee-membership' ) . '">' . esc_html__( 'Reactivate', 'chargebee-membership' ) . '</a>';
				}
					?>
				</td>
				<td><?php echo ! empty( $value['product_price'] ) ? esc_html( $value['product_price'] ) : ''; ?></td>
				<td><?php echo ! empty( $value['trail_start'] ) ? esc_html( $value['trail_start'] ) : esc_html__( 'Not Available', 'chargebee-membership' ); ?></td>
				<td><?php echo ! empty( $value['trial_end'] ) ? esc_html( $value['trial_end'] ) : esc_html__( 'Not Available', 'chargebee-membership' ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php
}