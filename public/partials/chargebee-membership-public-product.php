<?php

/**
 * Provide a public-facing view for product single page.
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.chargebee.com
 * @since      1.0.0
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/public/partials
 */

$product_slug                  = get_query_var( '_product_slug' );
$product_data                  = Chargebee_Membership_Product_Query::get_product_data( $product_slug );
$user_id                       = get_current_user_id();
$subscribe_product_nonce       = ( ! empty( $user_id ) && ! empty( $product_slug ) ) ? wp_create_nonce( 'cbm_subscribe_product_' . $product_slug . '_' . $user_id ) : '';
$input_product_nonce           = filter_input( INPUT_GET, 'cbm_product_nonce', FILTER_SANITIZE_STRING );
$get_subscribe_product_nonce   = ! empty( $input_product_nonce ) ? $input_product_nonce : '';
$confirm_subscribe_msg         = false;
$input_product_subscription_id = filter_input( INPUT_GET, 'subscription_id', FILTER_SANITIZE_STRING );
$get_product_subscription_id   = ! empty( $input_product_subscription_id ) ? $input_product_subscription_id : '';

// Check if product present in db.
if ( ! empty( $product_data ) ) {
	$price_unit = $product_data->period . ' ' . $product_data->period_unit;
	$product_price = $product_data->currency_code . ' ' . $product_data->price . ' / ' . $price_unit;
	// Display product details.
?>
	<div>
		<div class="cbm_errors" style="display:none;">
			<span class="error"><strong><?php esc_html_e( 'Error', 'chargebee-membership' ); ?></strong></span><br/>
		</div>
		<h1 itemprop="name" class="product_title entry-title">
			<?php echo esc_html( $product_data->product_name . ' - ' . $price_unit . ' Plan' ); ?>
		</h1>
		<div itemprop="description">
			<?php
			if ( ! empty( $get_subscribe_product_nonce ) && ! empty( $user_id ) && ! empty( $product_slug ) ) {
				// In our file that handles the request, verify the nonce.
				if ( wp_verify_nonce( $get_subscribe_product_nonce, 'cbm_subscribe_product_' . $product_slug . '_' . $user_id ) ) {
					$confirm_subscribe_msg = true;
				}
			}

			if ( $confirm_subscribe_msg || ! empty( $get_product_subscription_id ) ) {
				?>
				<table>
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Product ID:', 'chargebee-membership' ); ?></th>
							<td><?php echo esc_html( $product_slug ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Subscription Price:', 'chargebee-membership' ); ?></th>
							<td><?php echo esc_html( $product_price ); ?></td>
						</tr>
					</tbody>
				</table>
				<p>
					<?php
					/* translators: %s: product_price */
					echo sprintf( esc_html__( 'You will be charged %s for this subscription. Click on checkout button to complete the payment.', 'chargebee-membership' ), esc_attr( $product_price ) ); ?>
				</p>
				<?php
				if ( ! empty( $get_product_subscription_id ) ) {
					$product_reactivate_nonce = ( ! empty( $user_id ) && ! empty( $get_product_subscription_id ) ) ? wp_create_nonce( 'cbm_product_reactivate_' . $get_product_subscription_id . '_' . $user_id ) : '';
					?>
					<input type="hidden" id="cbm_subscription_id" value="<?php echo esc_attr( $get_product_subscription_id ) ?>" />
					<input type="hidden" id="cbm_product_id" value="<?php echo esc_attr( $product_slug ) ?>" />
					<input type="hidden" id="cbm_product_reactivate_nonce" name="cbm_product_reactivate_nonce" value="<?php echo esc_html( $product_reactivate_nonce ); ?>"/>
					<input type="button" class="button" id="cbm_product_reactivate_checkout" value="<?php esc_attr_e( 'Checkout to Reactivate Product', 'chargebee-membership' ); ?>"/>
					<?php
				} else {
					$checkout_product_nonce = ( ! empty( $user_id ) && ! empty( $product_slug ) ) ? wp_create_nonce( 'cbm_checkout_product_' . $product_slug . '_' . $user_id ) : '';
					?>
					<input type="hidden" id="cbm_product_id" value="<?php echo esc_attr( $product_slug ) ?>" />
					<input type="hidden" id="cbm_checkout_product_nonce" name="cbm_checkout_product_nonce" value="<?php echo esc_html( $checkout_product_nonce ); ?>"/>
					<input type="button" class="button" id="cbm_subscribe_product_checkout" value="<?php esc_attr_e( 'Checkout', 'chargebee-membership' ); ?>"/>
					<?php
				}
				?>
				
				<?php
			} else {
				?>
				<div class="entry">
					<p class="price">
					<span class="amount">
					<?php
					echo esc_html( $product_price );
					?>
					</span>
					</p>
					<p>
						<?php
						echo esc_html( $product_data->description );
						?>
					</p>
					<div>
						<?php
						echo esc_html( $product_data->content );
						?>
					</div>
				</div>
				<input type="hidden" id="cbm_subscribe_product_nonce" name="cbm_subscribe_product_nonce" value="<?php echo esc_html( $subscribe_product_nonce ); ?>"/>
				<input type="hidden" value="<?php echo esc_attr( $product_slug ) ?>" id="cbm_product_id"/>
				<input type="button" class="button" data-cb-btn="cbm_subscribe_product" value="<?php esc_attr_e( 'Subscribe', 'chargebee-membership' ); ?>"
                                    data-cb-product-id="<?php echo esc_attr( $product_slug ) ?>"
                                    data-cb-subscribe-product-nonce="<?php echo esc_html( $subscribe_product_nonce ); ?>"
                                  />
				<?php
			}
			?>
		</div>
		
		<?php
		// Edit link of product for admin.
		if ( current_user_can( 'administrator' ) ) {
			$edit_link = admin_url() . 'admin.php?page=chargebee-membership-products&action=edit&product=' . $product_slug;
			printf( '<div><a href="%s">Edit Product</a></div>', esc_url( $edit_link ) );
		}
		?>
	</div>
<?php
} else {
	esc_html_e( 'The Product you are trying to get Doesn\'t exist.', 'chargebee-membership' );
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
