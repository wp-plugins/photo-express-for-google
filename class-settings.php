<?php
namespace photo_express;
require_once plugin_dir_path(__FILE__).'class-google-photo-access.php';
require_once plugin_dir_path(__FILE__).'class-settings-storage.php';
require_once plugin_dir_path(__FILE__).'class-common.php';

// ########################################################################
if (!class_exists( "Settings" )) {
    class Settings
    {
	    /**
	     * Possible values used for sorting the images within an album
	     */
	    const SORT_NONE = '0';
	    const SORT_DATE = '1';
	    const SORT_FILE = '2';
	    const SORT_TITLE = '3';
	    const SORT_RANDOM = '4';
	    /**
	     * @var $picasaAccess Google_Photo_Access
	     */
        private $picasaAccess;

	    /**
	     * @var $configuration Settings_Storage
	     */
	    private $configuration;

	    function __construct( $configuration, $picasaAccess ) {
		    $this->configuration = $configuration;
		    $this->picasaAccess  = $picasaAccess;
	    }


	    function load_textdomain()
        {
            load_plugin_textdomain('peg', false, dirname(plugin_basename(__FILE__)));
        }

        /**
         * Add setting link to plugin action
         * run by action 'plugin_action_links_*'
         *
         */
        function add_settings_link($links)
        {
            if (!current_user_can('manage_options')) return $links;
            $settings_link = '<a href="options-general.php?page=photo-express">' . __('Settings', 'peg') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Config settings, add actions for registry setting and add styles
         * run by action 'admin_menu'
         *
         */
        function add_settings_page()
        {
            if (!current_user_can('manage_options')) return;
            add_options_page(__('Photo Express for Google', 'peg'), __('Photo Express for Google', 'peg'), 'manage_options', 'photo-express', array(&$this, 'settings_form'));
            add_action('admin_init', array(&$this, 'settings_reg'));
            add_action('admin_print_styles-settings_page_photo-express', array(&$this, 'settings_style'));
        }

        /**
         * Register all option for save
         *
         */
        function settings_reg()
        {
	        register_setting('photo-express', 'peg_general_settings', array(&$this, 'validate_update'));
	        $this->picasaAccess->register_auth_settings();
        }

	    function validate_update($input){
		    //First make a validation
		    if(!(is_numeric($input['peg_cache_expiration_time']) && floor($input['peg_cache_expiration_time']) == $input['peg_cache_expiration_time'] && $input['peg_cache_expiration_time'] >= 0)){
			    add_settings_error('peg_general_settings[peg_cache_expiration_time]','cache_expiration_invalid','Only positive whole numbers are allowed for the cache expiration');
			    //reset
			    $input['peg_cache_expiration_time'] = $this->configuration->get_option('peg_cache_expiration_time');
		    }
		    return $input;

	    }
        /**
         * Define misseed style for setting page
         */
        function settings_style()
        {

        }

        /**
         * Add help to the top of the setting page
         */
        function contextual_help($help, $screen)
        {
            if ('settings_page_photo-express' == $screen) {
                $homepage = __('Plugin homepage', 'peg');
                $messages = array(
                    __('To receive access for private album press link under username. You will be redirected to Google for grant access. If you press "Grant access" button you will be returned to settings page, but access will be granted.', 'peg'),
                    __("In the album's images you have to press button with 'Image' button. The 'Gallery' will appear on the button and you can select several images. This can be happen if you use Thickbox, Lightbox or Highslide support.", 'peg'),
                    __("By default images inserted in the displayed order. If you need control the order in gallery - enable 'Selection order'.", 'peg'),
                    __('To use external libraries like Thickbox, Lightbox or Highslide you need to install and integrate the library independently', 'peg'),
                );
                $message = '<p>' . implode('</p><p>', $messages) . '</p>';
                $help .= <<<HELP_TEXT
				<h5>Small help</h5>
				$message
				<div class="metabox-prefs">
					<a href="http://wordpress.org/extend/plugins/picasa-express-x2">$homepage</a>
				</div>
HELP_TEXT;
            }
            return $help;
        }



        /**
         * Print the shared options (between settings and dialog)
         *
         * @param $settings - boolean - whether or not we're displaying options from plugin settings
         * @return none, outputs the options
         */
        function peg_shared_options($settings)
        {
            // print out the shared options, decyphering between the main settings
            // page and the limited overrides in the dialog
            if ($settings) {
                // we're in the main settings
                $this->picasaAccess->render_settings_overview();
	            ?>

	            <h3><?php _e('Google+ Express access', 'peg') ?></h3>
                <table class="peg-form-table">

                    <?php
                    $option =$this->configuration->get_option('peg_roles');
                    $editable_roles = get_editable_roles();

                    $peg_roles = array();
                    foreach ($editable_roles as $role => $details) {
                        $name = translate_user_role($details['name']);
                        $peg_roles[] = "<label><input id=\"peg_roles[$role]\" name=\"peg_general_settings[peg_roles][$role]\" type=\"checkbox\" value=\"1\" " . checked(isset($option[$role]), true, false) . "/> $name</label>";
                    }
                    $out = implode('<br/>', $peg_roles);
                    unset($peg_roles);

                    $this->make_settings_row(
                        __('Assign capability to Roles', 'peg'),
                        $out,
                        __('Roles for users who can use Google+ albums access via plugin', 'peg')
                    );

                    ?>

                </table>

                <h3><?php _e('Display properties', 'peg') ?></h3>
                <table class="peg-form-table">

                    <?php
                    $user =$this->configuration->get_option('peg_user_name');

                    $this->make_settings_row(
                        __('Google user name for site', 'peg')
                        ,'<input type="text" class="regular-text" id="peg_user_name" name="peg_general_settings[peg_user_name]" value="' . esc_attr($user) . '" />'
                         ,__('The Google user which albums should be displayed')
                    );


                    $option =$this->configuration->get_option('peg_save_state');
                    $this->make_settings_row(
                        __('Save last state', 'peg'),
                        '<label><input type="checkbox" id="peg_save_state" name="peg_general_settings[peg_save_state]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Save last state in dialog', 'peg') . '</label> ',
                        __('Save the last used username when it changes, the last selected album if you insert images, or the albums list if you insert an album shorcode', 'peg'),
                        'class="picasa-site-user" style="display:table-row"'
                    );

                    $opts = array(
                        1 => __('Picasa square icon', 'peg'),
                        2 => __('Picasa square grayscale icon', 'peg'),
                        3 => __('Picasa round icon', 'peg'),
                        4 => __('Picasa round grayscale icon', 'peg'),
                    );
                    $option =$this->configuration->get_option('peg_icon');
                    $out = '';
                    foreach ($opts as $i => $text) {
                        $out .= '<label>';
                        $out .= "<input type=\"radio\" id=\"peg_icon_$i\" name=\"peg_general_settings[peg_icon]\" value=\"$i\" " . checked($option, $i, false) . " />";
                        $out .= "<img src=\"".plugins_url('icon_picasa'.$i.'.gif',__FILE__)."\" alt=\"$text\" title=\"$text\"/> &nbsp; ";
                        $out .= '</label>';
                    }

                    $this->make_settings_row(
                        __('Picasa icon', 'peg'),
                        $out,
                        __('This icon marks the dialog activation link in the edit post page', 'peg')
                    );

                    $opts = array(
                        0 => __('None', 'peg'),
                        1 => __('Date', 'peg'),
                        2 => __('Title', 'peg'),
                        3 => __('File name', 'peg'),
                    );
                    $option =$this->configuration->get_option('peg_img_sort');
                    $out = '';
                    foreach ($opts as $i => $text) {
                        $out .= '<label>';
                        $out .= "<input type=\"radio\" id=\"peg_img_sort_$i\" name=\"peg_general_settings[peg_img_sort]\" value=\"$i\" " . checked($option, $i, false) . " /> $text &nbsp; ";
                        $out .= '</label>';
                    }
                    $this->make_settings_row(
                        __('Sorting images in album', 'peg'),
                        $out,
                        __('This option drives image sorting in the dialog', 'peg')
                    );

                    $option =$this->configuration->get_option('peg_img_asc');
                    $this->make_settings_row(
                        __('Sorting order', 'peg'),
                        '<label><input type="radio" id="peg_img_asc_asc" name="peg_general_settings[peg_img_asc]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Ascending', 'peg') . '</label> &nbsp; ' .
                        '<label><input type="radio" id="peg_img_asc_desc" name="peg_general_settings[peg_img_asc]" value="0" ' . checked($option, '0', false) . ' /> ' . __('Descending', 'peg') . '</label> '
                    );

                    $option =$this->configuration->get_option('peg_dialog_crop');
                    $this->make_settings_row(
                        __('Selection dialog thumbnail style', 'peg'),
                        '<label><input type="radio" id="peg_dialog_crop_true" name="peg_general_settings[peg_dialog_crop]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Crop into a square', 'peg') . '</label> &nbsp; ' .
                        '<label><input type="radio" id="peg_dialog_crop_false" name="peg_general_settings[peg_dialog_crop]" value="0" ' . checked($option, '0', false) . ' /> ' . __('Scale proportionally', 'peg') . '</label> ',
                        __('This applies to image thumbnails only, not album cover thumbnails')
                    );

                    $option =$this->configuration->get_option('peg_max_albums_displayed');
                    $this->make_settings_row(
                        __('Maximum albums displayed', 'peg'),
                        '<label>' . __('Max number of albums to display in the dialog:', 'peg') . '  <input type="text" id="peg_max_albums_displayed" name="peg_general_settings[peg_max_albums_displayed]" value="' . $option . '" size=4 /></label>',
                        __('Leave blank to display all albums', 'peg')
                    );

                    ?>

                </table>
            <?php }// end if we're in the main settings page

            ?>
				<h3><?php _e('Image properties', 'peg') ?></h3>

				<table class="peg-form-table">

					<?php
            // ---------------------------------------------------------------------
            // single image thunbnail size (override)
            $format =$this->configuration->get_option('peg_single_image_size_format');
            $option =$this->configuration->get_option('peg_single_image_size');
            preg_match('/(\w)(\d+)-*([whs]*)(\d*)/', $option, $mode);
            if (strpos($option, '-c') !== false)
                $crop = true;
            else
                $crop = false;
            if (!$mode) $mode = array('', '', '');
            $this->make_settings_row(
                __('Single image thumbnail size', 'peg'),
                '<input type="radio" id="peg_single_image_size_format_proportionally" name="peg_general_settings[peg_single_image_size_format]" value="P" ' . checked($format, 'P', false) . ' /> Proportionally &nbsp; ' .
                '<input type="radio" id="peg_single_image_size_format_non_proportionally" name="peg_general_settings[peg_single_image_size_format]" value="N" ' . checked($format, 'N', false) . ' /> Non-proportionally &nbsp; ' .
                '<input type="radio" id="peg_single_image_size_format_custom" name="peg_general_settings[peg_single_image_size_format]" value="C" ' . checked($format, 'C', false) . ' /> Custom<br/>' .
                '<div id="peg_single_image_proportional">' .
                '	Scale: &nbsp; &nbsp;[ <label><input type="radio" id="peg_single_image_size_mode_width" name="peg_general_settings[peg_single_image_size_mode]" class="peg_single_image_size" value="w" ' . checked($mode[1], 'w', false) . ' /> ' . __('width', 'peg') . '</label> &nbsp; ' .
                '	<label><input type="radio" id="peg_single_image_size_mode_height" name="peg_general_settings[peg_single_image_size_mode]" class="peg_single_image_size" value="h" ' . checked($mode[1], 'h', false) . ' /> ' . __('height', 'peg') . '</label> &nbsp; ' .
                '	<label><input type="radio" id="peg_single_image_size_mode_any" name="peg_general_settings[peg_single_image_size_mode]" class="peg_single_image_size" value="s" ' . checked($mode[1], 's', false) . ' /> ' . __('any', 'peg') . '</label> ' .
                __(' ]&nbsp; &nbsp; proportionally to ', 'peg') .
                '	<input type="text" id="peg_single_image_size_dimension" name="peg_general_settings[peg_single_image_size_dimension]" class="peg_single_image_size" style="width:60px;"  value="' . $mode[2] . '" />' .
                __(' pixels.', 'peg') .
                '	<label> &nbsp; &nbsp; &nbsp; <input type="checkbox" id="peg_single_image_size_crop" name="peg_general_settings[peg_single_image_size_crop]" class="peg_single_image_size" value="-c" ' . checked($crop, true, false) . ' /> ' . __(' Crop image into a square.', 'peg') . '</label> ' .
                '</div>' .
                '<div id="peg_single_image_non">' .
                '	Width: <input type="text" id="peg_single_image_size_width" name="peg_general_settings[peg_single_image_size_width]" style="width: 60px;" value="' . $mode[2] . '" /> &nbsp; &nbsp; ' .
                '	Height: <input type="text" id="peg_single_image_size_height" name="peg_general_settings[peg_single_image_size_height]" style="width: 60px;" value="' . $mode[4] . '" />' .
                '</div>' .
                '<div id="peg_single_image_custom">' .
                '	Custom request string: <input type="text" id="peg_single_image_size" name="peg_general_settings[peg_single_image_size]" style="width: 120px;" value="' . $option . '" />' .
                '</div>'
                ,
                '',
                '',
                'id="peg_single_image_size_message" style="display:' . (($option) ? 'block' : 'none') . ';"'
            );
            ?>
					<script type="text/javascript">
						// ------------------- TOGGLE SIZE MODE -------------------
						function peg_toggle_image_size_mode(type){
							var format = jQuery('input[name=peg_general_settings\\[peg_' + type + '_size_format\\]]:checked').val();
							if(format == 'C'){
								// custom, hide the others
								jQuery('#peg_' + type + '_proportional, #peg_' + type + '_non').hide();
								jQuery('#peg_' + type + '_custom').show();
							}else if(format == 'N'){
								// non-proportionally
								jQuery('#peg_' + type + '_proportional, #peg_' + type + '_custom').hide();
								jQuery('#peg_' + type + '_non').show();

								// execute our calculation based on data provided
								peg_compute_nonpro_image_size(jQuery('input[id=peg_' + type + '_size_width], input[id=peg_' + type + '_size_height]'), type);
							}else{
								// proportionally
								jQuery('#peg_' + type + '_non, #peg_' + type + '_custom').hide();
								jQuery('#peg_' + type + '_proportional').show();

								// execute our calculation based on data provided
								peg_compute_image_size('mode', jQuery('input[name=peg_general_settings\\[peg_' + type + '_size_mode\\]]:checked').val(), type);
								peg_compute_image_size('size', jQuery('input[id=peg_' + type + '_size_dimension]').val(), type);
								peg_determine_crop(type, jQuery('input[id=peg_' + type + '_size_crop]'));
							}
						}// end function peg_toggle_image_size_mode()

						// on load, toggle the appropriate section based on the mode
						// from the db
						peg_toggle_image_size_mode('single_image');

						// on image size format change, update the input fields
						jQuery('input[name=peg_general_settings\\[peg_single_image_size_format\\]]').click(function(){
							peg_toggle_image_size_mode('single_image');
						});

						// ------------------- PROPORTIONAL IMAGES -------------------
						function peg_compute_image_size(mode,value,type) {
							var target_input = jQuery('input[id=peg_' + type + '_size]');
							if (target_input.length == 0) {
								// this is the large image size selection
								target_input = jQuery('input[id=peg_' + type + '_limit]');
							}
							var val = target_input.val();
							var fireChange = false;
							// check for the case where it was just enabled after having
							// been disabled and saved
							if ((val == '') || (val == 'w')) {
								// override with some default
								val = 'w600';
								fireChange = true;
							}

							// split into our parts
							var parts = {
								mode: val.substring(0, 1),
								size: val.replace(/^[a-z]*([0-9]+).*$/, '$1'),
								crop: val.replace(/^[^\-]+(.*)$/, '$1')
							};


							// check if a change occured
							if (parts[mode] != value) {
								// override the particular part that was just changed
								parts[mode] = value;
								fireChange = true;
							}


							// make sure our crop variable is correct
							if ((parts.crop != '') && (parts.crop != '-c')) {
								parts.crop = '';
								fireChange = true;
							}

							if (fireChange) {
								// store the value back in our target
								target_input.val(parts.mode + parts.size + parts.crop);

								// update the text that displays the setting being used
								jQuery('#peg_' + type + '_size_message_option').text(parts.mode + parts.size + parts.crop);
								console.log("Changed "+mode+" to "+value);
								// trigger the .change event since we're changing
								// the value of the target with js, the event
								// doesn't get triggered
								target_input.trigger('change');
							}
						}// end function peg_compute_image_size(..)

						// if mode changes, update image size
						jQuery('input[name=peg_general_settings\\[peg_single_image_size_mode\\]]').change(function(){ if (jQuery(this).attr('checked')) peg_compute_image_size('mode',jQuery(this).val(), 'single_image'); });

						// if size changes, update image size
						jQuery('#peg_single_image_size_dimension').change(function(){
							peg_compute_image_size('size',jQuery('#peg_single_image_size_dimension').val(), 'single_image');
						});

						// function to determine size crop attribute
						function peg_determine_crop(name, obj){
							// use the checked selector to determine if the checkbox is
							// checked or not
							if(jQuery('#peg_' + name + '_size_crop:checked').length > 0){
								// the checkbox is checked
								peg_compute_image_size('crop',jQuery(obj).val(), name);
							}else{
								// the checkbox is not checked
								peg_compute_image_size('crop','',name);
							}
						}// end function peg_determine_crop(..)

						// if crop changes, update image size
						jQuery('#peg_single_image_size_crop').change(function(){
							peg_determine_crop('single_image', this);
						});

						// ------------------- NON-PROPORTIONAL -------------------
						function peg_compute_nonpro_image_size(obj, type){
							var target_input = jQuery('#peg_' + type + '_size');
							var val = target_input.val();

							// check for the case where it was just enabled after having
							// been disabled and saved
							if((val == '') || (val == 'w')){
								// override with some default
								val = 'w600-h450';
							}

							// split into our parts
							var width = val.match(/w([0-9]+)/);
							if(width == null){
								width = '600';
							}else{
								width = width[1];
							}
							var height = val.match(/h([0-9]+)/);
							if(height == null){
								height = '450';
							}else{
								height = height[1];
							}

							// determine if the width or the height was changed
							if(obj.attr('value') != ''){
								// we have a value, set it appropriately
								if(obj.attr('name').indexOf('width') !== -1){
									// this is width
									width = obj.attr('value');
								}else{
									// this is height
									height = obj.attr('value');
								}
							}

							// store the value back in our target
							target_input.val('w' + width + '-h' + height + '-p');

							// update the text that displays the setting being used
							jQuery('#peg_' + type + '_size_message_option').text('w' + width + 'h' + height + '-c');

							// if the target inputs type is hidden, then also trigger
							// the .change event (hiddens don't automatically trigger
							// the .change event for some reason)
							if(target_input.attr('type') == 'hidden'){
								target_input.trigger('change');
							}
						}// end function peg_compute_nonpro_image_size(..)

						// if the width or height changes, update the value
						jQuery('#peg_single_image_size_width, #peg_single_image_size_height').change(function(){ peg_compute_nonpro_image_size(jQuery(this), 'single_image'); });
					</script>
					<?php
            // ---------------------------------------------------------------------
            // single video thunbnail size (override)
            $format =$this->configuration->get_option('peg_single_video_size_format');
            $option =$this->configuration->get_option('peg_single_video_size');
            preg_match('/(\w)(\d+)-*([whs]*)(\d*)/', $option, $mode);
            if (strpos($option, '-c') !== false)
                $crop = true;
            else
                $crop = false;
            if (!$mode) $mode = array('', '', '');
            $this->make_settings_row(
                __('Single video thumbnail size', 'peg'),
                '<input type="radio" id="peg_single_video_size_format_proportional" name="peg_general_settings[peg_single_video_size_format]" value="P" ' . checked($format, 'P', false) . ' /> Proportionally &nbsp; ' .
                '<input type="radio" id="peg_single_video_size_format_non_proportional" name="peg_general_settings[peg_single_video_size_format]" value="N" ' . checked($format, 'N', false) . ' /> Non-proportionally &nbsp; ' .
                '<input type="radio" id="peg_single_video_size_format_custom" name="peg_general_settings[peg_single_video_size_format]" value="C" ' . checked($format, 'C', false) . ' /> Custom<br/>' .
                '<div id="peg_single_video_proportional">' .
                '	Scale: &nbsp; &nbsp;[ <label><input type="radio" id="peg_single_video_size_mode_width" name="peg_general_settings[peg_single_video_size_mode]" class="peg_single_video_size" value="w" ' . checked($mode[1], 'w', false) . ' /> ' . __('width', 'peg') . '</label> &nbsp; ' .
                '	<label><input type="radio" id="peg_single_video_size_mode_height" name="peg_general_settings[peg_single_video_size_mode]" class="peg_single_video_size" value="h" ' . checked($mode[1], 'h', false) . ' /> ' . __('height', 'peg') . '</label> &nbsp; ' .
                '	<label><input type="radio" id="peg_single_video_size_mode_any" name="peg_general_settings[peg_single_video_size_mode]" class="peg_single_video_size" value="s" ' . checked($mode[1], 's', false) . ' /> ' . __('any', 'peg') . '</label> ' .
                __(' ]&nbsp; &nbsp; proportionally to ', 'peg') .
                '	<input type="text" id="peg_single_video_size_dimension" name="peg_general_settings[peg_single_video_size_dimension]" class="peg_single_video_size" style="width:60px;" value="' . $mode[2] . '" />' .
                __(' pixels.', 'peg') .
                '	<label> &nbsp; &nbsp; &nbsp; <input type="checkbox" id="peg_single_video_size_crop" name="peg_general_settings[peg_single_video_size_crop]" class="peg_single_video_size" value="-c" ' . checked($crop, true, false) . ' /> ' . __(' Crop image into a square.', 'peg') . '</label> ' .
                '</div>' .
                '<div id="peg_single_video_non">' .
                '	Width: <input type="text" id="peg_single_video_size_width" name="peg_general_settings[peg_single_video_size_width]" style="width: 60px;" value="' . $mode[2] . '" /> &nbsp; &nbsp; ' .
                '	Height: <input type="text" id="peg_single_video_size_height" name="peg_general_settings[peg_single_video_size_height]" style="width: 60px;" value="' . $mode[4] . '" />' .
                '</div>' .
                '<div id="peg_single_video_custom">' .
                '	Custom request string: <input type="text" id="peg_single_video_size" name="peg_general_settings[peg_single_video_size]" style="width: 120px;" value="' . $option . '" />' .
                '</div>'
                ,
                '',
                '',
                'id="peg_single_video_size_message" style="display:' . (($option) ? 'block' : 'none') . ';"'
            );
            ?>
					<script type="text/javascript">
						// ------------------- TOGGLE SIZE MODE -------------------

						// on load, toggle the appropriate section based on the mode
						// from the db
						peg_toggle_image_size_mode('single_video');

						// on image size format change, update the input fields
						jQuery('input[name=peg_general_settings\\[peg_single_video_size_format\\]]').click(function(){
							peg_toggle_image_size_mode('single_video');
						});

						// ------------------- PROPORTIONAL IMAGES -------------------
						// if mode changes, update image size
						jQuery('input[name=peg_general_settings\\[peg_single_video_size_mode\\]]').change(function(){ if (jQuery(this).attr('checked')) peg_compute_image_size('mode',jQuery(this).val(), 'single_video'); });

						// if size changes, update image size
						jQuery('#peg_single_video_size_dimension').change(function(){
							peg_compute_image_size('size',jQuery('#peg_single_video_size_dimension').val(), 'single_video');
						});

						// if crop changes, update image size
						jQuery('#peg_single_video_size_crop').change(function(){
							peg_determine_crop('single_video', this);
						});

						// ------------------- NON-PROPORTIONAL -------------------
						// if the width or height changes, update the value
						jQuery('#peg_single_video_size_width, #peg_single_video_size_height').change(function(){ peg_compute_nonpro_image_size(jQuery(this), 'single_video'); });
					</script>
<?php

            // ---------------------------------------------------------------------
            // large image size
            $option =$this->configuration->get_option('peg_large_limit');
            preg_match('/(\w)(\d+)(-c)?/', $option, $mode);
            if (!$mode) $mode = array('', '', '');
            $this->make_settings_row(
                __('Large image size', 'peg'),
                '<input type="hidden" id="peg_large_limit" name="peg_general_settings[peg_large_limit]" value = "'.$option.'" />'.
	            '<label><input type="checkbox" id="peg_large_limit_activated" name="peg_general_settings[peg_large_limit_activated]" value="' . $option . '" ' . checked(($option) ? 1 : 0, 1, false) . ' /> ' . __('Set / Limit: ', 'peg') . '</label> ' .
                '<label> &nbsp; &nbsp;[ <input type="radio" id="peg_large_size_mode_width" name="peg_general_settings[peg_large_size_mode]" class="peg_large_limit" value="w" ' . checked($mode[1], 'w', false) . ' ' . disabled(($option) ? 1 : 0, 0, false) . ' /> ' . __('width', 'peg') . '</label> &nbsp; ' .
                '<label><input type="radio" id="peg_large_size_mode_height" name="peg_general_settings[peg_large_size_mode]" class="peg_large_limit" value="h" ' . checked($mode[1], 'h', false) . ' ' . disabled(($option) ? 1 : 0, 0, false) . ' /> ' . __('height', 'peg') . '</label> &nbsp; ' .
                '<label><input type="radio" id="peg_large_size_mode_any" name="peg_general_settings[peg_large_size_mode]" class="peg_large_limit" value="s" ' . checked($mode[1], 's', false) . ' ' . disabled(($option) ? 1 : 0, 0, false) . ' /> ' . __('any', 'peg') . ' ]&nbsp; &nbsp; </label> ' .
                __(' proportionally to ', 'peg') .
                '<input type="text" id="peg_large_size_dimension" name="peg_general_settings[peg_large_size_dimension]" class="peg_large_limit" style="width:60px;" value="' . $mode[2] . '" ' . disabled(($option) ? 1 : 0, 0, false) . ' />' .
                __(' pixels.', 'peg') .
                '<label> &nbsp; &nbsp; &nbsp; <input type="checkbox" id="peg_large_size_crop" name="peg_general_settings[peg_large_size_crop]" class="peg_large_limit" value="-c" ' . checked(isset($mode[3]), true, false) . ' /> ' . __(' Crop image into a square.', 'peg') . '</label> '
                ,
                sprintf(__('Value \'%s\' will be used to set / limit large image'), "<span id=\"peg_large_size_message_option\">$option</span>"),
                '',
                'id="large-limit-message" style="display:' . (($option) ? 'block' : 'none') . ';"'
            );
            ?>
					<script type="text/javascript">
						jQuery('#peg_large_limit_activated').change(function(){
							if (jQuery(this).attr('checked')) {
								// the checkbox is set
								jQuery('input.peg_large_limit').removeAttr('disabled');
								jQuery('#large-limit-message').show();

								// set the default for the input boxes
								jQuery('#peg_large_size_mode_width').attr('checked', 'true');
								jQuery('#peg_large_size_dimension').val('600');

								// call the calculation function for each section
								peg_compute_image_size('mode',jQuery('input[name=peg_general_settings\\[peg_large_size_mode\\]]').val(), 'large');
								peg_compute_image_size('size',jQuery('input[name=peg_general_settings\\[peg_large_size_dimension\\]').val(), 'large');
							} else {
								jQuery('input.peg_large_limit').removeAttr('checked').attr('disabled','disabled');
								jQuery('#peg_large_size_dimension').val('');
								jQuery('#peg_large_size_message_option').text('');
								jQuery('#peg_large_limit').val('');
								jQuery('#large-limit-message').hide();
							}
						});
						// if mode changes, update image size
						jQuery('input[name=peg_general_settings\\[peg_large_size_mode\\]]').change(function(){
							if (jQuery(this).attr('checked')) {
								peg_compute_image_size('mode',jQuery(this).val(), 'large');
							}
						});
						// if size changes, update image size
						jQuery('#peg_large_size_dimension').change(function(){
							peg_compute_image_size('size',jQuery('#peg_large_size_dimension').val(), 'large');
						});
						// if crop changes, update image size
						jQuery('#peg_large_size_crop').change(function(){
							peg_determine_crop('large', this);
						});
					</script>
					<?php

            $option =$this->configuration->get_option('peg_caption');
            $this->make_settings_row(
                __('Display caption', 'peg'),
                '<label><input type="checkbox" id="peg_caption" name="peg_general_settings[peg_caption]" value="1" ' . checked($option, '1', false) . ' onclick="toggle_caption_children()" /> ' . __('Show the caption under thumbnail image', 'peg') . '</label> ',
                null,
                'id="peg_caption_row"'
            );

            $this->make_settings_row(
                __('Caption container CSS class', 'peg'),
                '<input type="text" id="peg_caption_css" name="peg_general_settings[peg_caption_css]" class="regular-text peg_caption_child" value="' . esc_attr($this->configuration->get_option('peg_caption_css')) . '"/>',
                __("You can define one or more classes for the caption container tag", 'peg'),
                ' class="peg_caption_child"'
            );
            $this->make_settings_row(
                __('Caption container style', 'peg'),
                '<input type="text" id="peg_caption_style" name="peg_general_settings[peg_caption_style]" class="regular-text peg_caption_child" value="' . esc_attr($this->configuration->get_option('peg_caption_style')) . '"/>',
                __('You can hardcode any css attributes for the caption container tag', 'peg'),
                ' class="peg_caption_child"'
            );

            $this->make_settings_row(
                __('Caption P CSS class', 'peg'),
                '<input type="text" id="peg_caption_p_css" name="peg_general_settings[peg_caption_p_css]" class="regular-text peg_caption_child" value="' . esc_attr($this->configuration->get_option('peg_caption_p_css')) . '"/>',
                __("You can define one or more classes for the caption P tag", 'peg'),
                ' class="peg_caption_child"'
            );
            $this->make_settings_row(
                __('Caption P style', 'peg'),
                '<input type="text" id="peg_caption_p_style" name="peg_general_settings[peg_caption_p_style]" class="regular-text peg_caption_child" value="' . esc_attr($this->configuration->get_option('peg_caption_p_style')) . '"/>',
                __('You can hardcode any css attributes for the caption P tag', 'peg'),
                ' class="peg_caption_child"'
            );

            // end the caption child container and add the script to handle
            // hiding and clearing the class values if they don't have the
            // caption checkbox set
            ?><script>
function toggle_caption_children(){
	var val = jQuery('#peg_caption:checked').val();
	if(val == '1'){
		// the checkbox is checked, show the children
		jQuery('tr.peg_caption_child').show();
	}else{
		// the checkbox is unchecked, hide the children and clear any values
		jQuery('tr.peg_caption_child').hide();
		jQuery('input.peg_caption_child').val('');
	}
}
jQuery('document').ready(function(){
	// execute the toggle for caption children to hide them if the box is unchecked
	toggle_caption_children();
});
</script>
<?php

            $option =$this->configuration->get_option('peg_title');
            $this->make_settings_row(
                __('Add caption as title', 'peg'),
                '<label><input type="checkbox" id="peg_title" name="peg_general_settings[peg_title]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Show the caption by mouse hover tip', 'peg') . '</label> '
            );

            $option =$this->configuration->get_option('peg_parse_caption');
            $this->make_settings_row(
                __('Remove filename captions', 'peg'),
                '<label><input type="checkbox" id="peg_parse_caption" name="peg_general_settings[peg_parse_caption]" value="1" ' . checked($option, '1', false) . ' /> ' . __('If a caption is detected as the image filename, replace it with blank', 'peg') . '</label> '
            );

            // link option for photos
            if ($settings) {
                $opts = array(
                    'none' => __('No link', 'peg'),
                    'direct' => __('Direct link', 'peg'),
                    'picasa' => __('Link to Google+ Web Album', 'peg'),
                    'lightbox' => __('Lightbox (External)', 'peg'),
                    'thickbox' => __('Thickbox (External)', 'peg'),
                    'thickbox_integrated' => __('Thickbox (Integrated Wordpress version)', 'peg'),
                    'thickbox_custom' => __('Thickbox (Custom from this plugin)', 'peg'),
                    'highslide' => __('Highslide (External)', 'peg'),
                    'photoswipe' => __('PhotoSwipe (Mobile friendly)', 'peg')
                );

                $out = '<select name="peg_general_settings[peg_link]" id="peg_link" onchange="peg_toggle_large_image_link_options()">';
                $option =$this->configuration->get_option('peg_link');
                foreach ($opts as $key => $val) {
                    $out .= "<option value=\"$key\" " . selected($option, $key, false) . ">$val</option>";
                }
                $out .= '</select>';
                $this->make_settings_row(
                    __('Link to larger image', 'peg'),
                    $out,
                    '<span id="peg_external_message">' . __('To use external libraries like Thickbox, Lightbox or Highslide you need to install and integrate the library independently', 'peg') . '</span>'
                );

                $this->make_settings_row(
                    __('PhotoSwipe caption options', 'peg'),
                    '<label><input type="checkbox" id="peg_photoswipe_show_index_position" name="peg_general_settings[peg_photoswipe_show_index_position]" value="1" ' . checked($this->configuration->get_option('peg_photoswipe_show_index_position'), '1', false) . ' /> ' . __('Add the "X / X" text to the top left corner indicating the index of the currently shown image.', 'peg') . '</label> ' .
                    '<br/><label><input type="checkbox" id="peg_photoswipe_show_share_button" name="peg_general_settings[peg_photoswipe_show_share_button]" value="1" ' . checked($this->configuration->get_option('peg_photoswipe_show_share_button'), '1', false) . ' /> ' . __('Add a "Share" button that includes a download option.', 'peg') . '</label> ' .
                    '<br/><label><input type="checkbox" id="peg_photoswipe_show_close_button" name="peg_general_settings[peg_photoswipe_show_close_button]" value="1" ' . checked($this->configuration->get_option('peg_photoswipe_show_close_button'), '1', false) . ' /> ' . __('Add a "Close" button.', 'peg') . '</label> ' .
                    '<br/><label><input type="checkbox" id="peg_photoswipe_show_fullscreen_button" name="peg_general_settings[peg_photoswipe_show_fullscreen_button]" value="1" ' . checked($this->configuration->get_option('peg_photoswipe_show_fullscreen_button'), '1', false) . ' /> ' . __('Add a button that toggles browser fullscreen mode.', 'peg') . '</label> ',
                    null,
                    ' id="peg_photoswipe_options"'
                );

                $option =$this->configuration->get_option('peg_relate_images');
                $this->make_settings_row(
                    __('Relate all of a post\'s images', 'peg'),
                    '<label><input type="checkbox" id="peg_relate_images" name="peg_general_settings[peg_relate_images]" value="1" ' . checked($option, '1', false) . ' /> ' . __('If using PhotoSwipe, Thickbox, Lightbox or Highslide, relate all images in the page/post together for fluid next/prev navigation', 'peg') . '</label> ',
                    null,
                    'id="peg_relate_row"'
                );

                // add the scripts to handle hiding the external script sentence
                // if one of the external options are selected, to handle hiding
                // the "relate images" option if one of the gallery options is
                // enabled, and to handle hiding and clearing the of the second
                // row caption options if they don't have photoswipe selected
                ?>
                <script>
                    function peg_toggle_large_image_link_options() {
                        // get the selected value of the selection box
                        var val = jQuery('#peg_link').val();
                        var external = false;
                        var gallery = false;
                        var photoswipe = false;

                        // check to see if we need to show or hide the external script message
                        if ((val == 'lightbox') || (val == 'thickbox') || (val == 'highslide')) {
                            // these are external utilities that need to have the message added,
                            // and of course also a gallery
                            external = true;
                            gallery = true;
                        } else if ((val == 'thickbox_integrated') || (val == 'thickbox_custom')) {
                            // the two integrated versions of thickbox, gallery = true
                            gallery = true;
                        } else if (val == 'photoswipe') {
                            // integrated version of PhotoSwipe - gallery & photoswipe are true
                            gallery = true;
                            photoswipe = true;
                        }

                        // determine if we're hiding/showing the external library message
                        if (external) {
                            jQuery('#peg_external_message').show();
                        } else {
                            jQuery('#peg_external_message').hide();
                        }

                        // determine if we're hiding/showing the "relate images" gallery option
                        if (gallery) {
                            jQuery('#peg_relate_row').show();
                        } else {
                            // hide the option and clear a checkbox if it is checked
                            jQuery('#peg_relate_row').hide();
                            jQuery('#peg_relate_row input').attr('checked', false);
                        }

                        // determine if we're using photoswipe and hide/show the related options
                        if (photoswipe) {
                            jQuery('#peg_photoswipe_options').show();
                        } else {
                            // hide the options and clear any checkboxes if any are checked
                            jQuery('#peg_photoswipe_options').hide();
                            jQuery('#peg_photoswipe_options input').attr('checked', false);
                        }
                    }// end function peg_toggle_large_image_link_options()
                    jQuery('document').ready(function () {
                        // execute the toggle for large image link options on ready
                        peg_toggle_large_image_link_options();
                    });
                </script>
            <?php

            }// end if we're on the main settings page

            $opts = array(
                'none' => __('None'),
                'left' => __('Left'),
                'center' => __('Center'),
                'right' => __('Right'),
            );
            $option =$this->configuration->get_option('peg_img_align');
            $out = '';
            foreach ($opts as $key => $val) {
                $out .= "<input type=\"radio\" id=\"peg_img_align_$key\" name=\"peg_general_settings[peg_img_align]\" value=\"$key\" " . checked($option, $key, false) . " /> ";
                $out .= "<label for=\"peg_img_align_$key\" style=\"padding-left:22px;margin-right:13px;\" class=\"image-align-$key-label\">$val</label>";
            }
            $this->make_settings_row(
                __('Image alignment', 'peg'),
                $out
            );

            $option =$this->configuration->get_option('peg_auto_clear');
            $this->make_settings_row(
                __('Auto clear: both', 'peg'),
                '<label><input type="checkbox" id="peg_auto_clear" name="peg_general_settings[peg_auto_clear]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Automatically add &lt;p class="clear"&gt;&lt;/p&gt; after groups of images inserted together', 'peg') . '</label> '
            );

            $this->make_settings_row(
                __('Image CSS class', 'peg'),
                '<input type="text" id="peg_img_css" name="peg_general_settings[peg_img_css]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_img_css')) . '"/>',
                __("You can define one or more classes for img tags", 'peg')
            );
            $this->make_settings_row(
                __('Image style', 'peg'),
                '<input type="text" id="peg_img_style" name="peg_general_settings[peg_img_style]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_img_style')) . '"/>',
                __('You can hardcode any css attributes for the img tags', 'peg')
            );

            $this->make_settings_row(
                __('Image A CSS class', 'peg'),
                '<input type="text" id="peg_a_img_css" name="peg_general_settings[peg_a_img_css]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_a_img_css')) . '"/>',
                __("You can define one or more classes for the a tags wrapping the img tags", 'peg')
            );
            $this->make_settings_row(
                __('Image A style', 'peg'),
                '<input type="text" id="peg_a_img_style" name="peg_general_settings[peg_a_img_style]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_a_img_style')) . '"/>',
                __('You can hardcode any css attributes for the a tags wrapping the img tags', 'peg')
            );

            // check to see if we're on the main settings page, and if so add
            // the option for returning HTML rather than shortcode
            if ($settings) {
                // option to translate shortcode into HTML
                $option =$this->configuration->get_option('peg_return_single_image_html');
                $this->make_settings_row(
                    __('Return HTML instead of shortcode', 'peg'),
                    '<label><input type="checkbox" id="peg_return_single_image_html" name="peg_general_settings[peg_return_single_image_html]" value="1" ' . checked($option, '1', false) . ' /> ' . __('Return HTML for images selected from the dialog instead of the [peg-image] shortcode', 'peg') . '</label> ',
                    __('NOTE: Enabling this feature limits the ability of this plugin to update old posts when image size options are updated or if Google\'s migration from Picasaweb to Google+ breaks existing URLs', 'peg')
                );
            }// end if we're on main settings page

            ?>
				</table>

				<h3><?php _e('Gallery properties', 'peg') ?></h3>

				<table class="peg-form-table">

