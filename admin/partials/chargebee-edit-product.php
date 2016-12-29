<?php
/**
 * Edit Product page of chargebee product
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/admin
 */
?>
<?php
// Error if ABSPATH not define.
if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'You are not allowed to call this page directly.','chargebee-membership' ) );
}
?>
<div class="wrap">
	<div class="icon32"></div>
	<div style="display: none"  class="cbm_edit_messages">
		<p></p>
	</div>
	<h2><?php esc_html_e( 'Edit Product', 'chargebee-membership' ); ?></h2>

	<?php

	// Get product details.
	$product_data = Chargebee_Membership_Product_Query::get_product_data( $product_id );

	?>
	<div class="form-wrap">
		<form action="" method="post" id="product_edit_form">
			<?php if ( isset( $product_id ) ) :  ?>
				<input type="hidden" name="product_id" id="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
			<?php endif; ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Product ID', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_id ); ?></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Name', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_data->product_name ); ?></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Price', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_data->price . ' ' . $product_data->currency_code . ' / ' . $product_data->period . ' ' . $product_data->period_unit ); ?></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Status', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_data->status ); ?></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Description', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_data->description ); ?></td>
					</tr>

					<tr valign="top">
						<th>
							<?php esc_html_e( 'Content', 'chargebee-membership' ); ?>
						</th>
						<td>
						<?php
							$settings = array( 'textarea_rows' => 5 );
							wp_editor( $product_data->content, 'product_content', $settings );
						?>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Trial Period', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo ( ! empty( $product_data->trial_period ) ? esc_html( $product_data->trial_period . ' ' . $product_data->trial_period_unit ) : esc_html__( 'Not Available', 'chargebee-membership' ) ); ?></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Charge Model', 'chargebee-membership' ); ?>
						</th>
						<td><?php echo esc_html( $product_data->charge_model ); ?></td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="hidden" id="cbm_edit_product_nonce" name="cbm_edit_product_nonce" value="<?php echo esc_html( wp_create_nonce( 'cbm_edit_product_nonce' ) ); ?>"" />
				<input type="submit" id="submit" class="button button-primary cbm_edit_product" value="<?php esc_attr_e( 'Update', 'chargebee-membership' ); ?>" />
			</p>
		</form>
	</div>

</div>
<?php
