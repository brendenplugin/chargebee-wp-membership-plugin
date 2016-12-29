<?php
/**
 * Display product list and edit page on backend
 */


$action = ( ! empty( $_REQUEST['action'] )) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false;

// edit action is called.
if ( 'edit' === $action ) {
	$product_id = filter_input( INPUT_GET, 'product', FILTER_SANITIZE_STRING );
	if ( ! empty( $product_id ) ) {
		// include edit product html file.
		include_once CHARGEBEE_MEMBERSHIP_PATH . 'admin/partials/chargebee-edit-product.php';
	}
} else {
?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Products', 'chargebee-membership' ) ?></h2>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-4">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							$this->products_obj->prepare_items();
							$this->products_obj->display(); ?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</div>
<?php
}
