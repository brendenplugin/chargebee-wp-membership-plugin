<?php
/**
 * Provide a public-facing view for thank you page.
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.chargebee.com
 * @since      1.0.0
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/public/partials
 */

// Check if user is loggedin.
if ( is_user_logged_in() ) {

	$user_id             = get_current_user_id();
	$cbm_checkout_nonce = filter_input( INPUT_GET, 'cbm_checkout_nonce', FILTER_SANITIZE_STRING );

	if ( ! empty( $cbm_checkout_nonce ) && ! empty( $user_id ) ) {
		// In our file that handles the request, verify the nonce.
		if ( wp_verify_nonce( $cbm_checkout_nonce, 'cbm_checkout_' . $user_id ) ) {
			?>
			<span><?php esc_html_e( 'Your Subscription is successfully added.' , 'chargebee-membership' ); ?></span>
			<?php
			// Shortcode added for subscription display.
			echo do_shortcode( '[cb_display_subscription]' );
		}
	}
} else {
	$url = get_cbm_page_link( 'login' );
	$valid_tags = array(
		'a' => array(
			'href' => array(),
		),
	);

	printf( wp_kses( __( 'Please <a href="%s">login</a> to see your subscriptions.', 'chargebee-membership' ), $valid_tags ), esc_url( $url ) );
}
