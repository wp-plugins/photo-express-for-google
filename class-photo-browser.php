<?php
namespace photo_express;
if (!class_exists("Photo_Browser")) {
    class Photo_Browser
    {
	    /**
	     * @var $configuration Settings_Storage
	     */
        private $configuration;
	    /**
	     * @var $admin Settings
	     */
	    private $admin;
	    /**
	     * @var $picasaAcess Feed_Fetcher
	     */
        private $picasaAccess;

	    function __construct( $configuration, $picasaAccess, $admin ) {
		    $this->configuration = $configuration;
		    $this->picasaAccess  = $picasaAccess;
		    $this->admin = $admin;
	    }

	    /**
         * Echo the link with icon to run plugin dialog
         *
         * @param string $id optinal id for link to plugin dialog
         * @return void
         */
        function add_media_button($id = '')
        {

            if (!current_user_can('picasa_dialog')) return;

            $plugin_URL = plugin_dir_url( __FILE__ );
            $icon = $this->configuration->get_option('peg_icon');
// 'type=picasa' => 'media_upload_picasa' action above
            $media_picasa_iframe_src = "media-upload.php?type=picasa&tab=type&TB_iframe=true&width=640&height=566";
            $media_picasa_title = __("Add Google+ image or gallery", 'peg');
            $put_id = ($id) ? "id=\"$id-picasa_dialog\"" : '';

            echo "<a href=\"$media_picasa_iframe_src\" $put_id class=\"thickbox\" title=\"$media_picasa_title\"><img
        src=\"".plugins_url('/icon_picasa'.$icon.'.gif',__FILE__)."\" alt=\"$media_picasa_title\"/></a>";

        }

        /**
         * wp_ajax_peg_get_gallery
         * print html for gallery
         *
         */
        function get_gallery()
        {

            if (!current_user_can('picasa_dialog')) {
                echo json_encode((object)array('error' => __('Insufficient privelegies', 'peg')));
                die();
            }

            $out = (object)array();

            if (isset($_POST['user'])) {
                $user = $_POST['user'];
            } else die();

            $rss = $this->picasaAccess->get_feed("http://picasaweb.google.com/data/feed/api/user/$user?alt=rss&kind=album&hl=en_US");
            if (is_wp_error($rss)) {
                $out->error = $rss->get_error_message();
            } else if (!Common::get_item($rss, 'atom:id')) {
                $out->error = __('Invalid picasa username: ', 'peg') . $user;
            } else {
                $items = Common::get_item($rss, 'item');
                $output = '';
                if ($items) {
                    if (!is_array($items)) $items = array($items);
                    $output .= "\n<table><tr>\n";
                    $i = 0;
                    $max_albums = get_option('peg_max_albums_displayed');
                    foreach ($items as $item) {
                        // http://picasaweb.google.com/data/entry/base/user/wotttt/albumid/5408701349107410241?alt=rss&amp;hl=en_US
                        $guid = str_replace("entry", "feed", Common::get_item($item, 'guid')) . "&kind=photo";
                        $title = Common::escape(Common::get_item($item, 'title'));
                        $desc = Common::escape(Common::get_item($item, 'media:description'));
                        $url = Common::get_item_attr($item, 'media:thumbnail', 'url');
                        $item_type = (strpos($item, 'medium=\'video\'') !== false ? 'video' : 'image');

                        // resize the thumbnail URL so that it fits properly in the media
                        // window
                        $url = str_replace('s160-c', 's140-c-o', $url);

                        // generate the output
                        $output .= "<td><a href='#$guid'><img src='$url' alt='$desc' type='$item_type'/><span>$title</span></a></td>\n";

                        // increment the shared image counter for the following
                        // two checks
                        $i++;

                        // determine if we need to stop outputting albums
                        if (($max_albums > 0) && ($i >= $max_albums)) {
                            // we've reached our max, break out of the loop
                            break;
                        }

                        // determine if we need to break this row and start a new
                        // one
                        if ($i % 4 == 0) $output .= "</tr><tr>\n";
                    }// end foreach album item to output
                    $output .= "</tr></table>\n";
                }// end if we have items to output

                $out->items = Common::get_item($rss, 'openSearch:totalResults');
                $out->title = Common::get_item($rss, 'title', true);
                $out->data = $output;
                $out->cache = $_POST['cache'];
            }// end else for if there were any errors

            echo json_encode($out);
            die();
        }
		function get_title($item){
			$title = Common::escape(Common::get_item($item, 'media:description'));
			if(empty($title)){
				$title = Common::escape(Common::get_item($item, 'media:title'));
			}
			return $this->configuration->parse_caption($title);
		}
        /**
         * wp_ajax_peg_get_images
         * print html for images
         *
         */
        function get_images()
        {

            if (!current_user_can('picasa_dialog')) {
                echo json_encode((object)array('error' => __('Insufficient privelegies', 'peg')));
                die();
            }

            $out = (object)array();

            if (isset($_POST['guid'])) {
                // determine if this guid is base64 encoded or a straight album URL,
                // decoding if necessary
                if (strpos($_POST['guid'], 'http') !== 0) {
                    // decode it
                    $album = base64_decode($_POST['guid']);
                } else {
                    // simply store it after decoding any entities that may have been
                    // created by the editor or elsewhere
                    $album = html_entity_decode($_POST['guid']);
                }
            } else die();

            $rss = $this->picasaAccess->get_feed($album);
            if (is_wp_error($rss)) {
                $out->error = $rss->get_error_message();
            } else if (!Common::get_item($rss, 'atom:id')) {
                $out->error = __('Invalid album ', 'peg');
            } else {
                $items = Common::get_item($rss, 'item');
                $output = '';
                $key = 1;
                $images = array();
                $sort = $this->options['peg_img_sort'];
                $dialog_crop = ($this->options['peg_dialog_crop'] == 1 ? '-c' : '');
                if ($items) {
                    if (!is_array($items)) $items = array($items);
                    foreach ($items as $item) {
                        switch ($sort) {
                            case 0:
                                $key++;
                                break;
                            case 1:
                                $key = strtotime(Common::get_item($item, 'pubDate', true));
                                break;
                            case 2:
                                $key = Common::get_item($item, 'title', true);
                                break;
                            case 3:
                                $key = Common::get_item($item, 'media:title', true);
                                break;
                        }

						$title = $this->get_title($item);

                        $images[$key] = array(
                            'album' => Common::get_item($item, 'link'), // picasa album image
                            'title' => $title,
                            'file' => Common::escape(Common::get_item($item, 'media:title')),
                            'desc' => Common::escape(Common::get_item($item, 'media:description')),
                            'item_type' => (strpos($item, 'medium=\'video\'') !== false ? 'video' : 'image'),
                            'url' => str_replace('s72', 's144' . $dialog_crop . '-o', Common::get_item_attr($item, 'media:thumbnail', 'url')),
	                        'width' => Common::get_item($item, 'gphoto:width'),
	                        'height' => Common::get_item($item, 'gphoto:height')
                        );
                    }
                    if ($this->options['peg_img_asc']) ksort($images);
                    else krsort($images);
                    $output .= "\n<table><tr>\n";
                    $i = 0;
                    foreach ($images as $item) {
                        $output .= "<td><a href='{$item['album']}' data-size='{$item['width']}x{$item['height']}'><img src='{$item['url']}' alt='{$item['file']}' type='{$item['item_type']}' title='{$item['desc']}' /><span>{$item['title']}</span></a></td>\n";
                        if ($i++ % 4 == 3) $output .= "</tr><tr>\n";
                    }
                    $output .= "</tr></table>\n";
                }

                // do our action for dialog footer
                $output = apply_filters('peg_get_images_footer', $output);

                // add our successful results to the output to return
                $out->items = Common::get_item($rss, 'openSearch:totalResults');
                $out->title = Common::get_item($rss, 'title', true);
                $out->data = $output;
                $out->cache = $_POST['cache'];
            }// end else for if we had an error getting the images

            // output the result and exit
            echo json_encode($out);
            die();
        }

        /**
         * wp_ajax_peg_save_state
         * save state of dialog
         */
        function save_state()
        {
            if (!current_user_can('picasa_dialog')) {
                echo json_encode((object)array('error' => __('Insufficient privelegies', 'peg')));
                die();
            }

            if (!isset($_POST['state'])) die();
            global $current_user;

            switch ($saved_state = sanitize_text_field($_POST['state'])) {
                case 'nouser' :
                case 'albums' :
                    update_option('peg_saved_user_name', sanitize_text_field($_POST['last_request']));
                    break;
                case 'images' :
                    update_option('peg_last_album', sanitize_text_field($_POST['last_request']));
                    break;
                default:
                    die();
            }
            update_option('peg_saved_state', $saved_state);
            die();
        }

        /**
         * wp_ajax_peg_process_shortcode
         * convert image shortcode into HTML to return to the editor
         *
         */
        function peg_process_shortcode()
        {

            if (!current_user_can('picasa_dialog')) {
                echo json_encode(array('error' => __('Insufficient privelegies', 'peg')));
                die();
            }

            // verify that we have our data
            if (!isset($_POST['data'])) {
                // we don't have any data
                echo json_encode(array('error' => __('No shortcode to process', 'peg')));
                die();
            }

            // process the shortcodes and return the result to the ajax caller
            echo json_encode(array('html' => do_shortcode(stripslashes($_POST['data']))));
            die();
        }// end function peg_process_shortcode()

        /**
         * Config scrips and styles and print iframe content for dialog
         *
         */
        function media_upload_picasa()
        {

            if (!current_user_can('picasa_dialog')) return;

            // add script and style for dialog
            add_action('admin_print_styles', array(&$this, 'add_style'));
            add_action('admin_enqueue_scripts', array(&$this, 'add_script'));

            // we do not need default script for media_upload
            $to_remove = explode(',', 'swfupload-all,swfupload-handlers,image-edit,set-post-thumbnail,imgareaselect');
            foreach ($to_remove as $handle) {
                if (function_exists('wp_dequeue_script')) wp_dequeue_script($handle);
                else wp_deregister_script($handle);
            }

            // but still reuse code for make media_upload iframe
            return wp_iframe(array(&$this, 'type_dialog'));
        }

        /**
         * Attach script and localisation text in dialog
         * run from action 'admin_enqueue_scripts' from {@link media_upload_picasa()}
         *
         * @global object $wp_scripts
         */
        function add_script()
        {
// load the appropriate older thickbox-based media-upload scripts that
// we will depend upon:
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');

// now add our peg-script:
            global $wp_scripts;
            $wp_scripts->add('peg-script', plugins_url('/peg-scripts.js', __FILE__), array('jquery'), PEG_VERSION);
            $options = array(
                'waiting' => __("<img src='".plugins_url('/loading.gif', __FILE__)."' height='16'
                                                                       width='16'/> Please wait", 'peg'),
                'env_error' => __("Error: Cannot insert image(s) due to incorrect or missing /wp-admin/js/media-upload.js\nConfirm that media-upload.js is loaded in the parent/editor window.\n\nThis error can be caused by:\n\n#1 - opening the image selection dialog before the Wordpress admin window\nis fully loaded.  Reload the post/page editor page in wp-admin and try again.\n\n#2 - the CKEditor for WP plugin is known to conflict with media-upload.js\nDisable the plugin and try again.", 'peg'),
                'image' => __('Image', 'peg'),
                'gallery' => __('Gallery', 'peg'),
                'reload' => __('Reload', 'peg'),
                'options' => __('Options', 'peg'),
                'album' => __('Album', 'peg'),
                'shortcode' => __('Shortcode', 'peg'),
                'thumb_w' => get_option('thumbnail_size_w'),
                'thumb_h' => get_option('thumbnail_size_h'),
                'thumb_crop' => get_option('thumbnail_crop'),
                'state' => 'albums',
            );
            foreach ($this->configuration->get_options() as $key => $val) {
                if (!is_array($val)) // skip arrays: peg_roles
                    $options[$key] = $val;
            }

            if ($options['peg_save_state']) {
                if ($options['peg_saved_state']) $options['state'] = $options['peg_saved_state'];
                if ($options['peg_saved_user_name']) $options['peg_user_name'] = $options['peg_saved_user_name'];
            }

            $options['peg_user_name'] = trim($options['peg_user_name']);
            if ('' == $options['peg_user_name']) $options['peg_user_name'] = 'undefined';
            if ('undefined' == $options['peg_user_name']) $options['state'] = 'nouser';

            foreach ($options as $key => $val) {
                $options[$key] = rawurlencode($val);
            }
            $wp_scripts->localize('peg-script', 'peg_options', $options);

            $wp_scripts->enqueue('peg-script');
        }

        /**
         * Request styles
         * run by action 'admin_print_styles' from {@link media_upload_picasa()}
         *
         * @global boolean $is_IE
         */
        function add_style()
        {
            global $is_IE;
            wp_enqueue_style('media');
        }

        /**
         * Print dialog html
         * run by parameter in (@link wp_iframe()}
         *
         * @global object $current_user
         */
        function type_dialog()
        {

            /*
            <a href="#" class="button alignright">Search</a>
            <form><input type="text" class="alignright" value="Search ..."/></form>
            */
            ?>
            <div id="peg-nouser" class="peg-header" style="display:none;">
                <input type="text" class="alignleft" value="user name"/>
                <a id="peg-change-user" href="#"
                   class="button alignleft peg-space"><?php _e('Change user', 'peg') ?></a>
                <a id="peg-cu-cancel" href="#" class="button alignleft peg-space"><?php _e('Cancel', 'peg') ?></a>

                <div id="peg-message1" class="alignleft"></div>
                <br style="clear:both;"/>
            </div>
            <div id="peg-albums" class="peg-header" style="display:none;">
                <a id="peg-user" href="#" class="button alignleft"></a>

                <div id="peg-message2" class="alignleft"><?php _e('Select an Album', 'peg') ?></div>
                <a id="peg-switch2" href="#" class="button alignleft"><?php _e('Album', 'peg') ?></a>
                <a href="#" class="peg-options button alignright peg-space"><?php _e('Options', 'peg'); ?></a>
                <a href="#" class="peg-reload button alignright"></a>
                <br style="clear:both;"/>
            </div>
            <div id="peg-images" class="peg-header" style="display:none;">
                <a id="peg-album-name" href="#" class="button alignleft"><?php _e('Select an Album', 'peg') ?></a>

                <div id="peg-message3" class="alignleft"><?php _e('Select images', 'peg') ?></div>

                <a id="peg-switch" href="#" class="button alignleft"><?php _e('Image', 'peg') ?></a>
                <a id="peg-insert" href="#" class="button alignleft peg-space"
                   style="display:none;"><?php _e('Insert', 'peg') ?></a>
                <a href="#" class="peg-options button alignright peg-space"></a>
                <a href="#" class="peg-reload button alignright"></a>
                <br style="clear:both;"/>
            </div>

            <div id="peg-options" style="display:none;">
                <?php // print out the shared options for the dialog
                $this->admin->peg_shared_options(false);

                ?>
            </div><!-- end peg-options -->
            <div id="peg-main">
            </div>
        <?php
        }// end function type_dialog()


    }
}