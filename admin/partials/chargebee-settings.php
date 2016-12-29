<?php
/**
 * setting page of chargebee
 *
 * @package    Chargebee_Membership
 * @subpackage Chargebee_Membership/admin
 */
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Settings', 'chargebee-membership' ); ?></h1>
	<?php
		// Display all settings error.
		settings_errors();

		// Set default active tab.
		$active_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$active_tab = ! empty( $active_tab ) ? $active_tab : 'integration' ;

		// Get option for valid API key.
		$valid_key = get_option( 'cbm_api_key' );
	?>
	<!-- Tabs for Settings -->
	 <h2 class="nav-tab-wrapper">
	    <a href="?page=chargebee-membership-settings&tab=integration" class="nav-tab <?php echo ( 'integration' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Integration', 'chargebee-membership' ); ?></a>
	    <a href="?page=chargebee-membership-settings&tab=pages" class="nav-tab <?php echo ( 'pages' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pages', 'chargebee-membership' ); ?></a>
	    <a href="?page=chargebee-membership-settings&tab=account" class="nav-tab <?php echo ( 'account' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Account', 'chargebee-membership' ); ?></a>
		<!-- Phase2 -->
	    <!--<a href="?page=chargebee-membership-settings&tab=fields" class="nav-tab <?php // echo $active_tab == 'fields' ? 'nav-tab-active' : '';. ?>"><?php // _e( 'Fields', 'chargebee-membership' );. ?></a>-->
	    <a href="?page=chargebee-membership-settings&tab=general" class="nav-tab <?php echo ( 'general' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'chargebee-membership' ); ?></a>
	</h2>

	<!-- Display options as per tab -->
	<form method="post" action="options.php">
		<?php
		switch ( $active_tab ) {
			case 'integration':
				?>
				<input type="hidden" id="cbm_key_present" value="<?php echo ! empty( $valid_key ) ? 'yes' : 'no'; ?>"/>
				<?php
				settings_fields( 'integration' );
				do_settings_sections( 'integration' );
				submit_button( __( 'Save API Key & Synchronize', 'chargebee-membership' ), 'primary', 'cbm_api_key_save',false );
				break;

			default:
				if ( ! empty( $valid_key ) && false !== $valid_key ) {
					settings_fields( 'cbm_' . $active_tab );
					do_settings_sections( 'cbm_' . $active_tab );
					submit_button();
				} else {
					// Error display if API key is not set.
					$url = admin_url( 'admin.php?page=chargebee-membership-settings&tab=integration' );
					$valid_tags = array(
						'a' => array(
			             	'href' => array(),
					 	),
					);
					?>
					<h3><?php esc_html_e( 'Chargebee Setup Required', 'chargebee-membership' ); ?></h3>
					<p>
						<?php
						printf(
			             	wp_kses(
		 	        			__( 'Chargebee needs to be setup. Go to <a href="%s">Integration</a> Tab to set API key.', 'chargebee-membership' ),
								$valid_tags
							),
			             	esc_url( $url )
		             	);
		             	?>
		            </p>
		        <?php
				}
			break;
		}
		?>
	</form>

</div>
