=== Plugin Name ===
Contributors: thhake, gjanes, wott
Tags: google+, oauth, googleplus, google, picasaweb, picasa, phototile, tile, gallery, album, photo, photos, image, images, picture, pictures, video, videos, photoswipe, mobile, iphone, android, highslide, thickbox, lightbox, private, wpmu, user, blog, sitewide, multisite, post, shortcode, thumbnail
Requires at least: 3.7
Tested up to: 4.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Browse and select photos from any public or private Google+ album and add them to your posts/pages. This is an unofficial
fork of "Picasa and Google Plus Express" that fixes google authentication.

== Description ==

Browse your Google+/Picasa Web albums and select images or whole albums to insert into your posts/pages. This is an unofficial
fork of Picasa Express 2.

The fork has been necessary because the authentication for private photo albums of "Picasa and Google Plus Express" has been broken since
 April 2015 due to changes to the authentication protocol. This is the first release after a major change to the code. Please
  be so kind and test this version before putting it into production.

**This fork supports OAUTH authentication with Google, so you can access private photos and albums again! Currently this only works on a blog-wide mode (Google+ Express access level: blog). I am working on a solution for a per user authentication.**

Upgrade notice:
*	The plugin requires at **least PHP 5.4**. Using a version before PHP 5.4 leads to parse errors.
*	If you have shortcodes for single images that have been generated using a prior version of "Photo Express for Google" (0.1) or "Picasa and Google Plus Express" (all versions), you'll have to update them to make photoswipe work correctly. Please have a look into the FAQ.
*	The prefix for all CSS classes has changed from "pe2" to "peg". Please consider this when upgrading from "Picasa and Google Plus Express".
*	The function to store authentication data on a user basis has been removed. The use case for storing it on a per user basis was not clear. If you rely on this function, please post a feature request with your particular use case.


Features:

*	Use your Google user to access your photos and albums
*	**Private** album access after granting access via Google auth service
*	Select albums / images from GUI listing by album cover and name
*	**Mobile gallery** display support with PhotoSwipe
*	Google+ style **phototile** gallery display or standard gallery utilizing native Wordpress image thumbnail size
*	Create a gallery of a subset of photos from an album by **filtering the album with tags**
*   Gallery and image shortcodes for display of entire album or selected images
*	**Wordpress MU** support - sitewide activation, users, roles

**Additional settings:**

*	Image link:
 * **Mobile gallery** with PhotoSwipe - in desktop browsers PhotoSwipe is clean and works nicely.  On a mobile device, swiping between photos is supported
 * Custom thickbox with plugin - including keyboard navigation and Google+ view link
 * Wordpress thickbox - using the integrated thickbox provided by Wordpress
 * 3rd-party thickbox - setup the thickbox class/rel, but rely on external library
 * 3rd-party lightbox - setup the lightbox class/rel, but rely on an external library
 * 3rd-party highslide - setup the highslide class/rel, but rely on an external library
 * Google+ image page - a direct link to the Google+ image page for the photo
 * direct - a direct link to the large photo
 * none - just the image thumbnails are displayed with no link
*	CSS / style customizations for most tags created in galleries, photos & captions
*	Caption under image and/or in image/link title and gallery display
*	Alignment for images and gallery using standard CSS classes
*	Define **Roles** who are allowed to use the plugin
*	Switch from blog to **user level** for storing the user and private access token
*	Settings for single-image thumbnail size, single-video thumbnail size and large image size limit
* Forcing SSL connection to the Google photo APIs
* Optional caching of the Google photo API results

**And by design:**

*	Shortcodes inserted for both albums and photos in anticipation of changes forthcoming in Picasaweb to Google+ migration.
*	Support native Wordpress image and link dialog for album image thumbnail size
*	Support native Wordpress gallery captions
*	Multilanguage support

== Installation ==


