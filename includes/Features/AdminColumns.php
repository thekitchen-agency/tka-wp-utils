<?php

namespace TKA\WPUtils\Features;

/**
 * Handles custom columns customizer logic inside WordPress admin list tables.
 */
class AdminColumns {

	/**
	 * Configured columns option.
	 */
	private array $columns;

	/**
	 * Constructor.
	 */
	public function __construct( array $columns ) {
		$this->columns = $columns;
	}

	/**
	 * Hook actions.
	 */
	public function hook(): void {
		if ( empty( $this->columns ) ) {
			return;
		}

		foreach ( $this->columns as $post_type => $custom_cols ) {
			if ( empty( $custom_cols ) ) {
				continue;
			}

			// Filter the table headers
			add_filter( "manage_edit-{$post_type}_columns", function( array $columns ) use ( $custom_cols ): array {
				foreach ( $custom_cols as $col ) {
					if ( empty( $col['meta_key'] ) ) {
						continue;
					}
					$key = 'tka_col_' . sanitize_key( $col['meta_key'] );
					$columns[ $key ] = esc_html( $col['label'] ?? $col['meta_key'] );
				}
				return $columns;
			}, 99 );

			// Output cell contents
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'renderCustomColumnCell' ], 10, 2 );

			if ( 'page' === $post_type || ( function_exists( 'is_post_type_hierarchical' ) && is_post_type_hierarchical( $post_type ) ) ) {
				add_action( "manage_pages_custom_column", [ $this, 'renderCustomColumnCell' ], 10, 2 );
			}