<?php
            // ---------------------------------------------------------------------
            // album format / thumbnail size
            $this->make_settings_row(
                __('Album format', 'peg'),
                '<label><input type="radio" id="peg_gal_format_phototile" name="peg_general_settings[peg_gal_format]" value="phototile" ' . ($this->configuration->get_option('peg_phototile') != null ? 'checked="checked"' : '') . ' /> ' . __('Use the Google+ phototile style for album layout and thumbnail size selection', 'peg') . '</label>' .
                '<div id="peg_phototile_container"><br/> &nbsp; &nbsp; &nbsp; <label for="peg_phototile" style="vertical-align: top;">' . __('Phototile album width:', 'peg') . '</label>' .
                '<div style="display: inline-block;"><input type="text" id="peg_phototile" name="peg_general_settings[peg_phototile]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_phototile')) . '" size="6" maxlength="10" style="width: 50px;" />px; &nbsp; &nbsp; (' . __('Example: 600px;', 'peg') . ')<br/><span style="font-size: 9px;">' . __('This is used to to calculate how much room thumbnails can consume', 'peg') . '<br/>' . __('NOTE: By enabling the phototile option, gallery alignment and caption display are disabled', 'peg') . '</span></div></div>' .
                '<br/><label><input type="radio" id="peg_gal_format_standard" name="peg_general_settings[peg_gal_format]" value="standard" ' . ($this->configuration->get_option('peg_phototile') == null ? 'checked="checked"' : '') . ' /> ' . __('Use default thumbnail size configured using the <a href="options-media.php">Settings-&gt;Media</a> page.', 'peg') . '</label>'
            );

            // script for enabling phototile input / clearing its value
            ?><script>
