<?php
namespace photo_express;

require_once plugin_dir_path(__FILE__).'class-settings-storage.php';
require_once plugin_dir_path(__FILE__).'class-common.php';
require_once plugin_dir_path(__FILE__).'class-feed-fetcher.php';

if (!class_exists("Photo_Renderer")) {
    class Photo_Renderer
    {
	    private $photos_displayed = array();
	    /**
	     * @var $configuration Settings_Storage
	     */
        private $configuration;
	    /**
	     * @var $picasaAccess Feed_Fetcher
	     */
        private $picasaAccess;
        /**
         * Phototile template array
         *  https://lh4.googleusercontent.com/-3jmux-dXxzk/UNh-W44QB5I/AAAAAAAAHSg/AKXz6my4lMg/w619-h414-o-k/DSC_0785.JPG
         *  https://lh4.googleusercontent.com/-klsfBvgXcqc/UNh7wHQH7wI/AAAAAAAAHMA/Sfl7nj7Y-Xo/w566-h424-p-o-k/DSC_0750.JPG
         *  the different layout options and their max number of photos 1, 1
         * @var array
         */
        private $peg_phototile_template = array(
            array(
                'max' => 2,
                'format' => array(
// 619x414
                    array('w' => 500, 'h' => 350),
// 619x414
                    array('w' => 500, 'h' => 350)
                )),
// 1, 1, 1
            array(
                'max' => 3,
                'format' => array(
// 225x336
                    array('w' => 186, 'h' => 270),
// 503x336
                    array('w' => 407, 'h' => 270),
// 503x336
                    array('w' => 407, 'h' => 270)
                )),
// 1, 1, 1
            array(
                'max' => 3,
                'format' => array(
// 648x434
                    array('w' => 528, 'h' => 380),
// 291x434
                    array('w' => 236, 'h' => 380),
// 291x434
                    array('w' => 236, 'h' => 380)
                )),
// 1, 1, 1, 1
            array(
                'max' => 4,
                'format' => array(
// 234x349
                    array('w' => 191, 'h' => 280),
// 234x349
                    array('w' => 191, 'h' => 280),
// 234x349
                    array('w' => 191, 'h' => 280),
// 521x349
                    array('w' => 427, 'h' => 280)
                )),
// 1, 1, 1, 1, 1
            array(
                'max' => 5,
                'format' => array(
                    array('w' => 200, 'h' => 300),
                    array('w' => 200, 'h' => 300),
                    array('w' => 200, 'h' => 300),
                    array('w' => 200, 'h' => 300),
                    array('w' => 200, 'h' => 300)
                )),
// 1, 2, 3
            array(
                'max' => 6,
                'format' => array(
                    array('w' => 466, 'h' => 339),
                    array(
                        array(
                            array('w' => 267, 'h' => 190),
                            array('w' => 267, 'h' => 190)
                        ),
                        array(
                            array('w' => 100, 'h' => 149),
                            array('w' => 217, 'h' => 149),
                            array('w' => 217, 'h' => 149)
                        ),
                        'width' => 534
                    )
                )),
// 1, 3, 3
            array(
                'max' => 7,
                'format' => array(
// 566x419
                    array('w' => 466, 'h' => 339),
                    array(
                        array(
// 155x231
                            array('w' => 132, 'h' => 190),
// 155x231
                            array('w' => 132, 'h' => 190),
// 346x231
                            array('w' => 270, 'h' => 190)
                        ),
                        array(
// 120x179
                            array('w' => 100, 'h' => 149),
// 267x179
                            array('w' => 217, 'h' => 149),
// 267x179
                            array('w' => 217, 'h' => 149)
                        ),
                        'width' => 534
                    )
                )),
// 1, 4, 2
            array(
                'max' => 7,
                'format' => array(
                    // 566x424
                    array('w' => 480, 'h' => 480),
                    array(
                        array(
                            // 123x185
                            array('w' => 130, 'h' => 190),
                            // 123x185
                            array('w' => 130, 'h' => 190),
                            // 123x185
                            array('w' => 130, 'h' => 190),
                            // 277x185
                            array('w' => 130, 'h' => 190)
                        ),
                        array(
                            array('w' => 320, 'h' => 290),
                            array('w' => 200, 'h' => 290)
                        ),
                        'width' => 520
                    )
                )));

        function __construct($configuration, $picasaAccess)
        {
            $this->configuration = $configuration;
            $this->picasaAccess = $picasaAccess;
        }// end else for if this is in the admin


        /**
         * Envelope content with tag
         * used by shortcode 'peg_gallery'
         *
         * @param array $atts tag, class and style defined. album also
         * @param string $content
         *
         * @return string
         */
        function gallery_shortcode($atts, $content)
        {
            // go through any attributes and set string values of "false"
            // to boolean false to fix lazy evaluation issues
	        // also replace all attributes with old pe2 prefixes

            if (is_array($atts)) {
	            $newAtts = array();
                // we have at least one attribute, we can process them:
                foreach ($atts AS $key => $value) {
                    if ($atts[$key] === 'false') {
                        // set this attribute to boolean false
                        $atts[$key] = false;
                    }
	                if(substr($key,0,3) == "pe2"){
		                $newAtts['peg'.substr($key,3)] = $atts[$key];
	                }else{
		                $newAtts[$key] = $atts[$key];
	                }
                }
	            $atts = $newAtts;

            }

            // extract the attributes
            /**
             * The following options will be introduced
             * @see PicasaConfiguration->options for a description of $peg_XXX variables
             * @var $album             string   the base64 encoded guid of the album
             * @var $peg_relate_images boolean
             * @var $peg_img_css       string
             * @var $peg_img_style     string
             * @var $peg_phototile     int
             * @var $thumb_w           int      width of the thumbnail
             * @var $thumb_h           int      height of the thumbnail
             * @var $peg_gal_css       string
             * @var $peg_gal_style     string
             * @var $peg_img_align     string
             * @var $peg_large_limit   boolean
             * @var $peg_a_img_css     string
             * @var $peg_a_img_style   string
             * @var $peg_link          string
             * @var $tag               string   the HTML tag that is to be used to represent the shortcode
             * @var $thumb_crop        boolean  true if a crop of the thumbnails should be performed
             * @var $peg_img_sort      string
             * @var $peg_title         string
             * @var $peg_gal_align     string
             * @var $peg_img_asc       string
             * @var $limit             int      how many pictures are to be displayed
             * @var $hide_rest          boolean  if the remaining images over $limit should be hidden
             */
            extract(shortcode_atts(array_merge(array(
                'tag' => 'div',
                'album' => '',
                'thumb_w' => get_option('thumbnail_size_w'),
                'thumb_h' => get_option('thumbnail_size_h'),
                'thumb_crop' => get_option('thumbnail_crop'),
                'limit' => '',
                'hide_rest' => ''
            ), $this->configuration->get_options()
            ), $atts));


            if ($album) {
                // request images for album - generate the request URL
                if (strpos($album, 'http') !== 0) {
                    // for backwards compatibility, decode the base64 encoded album data
                    // stored in the tag
                    $feed_url = base64_decode($album);
                } else {
                    // simply store the album url after decoding any entities created by
                    // the visual/HTML editor
                    $feed_url = html_entity_decode($album);
                }

                // determine if we have any tags to send with the query
                if (isset($atts['tags'])) {
                    // we also have tags to query, append them
                    $feed_url .= '&tag=' . str_replace('-', '+', urlencode($atts['tags'])) . '&orderby=date&showall';
                }
	            //Check if the feed is based on a "base" url
	            if(strpos($feed_url, 'picasaweb.google.com/data/feed/base') !== 0){
		            $feed_url = str_replace('picasaweb.google.com/data/feed/base', 'picasaweb.google.com/data/feed/api',$feed_url);
	            }

                // grab the data and process it
                $rss = $this->picasaAccess->get_feed($feed_url);
                if (is_wp_error($rss)) {
                    $content = $rss->get_error_message();
                } else if (Common::get_item($rss, 'atom:id')) {
                    $items = Common::get_item($rss, 'item');
                    $output = '';

                    // determine if we're relating all images, or just those
                    // in this gallery
                    if ($peg_relate_images) {
                        // use the per-post unique ID so all images in the post
                        // are related
                        $uniqid = 'post-' . get_the_ID();
                    } else {
                        // generate a unique id for this gallery
                        $uniqid = uniqid('');
                    }

                    // prepare common image attributes
                    $iclass = explode(' ', $peg_img_css);
                    $istyle = array($peg_img_style);

                    // determine if we have a static thumbnail size, or if it needs to
                    // be calculated per image based on the phototile template
                    if ($peg_phototile != null) {
                        // this is a phototile, add our class to the main div
                        $peg_gal_css .= ' peg-phototile';

                        // force some other settings required for this to work
                        // properly
                        $peg_align = 'left';

                        // turn captions off
                        $peg_caption = 0;

                        // now create our reference array for the various sizes
                        // available in our template
                        $tile_template_sizes = array();
                        $tile_template_max_size = 0;
                        foreach ($this->peg_phototile_template AS $tmp_tile_template) {
                            // store this if it doesn't already exist
                            if (!in_array($tmp_tile_template['max'], $tile_template_sizes)) {
                                // add it
                                $tile_template_sizes[] = $tmp_tile_template['max'];

                                // see if this is the largest so far
                                if ($tmp_tile_template['max'] > $tile_template_max_size) {
                                    $tile_template_max_size = $tmp_tile_template['max'];
                                }
                            }// end if we haven't stored this max size yet
                        }// end foreach tile template we have
                        unset($tmp_tile_template);

                        //TODO Remove compatibility with PHP 5.3 as it is no longer supported officially
                        // determine if we have a PHP version new enough to use the
                        // 3rd parameter of round
                        if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.3.0', '<')) {
                            // cannot use the 3rd parameter of round when performing
                            // the phototile scaling
                            $round_without_3rd_parameter = true;
                        }
                    } else {
                        // standard gallery, calculate the thumbnail size from wp
                        // settings
                        $new_thumb_size = '';
                        if ($thumb_w && $thumb_h) {
                            // both sizes and crop
                            if ($thumb_w == $thumb_h) {
                                if ($thumb_crop) {
                                    $new_thumb_size = '/s' . $thumb_w . '-c';
                                } else {
                                    $new_thumb_size = '/s' . $thumb_w;
                                }
                            } else if ($thumb_w > $thumb_h) {
                                $new_thumb_size = '/w' . $thumb_w;
                            } else {
                                $new_thumb_size = '/h' . $thumb_h;
                            }
                        } else if ($thumb_w) {
                            $new_thumb_size = '/w' . $thumb_w;
                        } else if ($thumb_h) {
                            $new_thumb_size = '/h' . $thumb_h;
                        }

                        // add the overlay option
                        $new_thumb_size .= '-o';
                    }// end else for if we're using a phototile gallery

                    // create align vars
                    // for caption - align="alignclass" including alignnone also
                    $calign = '';
                    if ($peg_caption) {
                        $calign = 'align="align' . $peg_img_align . '" ';
                    }

                    // new size for large image
                    $new_large_size = '/s0';
                    if ($peg_large_limit) {
                        $new_large_size = '/' . $peg_large_limit;
                    }

                    $cdim = ($thumb_w) ? ('width="' . $thumb_w . '" ') : '';

                    // determine if we have an a_img_css
                    if ($peg_a_img_css != null) {
                        $aclass = ' ' . $peg_a_img_css;
                    } else {
                        $aclass = '';
                    }
                    if ($peg_a_img_style != null) {
                        $astyle = ' style="' . $peg_a_img_style . '"';
                    } else {
                        $astyle = '';
                    }

                    // link and gallery additions
                    $amore = '';
                    switch ($peg_link) {
                        case 'thickbox':
                        case 'thickbox_integrated':
                        case 'thickbox_custom':
                            $amore = 'class="thickbox' . $aclass . '" rel="' . $uniqid . '" ';
                            break;
                        case 'lightbox':
                            $amore = 'class="' . $aclass . '" rel="lightbox-' . $uniqid . '" ';
                            break;
                        case 'highslide':
                            $amore = 'class="highslide' . $aclass . '" onclick="return hs.expand(this,{ slideshowGroup: \'' . $uniqid . '\' })" ';
                            break;
                        case 'photoswipe':
                            $amore = 'class="photoswipe' . $aclass . '" rel="' . $uniqid . '" ';
                            break;
                    }

                    // append the astyle to the amore
                    $amore .= $astyle;

                    // set image classes and styles
                    $iclass = implode(' ', array_diff($iclass, array('')));
                    $iclass = ($iclass) ? ('class="' . $iclass . '" ') : '';
                    $istyle = implode(' ', array_diff($istyle, array('')));
                    $istyle = ($istyle) ? ('style="' . $istyle . '" ') : '';

                    $key = 1;
                    $images = array();

                    if ($items) {
                        if (!is_array($items)) {
                            $items = array($items);
                        }

                        // if we're searching by tags, the RSS feed returned the results
                        // in the order of most-recent first, which doesn't make very
                        // much sense when considering how this works.  reverse teh
                        // array order
                        if (isset($atts['tags'])) {
                            // we searched for tags and messed up the order, perform our
                            // sorting on the items array to correct the order
                            $items = array_reverse($items);
                            usort($items, 'PicasaExpressX2::peg_items_sorting_callback');
                        }

                        // loop through each and build the HTML
                        $image_count = 0;
                        $new_width_round_diff_total = 0;
                        $count = 0;
                        foreach ($items as $item) {
                            // keep track of the image number we're on
                            $image_count++;

                            // init/reset/any prefix/suffix
                            $tile_wrap_prefix = $tile_wrap_suffix = '';

                            // -------------------------------------------------------------
                            // gallery type
                            if ($peg_phototile != null) {
                                // we're tiling the photos, using our width, define the
                                // dimensions using our template array of size combinations

                                // if necessary, start a new row
                                if (!isset($tile_row)) {
                                    // randomly select a row from the template
                                    $tmp = 0;
                                    do {
                                        $tmp++;
                                        if ($tmp > 100) {
                                            break;
                                        }
                                        // select a tile row
                                        $tile_row = rand(0, count($this->peg_phototile_template) - 1);

                                        // determine if this size is appropriate for the
                                        // number of photos that remain to be displayed
                                        $continue_looping = false;
                                        $tmp_tile_images_left = count($items) - $image_count + 1;
                                        if ($tmp_tile_images_left == 0) {
                                            // we're all done
                                            break;
                                        } elseif ($tmp_tile_images_left <= ($tile_template_max_size + 1)) {
                                            // we need to confirm that our selected row is
                                            // appropriate
                                            if (in_array($tmp_tile_images_left, $tile_template_sizes)) {
                                                // we have one that matches exactly, force one
                                                // of those
                                                if ($this->peg_phototile_template[$tile_row]['max'] == $tmp_tile_images_left) {
                                                    // we selected one that's ok, we can stop
                                                    // looping
                                                    $continue_looping = false;
                                                } else {
                                                    // we haven't selected one of the templates
                                                    // that fits this size, force a loop right now
                                                    // that locates the correct matching record
                                                    foreach ($this->peg_phototile_template AS $tmp_key => $tmp_row) {
                                                        // if we find the one with the correct max value,
                                                        // then we select it and break
                                                        if ($tmp_row['max'] == $tmp_tile_images_left) {
                                                            // use this one
                                                            $tile_row = $tmp_key;
                                                            unset($tmp_key, $tmp_row);
                                                            break 2;
                                                        }
                                                    }// end foreach template record
                                                }// end else if our randomly selected row matches
                                            } elseif ($tmp_tile_images_left == 1) {
                                                // we need to find a template that serves 2
                                                // images, that's the best we can do
                                                if ($this->peg_phototile_template[$tile_row]['max'] == 2) {
                                                    // we're ok
                                                    $continue_looping = false;
                                                } else {
                                                    // not ok, keep searching
                                                    $continue_looping = true;
                                                }
                                            } else {
                                                // we don't have an exact match and we have
                                                // more than 1 photo left, find one that
                                                // is smaller than our current number
                                                if (($this->peg_phototile_template[$tile_row]['max'] + 1) < $tmp_tile_images_left) {
                                                    // we're ok, we're less than the images left,
                                                    // but our remainder must be at least 2, thus
                                                    // preventing leaving just 1 photo left in the
                                                    // last row
                                                    $continue_looping = false;
                                                } else {
                                                    // not ok, keep searching
                                                    $continue_looping = true;
                                                }
                                            }
                                        }// end if we have a small number and the template is important
                                    } while ($continue_looping);
                                    unset($tmp_tile_images_left, $continue_looping);

                                    // initialize some variables for this row
                                    $tile_row_column = 0;
                                    $tile_row_count = 0;
                                    $tile_row_row_count = 0;

                                    // cols is the count of the format array
                                    $row_order = $this->peg_phototile_template[$tile_row]['format'];
                                    shuffle($row_order);

                                    // reset round up/down
                                    $round_down = false;

                                    // set up our wrapper for the row
                                    $tile_wrap_prefix = '<div style="float: left; width: ' . $peg_phototile . 'px;">';
                                }// end if we need to start a new row

                                // go through the row order and get the template dimensions
                                // for the column we're currently on
                                if (!isset($row_order[$tile_row_column]['w'])) {
                                    // we have sub-arrays for a multi-row phototile,
                                    // determine where we are
                                    if (!isset($tile_row_row) || !isset($tile_row_row_column)) {
                                        // we're just entering this multi-row section,
                                        // set it up
                                        $tile_row_row = rand(0, 1);
                                        $tile_row_row_column = 0;
                                        $tile_row_row_count++;

                                        // check to see if we need to create the container
                                        // for the images (if the multi-row is the first
                                        // tile column)
                                        if ($tile_row_column == 0) {
                                            // we're doing the multi-row column first, we
                                            // need our wrapper
                                            $tile_multi_row_wrapper = true;
                                            $tile_wrap_prefix .= '<div style="float: left; width: ' . ($row_order[$tile_row_column]['width'] / 1000 * $peg_phototile) . 'px;">';
                                        }
                                    } elseif ($tile_row_row_column == (count($row_order[$tile_row_column][$tile_row_row]) - 1)) {
                                        // we're on the very last image for this row,
                                        // flag the rounding check
                                        $tile_row_row_round_check = true;
                                    } elseif ($tile_row_row_column >= count($row_order[$tile_row_column][$tile_row_row])) {
                                        // we've surpassed the images for this row, we
                                        // need to start the next
                                        $tile_row_row = ($tile_row_row == 1 ? 0 : 1);
                                        $tile_row_row_column = 0;
                                        $tile_row_row_count++;
                                    }

                                    // check to see if we've surpassed the images for
                                    // this tile_row_column
                                    if ($tile_row_row_count <= 2) {
                                        // check to see if we need to randomize our row-row
                                        if ($tile_row_row_column == 0) {
                                            // randomize this array before using it
                                            $row_order_tmp = $row_order[$tile_row_column][$tile_row_row];
                                            shuffle($row_order_tmp);
                                        }

                                        // assign the current column to the tile_row_row_column
                                        $current_column = $tile_row_row_column;

                                        // mark that we're on a multi-row column
                                        $tile_multi_row = true;
                                    } else {
                                        // we're done with this row_row column,
                                        // increment our $tile_row_column counter
                                        $tile_row_column++;

                                        // if we need to end our wrapper, do it now
                                        if (isset($tile_multi_row_wrapper)) {
                                            // define our prefix
                                            $tile_wrap_prefix = '</div>';
                                        }

                                        // and simulate the variables for a
                                        // standard row (same code as the else
                                        // immediately below)
                                        $row_order_tmp = $row_order;
                                        $current_column = $tile_row_column;
                                        $tile_multi_row = false;
                                    }
                                } else {
                                    // this is a direct image configuration for a
                                    // non-multi row, assign the row_order_tmp
                                    // simply from $row_order
                                    $row_order_tmp = $row_order;

                                    // assign the current column to the $tile_row_column
                                    $current_column = $tile_row_column;

                                    // mark that we're on a single-row column
                                    $tile_multi_row = false;
                                }// end else for if we have a multi-row row

                                // define our width, subtracting 10 pixels for image pad
                                $new_width = ($row_order_tmp[$current_column]['w'] / 1000 * $peg_phototile) - 4;
                                if ($new_width != round($new_width)) {
                                    // store our original width temporarily
                                    $tmp_new_width = $new_width;

                                    // we need to round, determine if we round half-up
                                    // or half-down which should hopefully make multiple
                                    // .5 rounds add up correctly
                                    if (!isset($round_without_3rd_parameter) && $round_down) {
                                        // we're going down
                                        $new_width = round($new_width, null, PHP_ROUND_HALF_DOWN);
                                    } else {
                                        // we're rounding normally
                                        $new_width = round($new_width);
                                    }
                                    $round_down = !$round_down;

                                    // now figure out the difference between the two
                                    $new_width_round_diff = $tmp_new_width - $new_width;
                                } else {
                                    // width round diff = 0
                                    $new_width_round_diff = 0;
                                }// end else for if we need to round

                                // define our height
                                $new_height = round($row_order_tmp[$current_column]['h'] / 1000 * $peg_phototile);


                                // perform any special processing for multi-row
                                if ($tile_multi_row) {
                                    // we need to subtract 1 pixel from our height so that
                                    // we account for the padding between rows
                                    $new_height = $new_height - 1;

                                    // sum up our rounding differences
                                    $new_width_round_diff_total += $new_width_round_diff;

                                    // check to see if we need to calculate our rounding
                                    // difference and adjust this image's width
                                    if (isset($tile_row_row_round_check)) {
                                        // we need to perform the check
                                        unset($tile_row_row_round_check);

                                        // now adjust the width of the last image
                                        // in the row to help compensate
                                        $new_width += round($new_width_round_diff_total);

                                        // reset our round_diff_total
                                        $new_width_round_diff_total = 0;
                                    }
                                }// end if we're on multi-row

                                // generate this image's thumbnail size from the phototile
                                // template
                                #print "tr=$tile_row cc=$current_column count=$tile_row_count trc=$tile_row_column trr=$tile_row_row trrc=$tile_row_row_column w={$row_order_tmp[$current_column]['w']} h={$row_order_tmp[$current_column]['h']} new_thumb_size=/w$new_width-h$new_height-p-o-k<br/>";
                                $new_thumb_size = "/w$new_width-h$new_height-p-k-o";

                                // determine if we're done with this row
                                $tile_row_count++;
                                if ($tile_row_count >= $this->peg_phototile_template[$tile_row]['max']) {
                                    // we're done with this row, unset the $tile_row variable
                                    // so it gets re-calculated during the next iteration
                                    unset($tile_row, $tile_row_row, $tile_multi_row_wrapper);

                                    // and add our suffix for the end of the row
                                    $tile_wrap_suffix = '</div>';
                                } elseif (!isset($row_order[$tile_row_column]['w'])) {
                                    // this is a multi-row column, increment our row-row column
                                    // counter appropriately
                                    $tile_row_row_column++;
                                } else {
                                    // this is a standard column, increment our column counter
                                    // appropriately
                                    $tile_row_column++;
                                }
                            }// end if we're using phototile and need to calculate this image's size


                            $url = Common::get_item_attr($item, 'media:thumbnail', 'url');
                            $title = $this->get_title($item);

	                        $picasa_link = Common::get_item($item, 'link');

                            //Calculate correct max width and height according to limits
	                        $image_width = Common::get_item($item, 'gphoto:width');
	                        $image_height = Common::get_item($item, 'gphoto:height');
	                        $fitted_size = $this->determine_image_size(array($image_width,$image_height), $new_large_size);
                            //First check if there is already
	                        $images[] = array(
                                'ialbum' => Common::get_item($item, 'link'), // picasa album image
                                'icaption' => $title,
                                'ialt' => Common::escape(Common::get_item($item, 'media:title')),
                                'isrc' => str_replace('/s72', $new_thumb_size, $url),
                                'iorig' => str_replace('/s72', $new_large_size, $url),
                                'ititle' => ($peg_title) ? 'title="' . $title . '" ' : '',
                                'ilink' => $picasa_link,
		                        'idate' => Common::get_item($item, 'pubDate'),
//FIXME - CSS needs to be corrected
                                //'itype'	   => (strpos($item, 'medium=\'video\'') !== false ? 'video' : 'image')
                                'itype' => '',
                                'prefix' => $tile_wrap_prefix,
                                'suffix' => $tile_wrap_suffix,
		                        "width" => $fitted_size[0],
		                        "height" => $fitted_size[1]
                            );
                            if ($limit && !$hide_rest) {
                                if (++$count >= $limit) {
                                    break;
                                }
                            }
                        }// end foreach items to process
                        if($peg_img_sort == Settings::SORT_RANDOM){
	                        shuffle($images);
                        }else if($peg_img_sort != Settings::SORT_NONE) {
	                        //Sort the images
	                        usort( $images, function ( $a, $b ) use ($peg_img_asc, $peg_img_sort) {

		                        $result = 0;
		                        if($peg_img_sort == Settings::SORT_DATE){
			                        $result = $a['idate'] < $b['idate'] ? -1 : ($a['idate'] == $b['idate'] ? 0 : 1);
		                        }else if($peg_img_sort == Settings::SORT_TITLE){
			                        $result = $a['ititle'] < $b['ititle'] ? -1 : ($a['ititle'] == $b['ititle'] ? 0 : 1);
		                        }else if($peg_img_sort == Settings::SORT_FILE){
			                        $result = $a['ialt'] < $b['ialt'] ? -1 : ($a['ialt'] == $b['ialt'] ? 0 : 1);
		                        }
		                        if(!$peg_img_asc){
			                        $result = $result * -1;
		                        }
		                        return $result;
	                        } );
                        }


                        if ($limit && $hide_rest && $limit == absint($limit)) {
                            $count = 0;
                        } else {
                            $limit = false;
                        }

                        foreach ($images as $item) {
                            $img = "<img src=\"{$item['isrc']}\" alt=\"{$item['ialt']}\" type=\"{$item['itype']}\" {$item['ititle']}{$iclass}{$istyle} />";

                            if ($peg_link != 'none') {
                                if ($peg_link == 'picasa') {
                                    $item['iorig'] = $item['ialbum'];
                                }

                                $amore_this = $amore;

                                // store this photo in our list of displayed photos
                                $this->photos_displayed[] = $item['iorig'];

                                // create the image link
                                $img = "<a href=\"{$item['iorig']}\" link=\"{$item['ilink']}\" data-size=\"{$item['width']}x{$item['height']}\" {$item['ititle']}{$amore_this}>$img</a>";
                            }
                            if ($peg_caption) {
                                // add caption
//FIXME - add any peg_caption attributes to the caption shorttag
                                $img = "[caption id=\"\" {$calign}{$cdim}caption=\"{$item['icaption']}\"]{$img}[/caption] ";
                            }

                            // wrap our img/a/caption with the prefix/suffix
                            $img = $item['prefix'] . $img . $item['suffix'];

                            // append this image to our output
                            $output .= $img;

                            if ($limit) {
                                if (++$count >= $limit) {
                                    //TODO What is hstyle? Uncomment it if needed ... right now this seems to be junk
                                    // $istyle = $hstyle;
                                    $amore .= ' style="display:none;"';
                                    $peg_caption = false;
                                }
                            }
                        }// end going through all of the images in the gallery

                        //
                        $peg_gal_css = array_merge(array(($peg_gal_align != 'none') ? 'align' . $peg_gal_align : ''), explode(' ', $peg_gal_css));
                        $peg_gal_css = array_diff($peg_gal_css, array(''));
                        $peg_gal_css = implode(' ', $peg_gal_css);

                    }// end if we have items
                }//end if we were able to get the rss
                $content .= $output;
            }// end if($album) ???

            // check to see if we're setting up a phototile
            if ($peg_phototile != null) {
                // we're using a phototile, so we need to constrain our gallery
                // container to the width of the phototile, thus not letting any
                // funky wrapping occur
                // determine if we already have a width in the peg_gal_style, and if
                // not, set our width for the container to
                if (strpos($peg_gal_style, 'width:') === false) {
                    // there isn't a width style already, set it
                    $peg_gal_style = 'width: ' . $peg_phototile . 'px;' . $peg_gal_style;
                }
            }// end if we're doing a phototile

            // create the gallery code and return it
            $code = "<$tag class=\"peg-album $peg_gal_css\" style=\"$peg_gal_style\">" . do_shortcode($content) . "</$tag><div class='clear'></div>";

            return $code;
        }// end function gallery_shortcode(..)

	    function get_title($item){
		    $title = Common::escape(Common::get_item($item, 'media:description'));
		    if(empty($title)){
			    $title = Common::escape(Common::get_item($item, 'media:title'));
		    }
		    return $this->configuration->parse_caption($title);
	    }
        /**
         * Callback function to assist with sorting the items array returned by
         * a "tag" search to Google+, ordering by EXIF photo taken date
         *
         * @param mixed $item1
         * @param mixed $item2
         *
         * @return int -> -1 if item1 < item2, 0 if item1 == item2, 1 if item1 > item2
         */
        function peg_items_sorting_callback($item1, $item2)
        {
            // determine which of the two elements is greater
            $item1 = $this->peg_items_sorting_get_timestamp($item1);
            $item2 = $this->peg_items_sorting_get_timestamp($item2);

            // determien if we were successfully able to get both timestamps
            if (($item1 === false) || ($item2 === false)) {
                // darn, can't sort
                return 0;
            }

            // return the difference in the two
            return $item1 - $item2;
        }// end function peg_items_sorting_callback(..)

        /**
         * Function to assist with the above callback in parsing an RSS item's
         * EXIF image date and returning it as a timestamp
         *
         * @param string $item
         *
         * @return int the resulting timestamp
         */
        function peg_items_sorting_get_timestamp($item)
        {
            // get just the content of the <description> tag
            $tmp = Common::get_item($item, 'description');
            // strip off the garbage ahead of Date:
            $tmp = substr($tmp, strpos($tmp, 'Date:'));
            // decode the HTML enties
            $tmp = html_entity_decode($tmp);
            // strip off all after and including the next <br/>
            $tmp = substr($tmp, 0, strpos($tmp, '<br/>'));
            // strip out any other garbage HTML tags and trim it
            $tmp = trim(strip_tags($tmp));
            // strip off the "Date: " prefix
            $tmp = substr($tmp, 6);

            // return the strtotime
            return strtotime($tmp);
        }// end function peg_items_sorting_get_timestamp(..)

        /**
         * Envelope content with tag
         * used by shortcode 'peg_image'
         *
         * @param array $atts tag, class and style defined.
         * @param string $content
         *
         * @return string
         */
        function image_shortcode($atts, $content)
        {
            // extract all of the variables from defaults/options with
            // any tag attribute overrides

            // go through any attributes and set string values of "false"
            // to boolean false to fix lazy evaluation issues
            $newAtts = array();
	        foreach ($atts AS $key => $value) {
                if ($atts[$key] === 'false') {
                    // set this attribute to boolean false
                    $atts[$key] = false;
                }
	            if(substr($key,0,3) == "pe2"){
		            $newAtts['peg'.substr($key,3)] = $atts[$key];
	            }else{
		            $newAtts[$key] = $atts[$key];
	            }
            }
	        $atts = $newAtts;

            /**
             * The following variables will be used in this scope
             * @see PicasaConfiguration->options for a description of $peg_XXX variables
             * @var $peg_caption           boolean
             * @var $peg_img_align         string
             * @var $peg_a_img_css         string
             * @var $peg_a_img_style       string
             * @var $peg_link              string
             * @var $peg_relate_images     boolean
             * @var $type                  string image or video
             * @var $peg_single_image_size string
             * @var $peg_large_limit       boolean
             * @var $peg_single_video_size string
             * @var $href                  string the Picasa URL of the image/video
             * @var $peg_title             boolean
             * @var $caption               string the individual caption of the image/video
             * @var $peg_img_css           string
             * @var $peg_img_style         string
             * @var $alt                   string the alternative text for the img tag
             * @var $src                   string
             * @var $image_size             string the size of the image
             */
            // extract our attributes
            extract(shortcode_atts(array_merge(array(
                    'src' => '',
                    'href' => '',
                    'caption' => '',
                    'type' => '',
                    'alt' => '',
                    'limit' => '',
                    'hide_rest' => '',
	                'image_size' => ''
                ), $this->configuration->get_options()
                ), $atts)
            );

            // create align vars
            // for caption - align="alignclass" including alignnone also
            // else add alignclass to iclass
            $calign = '';
            $iclass = array();
            if ($peg_caption) {
                // captions have a surrounding div that must be aligned properly
                $calign = 'align="align' . $peg_img_align . '" ';
            }
            // also put the align variable on the image itself
            array_push($iclass, 'align' . $peg_img_align);

            if ($peg_a_img_css != null) {
                $aclass = ' ' . $peg_a_img_css;
            } else {
                $aclass = '';
            }
            if ($peg_a_img_style != null) {
                $astyle = ' style="' . $peg_a_img_style . '"';
            } else {
                $astyle = '';
            }

            // generate the unique id if we're relating images
            $uniqid = 'post-' . get_the_ID();

            // link and gallery additions
            $a_link_additions = '';
            switch ($peg_link) {
                case 'thickbox':
                case 'thickbox_integrated':
                case 'thickbox_custom':
                    $a_link_additions = 'class="thickbox' . $aclass . '"' . $astyle . ' ';
                    if ($peg_relate_images) {
                        // they have chosen to relate all of the images, use the post id
                        $a_link_additions .= 'rel="' . $uniqid . '" ';
                    }
                    break;
                case 'lightbox':
                    if ($peg_relate_images) {
                        // they have chosen to relate all of the images, use the post id
                        $a_link_additions = 'rel="lightbox-' . $uniqid . '" ';
                    } else {
                        // separate images without navigation
                        $a_link_additions = 'rel="lightbox" ';
                    }
                    $a_link_additions .= 'class="' . $aclass . '"' . $astyle . ' ';
                    break;
                case 'highslide':
                    if ($peg_relate_images) {
                        // they have chosen to relate all of the images, use the post id
                        $a_link_additions = 'class="highslide' . $aclass . '"' . $astyle . ' onclick="return hs.expand(this,{ slideshowGroup: \'' . $uniqid . '\' })"';
                    } else {
                        // separate images without navigation
                        $a_link_additions = 'class="highslide' . $aclass . '"' . $astyle . ' onclick="return hs.expand(this)"';
                    }
                    break;
                case 'photoswipe':
                    $a_link_additions = 'class="photoswipe' . $aclass . '" ';
                    if ($peg_relate_images) {
                        // they've chosen to relate, use the post id
                        $a_link_additions .= 'rel="' . $uniqid . '" ';
                    }
                    break;
            }// end switch
            $a_link_additions .= $astyle;

            // determine the type and then set the thumbnail url
            $amore = '';
            $imore = '';
            if ($type == 'image') {
                // use the image size
                $thumb_size = $peg_single_image_size;

                // set the link href to the large size.  determine if the
                // size has been defined, or if we just use the default
                if ($peg_large_limit == null) {
                    // none set, use a default
                    $large_size = 's0';
                } else {
                    // use the large limit from the configuration
                    $large_size = $peg_large_limit;
                }

                // create the a link, linking to the larger version of the image
                $a_href = preg_replace('/\/(w|h|s)[0-9]+(-c-o|-c|-o|)\//', '/' . $large_size . '/', $src);

	            // Reduce the size correctly
	            $new_size = $this->determine_image_size( explode('x',$image_size),$large_size);
	            $a_link_additions = $a_link_additions . ' data-size="'.$new_size[0].'x'.$new_size[1].'"';
                // set the amore to our a_link_additions
                $amore = $a_link_additions;
            } else {
                // use the video size
                $thumb_size = $peg_single_video_size;

                // set the link href to the picasa HREF
                $a_href = $href;

                // set the amore to make it open in a new tab and mark it as a video
                // type, and not add in the a_link_additions configuration
                $amore .= ' target="_blank" type="video"';
                $imore = ' type="video"';
            }// end else for if we're displaying an image

            // determine width if captions are enabled
            if ($peg_caption) {
                // extract (or calculate) the width for the caption box
                if (preg_match('/(w|h|s)([0-9]+)(-c-o|-c|-o|)/', $thumb_size, $matches) > 0) {
                    // we were able to match it, figure out what our width is
                    if ($matches[1] == 'w') {
                        // our width is this number
                        $cwidth = $matches[2];
                    } elseif (($matches[1] == 's') && (strpos($matches[3], '-c') !== false)) {
                        // our width is always = height in a square, we can use
                        // the raw number
                        $cwidth = $matches[2];
                    } else {
                        // for height or uncropped square, we have no idea what the
                        // width is going to be.  This is a very tricky situation.
                        // The width really needs to be determined via JavaScript.
                        // Perform our best guess here (which is not a good one as
                        // it only works for portrait photos), then include a
                        // bit of JavaScript that will adjust the width appropriately
                        // on page load
                        $cwidth = round($matches[2] * 3 / 4);

                        // set our variable to enable the JavaScript calculation
                        $GLOBALS['peg_include_caption_width_correction_javascript'] = true;
                    }
                } else {
                    // unable to parse the thumb size, simply set to a numeric
                    // value of something small, then enable the JavaScript to
                    // calculate the width
                    $cwidth = 75;

                    // set our variable to enable the JavaScript calculation
                    $GLOBALS['peg_include_caption_width_correction_javascript'] = true;
                }
            }// end if we need to calculate width for the caption

            // generate the URL for the thumbnail image
            $thumb_src = preg_replace('/\/(w|h|s)[0-9]+(-c-o|-c|-o|)\//', '/' . $thumb_size . '-o/', $src);

            // add our peg class to the image class
            $iclass[] = 'peg-photo';

            // generate the other image attributes we need
            $ititle = ($peg_title) ? 'title="' . $caption . '" ' : '';
            $iclass = implode(' ', $iclass);
            if ($peg_img_css) {
                $iclass .= ' ' . $peg_img_css;
            }
            if ($iclass) {
                $iclass = 'class="' . $iclass . '" ';
            }
            if ($peg_img_style) {
                $istyle = 'style="' . $peg_img_style . '" ';
            } else {
                $istyle = '';
            }

	        //Force SSL if needed
	        if($this->configuration->get_option('peg_force_ssl')){
		        $a_href = convert_to_https($a_href);
		        $thumb_src = convert_to_https($thumb_src);
	        }

            // create the HTML for the image tag
            $html = "<img src=\"{$thumb_src}\" alt=\"{$alt}\" {$ititle}{$iclass}{$istyle}{$imore} />";

            // add the link?
            if ($peg_link != 'none') {
                // the image should also have a link, determine if this particular
                // link has been displayed already or not (to prevent multiple
                // copies related to each other from busting the navigation)
                if (in_array($a_href, $this->photos_displayed)) {
                    // this photo has already been displayed, skip relating
                    // it to the rest and instead make up a new relationship
                    // for it so that we don't break the navigation
                    $amore_this = str_replace($uniqid, uniqid(), $amore);
                } else {
                    // this photo hasn't been displayed yet, it can be related
                    // without issue
                    $amore_this = $amore;
                }

                // store this photo in our list of displayed photos
                $this->photos_displayed[] = $a_href;

                // figure out what the link is
                if ($peg_link == 'picasa') {
                    // the large_url gets switched for the href
                    $a_href = $href;
                }

                // wrap the current image tag with the A tag, adding the "link"
                // attribute so the thickbox-custom can add the link to picasa
                $html = "<a href=\"{$a_href}\" link=\"{$href}\" {$ititle}{$amore_this}>$html</a>";
            }// end if we need to add the link

            if ($peg_caption) {
                // add caption
                $html = "[caption id=\"\" {$calign} width=\"{$cwidth}\" caption=\"{$caption}\"]{$html}[/caption] ";
            }

            // return our processed shortcode with the image link
            return do_shortcode($html);
        }// end function image_shortcode(..)

	    function determine_image_size($originalSize, $limit){
		    $image_width = $originalSize[0];
		    $image_height = $originalSize[1];

		    if($limit !== '' && $limit !== '/s0'){
			    //Check the kind of limitation
			    // h = height
			    // w = width
			    // s = square
			    $size_config = array();
			    preg_match('/\/([whs])([0-9]+)(-c)?/',$limit,$size_config);
			    $kind =  $size_config[1];
			    $size = $size_config[2];
			    $crop = isset($size_config[3]);
			    if($kind == 's'){
				    $kind = ($image_width > $image_height) ?  'w' : 'h';
			    }
			    if(!$crop) {
				    switch ( $kind ) {
					    case 'h':
						    $scale = $size / $image_height;
						    if ( $scale < 1 ) {
							    $image_height = $size;
							    $image_width  = round($image_width * $scale);
						    }
						    break;
					    case 'w':
						    $scale        = $size / $image_width;
						    if($scale < 1){
							    $image_width  = $size;
							    $image_height = round($scale * $image_height);
						    }
						    break;
					    default:
						    break;
				    }
			    }else{
				    $image_height = $image_height > $size ? $size : $image_height;
				    $image_width = $image_width > $size ? $size : $image_width;
			    }
		    }
			return array($image_width, $image_height);
	    }
        function add_footer_link()
        {
            echo "<p class=\"footer-link\" style=\"font-size:75%;text-align:center;\"><a
        href=\"http://wordpress.org/extend/plugins/picasa-express-x2\">" . __('With Google+ plugin by Geoff
        Janes and Thorsten Hake', 'peg') . "</a></p>";
        }

        /**
         * Envelope content with tag with additinoal class 'clear'
         * used by shortcode 'clear'
         *
         * @param array $atts tag and class
         * @param string $content
         * @return string
         */
        function clear_shortcode($atts, $content)
        {
            /**
             * The following variables are required:
             * @var $class string the additional class for the clear element
             * @var $tag   string the HTML tag used for the clear element
             */
            extract(shortcode_atts(array(
                'class' => '',
                'tag' => 'div',
            ), $atts));

            $class .= (($class) ? ' ' : '') . 'clear';

            $code = "<$tag class='$class'>" . do_shortcode($content) . "</$tag>";

            return $code;
        }

// add the public display css
        function peg_add_display_css()
        {
            // add the peg-display.css file
            wp_enqueue_style('peg-display.css', plugins_url('/peg-display.css', __FILE__), null, PEG_VERSION);
        }// end function peg_add_display_css()

// add the built-in wordpress thickbox script (if they selected the option)
        function peg_add_thickbox_script()
        {
            // add in the thickbox script built into wordpress if not in the admin
            // and the user has selected thickbox as the display method
            wp_enqueue_script('thickbox', null, array('jquery'));
            wp_enqueue_style('thickbox.css', '/' . WPINC . '/js/thickbox/thickbox.css', null, '1.0');
        }// end function peg_add_thickbox_script()

// add the custom thickbox script included with this plugin (if they selected
// the option)
        function peg_add_custom_thickbox_script()
        {
            // add in the thickbox script built into wordpress if not in the admin
            // and the user has selected thickbox as the display method
            wp_enqueue_script('jquery');
            wp_enqueue_style('thickbox.css', '/' . WPINC . '/js/thickbox/thickbox.css', null, PEG_VERSION);
            add_action('wp_footer', array(&$this, 'peg_add_custom_thickbox_config'));
        }// end function peg_add_custom_thickbox_script()

        function peg_add_custom_thickbox_config()
        {
            ?>
            <script type='text/javascript'>
                /* <![CDATA[ */
                var thickboxL10n = {
                    "next": "Next >",
                    "prev": "< Prev",
                    "image": "Image",
                    "of": "of",
                    "close": "Close",
                    "noiframes": "This feature requires inline frames. You have iframes disabled or your browser does not support them.",
                    "loadingAnimation": "<?= str_replace('/', '\/', includes_url('/js/thickbox/loadingAnimation.gif')) ?>",
                    "closeImage": "<?= str_replace('/', '\/', includes_url('/js/thickbox/tb-close.png')) ?>"
                };
                /* ]]> */
            </script>
            <script src="<?= plugins_url('/thickbox-custom.js', __FILE__) ?>?ver=<?= PEG_VERSION ?>"></script>
        <?php
        }// end function peg_add_custom_thickbox_config()

// add the photoswipe script included with this plugin (if they selected
// the option)
        function peg_add_photoswipe_script()
        {
            // add in the photoswipe script and related files
            wp_enqueue_script('peg_photoswipe', plugins_url('/photoswipe/photoswipe.min.js', __FILE__), null, PEG_PHOTOSWIPE_VERSION);
            wp_enqueue_script('peg_photoswipe_ui', plugins_url('/photoswipe/photoswipe-ui-default.min.js', __FILE__), array('peg_photoswipe'), PEG_PHOTOSWIPE_VERSION);
	        wp_enqueue_script('peg_photoswipe_init', plugins_url('photoswipe-init.js', __FILE__),array('peg_photoswipe','peg_photoswipe_ui'),PEG_VERSION);
            wp_enqueue_style('peg_photoswipe_css', plugins_url('/photoswipe/photoswipe.css', __FILE__), null, PEG_PHOTOSWIPE_VERSION);
	        wp_enqueue_style('peg_photoswipe_skin', plugins_url('/photoswipe/default-skin/default-skin.css', __FILE__), null, PEG_PHOTOSWIPE_VERSION);

            // add the action to wp_footer to init photoswipe
            add_action('wp_footer', array(&$this, 'peg_init_photoswipe'));
        }// end function peg_add_photoswipe_script()
        function trueOrFalse($value){
	        return ($value === "1") ? 'true' : 'false';
        }
		function peg_init_photoswipe()
        {
	        //Render photoswipe html content
	        echo file_get_contents(plugin_dir_path(__FILE__).'/photoswipe.html');
            // output the jQuery call to setup photoswipe
            ?>

            <script>
                jQuery(document).ready(function () {
                    options = {
	                    shareEl: <?php echo $this->trueOrFalse($this->configuration->get_option('peg_photoswipe_show_share_button'));?>,
	                    fullscreenEl:<?php echo $this->trueOrFalse($this->configuration->get_option('peg_photoswipe_show_fullscreen_button'));?>,
	                    closeEl:<?php echo $this->trueOrFalse($this->configuration->get_option('peg_photoswipe_show_close_button'));?>,
	                    counterEl:<?php echo $this->trueOrFalse($this->configuration->get_option('peg_photoswipe_show_index_position'));?>
                    };
                    // ready event, get a list of unique rel values for the photoswiped images
                    var rels = [];
                    var rel = '';
                    jQuery('a.photoswipe').each(function () {
                        // for each photoswipe rel, if the rel value doesn't exist yet,
                        // add it to our array
                        rel = jQuery(this).attr('rel');
                        if (rel != undefined) {
                            if (!peg_in_array(rels, rel)) {
                                // add this rel to our array
                                rels.push(jQuery(this).attr('rel'));
                            }
                        }
                    });

                    // check to see if our rels array has been built and has any values
                    if (rels.length > 0) {
                        // we have at least one individual set of unique rels, setup photoswipe
                        // for each
                        jQuery.each(rels, function (key, value) {
                            // get this rel and create the collection
	                        initPhotoSwipeFromDOM(jQuery('a.photoswipe[rel=' + value + ']'),options)
                        });
                        //Check if there are any images without any relation (i.e. single images)
	                    jQuery('a.photoswipe:not([rel])').each(function(key, value){
		                    initPhotoSwipeFromDOM(jQuery(this),options);
	                    });
                    } else {
                        // we didn't get any rels, so attempt without rel checking
	                    initPhotoSwipeFromDOM(jQuery('a.photoswipe'),options);
                    }

                });

                function peg_in_array(array, value) {
                    for (var i = 0; i < array.length; i++) {
                        if (array[i] === value) {
                            return true;
                        }
                    }
                    return false;
                }
            </script>
        <?php
        }// end function peg_init_photoswipe()

// add the caption width calculation javascript if necessary
        function peg_add_caption_width_javascript()
        {
            // check to see if our global variable is defined
            if (isset($GLOBALS['peg_include_caption_width_correction_javascript'])) {
                // we must perform the correction, setup the javascript
                ?>
                <script>
                    jQuery(document).ready(function () {
                        // ready event, find any images inside a caption, grab their width
                        // and then use that width to update the corresponding caption
                        jQuery('div.wp-caption').each(function () {
                            // for each caption, we need to locate the image inside, get the
                            // width of it, then set the width of the caption element
                            peg_add_caption_width_javascript_helper(jQuery(this));
                        });
                    });
                    function peg_add_caption_width_javascript_helper(caption_obj) {
                        // check to make sure we have our image dimensions
                        if (caption_obj.find('img').width() > 0) {
                            // we're good, we have image dimensions.  wait one more second
                            // to make sure they're correct, then update our caption
                            // element's width correctly
                            setTimeout(function () {
                                peg_add_caption_width_javascript_helper_run(caption_obj)
                            }, 1000);
                        } else {
                            // no dimensions yet, delay and retry
                            setTimeout(function () {
                                peg_add_caption_width_javascript_helper(caption_obj)
                            }, 1000);
                        }
                    }// end function peg_add_caption_width_javascript_helper(..)
                    function peg_add_caption_width_javascript_helper_run(caption_obj) {
                        // we have our width, adjust the caption's width appropriately
                        caption_obj.css('width', caption_obj.find('img').width());
                    }// end function peg_add_caption_width_javascript_helper_run(..)
                </script>
            <?php
            }// end if we have the width correction indicator set
        }// end function peg_add_caption_width_javascript()

// filter the caption shortcode to add our class/style attributes
        function peg_img_caption_shortcode_filter($content)
        {
            // parse our content for each caption pass the matched area to our
            // callback for appropriate modification

            // perform the preg_replace if they have set either the peg_caption_css
            // or peg_caption_style
            if (($this->configuration->get_options()['peg_caption_css'] != null) || ($this->configuration->get_options()['peg_caption_style'] != null)) {
                // one of hte container styles is configured, set them
                $content = preg_replace_callback('/class="([^"]*)wp-caption([^"]*)"([^>]+)/', 'PicasaExpressX2::peg_img_caption_shortcode_filter_container_callback', $content);
            }

            // perform the preg_replace if they have set either the peg_caption_css
            // or peg_caption_style
            if (($this->configuration->get_options()['peg_caption_p_css'] != null) || ($this->configuration->get_options()['peg_caption_p_style'] != null)) {
                // one of the p styles is configured, set them
                $content = preg_replace_callback('/class="([^"]*)wp-caption-text([^"]*)"([^>]*)/', 'PicasaExpressX2::peg_img_caption_shortcode_filter_p_callback', $content);
            }

            // now return our modified content
            return $content;
        }// end function peg_img_caption_shortcode_filter(..)


        function peg_img_caption_shortcode_filter_container_callback($matches, $prefix = 'peg_caption')
        {
            // this function gets called for every match found in the function
            // above.  Process each one properly

            // start our return
            $return = 'class="' . $matches[1] . 'wp-caption';

            // see if we need to the class if we're on the P
            if ($prefix == 'peg_caption_p') {
                $return .= '-text';
            }

            // check to see if we need to add the class
            if ($this->configuration->get_options()[$prefix . '_css'] != null) {
                // yep, lets add our class
                $return .= ' ' . $this->configuration->get_options()[$prefix . '_css'];
            }

            // finish up the class attribute
            $return .= $matches[2] . '"';

            // check to see if we need to add the style attribute
            if ($this->configuration->get_options()[$prefix . '_style'] != null) {
                // yep, lets parse the $matches[2] for the style attribute, if found
                // add our style to it, if not, add the style attribute
                if (preg_match('/^(.+)style\s*=\s*"(.+)$/', $matches[3], $style_matches) > 0) {
                    // we matched a style attribute we need to modify

                    // determine if our style option has a trailing semi-colon
                    $add_style = $this->configuration->get_options()[$prefix . '_style'];
                    if ((strrpos($this->configuration->get_options()[$prefix . '_style'], ';') + 1) != strlen($this->configuration->get_options()[$prefix . '_style'])) {
                        // the last character of our style option value that we're
                        // adding is not a semi-colon, we need to add one
                        $add_style .= ';';
                    }

                    // determine if our existing style matches contains a width, and
                    // our set style has a width, if so remove the existing style width
                    if (strpos($add_style, 'width:') !== false) {
                        // we have width, so we need to remove any width from the
                        // $style_matches[2]
                        $style_matches[2] = preg_replace('/width:\s*[^;"]+/', '', $style_matches[2]);
                    }

                    // add the appropriate style data to the return
                    $return .= $style_matches[1] . 'style="' . $add_style . ' ' . $style_matches[2];
                } else {
                    // no style attribute found, lets add one
                    $return .= ' style="' . $this->configuration->get_options()[$prefix . '_style'] . '"' . $matches[3];
                }
            } else {
                // no style modification, simply add our $matches[3]
                $return .= $matches[3];
            }

            // return the modified caption output
            return $return;
        }// end function peg_img_caption_shortcode_filter_callback(..)

        function peg_img_caption_shortcode_filter_p_callback($matches)
        {
            // this function gets called for every match found in the function
            // 2 above.  Simply configure the function 1 above to do the other
            // option setting
            return Google_Photo_Access::peg_img_caption_shortcode_filter_container_callback($matches, 'peg_caption_p');
        }

    }
}