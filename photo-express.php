<?php
/*
Plugin Name: Photo Express for Google
Plugin URI: http://wordpress.org/extend/plugins/photo-express
Description: Browse and select photos from any public or private Google+ album and add them to your posts/pages.
Version: 0.3.2
Author: thhake
Author URI: http://www.thorsten-hake.com
Text Domain: peg
Domain Path: /

Thank you to Geoff Janes for version 2.0 of the Picasa Express plugin of which this plugin is a fork.
The fork was needed, as the old plugin was no longer maintained and a change was needed for the OAuth
authentification protocol.
Thank you to Wott (wotttt@gmail.com | http://wott.info/picasa-express) for plugin 
Picasa Express 2.0 version 1.5.4.  This plugin and version contained a large 
re-write and many improvements of the plugin: Picasa Image Express 2.0 RC2

Thank you to Scrawl ( scrawl@psytoy.net ) for plugin Picasa Image Express 2.0 RC2
for main idea and Picasa icons

Copyright 2015 thhake (email : mail@thorsten-hake.com)
Copyright 2013 gjanes ( email : gcj.wordpress@janesfamily.org )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
defined( 'ABSPATH' ) OR exit;
define( 'PEG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

//First check PHP compatibility
define( 'PEG_REQUIRED_PHP_VERSION', '5.4' );
function peg_is_php_supported() {
	return ! version_compare( PHP_VERSION, PEG_REQUIRED_PHP_VERSION, '<' );
}

/**
 * Checking the required version so that the user does not get strange error messages.
 *
 * @param string $php
 */
function peg_check_php_version() {
	global $wp_version;
	if ( ! peg_is_php_supported() ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( '<p>The <strong>Photo Express for Google</strong> plugin requires PHP  version ' . PEG_REQUIRED_PHP_VERSION . ' or greater. You are running PHP ' . PHP_VERSION . '.</p>', 'Plugin Activation Error', array(
			'response'  => 200,
			'back_link' => true
		) );

	}
}