function peg_toggle_phototile_option(){
	// get the selected value of the selection box
	var val = jQuery('input[name=peg_general_settings\\[peg_gal_format\\]]:checked').val();
	if(val == 'phototile'){
		// show the phototile option
		jQuery('#peg_phototile_container').show();

		// hide the other options that are no longer allowed to be
		// set
		jQuery('#peg_caption_row').hide();
		jQuery('tr.peg_caption_child').hide();
		jQuery('#peg_gal_align').hide();
	}else{
		// hide the phototile option and clear any value
		jQuery('#peg_phototile_container').hide();
		jQuery('#peg_phototile_container input').val('');

		// show the other options that can now be set
		jQuery('#peg_gal_align').show();
		jQuery('#peg_caption_row').show();

		// determine if we should show the caption children
		// or not, based on the caption checkbox
		var val = jQuery('#peg_caption:checked').val();
		if(val == '1'){
			// we can show them
			jQuery('tr.peg_caption_child').show();
		}
	}
}// end function peg_toggle_phototile_option()
jQuery('input[name=peg_general_settings\\[peg_gal_format\\]]').click(function(){
	// execute the toggle for large image link options on ready
	peg_toggle_phototile_option();
});
jQuery('document').ready(function(){
	// execute the toggle for large image link options on ready
	peg_toggle_phototile_option();
});
</script>
<?php

            // ---------------------------------------------------------------------
            // display tag options
            $this->make_settings_row(
                __('Photo tag options', 'peg'),
                '<input id="peg_featured_tag" name="peg_general_settings[peg_featured_tag]" type="checkbox" value="1" ' . checked('1', $this->configuration->get_option('peg_featured_tag'), false) . '/> ' .
                '<label for="peg_featured_tag">' . __('Include photos from albums only if they contain the "Featured" tag') . '</label><br />' .
                '<label for="peg_additional_tags" style="vertical-align: top;">' . __('Additional tag(s) required') . '</label> ' .
                '<div style="display: inline-block;"><input type="text" id="peg_additional_tags" name="peg_general_settings[peg_additional_tags]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_additional_tags')) . '"/><br/><span style="font-size: 9px;">' . __('Separate multiple tags by commas.  NOTE: currently Google requires private album access for tags to work') . '</span></div>'
            );

            // ---------------------------------------------------------------------
            // remaining gallery options
            $this->make_settings_row(
                __('Selection order', 'peg'),
                '<label><input type="checkbox" id="peg_gal_order" name="peg_general_settings[peg_gal_order]" value="1" ' . checked($this->configuration->get_option('peg_gal_order'), '1', false) . ' /> ' . __("Click images in your preferred order", 'peg') . '</label>'
            );

            $option =$this->configuration->get_option('peg_gal_align');
            $out = '';
            foreach ($opts as $key => $val) {
                $out .= "<input type=\"radio\" id=\"peg_gal_align_$key\" name=\"peg_general_settings[peg_gal_align]\" value=\"$key\" " . checked($option, $key, false) . " /> ";
                $out .= "<label for=\"peg_gal_align_$key\" style=\"padding-left:22px;margin-right:13px;\" class=\"image-align-$key-label\">$val</label>";
            }
            $this->make_settings_row(
                __('Gallery alignment', 'peg'),
                $out,
                null,
                'id="peg_gal_align"'
            );

            $this->make_settings_row(
                __('Gallery CSS class', 'peg'),
                '<input type="text" id="peg_gal_css" name="peg_general_settings[peg_gal_css]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_gal_css')) . '"/>',
                __("You can define one or more classes for the gallery container tag", 'peg')
            );
            $this->make_settings_row(
                __('Gallery style', 'peg'),
                '<input type="text" id="peg_gal_style" name="peg_general_settings[peg_gal_style]" class="regular-text" value="' . esc_attr($this->configuration->get_option('peg_gal_style')) . '"/>',
                __('You can hardcode any css attributes for the gallery container tag', 'peg')
            );
            ?>

				</table>
