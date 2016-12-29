<?php
if ( ! class_exists( 'Chargebee_Membership_Metabox' ) ) {
	/**
	 * Class to add metaboxes.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Metabox {

		/**
		 * Array of Post-types to add metaboxes
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      array $post_types Array of Post-types to add metaboxes
		 */
		private $post_types;

		/**
		 * Array of taxonomies to add metaboxes
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      array $taxonomies Array of taxonomies to add metaboxes
		 */
		private $taxonomies;

		/**
		 * Constructor of Chargebee_Membership_Metabox class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			// Get post types where we need to add metaboxes.
			$restrict_post_types = get_transient( 'cbm_restrict_post_types' );
			if ( is_wp_error( $restrict_post_types ) || empty( $restrict_post_types ) ) {
				$post_types = array( 'post', 'page' );
				set_transient( 'cbm_restrict_post_types', $post_types, 6 * HOUR_IN_SECONDS );
				$restrict_post_types = $post_types;
			}
			$this->post_types = $restrict_post_types;

			// Get taxonomies where we need to add metaboxes.
			$restrict_taxonomies = get_transient( 'cbm_restrict_taxonomies' );
			if ( is_wp_error( $restrict_taxonomies ) || empty( $restrict_taxonomies ) ) {
				$taxonomies = array( 'category' );
				set_transient( 'cbm_restrict_taxonomies', $taxonomies, 6 * HOUR_IN_SECONDS );
				$restrict_taxonomies = $taxonomies;
			}
			$this->taxonomies = $restrict_taxonomies;

			if ( is_admin() ) {
				// Initialize metabox related action in post add/edit pages.
				$this->init_metabox();
			}
		}

		/**
		 * Return post types.
		 *
		 * @return array
		 */
		public function get_post_types() {
			return $this->post_types;
		}

		/**
		 * Return taxonomies.
		 *
		 * @return array
		 */
		public function get_taxonomies() {
			return $this->taxonomies;
		}

		/**
		 * Initialize metabox related actions.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function init_metabox() {

			// Add metaboxes on add_meta_boxes hook.
			add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
			// Save metaboxes values on save_post hook.
			add_action( 'save_post', array( $this, 'save_metabox_values' ), 10, 2 );

			$taxonomies = $this->get_taxonomies();
			foreach ( $taxonomies as $taxonomy ) {

				// Add metabox in add taxonomy form.
				add_action( $taxonomy . '_add_form_fields', array( $this, 'render_add_taxonomy_metabox_cbm_restrict_content' ), 10, 1 );

				// Add taxonomies custom meta.
				add_action( 'create_' . $taxonomy, array( $this, 'save_taxonomy_custom_meta_values' ), 10, 1 );

				// Add metabox in edit taxonomy form.
				add_action( $taxonomy . '_edit_form_fields', array( $this, 'render_edit_taxonomy_metabox_cbm_restrict_content' ), 10, 1 );

				// update taxonomies custom meta.
				add_action( 'edited_' . $taxonomy, array( $this, 'save_taxonomy_custom_meta_values' ), 10, 1 );

			}
		}

		/**
		 * Add metaboxes to the post types.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function add_metaboxes() {
			// Metabox for content restriction.
			$restricted_post_types = $this->get_post_types();
			add_meta_box( 'cbm-restrict-content', __( 'Chargebee Content Restriction', 'chargebee-membership' ), array( $this, 'render_metabox_cbm_restrict_content' ), $restricted_post_types );
		}

		/**
		 * Save metabox values on save_post action.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int     $post_id  Save post id.
		 * @param WP_Post $post     Save post object.
		 */
		public function save_metabox_values( $post_id, $post ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			$post_type             = $post->post_type;
			$restricted_post_types = $this->get_post_types();
			if ( in_array( $post_type, $restricted_post_types, true ) ) {
				$input_restrict_option = filter_input( INPUT_POST, 'cbm_restrict_option', FILTER_VALIDATE_INT );
				$restrict_option       = ! empty( $input_restrict_option ) ? $input_restrict_option : '';
				$restrict_level        = '';

				if ( ! empty( $restrict_option ) ) {
					if ( 3 === $restrict_option ) {
						$input_restrict_level = filter_input( INPUT_POST, 'cbm_restrict_level', FILTER_VALIDATE_INT );
						$restrict_level       = ! empty( $input_restrict_level ) ? $input_restrict_level : '';
					}
					update_post_meta( $post_id, 'cbm_restrict_option', $restrict_option );
					update_post_meta( $post_id, 'cbm_restrict_level', $restrict_level );
				}
			}
		}

		/**
		 * Callback function of restrict content metabox.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function render_metabox_cbm_restrict_content() {
			$level_obj           = new Chargebee_Membership_Level_List();
			$levels              = $level_obj->get_levels( 0, 0, true );
			$post_id             = get_the_ID();
			$post_type           = get_post_type( $post_id );
			$post_taxonomies     = get_cbm_taxonomies_for_post_type( $post_type );
			$restriction_options = array(
				1 => __( 'Everyone', 'chargebee-membership' ),
				3 => __( 'Selected Level', 'chargebee-membership' ),
				4 => __( 'As per content shortcodes', 'chargebee-membership' ),
			);
			// Add 'As restricted at Category level' option only if post have cbm taxonomies.
			if ( false !== $post_taxonomies ) {
				$restriction_options[2] = __( 'As restricted at Category level', 'chargebee-membership' );
			}
			$cbm_restrict_option = get_post_meta( $post_id, 'cbm_restrict_option', true );
			$cbm_restrict_level  = get_post_meta( $post_id, 'cbm_restrict_level', true );
			$hide_levels         = 'hidden';
			if ( '3' === $cbm_restrict_option ) {
				$hide_levels = '';
			}
			// TODO : Make templates for this metabox markup.
			?>
			<table class="form-table cb-table">
				<tbody>
				<tr class="form-field" id="cbm-restrict-options-container">
					<th scope="row">
						<label><?php esc_html_e( 'Select restrict option:', 'chargebee-membership' ); ?></label>
					</th>
					<td>
						<select id="cbm-restrict-options" name="cbm_restrict_option" class="cbm-restrict-options">
							<?php
							foreach ( $restriction_options as $restriction_option_val => $restriction_option_label ) {
								$selected = '';
								if ( intval( $cbm_restrict_option ) === $restriction_option_val ) {
									$selected = 'selected="selected"';
								}
								echo '<option value="' . esc_attr( $restriction_option_val ) . '" ' . esc_html( $selected ) . '>' . esc_html( $restriction_option_label ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr class="form-field <?php echo esc_attr( $hide_levels ); ?>" id="cbm-restrict-level-container">
					<th scope="row">
						<label><?php esc_html_e( 'Select levels :', 'chargebee-membership' ); ?></label>
					</th>
					<td>
						<?php
						if ( ! empty( $levels ) ) {
							?>
							<select class="cbm-restrict-levels" name="cbm_restrict_level">
								<?php
								foreach ( $levels as $level ) {
									$selected = '';
									if ( ! empty( $cbm_restrict_level ) && $level['id'] === $cbm_restrict_level ) {
										$selected = 'selected="selected"';
									}
									echo '<option value="' . esc_attr( $level['id'] ) . '" ' . esc_html( $selected ) . '>' . esc_html( $level['level_name'] ) . '</option>';
								}
								?>
							</select>
							<?php
						} else {
							esc_html_e( 'No levels are created. Please add some levels first.', 'chargebee-membership' );
						}
						?>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Callback function of add category form restrict content metabox.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param string $taxonomy Taxonomy name.
		 */
		public function render_add_taxonomy_metabox_cbm_restrict_content( $taxonomy ) {
			$level_obj           = new Chargebee_Membership_Level_List();
			$levels              = $level_obj->get_levels( 0, 0, true );

			// TODO : Make templates for this metabox markup.
			?>
			<div class="form-field" id="cbm-restrict-level-container">
				<label for="cbm-restrict-levels"><?php esc_html_e( 'Select levels:', 'chargebee-membership' ); ?></label>
				<?php
				if ( ! empty( $levels ) ) {
					?>
					<select class="cbm-restrict-levels" name="cbm_restrict_level" id="cbm-restrict-levels">
						<?php
						foreach ( $levels as $level ) {
							echo '<option value="' . esc_attr( $level['id'] ) . '" >' . esc_html( $level['level_name'] ) . '</option>';
						}
						?>
					</select>
					<?php
				} else {
					esc_html_e( 'No levels are created. Please add some levels first.', 'chargebee-membership' );
				}
				?>
			</div>
			<?php
		}

		/**
		 * Callback function of edit category form restrict content metabox.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param  WP_Term $term        WP_Term object.
		 *
		 * @return bool
		 */
		public function render_edit_taxonomy_metabox_cbm_restrict_content( $term ) {

			if ( ! is_object( $term ) ) {
				return false;
			}
			// Put the term ID into a variable.
			$term_id = ! empty( $term->term_id ) ? $term->term_id : 0 ;

			if ( empty( $term_id ) ) {
				return false;
			}

			$level_obj          = new Chargebee_Membership_Level_List();
			$levels             = $level_obj->get_levels( 0, 0, true );
			$cbm_restrict_level = get_term_meta( $term_id, 'cbm_restrict_level', true );

			// TODO : Make templates for this metabox markup.
			?>
			<tr class="form-field" id="cbm-restrict-level-container">
				<th scope="row" valign="top">
					<label for="cbm-restrict-levels"><?php esc_html_e( 'Select levels:', 'chargebee-membership' ); ?></label>
				</th>
				<td>
					<?php
					if ( ! empty( $levels ) ) {
						?>
						<select class="cbm-restrict-levels" name="cbm_restrict_level" id="cbm-restrict-levels">
							<?php
							foreach ( $levels as $level ) {
								$selected = '';
								if ( ! empty( $cbm_restrict_level ) && $level['id'] === $cbm_restrict_level ) {
									$selected = 'selected="selected"';
								}
								echo '<option value="' . esc_attr( $level['id'] ) . '" ' . esc_html( $selected ) . '>' . esc_html( $level['level_name'] ) . '</option>';
							}
							?>
						</select>
						<?php
					} else {
						esc_html_e( 'No levels are created. Please add some levels first.', 'chargebee-membership' );
					}
					?>
				</td>
			</tr>
			<?php
		}

		/**
		 * Add/Update metabox values on create_$taxonomy and edited_$taxonomy action.
		 *
		 * @since    1.0.0
		 * @access   public
		 *
		 * @param int $term_id  Term ID.
		 */
		public function save_taxonomy_custom_meta_values( $term_id ) {
			$input_restrict_level = filter_input( INPUT_POST, 'cbm_restrict_level', FILTER_VALIDATE_INT );
			$restrict_level       = ! empty( $input_restrict_level ) ? $input_restrict_level : '';
			update_term_meta( $term_id, 'cbm_restrict_level', $restrict_level );
		}
	}

}// End if().
