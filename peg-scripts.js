(function ($) {

// gallery/image switch by button #peg-switch
    var peg_gallery = false;
// falg to album handler for insert shortcode instead of open album's images
    var peg_shortcode = false;
    /* state of the code: reflect the header part displayed
     * 	nouser - input for user
     * 	albums - show albums
     *  images - show images from album
     */
    var peg_state = 'albums';
// picasa user name
    var peg_user_name = 'undefined';
// numbering 
    var peg_current = 1;
// save the last request to the server for reload button
    var peg_last_request = false;
    var peg_no_request = false;
// cache for server request both albums and images 
    var peg_cache = [];
    var peg_options = {
        waiting: 'loading image and text for waiting message',
        env_error: 'error if editor function can not be found',
        image: 'label for button Image',
        gallery: 'label for button Gallery',
        reload: 'label for button Reload',
        options: 'label for button Reload',

        uniqid: 'uniq id for gallery',

        thumb_w: 150,      //'thumbnail width',
        thumb_h: 0,        //'thumbnail height',
        thumb_crop: false,    //'exact dimantions for thumbnail',

        state: 'state by default or saved'
    };

// variable to store options that are updated temporarily during this insert
    var peg_updated_options = [];

    $(function () {
        // convert encoded options
        for (var i in window['peg_options']) {
            if (window['peg_options'][i] == '0' || window['peg_options'][i] == '') peg_options[i] = false;
            else peg_options[i] = decodeURIComponent(window['peg_options'][i]);
        }

        // get username
        peg_user_name = peg_options.peg_user_name;
        $('#peg-user').text(peg_user_name);
        // restore state
        if ('images' == peg_options.state) {
            $('#peg-albums').show().siblings('.peg-header').hide();
            peg_request({
                action: 'peg_get_images',
                guid: peg_options.peg_last_album
            });
        } else {
            peg_switch_state(peg_options.state);
        }

        // set options unchanged handlers
        $('#peg-options input').change(function () {
            // options page input value changed
            var name = $(this).attr('name');
            var extractOptionRegex = /\w+\[(\w+)\]/;
            var result = extractOptionRegex.exec(name);
            if(result == null){
                return;
            }
            name = result[1];
            if (($(this).attr('type') == 'text') || ($(this).attr('type') == 'hidden')) {
                // this is a text or hidden input, we can just get the value
                peg_updated_options[name] = $(this).val();
                peg_options[name] = $(this).val();
            } else {
                // we only set the value if this element is checked
                if ($(this).attr('checked')) {
                    peg_updated_options[name] = $(this).val();
                    peg_options[name] = $(this).val();
                } else {
                    peg_updated_options[name] = false;
                    peg_options[name] = false;
                }
            }
        });
        $('#peg-options select').change(function () {
            // options page selection box value changed
            var name = $(this).attr('name');
            peg_updated_options[name] = $(this).val();
            peg_options[name] = $(this).val();
        });

        // the form unchanged handler
        $('#peg-nouser form').submit(peg_change_user);
    });

    $(document).ajaxError(function (event, request, settings, error) {
        console.log("Error requesting page " + settings.url + '\nwith data: ' + settings.data + '\n' + error);
    });

    function peg_switch_state(state) {
        peg_state = state;
        $('#peg-' + state).show().siblings('.peg-header').hide();
        peg_set_handlers();
    }

    function peg_save_state(last_request) {
        if (peg_options.peg_save_state) {
            $.post(ajaxurl, {
                action: 'peg_save_state',
                state: peg_state,
                last_request: last_request
            });
        }
    }

    function peg_set_handlers() {

        $('.button').unbind();

        $('.peg-reload').click(function () {
            if (peg_last_request) {
                if (peg_state != 'albums') $('#peg-albums').show().siblings('.peg-header').hide();
                peg_cache[peg_serialize(peg_last_request)] = false;
                peg_request(peg_last_request);
            }
            return (false);
        });

        $('.peg-options').toggle(
            function () {
                $('#peg-options').slideDown('fast');
                peg_show_options();
                return (false);
            }, function () {
                $('#peg-options').slideUp('fast');
                peg_show_options();
                // handle exceptions
                if (peg_gallery && !(peg_options['peg_link'].indexOf('thickbox') != -1 || peg_options.peg_link == 'lightbox' || peg_options.peg_link == 'highslide')) {
                    $('#peg-switch').click();
                }
                return (false);
            });
        peg_show_options();

        switch (peg_state) {
            case 'nouser':
                $('#peg-change-user').click(peg_change_user);
                $('#peg-cu-cancel').click(function () {
                    peg_switch_state('albums');
                    return (false);
                });
                $('#peg-main').empty();
                break;

            case 'albums':
                $('#peg-user').click(function () {
                    $('#peg-nouser input').val(peg_user_name);
                    peg_switch_state('nouser');
                    return (false);
                });
                $('#peg-switch2').click(function () {
                    peg_shortcode = !peg_shortcode;
                    $(this).text((peg_shortcode) ? peg_options.shortcode : peg_options.album);
                    return (false);
                });
                peg_get_albums();
                break;

            case 'images':
                peg_current = 1;
                $('#peg-switch').click(function () {
                    if (peg_gallery || (peg_options['peg_link'].indexOf('thickbox') != -1) || peg_options.peg_link == 'lightbox' || peg_options.peg_link == 'highslide') {
                        peg_gallery = !peg_gallery;
                        peg_current = 1;
                        $('#peg-main td.selected').removeClass('selected').click();
                    }
                    $(this).text((peg_gallery) ? peg_options.gallery : peg_options.image);
                    return (false);
                });
                $('#peg-album-name').click(function () {
                    peg_switch_state('albums');
                    return (false);
                });
                $('#peg-insert').click(function () {
                    // hide the insert button so it cannot be clicked again (since the
                    // ajax for processing the shortcodes might take a couple of secs)
                    $('#peg-insert').hide();

                    // save our current state
                    peg_save_state(peg_last_request.guid);

                    // grab the shortcode
                    var shortcode = peg_make_image_shortcode('#peg-main td.selected');

                    // check to see if we need to add the shortcode to the editor
                    // OR perform the ajax request to process the shortcode into
                    // HTML to return HTML instead
                    if (peg_options['peg_return_single_image_html'] == true) {
                        // use ajax to send the generated shortcode back to the
                        // plugin for processing into HTML, then return the
                        // returned HTML to the editor instead of the shortcode
                        $.ajax({
                            url: ajaxurl,
                            data: {action: 'peg_process_shortcode', data: shortcode},
                            type: 'POST',
                            success: function (data) {
                                // throw the returned data into the editor
                                if (data['error']) {
                                    // we had an error
                                    alert(data['error']);
                                } else if (data['html']) {
                                    // we're ok
                                    peg_add_to_editor(data['html']);
                                    return (false);
                                } else {
                                    // hmm, not sure what happened
                                    alert('Error, no data returned');
                                }
                            }, // end success function
                            dataType: 'json'
                        });
                    } else {
                        // this is a standard insert of the shortcode into the editor
                        peg_add_to_editor(shortcode);
                        return (false);
                    }
                }).hide();
                break;
        }
    }

    function peg_change_user() {
        peg_user_name = $('#peg-nouser input').val();
        $('#peg-user').text(peg_user_name);
        peg_switch_state('albums');
        peg_save_state(peg_user_name);
        return (false);
    }

    function peg_request(data) {

        if (peg_no_request) return;
        peg_no_request = true;
        $('.peg-reload').hide();

        peg_last_request = data;
        var callback = (data.action == 'peg_get_gallery') ? peg_albums_apply : peg_images_apply;

        if (peg_cache[peg_serialize(data)]) {
            callback(peg_cache[peg_serialize(data)]);
        } else {
            // set progress image
            $('#peg-message2').html(peg_options.waiting);

            data['cache'] = peg_serialize(data);
            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            $.post(ajaxurl, data, callback, 'json');
        }
    }

    function peg_get_albums() {

        peg_request({
            action: 'peg_get_gallery',
            user: peg_user_name
        });
    }

    function peg_album_handler() {

        var guid = $('a', this).attr('href').replace(/^[^#]*#/, '');

        if (peg_shortcode) {
            // inserting the shortcode for the album now.  first, nab the
            // tags options
            var include_featured = $('#peg_featured_tag:checked').val();
            var additional_tags = $('#peg_additional_tags').val();
            if (include_featured != null) {
                // include only featured tagged photos
                var tags = 'Featured';
            }
            if ((additional_tags != undefined) && (additional_tags.length > 0)) {
                // include additional tags
                if (tags == undefined) {
                    var tags = '';
                } else {
                    tags += ',';
                }
                tags += additional_tags.replace(/\s/g, '+');
            }

            // generate the short code
            var shortcode = '[peg-gallery album="' + guid + '"';
            if (tags != undefined) {
                // add our tags query string to the string returned
                shortcode += ' tags="' + tags + '"';
            }

            // initialize the attributes to add
            var attributes = ' ';

            // go through any updated options and add the attributes
            for (updated in peg_updated_options) {
                // go through any elements of the peg_updated_options array and add
                // those to our attributes
                if ((updated.indexOf('_size_mode') > 0) || (updated.indexOf('_size_dimension') > 0) || (updated.indexOf('_size_crop') > 0)) {
                    // this is one of the fields used to calculate the single image/video
                    // size, we want to ignore them
                } else if ((updated == 'peg_featured_tag') || (updated == 'peg_additional_tags')) {
                    // this is one of the tag options that we've already handled above,
                    // skip it
                } else {
                    // this is an attribute we need to include in the shortcode
                    attributes += updated + '="' + peg_updated_options[updated] + '" ';
                }
            }// end looping through any updated options

            // add any updated attributes to the shortcode
            shortcode += attributes + ']';

            // now save state and return the data to the editor
            peg_save_state(peg_user_name);

            // add the short code to the editor
            peg_add_to_editor(shortcode);
        } else {
            peg_request({
                action: 'peg_get_images',
                guid: guid
            });
        }

        return (false);
    }

    function peg_show_reload() {
        peg_no_request = false;
        $('.peg-reload').show().text(peg_options.reload);
    }

    function peg_show_options() {
        $('.peg-options').show().text(peg_options.options);
    }

    function peg_albums_apply(response) {

        peg_show_reload();

        if (response.error) {
            $('#peg-nouser input').val(peg_user_name);
            peg_switch_state('nouser');
            $('#peg-message1').text(response.error);
            return;
        }

        peg_cache[response.cache] = response;

        $('#peg-main').html(response.data);
        $('#peg-message2').text(response.title);
        document.body.scrollTop = 0;

        $('#peg-main td').unbind().click(peg_album_handler);
        // state switched before request
    }

    function peg_images_apply(response) {

        peg_show_reload();

        if (response.error) {
            $('#peg-message2').text(response.error);
            return;
        }

        peg_cache[response.cache] = response;

        $('#peg-main').html(response.data);
        $('#peg-album-name').text(response.title);
        document.body.scrollTop = 0;

        $('#peg-main td').unbind().click(peg_image_handler);
        peg_switch_state('images');
    }

    function peg_image_handler() {
        if (peg_options.peg_gal_order) {
            if ($(this).hasClass('selected')) {
                var current = Number($('div.numbers', this).html());
                $('div.numbers', this).remove();
                // decrement number for rest if >current
                $('#peg-main td.selected').each(function () {
                    var i = Number($('div.numbers', this).html());
                    if (i > current) $('div.numbers', this).html(i - 1);
                });
                peg_current--;
            } else {
                $(this).prepend("<div class='numbers'>" + peg_current + "</div>");
                peg_current++;
            }
        }

        $(this).toggleClass('selected');

        // check selected to show/hide Insert button
        if ($('#peg-main td.selected').length == 0) $('#peg-insert').hide();
        else $('#peg-insert').show();

        return (false);
    }

    function peg_serialize(data) {
        function Dump(d, l) {
            if (l == null) l = 1;
            var s = '';
            if (typeof(d) == "object") {
                s += typeof(d) + " {\n";
                for (var k in d) {
                    for (var i = 0; i < l; i++) s += "  ";
                    s += k + ": " + Dump(d[k], l + 1);
                }
                for (var i = 0; i < l - 1; i++) s += "  ";
                s += "}\n";
            } else {
                s += "" + d + "\n";
            }
            return s;
        }

        return Dump(data);
    }

    function peg_add_to_editor(data) {
        var win = window.dialogArguments || opener || parent || top;
        if (win['send_to_editor']) win.send_to_editor(data);
        else {
            alert(peg_options.env_error);
            tb_remove();
        }
    }

    String.prototype.trim = function () {
        var s = this.toString().split('');
        for (var i = 0; i < s.length; i++) if (s[i] != ' ') break;
        for (var j = s.length - 1; j >= i; j--) if (s[j] != ' ') break;
        return this.substring(i, j + 1);
    };

    String.prototype.escape = function () {
        var s = this.toString();
        s = s.replace(/&/g, "&amp;");
        s = s.replace(/>/g, "&gt;");
        s = s.replace(/</g, "&lt;");
        s = s.replace(/"/g, "&quot;");
        s = s.replace(/'/g, "&#039;");
        return s;
    };

    function peg_make_image_shortcode(case_selector) {

        var codes = [], code, icaption, ihref, isrc, ialt, ititle, ilink, iorig, item_type, imageSize;

        // begin the attributes to add to the shortcode
        var attributes = '';

        // go through any updated options and add the attributes
        for (updated in peg_updated_options) {
            // go through any elements of the peg_updated_options array and add
            // those to our attributes
            if ((updated.indexOf('_size_mode') > 0) || (updated.indexOf('_size_dimension') > 0) || (updated.indexOf('_size_crop') > 0)) {
                // this is one of the fields used to calculate the single image/video
                // size, we want to ignore them
            } else {
                // this is an attribute we need to include in the shortcode
                attributes += updated + '="' + peg_updated_options[updated] + '" ';
            }
        }// end looping through any updated options

        // selection order
        var order = (peg_options.peg_gal_order);

        // define our codes array that we'll generate next
        var codes = [];

        // go through each selected image and add the shorttags
        $(case_selector).each(function (i) {
            // for each image in our selecctions, grab the necessary info storing
            // the URLs, captions, etc
            icaption = $('span', this).text().escape(); // ENT_QUOTES
            ihref = $('a', this).attr('href');
            isrc = $('img', this).attr('src');
            ialt = $('img', this).attr('alt');
            ilink = $('a', this).attr('href');
            item_type = $('img', this).attr('type');
            imageSize = $('a',this).attr('data-size');
            // create the shortcode adding in any common attributes overridden by
            // the "Options" page in the image selection window
            code = '[peg-image src="' + isrc + '" href="' + ihref + '" caption="' +
            icaption + '" type="' + item_type + '" alt="' + ialt + '" image_size="' + imageSize +'" ' + attributes + ']';

            // add this image's shortcode to our array of image shortcodes to join together
            if (order) {
                codes[Number($('div.numbers', this).html())] = code;
            } else {
                codes.push(code);
            }
        });

        // join all of the selected images together
        if (peg_gallery) {
            //FIXME - this logic is not complete nor tested.  The shortcode processing
            // in picasa-express2.php needs to change the thumbnail size to the album
            // thumb size when the peg-image shortcodes are wrapped with the peg-gallery
            // shortcode, and full testing needs completed

            // join all of the codes together inside the gallery tag
            codes = codes.join('');

            var gal_css = [peg_options.peg_gal_css || '', ((peg_options.peg_gal_align != 'none') && 'align' + peg_options.peg_gal_align) || ''].join(' ').trim();

            codes = '[peg-gallery%css_style%]\n%images%[/peg-gallery]'.replace(/%(\w+)%/g, function ($0, $1) {
                switch ($1) {
                    case 'css_style':
                        var a = [(gal_css && 'class="' + gal_css + '"') || '', (peg_options.peg_gal_style && 'style="' + peg_options.peg_gal_style + '"') || ''].join(' ').trim();
                        return (a && ' ' + a + ' ');
                    case 'images':
                        return codes;
                }
            });
        } else {
            // we're not creating a "gallery" of individual images, so simply join
            // all of the selected image codes together, separating by two character
            // returns to return to the editor
            codes = codes.join("\n\n") + ' ';
        }

        // determine if we're adding our automatic clear after the group of images that
        // we just created
        if (peg_options.peg_auto_clear) {
            // add the clear
            codes = codes + "\n\n<p class=\"clear\"></p>\n\n";
        }

        // return our formatted codes
        return codes;
    }// end function peg_make_image_shortcode(..)


})(jQuery);
