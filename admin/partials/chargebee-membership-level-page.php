<?php
global $wpdb;
$page               = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
$action             = ( ! empty( $_REQUEST['action'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false;
$input_submit_level = filter_input( INPUT_POST, 'submit_level' );
$input_level_action = filter_input( INPUT_POST, 'level_action' );
$submit_level       = ! empty( $input_submit_level ) ? $input_submit_level : '';
$level_action       = ! empty( $input_level_action ) ? $input_level_action : '';
$product_list_obj   = new Chargebee_Membership_Product_List();
$products           = $product_list_obj->get_products( 0, 0, true );
$user_id            = get_current_user_id();

if ( empty( $user_id ) ) {
	wp_die( esc_html__( 'You must have to logged in to access this page.', 'chargebee-membership' ) );
}

// edit action is called.
if ( ! empty( $action ) && 'edit' === $action ) {

	if ( ! empty( $submit_level ) && ! empty( $level_action ) && 'update' === $level_action ) {
		$post_filter_args = array(
			'level_id'          => FILTER_VALIDATE_INT,
			'level_name'        => FILTER_SANITIZE_STRING,
			'level_description' => FILTER_SANITIZE_STRING,
			'level_products'    => array(
				'filter' => FILTER_VALIDATE_INT,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
		);
		$post_filter      = filter_input_array( INPUT_POST, $post_filter_args );

		$level_id = ! empty( $post_filter['level_id'] ) ? $post_filter['level_id'] : '';
		if ( empty( $level_id ) ) {
			wp_die( esc_html__( 'This level does not exist.', 'chargebee-membership' ) );
		}
		$input_wpnonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
		$edit_nonce    = ! empty( $input_wpnonce ) ? $input_wpnonce : '';
		if ( ! empty( $edit_nonce ) ) {
			// In our file that handles the request, verify the nonce.
			if ( ! wp_verify_nonce( $edit_nonce, 'cbm_edit_level_' . $user_id . '_' . $level_id ) ) {
				wp_die( esc_html__( 'Go get a life script kiddies', 'chargebee-membership' ) );
			}
		}

		$level_name        = ! empty( $post_filter['level_name'] ) ? $post_filter['level_name'] : '';
		$level_description = ! empty( $post_filter['level_description'] ) ? $post_filter['level_description'] : '';
		$level_product_ids = ! empty( $post_filter['level_products'] ) ? $post_filter['level_products'] : array();

		if ( empty( $level_name ) ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Level name cannot be empty. Please add level name and try again.', 'chargebee-membership' ); ?></p>
			</div>
			<?php
		} elseif ( empty( $level_product_ids ) ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'You haven\'t selected any product. Please select at least one product and try again.', 'chargebee-membership' ); ?></p>
			</div>
			<?php
		} else {
			/**
			 * Before update level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_before_update_level' );
			$update_val   = array(
				'level_name'        => $level_name,
				'level_description' => $level_description,
			);
			$where        = array(
				'id' => $level_id,
			);
			$update_level = $wpdb->update( CHARGEBEE_MEMBERSHIP_TABLE_LEVEL, $update_val, $where );

			if ( ! is_wp_error( $update_level ) ) {
				$wpdb->delete( CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION, array(
					'level_id' => $level_id,
				) );
				foreach ( $level_product_ids as $level_product_id ) {
					$insert_level_product_rel = $wpdb->insert( CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION, array(
						'level_id'   => $level_id,
						'product_id' => $level_product_id,
					) );
				}
				?>
				<div class="notice notice-success">
					<p><?php esc_html_e( 'Level updated Successfully.', 'chargebee-membership' ); ?></p>
				</div>
				<?php
			}
			/**
			 * Before update level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_after_update_level' );
		}// End if().
	}// End if().
	$level_data         = array();
	$level_action       = 'update';
	$level_action_title = __( 'Update Level', 'chargebee-membership' );
	$input_level_id     = filter_input( INPUT_GET, 'level' );
	$level_id           = ! empty( $input_level_id ) ? $input_level_id : 0;
	$level_products     = array();
	$level_obj          = new stdClass();

	if ( ! empty( $level_id ) ) {
		$level_obj      = new Chargebee_Membership_Level_List();
		$level_data     = $level_obj->get_level( $level_id );
		$level_products = $level_obj->get_products_of_level( $level_id );
	}

	?>
	<div class="wrap cbm-edit-level">
		<h1><?php echo esc_html( $level_action_title ); ?></h1>
		<form id="add-level" action="" method="post">
			<table class="form-table">
				<tbody>
				<tr>
					<th><?php esc_html_e( 'Level Name', 'chargebee-membership' ); ?></th>
					<td><input type="text" name="level_name" id="level_name" value="<?php echo ! empty( $level_data->level_name ) ? esc_html( $level_data->level_name ) : ''; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Level Description', 'chargebee-membership' ); ?></th>
					<!-- Removed esc_textarea because it will encode the quotes ( " => &#34; )  -->
					<td><textarea name="level_description" id="level_description" rows="5" cols="40"><?php echo ! empty( $level_data->level_description ) ? esc_html( $level_data->level_description ) : ''; ?></textarea>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Products', 'chargebee-membership' ); ?></th>
					<td>
						<select size="5" multiple="multiple" class="level-products" name="level_products[]">
							<?php
							foreach ( $products as $product ) {
								$selected = '';
								if ( ! empty( $level_products ) && in_array( $product['id'], $level_products, true ) ) {
									$selected = 'selected="selected"';
								}
								echo '<option value="' . esc_attr( $product['id'] ) . '" ' . esc_html( $selected ) . '>' . esc_attr( $product['product_name'] ) . '</option>';
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Hold the Control Key (Command Key on the Mac) in order to select or deselect multiple products.', 'chargebee-membership' ); ?></p>
					</td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" name="level_id" value="<?php echo esc_attr( $level_id ); ?>"/>
			<input type="hidden" name="level_action" value="<?php echo esc_attr( $level_action ); ?>"/>
			<input type="submit" name="submit_level" id="submit" class="button button-primary" value="<?php echo esc_attr( $level_action_title ); ?>"/>
		</form>
	</div>
	<?php
} else {
	if ( ! empty( $submit_level ) && ! empty( $level_action ) && 'insert' === $level_action ) {
		global $wpdb;
		$post_filter_args = array(
			'insert_level_wpnonce'          => FILTER_SANITIZE_STRING,
			'level_name'        => FILTER_SANITIZE_STRING,
			'level_description' => FILTER_SANITIZE_STRING,
			'level_products'    => array(
				'filter' => FILTER_VALIDATE_INT,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
		);
		$post_filter      = filter_input_array( INPUT_POST, $post_filter_args );

		$insert_nonce    = ! empty( $post_filter['insert_level_wpnonce'] ) ? $post_filter['insert_level_wpnonce'] : '';
		if ( ! empty( $insert_nonce ) ) {
			// In our file that handles the request, verify the nonce.
			if ( ! wp_verify_nonce( $insert_nonce, 'cbm_insert_level_' . $user_id ) ) {
				wp_die( esc_html__( 'Go get a life script kiddies', 'chargebee-membership' ) );
			}
		}
		$level_name        = ! empty( $post_filter['level_name'] ) ? $post_filter['level_name'] : '';
		$level_description = ! empty( $post_filter['level_description'] ) ? $post_filter['level_description'] : '';
		$level_product_ids = ! empty( $post_filter['level_products'] ) ? $post_filter['level_products'] : array();

		if ( empty( $level_name ) ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Level name cannot be empty. Please add level name and try again.', 'chargebee-membership' ); ?></p>
			</div>
			<?php
		} elseif ( empty( $level_product_ids ) ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'You haven\'t selected any product. Please select at least one product and try again.', 'chargebee-membership' ); ?></p>
			</div>
			<?php
		} else {
			/**
			 * Before insert level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_before_insert_level' );
			$insert_level = $wpdb->insert( CHARGEBEE_MEMBERSHIP_TABLE_LEVEL, array(
				'level_name'        => $level_name,
				'level_description' => $level_description,
			) );

			if ( ! is_wp_error( $insert_level ) ) {
				$level_id = $wpdb->insert_id;
				if ( ! empty( $level_id ) ) {
					foreach ( $level_product_ids as $level_product_id ) {
						$insert_level_product_rel = $wpdb->insert( CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION, array(
							'level_id'   => $level_id,
							'product_id' => $level_product_id,
						) );
					}
					?>
					<div class="notice notice-success">
						<p><?php esc_html_e( 'Level Added Successfully.', 'chargebee-membership' ); ?></p>
					</div>
					<?php
				}
			}
			/**
			 * After insert level do_action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'cbm_after_insert_level' );
		}
	}// End if().
	$insert_nonce = wp_create_nonce( 'cbm_insert_level_' . $user_id );
	?>
	<div class="wrap cbm-level-page">
		<h1><?php esc_html_e( 'Levels', 'chargebee-membership' ); ?></h1>
		<div id="col-container" class="wp-clearfix">
			<div id="col-left" class="cbm-add-level">
				<div class="col-wrap">
					<div class="form-wrap">
						<h2><?php esc_html_e( 'Add New Level', 'chargebee-membership' ); ?></h2>
						<form id="add-level" action="" method="post">
							<div class="form-field form-required level-name-wrap">
								<label for="level_name"><?php esc_html_e( 'Level Name', 'chargebee-membership' ); ?></label> <input name="level_name" id="level_name" type="text" value="" size="40" aria-required="true">
							</div>
							<div class="form-field form-required level-name-wrap">
								<label for="level_description"><?php esc_html_e( 'Level Description', 'chargebee-membership' ); ?></label>
								<textarea name="level_description" id="level_description" rows="5" cols="40" aria-required="true"></textarea>
							</div>
							<div class="form-field form-required level-name-wrap">
								<label for="level-name"><?php esc_html_e( 'Products', 'chargebee-membership' ); ?></label>
								<select size="5" class="level-products" multiple="multiple" name="level_products[]">
									<?php
									foreach ( $products as $product ) {
										echo '<option value="' . esc_attr( $product['id'] ) . '">' . esc_attr( $product['product_name'] ) . '</option>';
									}
									?>
								</select>
								<p><?php esc_html_e( 'Hold the Control Key (Command Key on the Mac) in order to select or deselect multiple products.', 'chargebee-membership' ); ?></p>
							</div>
							<p class="submit">
								<input type="hidden" name="insert_level_wpnonce" value="<?php echo esc_attr( $insert_nonce ); ?>"/>
								<input type="hidden" name="level_action" value="insert"/>
								<input type="submit" name="submit_level" id="submit" class="button button-primary" value="<?php esc_html_e( 'Add New Level', 'chargebee-membership' ); ?>">
							</p>
						</form>
					</div>
				</div>
			</div>
			<div id="col-right" class="cbm-level-list">
				<div class="col-wrap">
					<form method="post">
						<?php
						$this->level_obj->prepare_items();
						$this->level_obj->display();
						?>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}// End if().