register_activation_hook( __FILE__, 'peg_check_php_version' );
if ( peg_is_php_supported() ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-feed-fetcher.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-google-photo-access.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-settings-storage.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-photo-renderer.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-photo-browser.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-simple-cache.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-ssl-enforcer.php';

	define( 'PEG_VERSION', '0.3.1' );
	define( 'PEG_PHOTOSWIPE_VERSION', '4.0.8' );

	if ( ! class_exists( 'Photo_Express' ) ) {
		class Photo_Express {
			protected $tag = 'photoExpress';
			protected $name = 'Photo Express for Google';
			protected $version = PEG_VERSION;

			var $admin;
			var $browser;
			var $configuration;
			var $access;
			var $display;
			var $cache;

			function __construct() {
				//Create the needed objects and hook them to wordpress
				$this->configuration = new photo_express\Settings_Storage();
				$this->access        = new photo_express\Google_Photo_Access();
				$this->cache         = new photo_express\Simple_Cache( $this->configuration, $this->access );
				$fetcher             = $this->cache;
				//Needs to be effective before the cache, because the result of the page is different depending on the protocol
				if ( $this->configuration->get_option( 'peg_force_ssl' ) ) {
					$fetcher = new photo_express\SSL_Enforcer( $fetcher );
				}
				$this->admin   = new photo_express\Settings( $this->configuration, $this->access );
				$this->browser = new photo_express\Photo_Browser( $this->configuration, $this->access, $this->admin );
				$this->display = new photo_express\Photo_Renderer( $this->configuration, $fetcher );

				//Start hooking:
				$this->hook_activation();
				$this->hook_display();
				$this->hook_admin();
				$this->hook_media_browser();

			}


			function hook_activation() {
				// Hook option migration
				add_action( 'plugins_loaded', array( &$this->configuration, 'migrate_if_possible' ) );

				// Hook for plugin de/activation
				if ( is_multisite() ) {
					register_activation_hook( __FILE__, array( &$this->configuration, 'init_site_options' ) );
				} else {
					register_activation_hook( __FILE__, array( &$this->configuration, 'init_options' ) );
				}
			}

			function hook_media_browser() {
				if ( is_admin() ) {

					// Add media button to editor
					add_action( 'media_buttons', array( &$this->browser, 'add_media_button' ), 20 );

					// Add iframe page creator
					add_action( 'media_upload_picasa', array( &$this->browser, 'media_upload_picasa' ) );

					// AJAX request from media_upload_picasa iframe script ( peg-scripts.js )
					add_action( 'wp_ajax_peg_get_gallery', array( &$this->browser, 'get_gallery' ) );
					add_action( 'wp_ajax_peg_get_images', array( &$this->browser, 'get_images' ) );
					add_action( 'wp_ajax_peg_save_state', array( &$this->browser, 'save_state' ) );
					add_action( 'wp_ajax_peg_process_shortcode', array( &$this->browser, 'peg_process_shortcode' ) );
				}
			}

			function hook_admin() {
				if ( is_admin() ) {
					//Add check routine for authorization
					add_action( 'admin_init', array( &$this->access, 'check_for_authorization_code' ) );
					add_action( 'admin_init', array( &$this->access, 'check_for_revoke' ) );

					// loading localization if exist
					add_action( 'init', array( &$this->admin, 'load_textdomain' ) );

					// Add settings to the plugins management page under
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
						&$this->admin,
						'add_settings_link'
					) );
					// Add a page which will hold the  Options form
					add_action( 'admin_menu', array( &$this->admin, 'add_settings_page' ) );
					add_filter( 'contextual_help', array( &$this->admin, 'contextual_help' ), 10, 2 );

					//Init styles
					wp_enqueue_style( 'peg-style', plugins_url( '/photo-express.css', __FILE__ ), array(), PEG_VERSION, 'all' );
					// new site creation
					if ( is_multisite() && get_site_option( 'peg_multisite' ) ) {
						add_action( 'wpmu_new_blog', array( &$this->configuration, 'wpmu_new_blog' ) );
					}

				}
			}

			function hook_display() {
				$currentOptions = $this->configuration->get_options();

				// add the shortcode processing for Picasa Express 2 (legacy)
				add_shortcode( 'pe2-gallery', array( &$this->display, 'gallery_shortcode' ) );
				add_shortcode( 'pe2-image', array( &$this->display, 'image_shortcode' ) );
				// add the shortcode processing for PEG
				add_shortcode( 'peg-gallery', array( &$this->display, 'gallery_shortcode' ) );
				add_shortcode( 'peg-image', array( &$this->display, 'image_shortcode' ) );

				add_shortcode( 'clear', array( &$this->display, 'clear_shortcode' ) );


				//TODO
				// add the footer link
				if ( $currentOptions['peg_footer_link'] ) {
					add_action( 'wp_footer', array( &$this->display, 'add_footer_link' ) );
				}

				// add the peg display css file
				add_action( 'init', array( &$this->display, 'peg_add_display_css' ) );

				// to use the default thickbox script with wordpress:
				if ( $currentOptions['peg_link'] == 'thickbox_integrated' ) {
					// they chose the option to use the internal Wordpress version
					// of Thickbox
					add_action( 'init', array( &$this->display, 'peg_add_thickbox_script' ) );
				}

				// to use a custom thickbox script for display:
				if ( $currentOptions['peg_link'] == 'thickbox_custom' ) {
					// they chose the option to use the custom thickbox from this plugin
					add_action( 'init', array( &$this->display, 'peg_add_custom_thickbox_script' ) );
				}

				// to use the photoswipe script for display:
				if ( $currentOptions['peg_link'] == 'photoswipe' ) {
					// they chose the option to use photoswipe from this plugin,
					// check to see if we're on the login page, and if so skip
					// loading the photoswipe stuff.  jquery.mobile seems to
					// really goof up certain stuff with the login form
					if ( ! in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {
						// add the action to init photoswipe
						add_action( 'init', array( &$this->display, 'peg_add_photoswipe_script' ) );
					}
				}

				// function to add the caption width correction javascript, if
				// captions are enabled and image width is defined by height
				if ( $currentOptions['peg_caption'] == '1' ) {
					// captions are enabled, we need to perform the check in
					// the footer
					add_action( 'wp_footer', array( &$this->display, 'peg_add_caption_width_javascript' ) );
				}

				// determine if we need to filter the caption shortcode to add our
				// class and style attributes
				if ( ( $currentOptions['peg_caption_css'] != null ) || ( $currentOptions['peg_caption_style'] != null ) || ( $currentOptions['peg_caption_p_css'] != null ) || ( $currentOptions['peg_caption_p_style'] != null ) ) {
					// add the filter to parse the content for the caption HTML
					// and add the additional class/style attributes.
					// Filter: the_content was chosen instead of img_caption_shortcode
					// because media.php doesn't allow you to simply "edit" a generated
					// caption shortcode with this filter, but instead forces a complete
					// replacement of it.  So instead of taking over the caption
					// shortcode creation, this plugin will simply parse the content
					// and modify any caption tags it finds
					add_filter( 'the_content', array( &$this->display, 'peg_img_caption_shortcode_filter' ), 12 );
				}
			}

		}
	}
	function convert_to_https( $url ) {
		if ( ! str_starts_with( $url, "https" ) && str_starts_with( $url, "http" ) ) {
			$url = 'https' . substr( $url, 4 );
		}

		return $url;
	}

	function str_starts_with( $haystack, $needle ) {
		return substr( $haystack, 0, strlen( $needle ) ) === $needle;
	}

	if ( ! isset( $peg_instance ) ) {
		$peg_instance = new Photo_Express();
	}
}
?>