1. Check the requirements: At least PHP 5.4 is needed to run this plugin.
2. Upload `photo-express` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. If you have "Picasa and Google Plus Express" installed, **deactivate it**. Otherwise the plugin might not work. Settings from "Picasa and Google Plus Express" will be automatically migrated on the first activation of Photo Express for Google.
5. Use Settings link under 'Settings' -> 'Photo Express for Google' and set user name and other parameters. If you need **private albums** access (**required for tag searches**) use the link to request access from Google.
6. In the edit post/page screen use the picasa icon next to 'Add Media' for image/album selection dialog
7. Select an album or image to insert into your post/page:
	* To insert a whole album, click the "Album" button so it toggles to "Shortcode", then click on the album you wish to insert
	* To insert an image, click on the album the image is in, then select one or more images.  When done selecting images, click "Insert" to insert all of the selected images

== Frequently Asked Questions ==
= Photoswipe does not work anymore for single images =
With version 0.2 of Photo Express for Google the internal photowsipe library has been updated to version 4. The newest version of photoswipe needs to know the size in pixel of the image that should be displayed in photoswipe. Therefore you'll need to add the "image_size" attribute to your old `[peg-image]` shorttags. It should contain a value describing the images size in the format "<Width>x<Height>" (i.e. "1920x1080" for full HD). This manual step is not needed for any single picture that has been added with Photo Express for Google version 0.2 or later. All shortcodes generated by Photo Express for Google version 0.2 include the "image_size" attribute. So an alternative way of updating old shortcodes is to delete them and insert them again with the current version of Photo Express for Google.

= Tag searches in albums returns inconsistent results =
Google+ / Picasaweb tag searching currently contains a bug that causes images
to slowly start dropping out of tag search results, causing fewer than
expected images to be returned.

