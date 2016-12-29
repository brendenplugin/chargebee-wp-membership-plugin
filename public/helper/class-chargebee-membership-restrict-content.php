<?php
if ( ! class_exists( 'Chargebee_Membership_Restrict_Content' ) ) {
	/**
	 * Class to add metaboxes.
	 *
	 * @since    1.0.0
	 *
	 * @package    Chargebee_Membership
	 * @subpackage Chargebee_Membership/includes
	 */
	class Chargebee_Membership_Restrict_Content {

		/**
		 * Constructor of Chargebee_Membership_Metabox class.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function __construct() {
			add_action( 'the_content', array( $this, 'restrict_content' ) );
		}

		/**
		 * Restrict content for CB users as per their subscription.
		 *
		 * @param string $content post content.
		 *
		 * @return string
		 */
		public function restrict_content( $content ) {
			// TODO : Need to optimize the below code.
			$user       = wp_get_current_user();
			$user_id    = ( isset( $user->ID ) ? (int) $user->ID : 0 );
			$user_roles = $user->roles;
			if ( in_array( 'administrator', $user_roles, true ) ) {
				return $content;
			}

			global $post;
			$post_id                 = ( isset( $post->ID ) ? (int) $post->ID : 0 );
			$post_type               = ( isset( $post->post_type ) ? $post->post_type : '' );
			$user_subscriptions      = get_user_meta( $user_id, 'chargebee_user_subscriptions', true );
			$cbm_restrict_option     = get_post_meta( $post_id, 'cbm_restrict_option', true );
			$cbm_restrict_level_name = '';
			$cbm_restrict_products   = array();
			$cbm_level_product_data  = get_level_product_data();

			if ( ! in_array( 'chargebee_member', $user_roles, true ) && ! empty( $cbm_restrict_option ) && '1' !== $cbm_restrict_option ) {
				$url = get_cbm_page_link( 'login' );
				$valid_tags = array(
					'a' => array(
						'href' => array(),
					),
				);
				/* translators: %s: login_url */
				$restrict_content = sprintf( wp_kses( __( 'This content is only accessible to logged in members. Please <a href="%s">login</a> to continue.', 'chargebee-membership' ), $valid_tags ), esc_url( $url ) );
				return $restrict_content;
			}

			if ( empty( $cbm_level_product_data ) ) {
				return $content;
			}
			switch ( $cbm_restrict_option ) {
				// Everyone.
				case '1' :
					return $content;
					break;
				// As restricted at Category level.
				case '2' :
					$cbm_restricted_levels = array();
					// Check if current post have any restricted taxonomy registered.
					$restricted_post_taxonomies = get_cbm_taxonomies_for_post_type( $post_type );
					if ( false === $restricted_post_taxonomies ) {
						return $content;
					}
					if ( ! empty( $restricted_post_taxonomies ) ) {
						// Loop to Get All levels from assigned terms.
						foreach ( $restricted_post_taxonomies as $restricted_post_taxonomy ) {
							// Get all terms added for current post.
							$post_terms = get_the_terms( $post_id, $restricted_post_taxonomy );
							foreach ( $post_terms as $post_term ) {
								$term_id = isset( $post_term->term_id ) ? $post_term->term_id : 0;
								if ( ! empty( $term_id ) ) {
									$cbm_restrict_level = get_term_meta( $term_id, 'cbm_restrict_level', true );
									// Add level from term meta to $cbm_restricted_levels array.
									$cbm_restricted_levels[] = $cbm_restrict_level;
								}
							}
						}
						if ( ! empty( $cbm_restricted_levels ) ) {
							// Flip array to use it as array_intersect_key and get assigned level object from $cbm_level_product_data array.
							$cbm_restricted_flip_levels = array_flip( $cbm_restricted_levels );
							// Get assigned level array to current post.
							$cbm_restricted_level_arr = array_intersect_key( $cbm_level_product_data, $cbm_restricted_flip_levels );
							$cbm_restrict_level_names = array_column( $cbm_restricted_level_arr, 'level_name' );
							// Retrieve products from $cbm_restricted_level_arr.
							$cbm_restricted_product_obj = array_column( $cbm_restricted_level_arr, 'products' );

							$cbm_restricted_product_arr = array();
							// TODO: Need to optimize this loop if it can be OR simplify the array $cbm_level_product_data.
							foreach ( $cbm_restricted_product_obj as $cbm_restricted_product ) {
								foreach ( $cbm_restricted_product as $cbm_restricted_prod ) {
									$cbm_restricted_product_arr[] = $cbm_restricted_prod;
								}
							}
							$cbm_restrict_products   = array_column( $cbm_restricted_product_arr, 'product_id' );
							$cbm_restrict_level_name = implode( ',', $cbm_restrict_level_names );
						}
					}
					break;
				// Selected Level.
				case '3' :
					$cbm_restrict_level_id     = get_post_meta( $post_id, 'cbm_restrict_level', true );
					$level_obj                 = isset( $cbm_level_product_data[ $cbm_restrict_level_id ] ) ? $cbm_level_product_data[ $cbm_restrict_level_id ] : array();
					$cbm_restrict_level_name   = isset( $level_obj['level_name'] ) ? $level_obj['level_name'] : '';
					$cbm_restrict_products_arr = isset( $level_obj['products'] ) ? $level_obj['products'] : array();
					$cbm_restrict_products     = is_array( $cbm_restrict_products_arr ) ? array_column( $cbm_restrict_products_arr, 'product_id' ) : array();
					break;
				// As per content shortcodes.
				case '4' :
					return $content;
					break;
				default:
					return $content;
					break;
			}// End switch().

			// Get restricted content message from settings.
			if ( ! empty( $cbm_restrict_level_name ) ) {
				$user_level_restrict_msg = $this->get_restrict_content_message( $cbm_restrict_level_name );
			} else {
				$user_level_restrict_msg = '';
			}

			if ( ! empty( $user_subscriptions ) ) {
				$user_active_products = array();
				foreach ( $user_subscriptions as $user_subscription ) {
					if ( 'active' === $user_subscription['status'] || 'in_trial' === $user_subscription['status'] || 'non_renewing' === $user_subscription['status'] ) {
						$user_active_products[] = $user_subscription['product_id'];
					}
				}

				if ( empty( $user_active_products ) ) {
					return wp_kses_post( $user_level_restrict_msg );
				}

				$user_access_can_access_level = array_intersect( $cbm_restrict_products, $user_active_products );
				if ( empty( $user_access_can_access_level ) ) {
					return wp_kses_post( $user_level_restrict_msg );
				}
			} else {
				return wp_kses_post( $user_level_restrict_msg );
			}

			return $content;
		}

		/**
		 * Get message from settings for restricted content with short tag({user_level}).
		 *
		 * @param string $level_name Level name to replace short tag({user_level}).
		 *
		 * @return string return message with restricted level.
		 */
		public function get_restrict_content_message( $level_name ) {
			$options              = get_option( 'cbm_general' );
			$restrict_content_msg = '';
			if ( ! empty( $options ) ) {
				$restrict_content_msg = ! empty( $options['cbm_restriction_message'] ) ? $options['cbm_restriction_message'] : '';
			}
			$restrict_content_level_msg = str_replace( '{user_level}', $level_name, $restrict_content_msg );
			return $restrict_content_level_msg;
		}
	}

}// End if().