			// Add top-bar filters for post/term relations
			add_action( 'restrict_manage_posts', function( string $post_type_current ) use ( $post_type, $custom_cols ): void {
				if ( $post_type_current !== $post_type ) {
					return;
				}

				foreach ( $custom_cols as $col ) {
					if ( empty( $col['meta_key'] ) || empty( $col['field_type'] ) || ( 'post_relation' !== $col['field_type'] && 'term_relation' !== $col['field_type'] ) ) {
						continue;
					}

					$meta_key   = $col['meta_key'];
					$label      = $col['label'] ?? $meta_key;
					$field_type = $col['field_type'] ?? 'text';

					// Handle direct Taxonomy Terms filter
					if ( 'taxonomy' === $field_type || ( 'text' === $field_type && taxonomy_exists( $meta_key ) ) ) {
						$terms = get_terms( [
							'taxonomy'   => $meta_key,
							'hide_empty' => false,
						] );
						if ( empty( $terms ) || is_wp_error( $terms ) ) {
							continue;
						}

						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$selected_slug = isset( $_GET[ 'tka_tax_' . $meta_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'tka_tax_' . $meta_key ] ) ) : '';

						echo '<select name="' . esc_attr( 'tka_tax_' . $meta_key ) . '" id="' . esc_attr( 'tka_tax_' . $meta_key ) . '" style="max-width: 200px;">';
						/* translators: %s: Column label */
						echo '<option value="">' . esc_html( sprintf( __( 'All %s', 'tka-site-utilities' ), $label ) ) . '</option>';
						foreach ( $terms as $term ) {
							echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( $selected_slug, $term->slug, false ) . '>' . esc_html( $term->name ) . ' (' . intval( $term->count ) . ')</option>';
						}
						echo '</select>';
						continue;
					}

					$cache_key   = 'tka_distinct_meta_' . md5( $meta_key );
					$cache_group = 'tka-site-utilities';
					$raw_values  = wp_cache_get( $cache_key, $cache_group );
					if ( false === $raw_values ) {
						global $wpdb;
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$raw_values = $wpdb->get_col( $wpdb->prepare(
							"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
							$meta_key
						) );
						if ( ! is_array( $raw_values ) ) {
							$raw_values = [];
						}
						wp_cache_set( $cache_key, $raw_values, $cache_group, HOUR_IN_SECONDS );
					}

					$related_ids = [];
					foreach ( $raw_values as $val ) {
						if ( is_numeric( $val ) ) {
							$related_ids[] = intval( $val );
						} elseif ( is_serialized( $val ) ) {
							$unserialized = maybe_unserialize( $val );
							if ( is_array( $unserialized ) ) {
								foreach ( $unserialized as $sub_val ) {
									if ( is_numeric( $sub_val ) ) {
										$related_ids[] = intval( $sub_val );
									} elseif ( is_object( $sub_val ) ) {
										$id_key = 'term_relation' === $field_type ? 'term_id' : 'ID';
										if ( isset( $sub_val->$id_key ) ) {
											$related_ids[] = intval( $sub_val->$id_key );
										}
									}
								}
							} elseif ( is_object( $unserialized ) ) {
								$id_key = 'term_relation' === $field_type ? 'term_id' : 'ID';
								if ( isset( $unserialized->$id_key ) ) {
									$related_ids[] = intval( $unserialized->$id_key );
								}
							}
						} elseif ( is_string( $val ) && ! empty( $val ) ) {
							$parts = explode( ',', $val );
							foreach ( $parts as $part ) {
								$part = trim( $part );
								if ( is_numeric( $part ) ) {
									$related_ids[] = intval( $part );
								}
							}
						}
					}

					$related_ids = array_unique( array_filter( $related_ids ) );
					if ( empty( $related_ids ) ) {
						continue;
					}

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$selected_value = isset( $_GET[ 'tka_filter_' . $meta_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'tka_filter_' . $meta_key ] ) ) : '';

					echo '<select name="' . esc_attr( 'tka_filter_' . $meta_key ) . '" id="' . esc_attr( 'tka_filter_' . $meta_key ) . '" style="max-width: 200px;">';
					/* translators: %s: Column label */
					echo '<option value="">' . esc_html( sprintf( __( 'All %s', 'tka-site-utilities' ), $label ) ) . '</option>';
					foreach ( $related_ids as $id ) {
						if ( 'term_relation' === $field_type ) {
							$term = get_term( $id );
							if ( is_wp_error( $term ) || ! $term ) {
								continue;
							}
							$title = $term->name;
							$tax_obj = get_taxonomy( $term->taxonomy );
							$tax_label = $tax_obj ? ' (' . $tax_obj->labels->singular_name . ')' : '';
							echo '<option value="' . esc_attr( $id ) . '"' . selected( $selected_value, $id, false ) . '>' . esc_html( $title . $tax_label ) . '</option>';
						} else {
							$title = get_the_title( $id );
							if ( empty( $title ) ) {
								/* translators: %d: Post ID */
								$title = sprintf( __( 'Post #%d', 'tka-site-utilities' ), $id );
							}
							$post_type_obj = get_post_type_object( get_post_type( $id ) );
							$pt_label      = $post_type_obj ? ' (' . $post_type_obj->labels->singular_name . ')' : '';
							
							echo '<option value="' . esc_attr( $id ) . '"' . selected( $selected_value, $id, false ) . '>' . esc_html( $title . $pt_label ) . '</option>';
						}
					}
					echo '</select>';
				}
			} );

			// Filter query based on selected dropdown filter
			add_action( 'pre_get_posts', function( \WP_Query $query ) use ( $post_type, $custom_cols ): void {
				if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== $post_type ) {
					return;
				}

				$meta_query = $query->get( 'meta_query' );
				if ( ! is_array( $meta_query ) ) {
					$meta_query = [];
				}

				$added_to_meta_query = false;

				foreach ( $custom_cols as $col ) {
					if ( empty( $col['meta_key'] ) ) {
						continue;
					}

					$meta_key   = $col['meta_key'];
					$field_type = $col['field_type'] ?? 'text';

					// Handle taxonomy filtering in query
					if ( 'taxonomy' === $field_type || ( 'text' === $field_type && taxonomy_exists( $meta_key ) ) ) {
						$tax_param = 'tka_tax_' . $meta_key;
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( ! empty( $_GET[ $tax_param ] ) ) {
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$slug = sanitize_text_field( wp_unslash( $_GET[ $tax_param ] ) );
							$tax_query = $query->get( 'tax_query' );
							if ( ! is_array( $tax_query ) ) {
								$tax_query = [];
							}
							$tax_query[] = [
								'taxonomy' => $meta_key,
								'field'    => 'slug',
								'terms'    => $slug,
							];
							$query->set( 'tax_query', $tax_query );
						}
						continue;
					}

					if ( 'post_relation' !== $field_type && 'term_relation' !== $field_type ) {
						continue;
					}

					$filter_param = 'tka_filter_' . $meta_key;

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ! empty( $_GET[ $filter_param ] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$val = sanitize_text_field( wp_unslash( $_GET[ $filter_param ] ) );

						// Match numeric IDs, serialized arrays/keys, and comma-separated IDs
						$meta_query[] = [
							'relation' => 'OR',
							[
								'key'     => $meta_key,
								'value'   => $val,
								'compare' => '=',
							],
							[
								'key'     => $meta_key,
								'value'   => '"' . $val . '"',
								'compare' => 'LIKE',
							],
							[
								'key'     => $meta_key,
								'value'   => 'i:' . $val . ';',
								'compare' => 'LIKE',
							],
							[
								'key'     => $meta_key,
								'value'   => ',' . $val . ',',
								'compare' => 'LIKE',
							],
							[
								'key'     => $meta_key,
								'value'   => $val . ',%',
								'compare' => 'LIKE',
							],
							[
								'key'     => $meta_key,
								'value'   => '%,' . $val,
								'compare' => 'LIKE',
							],
						];
						$added_to_meta_query = true;
					}
				}

				if ( $added_to_meta_query ) {
					$query->set( 'meta_query', $meta_query );
				}
			} );
		}
	}

	/**
	 * Render cell value from post metadata.
	 */
	public function renderCustomColumnCell( string $column_name, int $post_id ): void {
		if ( ! str_starts_with( $column_name, 'tka_col_' ) ) {
			return;
		}

		$meta_key  = substr( $column_name, 8 );
		$post_type = get_post_type( $post_id );

		// Find the column config to check field type
		$col_config = null;
		if ( isset( $this->columns[ $post_type ] ) ) {
			foreach ( $this->columns[ $post_type ] as $col ) {
				if ( isset( $col['meta_key'] ) && sanitize_key( $col['meta_key'] ) === $meta_key ) {
					$col_config = $col;
					break;
				}
			}
		}

		$value      = get_post_meta( $post_id, $meta_key, true );
		$field_type = $col_config['field_type'] ?? 'text';

		if ( 'taxonomy' === $field_type || ( 'text' === $field_type && function_exists( 'taxonomy_exists' ) && taxonomy_exists( $meta_key ) ) ) {
			$terms = get_the_terms( $post_id, $meta_key );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				echo '<span class="tka-column-empty">—</span>';
				return;
			}

			$links = [];
			foreach ( $terms as $term ) {
				$term_link = get_edit_term_link( $term->term_id, $term->taxonomy );
				if ( $term_link && current_user_can( 'edit_term', $term->term_id ) ) {
					$links[] = '<a href="' . esc_url( $term_link ) . '" class="tka-related-post-link"><strong>' . esc_html( $term->name ) . '</strong></a>';
				} else {
					$links[] = esc_html( $term->name );
				}
			}
			echo wp_kses_post( implode( ', ', $links ) );
		} elseif ( 'post_relation' === $field_type ) {
			$related_ids = [];
			if ( is_numeric( $value ) ) {
				$related_ids[] = intval( $value );
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					if ( is_numeric( $val ) ) {
						$related_ids[] = intval( $val );
					} elseif ( is_object( $val ) && isset( $val->ID ) ) {
						$related_ids[] = intval( $val->ID );
					}
				}
			} elseif ( is_object( $value ) && isset( $value->ID ) ) {
				$related_ids[] = intval( $value->ID );
			} elseif ( is_string( $value ) && is_serialized( $value ) ) {
				$unserialized = maybe_unserialize( $value );
				if ( is_array( $unserialized ) ) {
					foreach ( $unserialized as $sub_val ) {
						if ( is_numeric( $sub_val ) ) {
							$related_ids[] = intval( $sub_val );
						} elseif ( is_object( $sub_val ) && isset( $sub_val->ID ) ) {
							$related_ids[] = intval( $sub_val->ID );
						}
					}
				} elseif ( is_object( $unserialized ) && isset( $unserialized->ID ) ) {
					$related_ids[] = intval( $unserialized->ID );
				}
			} elseif ( is_string( $value ) && ! empty( $value ) ) {
				$parts = explode( ',', $value );
				foreach ( $parts as $part ) {
					$part = trim( $part );
					if ( is_numeric( $part ) ) {
						$related_ids[] = intval( $part );
					}
				}
			}

			$related_ids = array_unique( array_filter( $related_ids ) );
			if ( empty( $related_ids ) ) {
				echo '<span class="tka-column-empty">—</span>';
				return;
			}

			$links = [];
			foreach ( $related_ids as $id ) {
				$title = get_the_title( $id );
				if ( empty( $title ) ) {
					/* translators: %d: Post ID */
					$title = sprintf( __( 'Post #%d', 'tka-site-utilities' ), $id );
				}

				if ( current_user_can( 'edit_post', $id ) ) {
					$links[] = '<a href="' . esc_url( get_edit_post_link( $id ) ) . '" class="tka-related-post-link"><strong>' . esc_html( $title ) . '</strong></a>';
				} else {
					$links[] = esc_html( $title );
				}
			}
			echo wp_kses_post( implode( ', ', $links ) );
		} elseif ( 'term_relation' === $field_type ) {
			$related_ids = [];
			if ( is_numeric( $value ) ) {
				$related_ids[] = intval( $value );
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					if ( is_numeric( $val ) ) {
						$related_ids[] = intval( $val );
					} elseif ( is_object( $val ) && isset( $val->term_id ) ) {
						$related_ids[] = intval( $val->term_id );
					}
				}
			} elseif ( is_object( $value ) && isset( $value->term_id ) ) {
				$related_ids[] = intval( $value->term_id );
			} elseif ( is_string( $value ) && is_serialized( $value ) ) {
				$unserialized = maybe_unserialize( $value );
				if ( is_array( $unserialized ) ) {
					foreach ( $unserialized as $sub_val ) {
						if ( is_numeric( $sub_val ) ) {
							$related_ids[] = intval( $sub_val );
						} elseif ( is_object( $sub_val ) && isset( $sub_val->term_id ) ) {
							$related_ids[] = intval( $sub_val->term_id );
						}
					}
				} elseif ( is_object( $unserialized ) && isset( $unserialized->term_id ) ) {
					$related_ids[] = intval( $unserialized->term_id );
				}
			} elseif ( is_string( $value ) && ! empty( $value ) ) {
				$parts = explode( ',', $value );
				foreach ( $parts as $part ) {
					$part = trim( $part );
					if ( is_numeric( $part ) ) {
						$related_ids[] = intval( $part );
					}
				}
			}

			$related_ids = array_unique( array_filter( $related_ids ) );
			if ( empty( $related_ids ) ) {
				echo '<span class="tka-column-empty">—</span>';
				return;
			}

			$links = [];
			foreach ( $related_ids as $id ) {
				$term = get_term( $id );
				if ( is_wp_error( $term ) || ! $term ) {
					/* translators: %d: Term ID */
					$links[] = sprintf( __( 'Term #%d', 'tka-site-utilities' ), $id );
					continue;
				}

				$title = $term->name;
				$edit_link = get_edit_term_link( $term->term_id, $term->taxonomy );

				if ( $edit_link && current_user_can( 'edit_term', $term->term_id ) ) {
					$links[] = '<a href="' . esc_url( $edit_link ) . '" class="tka-related-post-link"><strong>' . esc_html( $title ) . '</strong></a>';
				} else {
					$links[] = esc_html( $title );
				}
			}
		} elseif ( 'image' === $field_type || 'gallery' === $field_type ) {
			$image_id  = 0;
			$image_url = '';
			$count     = 0;

			// Check ACF formatted value first if available, otherwise raw post meta
			$raw = function_exists( 'get_field' ) ? get_field( $meta_key, $post_id ) : null;
			if ( empty( $raw ) ) {
				$raw = $value;
			}

			if ( 'gallery' === $field_type ) {
				$gallery_items = [];
				if ( is_array( $raw ) ) {
					$gallery_items = $raw;
				} elseif ( is_string( $raw ) && is_serialized( $raw ) ) {
					$unserialized = maybe_unserialize( $raw );
					if ( is_array( $unserialized ) ) {
						$gallery_items = $unserialized;
					}
				} elseif ( is_string( $raw ) && ! empty( $raw ) ) {
					$gallery_items = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
				}

				$count = count( $gallery_items );
				if ( $count > 0 ) {
					$first = reset( $gallery_items );
					if ( is_numeric( $first ) ) {
						$image_id = intval( $first );
					} elseif ( is_array( $first ) && isset( $first['ID'] ) ) {
						$image_id = intval( $first['ID'] );
					} elseif ( is_object( $first ) && isset( $first->ID ) ) {
						$image_id = intval( $first->ID );
					} elseif ( is_string( $first ) && filter_var( $first, FILTER_VALIDATE_URL ) ) {
						$image_url = $first;
					}
				}
			} else {
				if ( is_numeric( $raw ) ) {
					$image_id = intval( $raw );
				} elseif ( is_array( $raw ) && isset( $raw['ID'] ) ) {
					$image_id = intval( $raw['ID'] );
				} elseif ( is_object( $raw ) && isset( $raw->ID ) ) {
					$image_id = intval( $raw->ID );
				} elseif ( is_string( $raw ) && is_serialized( $raw ) ) {
					$unserialized = maybe_unserialize( $raw );
					if ( is_numeric( $unserialized ) ) {
						$image_id = intval( $unserialized );
					} elseif ( is_array( $unserialized ) && isset( $unserialized['ID'] ) ) {
						$image_id = intval( $unserialized['ID'] );
					}
				} elseif ( is_string( $raw ) && filter_var( $raw, FILTER_VALIDATE_URL ) ) {
					$image_url = $raw;
				}
			}

			if ( ! empty( $image_id ) ) {
				$thumb = wp_get_attachment_image_url( $image_id, 'thumbnail' );
				if ( ! empty( $thumb ) ) {
					$image_url = $thumb;
				}
			}

			if ( empty( $image_url ) ) {
				echo '<span class="tka-column-empty">—</span>';
				return;
			}

			$edit_url   = ! empty( $image_id ) ? get_edit_post_link( $image_id ) : '';
			$badge_html = ( 'gallery' === $field_type && $count > 1 ) ? '<span class="tka-column-gallery-badge">+' . ( $count - 1 ) . '</span>' : '';

			echo '<div class="tka-column-thumb-wrap">';
			if ( $edit_url && current_user_can( 'edit_post', $image_id ) ) {
				echo '<a href="' . esc_url( $edit_url ) . '" class="tka-column-thumb-link" title="' . esc_attr__( 'View / Edit media attachment', 'tka-site-utilities' ) . '">';
				echo '<img src="' . esc_url( $image_url ) . '" alt="" class="tka-column-thumb-img">';
				echo $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</a>';
			} else {
				echo '<span class="tka-column-thumb-link">';
				echo '<img src="' . esc_url( $image_url ) . '" alt="" class="tka-column-thumb-img">';
				echo $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</span>';
			}
			echo '</div>';
		} else {
			if ( is_array( $value ) ) {
				echo esc_html( implode( ', ', $value ) );
			} else {
				echo esc_html( $value );
			}
		}
	}
}
