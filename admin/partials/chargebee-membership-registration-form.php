<?php
/**
 * Registration form for Chargebee user.
 *
 * @since    1.0.0
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/includes
 */

?>
<!-- show any error messages after form submission -->
<div class="cbm_errors" style="display:none;">
	<span class="error"><strong><?php esc_html_e( 'Error', 'chargebee-membership' ); ?></strong></span><br/>
</div>

<!-- Form for Chargebee User registration -->
<form id="cbm_registration_form" class="cbm_form" action="" method="POST">
	<fieldset>
		<p>
			<label for="cbm_user_Login"><?php esc_html_e( 'Username', 'chargebee-membership' ); ?>*</label>
			<input name="cbm_user_Login" id="cbm_user_Login" class="required" type="text"/>
		</p>
		<p>
			<label for="cbm_user_email"><?php esc_html_e( 'Email', 'chargebee-membership' ); ?>*</label>
			<input name="cbm_user_email" id="cbm_user_email" class="required" type="email"/>
		</p>
		<p>
			<label for="cbm_user_first"><?php esc_html_e( 'First Name', 'chargebee-membership' ); ?></label>
			<input name="cbm_user_first" id="cbm_user_first" type="text"/>
		</p>
		<p>
			<label for="cbm_user_last"><?php esc_html_e( 'Last Name', 'chargebee-membership' ); ?></label>
			<input name="cbm_user_last" id="cbm_user_last" type="text"/>
		</p>
		<p>
			<label for="password"><?php esc_html_e( 'Password', 'chargebee-membership' ); ?>*</label>
			<input name="cbm_user_pass" id="password" class="required" type="password"/>
		</p>
		<p>
			<label for="password_again"><?php esc_html_e( 'Password Again', 'chargebee-membership' ); ?>*</label>
			<input name="cbm_user_pass_confirm" id="password_again" class="required" type="password"/>
		</p>
		<p>
			<input type="hidden" id="cbm_registration_nonce" name="cbm_register_nonce" value="<?php echo esc_html( wp_create_nonce( 'cbm-register-nonce' ) ); ?>"/>
			<input type="submit" value="<?php esc_html_e( 'Register', 'chargebee-membership' ); ?>"/>
		</p>
	</fieldset>
</form>
<?php
