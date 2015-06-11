<?php
namespace photo_express;
//TODO make the loading of the options more performant by using only one option in wordpress and save all options in an array
if (!class_exists("Settings_Storage")) {
    class Settings_Storage
    {
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
            'peg_icon' => 1,
            'peg_roles' => array('administrator' => 1),
            'peg_user_name' => 'undefined',

            'peg_caption' => 0,
            'peg_caption_css' => '',
            'peg_caption_style' => '',
            'peg_caption_p_css' => '',
            'peg_caption_p_style' => '',
            'peg_title' => 1,
            'peg_parse_caption' => 1,
            'peg_link' => 'photoswipe',
            'peg_photoswipe_caption_num' => '1',
            'peg_photoswipe_caption_view' => '1',
            'peg_photoswipe_caption_dl' => '1',
            'peg_relate_images' => '1',

            'peg_img_align' => 'left',
            'peg_auto_clear' => '1',
            'peg_img_css' => '',
            'peg_img_style' => '',
            'peg_a_img_css' => '',
            'peg_a_img_style' => '',
            'peg_return_single_image_html' => '',

            'peg_phototile' => '',
            'peg_featured_tag' => 1,
            'peg_additional_tags' => '',

            'peg_gal_align' => 'left',
            'peg_gal_css' => '',
            'peg_gal_style' => '',

            'peg_img_sort' => 0,
            'peg_img_asc' => 1,
            'peg_dialog_crop' => 1,
            'peg_max_albums_displayed' => '',

            'peg_gal_order' => 0,

            'peg_footer_link' => 1,
            'peg_donate_link' => 1,

            'peg_save_state' => 1,
            'peg_saved_state' => '',
            'peg_last_album' => '',
            'peg_saved_user_name' => '',

            'peg_large_limit' => '',
            'peg_single_image_size' => 'w400',
            'peg_single_image_size_format' => 'P',
            'peg_single_video_size' => 'w400',
            'peg_single_video_size_format' => 'P',

	        'peg_migrate_state' => 0
        );

	    public function get_option($name){
		    return $this->get_options()[$name];
	    }
	    public function set_option($name, $value){
		    $this->get_options()[$name] = $value;
		    update_option($name,$value);
	    }

	    private function migrate_if_possible(){
		    //First check if the user has already saved some uptions
		    $migration_state = get_option('peg_migrate_state');
		    if(!isset($migration_state) || !$migration_state){
			    //No options have been saved and no migration attempt has been tried before
			    //check for pe2 options!
			    foreach($this->options as $key => $value){
				    $legacy_option_value = get_option('pe2'.substr($key,3));
				    if(isset($legacy_option_value)){
					    //Set the corresponding option
					    $this->options[$key] = $legacy_option_value;
				    }
			    }
			    $this->options['peg_migrate_state'] = true;
			    //Store options
			    $this->store();
		    }
	    }
	    private function store(){

		    foreach($this->options as $key => $value){
			    update_option($key,$value);
		    }
	    }
        /**
         * @return array
         */
        public function get_options()
        {
	        if(!$this->initialized){
		        // -----------------------------------------------------------------------
		        // read all of the options from the database and store them in our local
		        // options array
		        foreach ($this->options as $key => $option) {
			        $this->options[$key] = get_option($key,$option);
			        if (!preg_match('/^[whs]\d+$/',$this->options['peg_large_limit'])){
				        $this->options['peg_large_limit'] = '';
			        }
		        }
		        $this->initialized = true;
	        }
            return $this->options;
        }



        function init_site_options()
        {
            $this->walk_blogs('init_options');
        }

        function delete_site_options()
        {
            $this->walk_blogs('delete_options');
            if (function_exists('delete_site_option')) delete_site_option('peg_multisite');
        }

        /**
         * Walk all blogs and apply $func to every founded
         *
         * @global integer $blog_id
         * @param $func Function to apply changes to blog
         */
        function walk_blogs($func)
        {

            $walk = isset($_GET['networkwide']) || isset($_GET['sitewide']); // (de)activate by command from site admin

            if (function_exists('get_site_option')) {
                $active_sitewide_plugins = (array)maybe_unserialize(get_site_option('active_sitewide_plugins'));
                $walk = $walk || isset($active_sitewide_plugins[plugin_basename(__FILE__)]);
            }

            if ($walk && function_exists('switch_to_blog')) {

                add_site_option('peg_multisite', true);

                global $blog_id, $switched_stack, $switched;
                $saved_blog_id = $blog_id;

                global $wpdb;
                $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'");
                if (is_array($blogs)) {
                    reset($blogs);
                    foreach ((array)$blogs as $new_blog_id) {
                        switch_to_blog($new_blog_id);
                        $this->$func();
                        array_pop($switched_stack); // clean
                    }
                    switch_to_blog($saved_blog_id);
                    array_pop($switched_stack); // clean
                    $switched = (is_array($switched_stack) && count($switched_stack) > 0);
                }
            } else {
                $this->$func();
            }
        }

        /**
         * Enable plugin configuration and set roles by config
         */
        function init_options()
        {
	        //Check for migration
	        $this->migrate_if_possible();

            foreach (get_option('peg_roles', $this->options['peg_roles']) as $role => $data) {
                if ($data) {
                    $role = get_role($role);
                    $role->add_cap('picasa_dialog');
                }
            }
        }

        /**
         * Delete plugin configuration flag
         */
        function delete_options()
        {
	        //Delete all options!
	        foreach($this->options as $key => $value){
		        delete_option($key);
	        }
        }

        function parse_caption($string)
        {
            // determine if there was just a file-name returned as the caption,
            // if so simply make it a blank caption
            if ($this->options['peg_parse_caption'] == '1') {
                // perform the replacement
                return preg_replace('/^[^\s]+\.[^\s]+$/', '', $string);
            } else {
                // simply return the caption untouched
                return $string;
            }
        }// end function parse_caption(..)
	    function wpmu_new_blog($new_blog_id)
	    {
		    switch_to_blog($new_blog_id);
		    $this->init_options();
		    restore_current_blog();
	    }
    }
}