I have posted several issues and am following threads like the following:
[Google Groups: Picasa Data API](https://groups.google.com/forum/?fromgroups=#!topic/google-picasa-data-api/YxBzKZYeIa4)

Hopefully Google will discover the cause of the problem soon.

= Username validation fails =
If when in the options and configuring the username, the "Checking user:" test
returns an error, it could be related to changes Google has been making when
migrating from picasaweb to Google+.

The error message provided should give you a URL you can click, which will
then provide you with Google's error message.  In most cases, the error
appears to be related to older "short username" values that Google no longer
supports.

Simply change the username you are using to your Google email address and save
the settings.

If this still doesn't work, check your hosting provider's PHP preferences - some hosting companies disable connect functions and Wordpress can not request data from other sites. 

= Error with media-upload.js =
When attempting to insert an image into a post and receive the error:

"Error: Cannot insert image(s) due to incorrect or missing /wp-admin/js/media-upload.js"

There are several possible causes for this:

#1 - if you attempt to open the selection dialog before the page has
completely loaded, some of the required JavaScript libraries may not be loaded
yet.  Refresh the page and wait for it to load completely, then try again.

#2 - it is known that the "CKEditor for WP" plugin conflicts with the
media-upload.js file.  Disable this plugin and try again.

= How can I select several images to insert at a time? =

When browsing the album's images you may click on multiple photos to insert at once.  These images will be inserted into the post as using the thumbnail size you have defined in "Single image thumbnail size" in settings.

If you have selected the option to "Relate all of a post's images" and you are
using a link type of one of the thickbox, lightbox or highslide libraries, all
of the photos within the post will be related for next/prev navigation within
the display window.

= How can I select several images for "Gallery" display? =
There are two options to filter images in a "Gallery" display:

**#1 - Use tags to filter photos in an album** so that only photos with a specific
tag are displayed.

This is done by inserting an "Album" shortcode, but in the "Options" prior to inserting populate the "Photo tag options" to select photos with the "Featured" tag, or a customized tag name typed into the text box.

NOTE - filtering by tags requires private album access.  This is a
Google+/picasaweb requirement.

**#2 - Manually select the photos** (and optionally their sort order).

This is done while browsing an album's photos, click the "Image" button.
After clicking the "Image" button, it will switch to "Gallery" indicating that
any photos selected this time will be inserted into the post in "Gallery"
format using the default blog thumbnail size.

= I want private albums browsing =

On the settings page press the link titled "Click here to configure private access to Google Photo". You will be guided through a multi step setup process and be redirected to Google and asked to grant access. If you press the "Grant access" button on Google, you will be returned to settings page and access will now be granted.

After successfully authorizing the access for the blog, the next time you open
the album / image selection dialog, you will see all of your photos and
albums, including any private ones.

= I revoked access to my server on Google's site, and now the plugin doesn't work =

On revoking access through Google instead of this plugin, Google will not inform your server. If you revoke access on Google's side, you have to click the "Click here to revoke the token" link on the settings page.

= Caption widths aren't determined correctly =
If you're using a single-image thumbnail width dimension of "Height" or "Any", the width is dynamically based on aspect ratio.  Thus the value generated for the caption is not terribly accurate, causing strange caption wrapping sometimes.

The best solution here is to change the single-image thumbnail dimension to "Width", because then the width of the caption can be determined precisely.

Another option would be to try a "Caption container style" setting of "width: auto;", although this isn't ideal either because it will scale the container to fit any size of caption text, and the text will never wrap.  The recommended solution is using a thumbnail dimension of "Width".

= Plugin has a bug. How can I fix it? =

Open a support thread in the support forum:

http://wordpress.org/support/plugin/photo-express

= I want another feature =

Open a support thread in the support forum:

http://wordpress.org/support/plugin/photo-express

== Screenshots ==

1. Opening the album/image selection dialog
2. Album selection dialog - either open an album to view/select individual
photos, or switch to shortcode mode and insert a whole album shortcode for a
gallery of photos from an entire album.
3. Photo selection dialog - select one or more photos to insert into the post,
or switch to gallery and select images to insert together into a
small-thumbnail gallery.
4. Plugin settings
5. An example of the phototile gallery layout


== Changelog ==

= todo =
* Embedded video support (currently doesn't appear to be allowed by Google)
* Add the button to Visual toolbar of Tiny editor in full screen mode
* Work with Google to correct tag searching index issues which cause tag searches to return unexpected results.

= 0.3.2 =
* Fixed another issue while uninstalling
* Fixed image browser not showing new albums when caching is activated.

= 0.3.1 =
* Fixed issue while uninstalling the plugin
* Fixed issues when upgrading the plugin
* Fixed filename captions
* Added a error msg when trying to activate the plugin with a not supported PHP version

= 0.3 =
* Caching of Google Photo API requests has been implemented. It is enabled by default and can be deactivated in the settings. For more information about how caching works, please have a look at the "Advanced" section on the settings page.
* SSL communication with the Google Photo API can now be enforced. This is enabled by default in order to improve security.
* Fixed not displayed captions

= 0.2 =
* Multisite installation has been tested
* Photoswipe has been upgraded to version 4. If you rely on Photoswipe, please make sure that you add a "image_size" attribute to each peg-image shortcode. Otherwise Photoswipe won't work for this particular image. The "image_size" attribute should contain the size of the image in pixel in the format "<Width>x<Height>" (i.e. "1920x1080").
* Fixed error on the settings side when trying to change the "Large Image Size" limitation
* Fixed errors in the navigation flow of the settings page
* Fixed an error that lead to albums only having one picture if being sorted by a criteria that has duplicate entries.
* The function to store authentication data on a user basis has been removed. The use case for storing it on a per user basis was not clear. If you rely on this function, please post a feature request with your particular use case.


= 0.1 =
* First version after the fork of Picasa Express 2 (Version 2.2.10)
* Introduced Google OAuth authentication protocol for private albums. This does currently only work if you want to have
  one google account authorized for the whole blog. If you want to authorize a google account per user, you have to wait
  for the next release.
* Revoking tokens after deactivation
* Migrating settings automatically from Picasa Express 2
* Now all settings will be deleted after un-installation.
* Refactored code to be more modularized


== Old changelog of Picasa Express 2 ==

= 2.2.10 =
* Corrects a bug introduced in 2.2.7 regarding per-post options on thumbnail size setting

= 2.2.9 =
* Corrects an undefined variable

= 2.2.8 =
* Corrects a bug with non-proportional image thumbnail sizes
* Corrects a bug with images displayed without a caption when captions are enabled, the alignment was not being properly assigned

= 2.2.7 =
* Added an option to allow single image/video thumbnails to be configured non-proportionally
* Added the configuration option for PhotoSwipe to prevent upscaling of images

= 2.2.6 =
* Added a browser check for Photoswipe to switch to Thickbox if the client is using any version of IE, as the current version of Photoswipe has a bug in all versions of IE.
* Standardized the image links in Thickbox-Custom to match those of Photoswipe.

= 2.2.5 =
* Attempted a fix suggested by Google to show all tag search results correctly.
* Corrected the use of the pe2_img_css and style options when displaying an album.

= 2.2.4 =
Bugfix for image thumbnail size that was introduced in new posts created after
installing version 2.2.3.

= 2.2.3 =
* Removed the "Video overlay" option and replaced with Google+'s native "play overlay" for videos
* Bugfix: A PHP warning was being generated when a gallery shortcode was wrapped around individual images.
* Bugfix: Removed the need for the alignX classes being added to the image when captions are being displayed below the image.  This was causing issues with certain themes, and is no longer necessary since the "Video overlay" is built into the thumbnail image.
* Bugfix: Changes to the js include method for thickbox used by the dialog to hopefully help resolve some issues certain users are experiencing with media-upload.js

= 2.2.2 =
* Changed the name of the plugin from "Picasa Express x2" to "Picasa and Google Plus Express":
 * As Google migrates to Google+ from Picasaweb, so is/will this plugin
 * Renamed the plugin to hopefully help indicate that and assist with plugin searches
 * Plugin URL / SVN name that Wordpress uses to update the plugin will remain unchanged so that plugin updates can continue to occur from older versions
* Removed the requirement of jquery.mobile for PhotoSwipe:
 * PhotoSwipe appears to work properly without jquery.mobile
 * jquery.mobile was causing many other issues with themes not designed for it
* Removed the reference to the 3rd parameter of "round()":
 * The 3rd parameter of round() was introduced in PHP 5.3, so users of previous versions were getting a warning.
 * If PHP version is detected previous to 5.3, it will not call round with the 3rd parameter.  This may cause some rounding errors, but will not prevent it from working.

= 2.2.1 =
Phototile last row will now properly select a row template so the template
number of images matches the number of images remaining, therefore preventing
the last row of a phototile gallery from being incomplete.

= 2.2 =
* Phototile support for the image gallery
 * Google+ style phototile image gallery option
 * Thumbnail sizes are generated dynamically based on phototile layout
 * Set the width of the phototile container and the phototile layout sizes to match
* Bugfix for mobile device detection to enable mobile interface for "Touch" Windows 8 devices
* Bugfix to move the is_mobile_device function into the class to prevent conflicts caused by the include

= 2.1 = 
* Mobile-friendly gallery option added:
 * The link option of [PhotoSwipe](http://www.photoswipe.com), an open-source
mobile-friendly image gallery.
 * This is now the default image link option as it provides superior performance in both desktop and mobile clients.
 * Added some additional options that display if PhotoSwipe is selected to configure the caption format.
* Some additional improvements to the preferences to clean things up and hide options that aren't necessary based on other option settings.
* RSS parsing option has been added to enable parsing the RSS to remove the caption entry for records where Google sends the caption as the image filename when the caption itself is blank. 
* Some bug-fixes related to caption width and non-width-based thumbnail scaling.

= 2.0.6 = 
* A small release to improve messaging for the Google username check and to
clean up a few additional bugs
 * Messaging when checking the username's validity is now improved
 * The FAQ now includes an entry speaking to the old picasaweb "short username" that Google appears to have removed support for
 * Some shortcode attributes for gallery shortcodes were previously not being sent if changed in the dialog options.  Corrected this and the back-end processing to handle them.
 * Some internal code cleanup and addition of some shared code to remove duplicate code.

= 2.0.5 = 
* A small release to add additional style control for captions.
 * Added the ability to set classes & styles for the P tag that contains the caption text

= 2.0.4 = 
* A small release to add additional style control for captions.
 * Added the ability to set classes & styles for the A tag that wraps the img tag
 * Added the ability to set classes & styles on the caption container if captions are enabled for display
 * Fixed a bug that was preventing the width from being set for single-image captions, therefore preventing the caption from appearing at all

= 2.0.3 =
* A bugfix and feature addition update.
 * Fixed bug related to PHP short_open_tag use
 * Fixed bug related to image insertion in order
 * Added option to return HTML rather than pe2-image shortcode

= 2.0.2 =
A bugfix release to correct bugs with the options page and requesting auth
tokens from an SSL-based site.

= 2.0.1 =
A small bugfix release to correct a fatal error when gathering the transport
for user-based access levels.

= 2.0 =
* A major change release that adds a lot of new functionality:
 * Fixed a bug introduced by Google+ that was preventing private albums access
 * Added video support - video thumbnail images have a "video play button overlay", and the link opens in a new tab for viewing on Google+
 * Added filtering albums with tags
 * Added built-in customized thickbox option
 * Added single-image shortcode
 * Single-image insert method now allows selecting multiple images to insert together

= 1.5.4 =
Picasa change URL for origin image so /s0

= 1.5.3 =
* Wordpress 3.3 fixes
* Gallery shortcode: 
 * `class`, `style` and `align` for gallery
 * `img_class`,`img_style` and `img_align` for images inside gallery
    ( align can be `none`, `left`, `right` or `center` )
 * `img_sort` for sort images ( possible values: `none`, `date`, `title`, `file` or `random` )
 * `img_asc=1` for ascending sort otherwise will be descending sort
 * `caption=1` for use image description as image caption
 * `use_title=1` for use description as image title
 * `tag` for enveloping tag ( `div` by default )
 * `thumb_w` and `thumb_h` for thumnails size and `thumb_crop=1` if you need to crop thumbnail to square 
 * `large_size` for limit opened image
 * `link` for enveloping link (possible values: `none`, `direct`, `picasa`, `thickbox`, `lightbox` or `highslide` )
 * `limit` to limit number of images displayed
 * `hide_rest=1` include images over limit but with display:none. It's for gallery script which show all images including hidden.

= 1.5.2 =
Wordpress 3.2 fixes

= 1.5 =
* Save last state
* Can limit the big size of images
* Revoke access to private albums from settings

* avoid some warnings for PHP4
* Add error handling in several cases
* remove SSL verification ( some hosts report the problem with ssl verification - thanks to streetdaddy)
* increase timeout for connection with Google Picasa
* add Picasa username test in settings
* Envelop html special chars in titles

= 1.4 =
* Some code was re-factored to be more extensible
* Warning for non standard image library. Help and links. 
* Donate banner and "power by" link in the footer (of course you can disable link and banner in the settings)
* New smart size for thumbnails
* Options on fly
* Insert Picasa album by shortcode

= 1.3 =
* Define roles which capable to use the plugin
* Switch from blog to user level for store Picasa user and private access token 
* Wordpress MU support - sitewide activation, users, roles

= 1.2 =
* Finally make compatible with old PHP versions
* Access to private albums via granting on Google page. See FAQ for more details 
* Reload button in dialog to retreive last changes

= 1.1 =
* Remove STATIC - should work with old PHP version. Optimize hooks and setting. Keep settings after deactivation.
* Change Picasa request method. Remove WP cache - changes displayed immediatelly.
* Add sorting ( date, title and file name , asc and desc ) for images in the dialog in settings. Without sorting should be displayed as in PicasaWeb.
* Add ordering for images in gallery ( by clicking )
* Add **Highslide** support

= 1.0 =
* First public release

== Upgrade Notice ==

= 0.3 =
* From this version onwards, an SSL connection with the Google Photos API is enforced by default. You can change this on the settings page.
* Caching of Google Photo API result (such as the list of photos in an album) is enabled by default. You can change this on the settings page.

= 0.2 =
* Photoswipe has been updated to version 4. If you are using the internal photoswipe you'll have to add an attribute "image_size" to each peg-image shorttag. Otherwise photoswipe won't work.
* The function to store authentication data on a user basis has been removed to reduce complexity. If you rely on this function, please post a feature request.

= 0.1 =
* Upgrade from Picasa Express x2 to Photo Express for Google if you want to use OAuth authentication.