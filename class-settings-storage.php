<?php
namespace photo_express;
//TODO make the loading of the options more performant by using only one option in wordpress and save all options in an array
if ( ! class_exists( "Settings_Storage" ) ) {
	class Settings_Storage {
		private $initialized = false;
		/**
		 * Define options and default values
		 * @var array
		 *          [peg_caption]           true if a caption should be shown for an image
		 *          [peg_link]              defines which kind of lightbox is used. Currently supported:thickbox, thickbox_integrated, thickbox_custom, lightbox, highslide, photoswipe
		 *          [peg_relate_images]     defines if images should be in put into a relationship with each other
		 *          [peg_img_align]         alignment of one image
		 *          [peg_img_css]           CSS class of the images
		 *          [peg_img_style]         CSS style for the image elements
		 *          [peg_a_img_css]         CSS class for the image link
		 *          [peg_a_img_style]       CSS style for the image link
		 *          [peg_phototile]         Defines if a phototile should be created
		 *          [peg_gal_align]         horizontal alignment of the album
		 *          [peg_gal_css]           Additional CSS classes for the album
		 *          [peg_gal_style]         CSS style for the album
		 *          [peg_img_sort]          Defines how the images should be ordered within an album. Possible values: 'none' (currently still supported: 'None', '0') , 'date' (currently still supported: '1') , 'title' (currently still supported: 'Title'), 'file' (currently still supported: 'File name','File'), 'random' (currently still suppored: 'Random')
		 *          [peg_img_asc]           if true, images will be ordered ascending within an album, descending if false.
		 *          [peg_large_limit]       true if a limit for the large display of the images is set
		 *          [peg_single_image_size] the size of images that are displayed in single view without the context of an album
		 *          [peg_single_video_size] the size of videos that are displayed in single view without the context of an album
		 *          [peg_title]             true if a IMG title should be shown in single view
		 *
		 */
		private $options = array(
			'peg_icon'                              => 1,
			'peg_roles'                             => array( 'administrator' => 1 ),
			'peg_user_name'                         => 'undefined',
			'peg_caption'                           => 0,
			'peg_caption_css'                       => '',
			'peg_caption_style'                     => '',
			'peg_caption_p_css'                     => '',
			'peg_caption_p_style'                   => '',
			'peg_title'                             => 1,
			'peg_parse_caption'                     => 1,
			'peg_link'                              => 'photoswipe',
			'peg_relate_images'                     => '1',
			'peg_img_align'                         => 'left',
			'peg_auto_clear'                        => '1',
			'peg_img_css'                           => '',
			'peg_img_style'                         => '',
			'peg_a_img_css'                         => '',
			'peg_a_img_style'                       => '',
			'peg_return_single_image_html'          => '',
			'peg_phototile'                         => '',
			'peg_featured_tag'                      => 1,
			'peg_additional_tags'                   => '',
			'peg_gal_align'                         => 'left',
			'peg_gal_css'                           => '',
			'peg_gal_style'                         => '',
			'peg_img_sort'                          => 0,
			'peg_img_asc'                           => 1,
			'peg_dialog_crop'                       => 1,
			'peg_max_albums_displayed'              => '',
			'peg_gal_order'                         => 0,
			'peg_footer_link'                       => 1,
			'peg_donate_link'                       => 1,
			'peg_save_state'                        => 1,
			'peg_saved_state'                       => '',
			'peg_last_album'                        => '',
			'peg_saved_user_name'                   => '',
			'peg_large_limit'                       => '',
			'peg_single_image_size'                 => 'w400',
			'peg_single_image_size_format'          => 'P',
			'peg_single_video_size'                 => 'w400',
			'peg_single_video_size_format'          => 'P',
			//Photoswipe options
			'peg_photoswipe_show_share_button'      => '1',
			'peg_photoswipe_show_fullscreen_button' => '1',
			'peg_photoswipe_show_caption'           => '1',
			'peg_photoswipe_show_close_button'      => '1',
			'peg_photoswipe_show_index_position'    => '1',
			//Caching options
			'peg_cache_activated'                   => '1',
			'peg_cache_expiration_time'             => 0,
			'peg_force_ssl'                         => '1'

		);
		private $migration_state = '';

		public function get_option( $name ) {
			return $this->get_options()[ $name ];
		}

		public function set_option( $name, $value ) {
			$this->get_options()[ $name ] = $value;
			update_option( $name, $value );
		}

		public function migrate_if_possible() {
			if ( ! $this->is_migrated() ) {
				//First check if the user has already saved some options.
				$migration_state = get_option( 'peg_migrate_state', 0.0 );

				$migrated = false;
				//Determine the previous version by evaluating 'peg_migrate_state'. Version 0.0 is picasa express 2. If $migration_state is 1 (true),
				// the version is version 0.1 as back then the $migration_state has just been a true/false flag. If the $migration_state is a numeric, the
				//version is 0.2 as the problematic situation with the $migration_state in version 0.1 has not been detected. All versions from 0.2 onwards are
				//labelled as "v0.3" and so on.
				$old_version = $migration_state === '1' ? 0.1 : ( preg_match( '/v([0-9.]+)/', $migration_state ) ? substr( $migration_state, 1 ) : ( is_numeric( $migration_state ) ? 0.2 : 0.0 ) );

				//In version 0.1, the option 'peg_migrate_state' has been set to true after successfully migrating. Thus
				//we also have to perform a migration if there is something to migrate between photo express versions and
				//the 'peg_migrate_state' is set to true.
				if ( $old_version == 0.0 ) {
					//no migration state has been set, so we've just installed this plugin. Check if photo express options
					//need to be migrated
					foreach ( $this->options as $key => $value ) {
						$legacy_option_value = get_option( 'pe2' . substr( $key, 3 ) );
						if ( isset( $legacy_option_value ) ) {
							//Set the corresponding option
							$this->options[ $key ] = $legacy_option_value;
						}
					}
					$migrated = true;
				}
				//Migration for version 0.2
				if ( $old_version < 0.2 ) {
					$photoswipe_migrate_options = array(
						'photoswipe_caption_num' => 'peg_photoswipe_show_index_position',
						'photoswipe_caption_dl'  => 'peg_photoswipe_show_share_button'
					);
					$old_key_prefix             = $old_version > 0.0 ? 'peg_' : 'pe2_';
					foreach ( $photoswipe_migrate_options as $old_key => $new_key ) {
						$old_key                   = $old_key_prefix . $old_key;
						$old_value                 = get_option( $old_key, $this->options[ $new_key ] );
						$this->options[ $new_key ] = empty( $old_value ) ? $this->options[ $new_key ] : $old_value;
					}
				}

				//Migration for version 0.3
				if ( $old_version > 0.0 && $old_version < 0.3 ) {
					//Check if we need to delete old options of our plugin (prefix peg).
					//We don't delete options of the pe2 prefix, as this is not our job. It should only be done by the picasa express 2 plugin
					foreach ( $this->options as $key => $value ) {
						delete_option( $key );
					}
					$migrated = true;
				}
				if ( $migrated ) {
					//Store options
					$this->store();
				}
				if ( ! $this->is_migrated() ) {
					//Store the migrate flag
					update_option( 'peg_migrate_state', $this->get_version_identifier() );
				}
			}
		}

		private function get_version_identifier() {
			return 'v' . PEG_VERSION;
		}

		private function store() {
			update_option( 'peg_general_settings', $this->options );
		}

		/**
		 * @return array
		 */
		public function get_options() {
			//Only load options that if we have already migrated the options to the newest level. Otherwise newly added
			// options will be left without default as they are overridden before migration takes place.
			if ( $this->is_migrated() ) {
				if ( ! $this->initialized ) {
					$stored_options = get_option( 'peg_general_settings', $this->options );

					if ( isset( $stored_options ) ) {
						$this->options = $stored_options;
					}
					// -----------------------------------------------------------------------
					// read all of the options from the database and store them in our local
					// options array

					if ( ! preg_match( '/^[whs]\d+(-c)?$/', $this->options['peg_large_limit'] ) ) {
						$this->options['peg_large_limit'] = '';
					}

					$this->initialized = true;
				}
			}

			return $this->options;
		}

		private function is_migrated() {
			if ( empty( $this->migration_state ) ) {
				$this->migration_state = get_option( 'peg_migrate_state' );
			}

			return $this->get_version_identifier() == $this->migration_state;
		}


		function init_site_options() {
			$this->walk_blogs( 'init_options' );
		}

		function delete_site_options() {
			$this->walk_blogs( 'delete_options' );
			if ( function_exists( 'delete_site_option' ) ) {
				delete_site_option( 'peg_multisite' );
			}
		}

		/**
		 * Walk all blogs and apply $func to every founded
		 *
		 * @global integer $blog_id
		 *
		 * @param $func Function to apply changes to blog
		 */
		function walk_blogs( $func ) {

			$walk = isset( $_GET['networkwide'] ) || isset( $_GET['sitewide'] ); // (de)activate by command from site admin

			if ( function_exists( 'get_site_option' ) ) {
				$active_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
				$walk                    = $walk || isset( $active_sitewide_plugins[ plugin_basename( __FILE__ ) ] );
			}

			if ( $walk && function_exists( 'switch_to_blog' ) ) {

				add_site_option( 'peg_multisite', true );

				global $blog_id, $switched_stack, $switched;
				$saved_blog_id = $blog_id;

				global $wpdb;
				$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'" );
				if ( is_array( $blogs ) ) {
					reset( $blogs );
					foreach ( (array) $blogs as $new_blog_id ) {
						switch_to_blog( $new_blog_id );
						$this->$func();
						array_pop( $switched_stack ); // clean
					}
					switch_to_blog( $saved_blog_id );
					array_pop( $switched_stack ); // clean
					$switched = ( is_array( $switched_stack ) && count( $switched_stack ) > 0 );
				}
			} else {
				$this->$func();
			}
		}


		/**
		 * Enable plugin configuration and set roles by config
		 */
		function init_options() {
			//Check for migration
			$this->migrate_if_possible();

			foreach ( get_option( 'peg_roles', $this->options['peg_roles'] ) as $role => $data ) {
				if ( $data ) {
					$role = get_role( $role );
					$role->add_cap( 'picasa_dialog' );
				}
			}
		}

		/**
		 * Delete plugin configuration flag
		 */
		function delete_options() {
			delete_option( 'peg_general_settings' );
			delete_option( 'peg_migrate_state' );
		}

		function parse_caption( $string ) {
			// determine if there was just a file-name returned as the caption,
			// if so simply make it a blank caption
			if ( $this->options['peg_parse_caption'] == '1' ) {
				// perform the replacement
				return preg_replace( '/^[^\s]+\.[^\s]+$/', '', $string );
			} else {
				// simply return the caption untouched
				return $string;
			}
		}// end function parse_caption(..)

		function wpmu_new_blog( $new_blog_id ) {
			switch_to_blog( $new_blog_id );
			$this->init_options();
			restore_current_blog();
		}
	}
}