<?php
            // check to see if we're on the main settings page, and if so add the last
            // section for advanced and advertising
            if ($settings) {
                // we're on main settings, add the remaining entries
                ?>
	            <h3><?php _e('Advanced', 'peg') ?></h3>
	            <table class="peg-form-table">
		            <?php
		            $this->make_settings_row(__('Caching','peg'),
			            '<label><input type="checkbox" id="peg_cache_activated" name="peg_general_settings[peg_cache_activated]" value="1" '. checked($this->configuration->get_option('peg_cache_activated'), '1', false) . ' />'. __('Enable Caching of Google Album feeds','peg') .'</label>',
			            __('If activated, the answers to google API calls for albums will be cached. This dramatically increases the time to load a page with a lot of galleries. If this option is set, it will take some time for new pictures to appear in an album gallery that has already been cached. You can always reset the cache for a certain post/site by republishing the content.', 'peg'));
		            $this->make_settings_row(__('Cache expires', 'peg'),
			            '<input type="text" id="peg_cache_expiration_time" name="peg_general_settings[peg_cache_expiration_time]" value="'.$this->configuration->get_option('peg_cache_expiration_time').'" />'
			            , __('The time in seconds after which a cached album feed should at least be refreshed. \'0\' means that album feeds do not need to be refreshed.', 'peg')
		            );
		            $this->make_settings_row(__('SSL','peg'),
			            '<label><input type="checkbox" id="peg_force_ssl" name="peg_general_settings[peg_force_ssl]" value="1"' . checked($this->configuration->get_option('peg_force_ssl'), '1', false) . ' />' . __('Force SSL for all connections to Google Photos','peg') .'</label>',
			            __('If activated, all requests to the google photo server will be send over SSL (https). If you disable this setting, your private access tokens for Google Photo will be send over an insecure connection to Google.'));
		            ?>
	            </table>
                <h3><?php _e('Advertising', 'peg') ?></h3>

                <table class="peg-form-table">

                    <?php
                    $this->make_settings_row(
                        __('Footer link', 'peg'),
                        '<label><input type="checkbox" id="peg_footer_link" name="peg_general_settings[peg_footer_link]" value="1" ' . checked($this->configuration->get_option('peg_footer_link'), '1', false) . ' /> ' . __('Enable footer link "With Google+ plugin by Geoff Janes and Thorsten Hake"', 'peg') . '</label>'
                    );

                    ?>

                </table>

            <?php
            }// end if we're on main settings page
        }// end function peg_shared_options(..)

        /**
         * Make the row from parameters for setting tables
         */
        function make_settings_row($title, $content, $description = '', $title_pars = '', $description_pars = '')
        {
           Common::make_settings_row($title,$content,$description,$title_pars,$description_pars);
        }



        /**
         * Show the main settings form
         */
        function settings_form()
        {
	        if (
                (isset($_GET['updated']) && 'true' == $_GET['updated']) ||
                (isset($_GET['settings-updated']) && 'true' == $_GET['settings-updated'])
            ) {
                // successfully performed an update, execute any custom
                // logic that can't be performed by automatic settings storage

                // change 'picasa_dialog' capability to new role
                $roles = get_editable_roles();

                foreach ($roles as $role => $data) {
                    $_role = get_role($role);
                    if (isset($this->configuration->get_option('peg_roles')[$role]) &&$this->configuration->get_option('peg_roles')[$role]) {
                        $_role->add_cap('picasa_dialog');
                    } else {
                        $_role->remove_cap('picasa_dialog');
                    }
                }

            }// end successful settings update


            if (isset($_GET['message']) && $_GET['message']) {
                $message = esc_html(stripcslashes($_GET['message']));
            }
	        ?><div class="wrap"><?php
	        if($this->picasaAccess->is_oauth_configuration_page()){
		        $this->picasaAccess->render_oauth_configuration_page();
	        }else{
            ?>
                <div id="icon-options-general" class="icon32"><br/></div>
                <h2><?php _e('Photo Express for Google', 'peg')?></h2>

                <?php
                if (isset($message) && $message) {
                    echo '<div id="picasa-express-x2-message" class="updated"><p><strong>' . $message . '</strong></p></div>';
                }
                ?>

                <form method="post" action="options.php">
                    <?php settings_fields('photo-express'); ?>



                    <?php

                    // call the function to get the shared options between these
                    // preferences and the option-overrides in the dialog
                    $this->peg_shared_options(true);

                    // finish the form with the submit button
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
                    </p>

                </form>
		        <?php } ?>
            </div>
        <?php
        }// end function settings_form()



    }
}