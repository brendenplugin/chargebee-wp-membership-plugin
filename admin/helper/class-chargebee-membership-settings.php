<?php
if ( ! class_exists( 'Chargebee_Membership_Settings' ) ) {
	/**
	 * Class For Setting page of Chargebee.
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 *
	 * @since    1.0.0
	 */
	class Chargebee_Membership_Settings {
		/**
		 * Constructor of Chargebee_Membership_Settings class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {

			// Admin setting for integration options.
			add_action( 'admin_init', array( $this, 'add_integration_options' ) );

			// Admin setting for Page options.
			add_action( 'admin_init', array( $this, 'add_page_options' ) );

			// Admin setting for Account options.
			add_action( 'admin_init', array( $this, 'add_account_options' ) );

			// Admin setting for Fields options
			// add_action( 'admin_init', array( $this, 'add_fields_options' ) );
			// Admin setting for General options.
			add_action( 'admin_init', array( $this, 'add_general_options' ) );

			// Option hook for Validation of Site Name and API key.
			add_action( 'pre_update_option_cbm_api_key', array( $this, 'chargebee_api_key_update' ), 20, 2 );

			// Option hook to flush_rewrite_rules after reserved pages change.
			add_action( 'update_option_cbm_pages', array( $this, 'update_reserved_pages_option' ), 20, 3 );

			// Option hook to check values in reserved pages option.
			add_action( 'pre_update_option_cbm_pages', array( $this, 'check_duplicate_reserved_page' ), 20, 2 );

			// Ajax call for api key change, validation and flush old data.
			// WIP.
			//add_action( 'wp_ajax_cbm_validate_and_flush_old_data', array( $this, 'cbm_validate_and_flush_old_data' ) );

		}

		/**
		 * Add Integration settings option.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_integration_options() {
			// Integration settings section.
			add_settings_section( 'cbm_integration',            // ID used to identify this section
				__( 'Integration', 'chargebee-membership' ),    // Title to be displayed on the administration page
			    array( $this, 'integration_options_callback' ), // Callback used to render the description of the section
			    'integration'                                   // Page on which to add this section of options.
			);

			// Site name for Chargebee API key validation.
			add_settings_field(
				'cbm_site_name',
				__( 'Chargebee Site Name', 'chargebee-membership' ),
				array( $this, 'integration_chargebee_sitename_callback' ),
				'integration',
				'cbm_integration'
			);

			// Chargebee API key field.
			add_settings_field( 'cbm_api_key',                           // ID used to identify the field
				__( 'Chargebee API Key', 'chargebee-membership' ),       // The label to the left of the option interface element
				array( $this, 'integration_chargebee_apikey_callback' ), // The name of the function responsible for rendering the option interface
				'integration',                                           // The page on which this option will be displayed
				'cbm_integration'                                        // The name of the section to which this field belongs.
			);

			// Chargebee Webhook Url.
			add_settings_field( 'cbm_web_hook_url',                           // ID used to identify the field
				__( 'Webhook URL', 'chargebee-membership' ),       // The label to the left of the option interface element
				array( $this, 'integration_chargebee_webhook_url_callback' ), // The name of the function responsible for rendering the option interface
				'integration',                                           // The page on which this option will be displayed
				'cbm_integration'                                        // The name of the section to which this field belongs.
			);
                        
			// Chargebee Auth Username & Password field.
			add_settings_field( 'cbm_webhook_username',                           // ID used to identify the field
				__( 'Username', 'chargebee-membership' ),       // The label to the left of the option interface element
				array( $this, 'integration_chargebee_username_callback' ), // The name of the function responsible for rendering the option interface
				'integration',                                           // The page on which this option will be displayed
				'cbm_integration'                                        // The name of the section to which this field belongs.
			);
                                                
			// Chargebee Auth Username & Password field.
			add_settings_field( 'cbm_webhook_password',                           // ID used to identify the field
				__( 'Password', 'chargebee-membership' ),       // The label to the left of the option interface element
				array( $this, 'integration_chargebee_password_callback' ), // The name of the function responsible for rendering the option interface
				'integration',                                           // The page on which this option will be displayed
				'cbm_integration'                                        // The name of the section to which this field belongs.
			);

			// Register settings for Integration.
			register_setting( 'integration', 'cbm_site_name' );
			register_setting( 'integration', 'cbm_api_key' );
		}

		/**
		 * Add Pages settings option
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_page_options() {
			// Pages Settings section for reserved pages.
			add_settings_section( 'cbm_reserved_pages', __( 'Reserved Pages', 'chargebee-membership' ), array( $this, 'reserved_pages_options_callback' ), 'cbm_pages' );

			// Login page option.
			add_settings_field( 'cbm_login_page', __( 'Chargebee Login Page', 'chargebee-membership' ), array( $this, 'login_page_callback' ), 'cbm_pages', 'cbm_reserved_pages' );


			// Login page option.
			add_settings_field( 'cbm_registration_page', __( 'Chargebee Registration Page', 'chargebee-membership' ), array( $this, 'registration_page_callback' ), 'cbm_pages', 'cbm_reserved_pages' );


			// Product page option.
			//add_settings_field( 'cbm_product_page', __( 'Chargebee Pricing Page', 'chargebee-membership' ), array( $this, 'product_page_callback' ), 'cbm_pages', 'cbm_reserved_pages' );

			// Thank you page option.
			add_settings_field( 'cbm_thankyou_page', __( 'Thank You Page', 'chargebee-membership' ), array( $this, 'thankyou_page_callback' ), 'cbm_pages', 'cbm_reserved_pages' );

			// Register pages options.
			register_setting( 'cbm_pages', 'cbm_pages' );
		}

		/**
		 * Add Account settings option.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_account_options() {
			// Account Permissions Settings section.
			add_settings_section( 'cbm_account_permissions', __( 'Permissions', 'chargebee-membership' ), array( $this, 'account_permissions_callback' ), 'cbm_account' );

			// Admin bar display.
			add_settings_field( 'cbm_adminbar_display', __( 'Admin bar', 'chargebee-membership' ), array( $this, 'adminbar_display_callback' ), 'cbm_account', 'cbm_account_permissions' );

			// Dashboard access option.
			add_settings_field( 'cbm_dashboard_access', __( 'Dashboard', 'chargebee-membership' ), array( $this, 'dashboard_access_callback' ), 'cbm_account', 'cbm_account_permissions' );

			// Login Logout option section.
			add_settings_section( 'cbm_login_logout', __( 'Login & Logout', 'chargebee-membership' ), array( $this, 'account_login_logout_callback' ), 'cbm_account' );

			// Use chargebee login or not.
			add_settings_field( 'cbm_use_cb_login', __( 'Chargebee Login Page', 'chargebee-membership' ), array( $this, 'use_login_page_callback' ), 'cbm_account', 'cbm_login_logout' );

			// Login page redirect.
			add_settings_field( 'cbm_login_redirect', __( 'URL to redirect after Login', 'chargebee-membership' ), array( $this, 'login_redirect_callback' ), 'cbm_account', 'cbm_login_logout' );

			// Logout page redirect.
//			add_settings_field( 'cbm_logout_redirect', __( 'URL to redirect after Logout', 'chargebee-membership' ), array( $this, 'logout_redirect_callback' ), 'cbm_account', 'cbm_login_logout' );

			// Register account options.
			register_setting( 'cbm_account', 'cbm_account' );

		}

		/**
		 * Add Fields settings option.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_fields_options() {
			// Account Permissions Settings section.
			add_settings_section( 'cbm_fields', __( 'Fields Settings', 'chargebee-membership' ), array( $this, 'fields_options_callback' ), 'cbm_fields' );

			// Register fields options.
			register_setting( 'cbm_fields', 'cbm_fields' );
		}

		/**
		 * Add General settings option.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_general_options() {
			// Account Permissions Settings section.
			add_settings_section( 'cbm_general', __( 'General Settings', 'chargebee-membership' ), array( $this, 'account_general_callback' ), 'cbm_general' );

			// Default membership level.
			add_settings_field( 'cbm_default_level', __( 'Default Membership Product', 'chargebee-membership' ), array( $this, 'default_level_callback' ), 'cbm_general', 'cbm_general' );

			// Default message for content restriction.
			add_settings_field( 'cbm_restriction_message', __( 'Content Restriction Message', 'chargebee-membership' ), array( $this, 'default_restriction_message' ), 'cbm_general', 'cbm_general' );

			// Register general options.
			register_setting( 'cbm_general', 'cbm_general' );
		}

		/**
		 * Integration option message.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_options_callback() {
			?>
			<p>
				<?php esc_html_e( 'Add Chargebee API key to import membership plans.', 'chargebee-membership' ); ?>
			</p>
			<?php
		}

		/**
		 * Callback function to display Field for API Key.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_chargebee_apikey_callback() {
			?>
			<input type="text" id="cbm_api_key" name="cbm_api_key" value="<?php echo esc_attr( get_option( 'cbm_api_key' ) ); ?>"/>
			<?php
		}

                		/**
		/** Callback function to display Field for Username Details.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_chargebee_username_callback() {
                        $chargebee_webhook_username="cb_wp_membership";
                        echo esc_html( $chargebee_webhook_username );
		}

		/** Callback function to display Field for Password Details.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_chargebee_password_callback() {
			    $cbm_options = get_option( 'cbm_site_settings' );
			    if(!isset($cbm_options["webhook_password"])){
	                            $CB_SALT=uniqid(mt_rand(), true);
        	                    $CB_PLUGIN_DOMAIN=get_site_url();
                	            $CB_AUTH_PASSWD=sha1($CB_SALT.$CB_PLUGIN_DOMAIN);
				    $cbm_options = array('webhook_password'=>"$CB_AUTH_PASSWD");
				    add_option("cbm_site_settings",$cbm_options);
                            }
                            echo esc_html( $cbm_options["webhook_password"] );
		}
                
                
		/**
		 * Callback function to display Webhook URL.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_chargebee_webhook_url_callback() {
			$domain_name = get_site_url();
			$webhook_url = $domain_name . '/wp-json/cbm/v2/webhook';
			echo '<p>';
				echo esc_html( $webhook_url );
			echo '</p>';
		}

		/**
		 * Callback function to display Field for Chargebee Site Name.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function integration_chargebee_sitename_callback() {
			?>
			<input type="text" id="cbm_site_name" name="cbm_site_name" value="<?php echo esc_attr( get_option( 'cbm_site_name' ) ); ?>"/>.chargebee.com
			<?php
		}

		/**
		 * Callback function to Reserved Pages setting section.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function reserved_pages_options_callback() {

		}

		/**
		 * Callback function to display Field for Login Page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function login_page_callback() {
			$options = get_option( 'cbm_pages' );
			if ( empty( $options['cbm_login_page'] ) ) {
				$page_id = '0';
			} else {
				$page_id = $options['cbm_login_page'];
			}
			$args = array(
				'selected' => $page_id,
				'name'     => 'cbm_pages[cbm_login_page]',
				'id'       => 'cbm_login_page',
			);
			wp_dropdown_pages( $args );
			?>
			<a href="<?php echo esc_url( admin_url( "post.php?post={$options['cbm_login_page']}&action=edit" ) ); ?>" class="button"><?php esc_html_e( 'Edit', 'chargebee-membership' ); ?></a>			<a
				href="<?php echo esc_url( get_permalink( $options['cbm_login_page'] ) ); ?>" class="button"><?php esc_html_e( 'View', 'chargebee-membership' ); ?></a>
			<?php
		}

		/**
		 * Callback function to display Field for Registration Page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function registration_page_callback() {
			$options = get_option( 'cbm_pages' );
			if ( empty( $options['cbm_registration_page'] ) ) {
				$page_id = '0';
			} else {
				$page_id = $options['cbm_registration_page'];
			}
			$args = array(
				'selected' => $page_id,
				'name'     => 'cbm_pages[cbm_registration_page]',
				'id'       => 'cbm_registration_page',
			);
			wp_dropdown_pages( $args );
			?>
			<a href="<?php echo esc_url( admin_url( "post.php?post={$options['cbm_registration_page']}&action=edit" ) ); ?>" class="button"><?php esc_html_e( 'Edit', 'chargebee-membership' ); ?></a>			<a
				href="<?php echo esc_url( get_permalink( $options['cbm_registration_page'] ) ); ?>" class="button"><?php esc_html_e( 'View', 'chargebee-membership' ); ?></a>
			<?php
		}

		/**
		 * Callback function to display Field for Product page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function product_page_callback() {
			$options = get_option( 'cbm_pages' );
			if ( empty( $options['cbm_product_page'] ) ) {
				$page_id = '0';
			} else {
				$page_id = $options['cbm_product_page'];
			}
			$args = array(
				'selected' => $page_id,
				'name'     => 'cbm_pages[cbm_product_page]',
				'id'       => 'cbm_product_page',
			);
			wp_dropdown_pages( $args );
			?>
			<a href="<?php echo esc_url( admin_url( "post.php?post={$options['cbm_product_page']}&action=edit" ) ); ?>" class="button"><?php esc_html_e( 'Edit', 'chargebee-membership' ); ?></a>			<a
				href="<?php echo esc_url( get_permalink( $options['cbm_product_page'] ) ); ?>" class="button"><?php esc_html_e( 'View', 'chargebee-membership' ); ?></a>
			<?php
		}

		/**
		 * Callback function to display Field for thank you page slug.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function thankyou_page_callback() {
			$options = get_option( 'cbm_pages' );
			if ( empty( $options['cbm_thankyou_page'] ) ) {
				$page_id = '0';
			} else {
				$page_id = $options['cbm_thankyou_page'];
			}
			$args = array(
				'selected' => $page_id,
				'name'     => 'cbm_pages[cbm_thankyou_page]',
				'id'       => 'cbm_thankyou_page',
			);
			wp_dropdown_pages( $args );
			?>
			<a href="<?php echo esc_url( admin_url( "post.php?post={$options['cbm_thankyou_page']}&action=edit" ) ); ?>" class="button"><?php esc_html_e( 'Edit', 'chargebee-membership' ); ?></a>			<a
				href="<?php echo esc_url( get_permalink( $options['cbm_thankyou_page'] ) ); ?>" class="button"><?php esc_html_e( 'View', 'chargebee-membership' ); ?></a>
			<?php
		}

		/**
		 * Callback function to display checkbox for admin bar display.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function adminbar_display_callback() {
			$options = get_option( 'cbm_account' );

			?>
			<input type='checkbox' id="cbm_adminbar_display" name='cbm_account[cbm_adminbar_display]' value='1'
				<?php  ! empty( $options['cbm_adminbar_display'] ) ? checked( 1, $options['cbm_adminbar_display'], 'checked' ) : ''; ?> />
			<label for="cbm_adminbar_display"><?php esc_html_e( 'Disable the Wordpress Admin bar for Members', 'chargebee-membership' ); ?></label>
			<?php
		}

		/**
		 * Callback function to display checkbox for dashboard access.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function dashboard_access_callback() {
			$options = get_option( 'cbm_account' );

			?>
			<input type='checkbox' id="cbm_dashboard_access" name='cbm_account[cbm_dashboard_access]' value='1'
				<?php  ! empty( $options['cbm_dashboard_access'] ) ? checked( 1, $options['cbm_dashboard_access'], 'checked' ) : ''; ?> />
			<label for="cbm_dashboard_access"><?php esc_html_e( 'Keep Members out of the Wordpress Dashboard', 'chargebee-membership' ); ?></label>
			<?php
		}

		/**
		 * Callback function to display Login/Logout options.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function account_login_logout_callback() {

		}

		/**
		 * Callback function to use chargebee login page.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function use_login_page_callback() {
			$options = get_option( 'cbm_account' );

			?>
			<input type='checkbox' id="cbm_use_cb_login" name='cbm_account[cbm_use_cb_login]' value='1'
				<?php  ! empty( $options['cbm_use_cb_login'] ) ? checked( 1, $options['cbm_use_cb_login'], 'checked' ) : ''; ?> />
			<label for="cbm_use_cb_login"><?php esc_html_e( 'Force Wordpress to use the Chargebee login page', 'chargebee-membership' ); ?></label>
			<?php
		}

		/**
		 * Callback function to display Field for login redirect URL.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function login_redirect_callback() {
			$options = get_option( 'cbm_account' );

			?>
			<input type="text" id="cbm_login_redirect" name="cbm_account[cbm_login_redirect]" size="35" value='<?php echo empty( $options['cbm_login_redirect'] ) ? '' : esc_attr( $options['cbm_login_redirect'] ); ?>'/>
			<span class="description"><?php esc_html_e( 'Provide url without site name like \'sample-page\'', 'chargebee-membership' ) ?></span>
			<?php
		}

		/**
		 * Callback function to display Field for logout redirect URL.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function logout_redirect_callback() {
			$options = get_option( 'cbm_account' );

			?>
			<input type='text' id="cbm_logout_redirect" name='cbm_account[cbm_logout_redirect]' size="35"
			       value='<?php echo empty( $options['cbm_logout_redirect'] ) ? '' : esc_attr( $options['cbm_logout_redirect'] ); ?>'/>
			<span class="description"><?php esc_html_e( 'Provide url without site name like \'sample-page\'', 'chargebee-membership' ) ?></span>
			<?php
		}

		/**
		 * Callback function for Account Permissions.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function account_permissions_callback() {
		}

		/**
		 * Callback function to display Field sections.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function fields_options_callback() {
			?>
			<p><?php esc_html_e( 'Fields Settings.', 'chargebee-membership' ); ?></p>
			<?php
		}

		/**
		 * Callback function for General tab setting options.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function account_general_callback() {
		}

		/**
		 * Callback function to Select default Membership Product e.g. Free Product.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function default_level_callback() {
			$options          = get_option( 'cbm_general' );
			$product_list_obj = new Chargebee_Membership_Product_List();
			$product_obj      = $product_list_obj->get_free_products();
			if ( empty( $product_obj ) ) {
				esc_html_e( 'Only free/$0 product can be set as default. Currently you don\'t have any free product in Chargebee.', 'chargebee-membership' );
			} else {
				?>
				<select name="cbm_general[cbm_default_level]" id="cbm_default_level">
					<option value="default"><?php echo esc_html__( 'Select Level', 'chargebee-membership' ); ?></option>
					<?php
					foreach ( $product_obj as $product ) {
						$product_id   = ! empty( $product['product_id'] ) ? $product['product_id'] : '';
						$product_name = ! empty( $product['product_name'] ) ? $product['product_name'] : '';
						?>
						<option value="<?php echo esc_attr( $product_id ); ?>" <?php ! empty( $options['cbm_default_level'] ) ? selected( $options['cbm_default_level'], $product_id, 'selected' ) : ''; ?> >
							<?php echo esc_html( $product_name ); ?>
						</option>
						<?php
					}
					?>
				</select>
				<span class="description"><?php esc_html_e( 'Only free($0) product can be set as default.', 'chargebee-membership' ) ?></span>
				<?php
			}
		}

		/**
		 * Callback function To display default message of content restriction.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function default_restriction_message() {
			$options                 = get_option( 'cbm_general' );
			$args                    = array(
				'textarea_name'    => 'cbm_general[cbm_restriction_message]',
				'textarea_rows'    => 5,
				'drag_drop_upload' => true,
			);
			$cbm_restriction_message = ! empty( $options['cbm_restriction_message'] ) ? $options['cbm_restriction_message'] : '';
			wp_editor( $cbm_restriction_message, 'restriction_message', $args );
			?>
			<span class="description"><?php esc_html_e( 'Add {user_level} for User Level Name.', 'chargebee-membership' ) ?></span>
			<?php
		}

		/**
		 * Check if API key is Authenticate while updating API key.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $new_value api key new.
		 * @param string $old_value old api key.
		 *
		 * @return mixed
		 */
		public function chargebee_api_key_update( $new_value, $old_value ) {

			$site = get_option( 'cbm_site_name' );

			// Check if site name and key both available.
			if ( false !== $site && ! empty( $site ) && ! empty( $new_value ) ) {

				$data     = Chargebee_Membership_Request::authorize_key( $new_value, $site );
				$res_code = wp_remote_retrieve_response_code( $data );
				$res_body = json_decode( wp_remote_retrieve_body( $data ) );

				if ( 200 !== $res_code ) {
					$error_msg = $res_body->message;
					if ( empty( $error_msg ) ) {
						$error_msg = __( 'Unexpected error occurred', 'chargebee-membership' );
					}
					add_settings_error( 'cbm_api_key', 'api_key_error', $error_msg, 'error' );
					$new_value = $old_value;
				} else {
					add_option("cbm_api_key",$new_value);
					// Import chargebee plans and insert into db.
					$product_imported = Chargebee_Membership_Product_Query::insert_imported_products( $res_body );

					if ( true === $product_imported ) {
						// Display success message.
						add_settings_error( 'chargebee-membership-settings', 'data_imported', __( 'Settings saved and Products from Chargebee imported successfully.', 'chargebee-membership' ), 'updated' );
					} else {
						// Display success message.
						add_settings_error( 'chargebee-membership-settings', 'data_import_failed.', __( 'Settings saved but Products from chargebee are not imported due to server error. Please try again.', 'chargebee-membership' ) );
					}
				}
			} elseif ( empty( $site ) && ! empty( $new_value ) ) {

				// Display Error if Site name is not available.
				add_settings_error( 'chargebee-membership-settings', 'api_key_error', __( 'Please Enter Site Name First.', 'chargebee-membership' ) );
				$new_value = $old_value;

			} elseif ( empty( $new_value ) && ! empty( $site ) ) {
				// API key empty.
				add_settings_error( 'cbm_api_key', 'api_key_error', __( 'Please Enter API Key.', 'chargebee-membership' ), 'error' );
			} elseif ( empty( $new_value ) && empty( $site ) ) {
				// Error display to provide API Key and Site Name.
				add_settings_error( 'cbm_api_key', 'api_key_error', __( 'Please Enter Site Name and API Key Both.', 'chargebee-membership' ), 'error' );
			}

			return $new_value;
		}

		/**
		 * Change Rewrite rule after option update.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $old_value old value of option.
		 * @param string $value new value of option.
		 * @param string $option option name.
		 */
		public function update_reserved_pages_option( $old_value, $value, $option ) {
			flush_rewrite_rules();
		}

		/**
		 * Callback function for ajax call to validate, update and flush data of old API key.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function cbm_validate_and_flush_old_data() {
			$new_api_key   = filter_input( INPUT_POST, 'api_key', FILTER_SANITIZE_STRING );
			$new_site_name = filter_input( INPUT_POST, 'site_name', FILTER_SANITIZE_STRING );
			$data          = Chargebee_Membership_Request::authorize_key( $new_api_key, $new_site_name );
			$res_code      = wp_remote_retrieve_response_code( $data );
			$res_body      = json_decode( wp_remote_retrieve_body( $data ) );

			if ( 200 !== $res_code ) {
				$error_msg = $res_body->message;
				if ( empty( $error_msg ) ) {
					$error_msg = __( 'Unexpected error occurred', 'chargebee-membership' );
				}
				add_settings_error( 'cbm_api_key', 'api_key_error', $error_msg, 'error' );
				wp_send_json_error( array( 'error' => $error_msg ) );
			}
		}

		/**
		 * Callback function to check for duplicate values in reserved pages.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param array $new_pages  new value of reserved pages.
		 * @param array $old_pages  old value of reserved pages.
		 *
		 * @return array    reserved pages value depending on duplicate values.
		 */
		public function check_duplicate_reserved_page( $new_pages, $old_pages ) {
			$duplicate_values = array_diff_assoc( $new_pages, array_unique( $new_pages ) );
			if ( is_array( $duplicate_values ) && ! empty( $duplicate_values ) ) {
				// Error display for duplicate values.
				add_settings_error( 'cbm_pages', 'duplicate_values', __( 'Each Chargebee Component needs its own Wordpress. Please select different pages for each component.', 'chargebee-membership' ), 'error' );
				return $old_pages;
			} else {
				return $new_pages;
			}
		}
	}

}
