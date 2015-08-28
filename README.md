# Photo Express for Google - A Wordpress plugin that integrates Google Photos

Browse your Google+/Picasa Web albums and select images or whole albums to insert into your posts/pages. This is an unofficial fork of ["Picasa and Google Plus Express"](https://wordpress.org/plugins/picasa-express-x2/).

The fork has been necessary because the authentication for private photo albums of "Picasa and Google Plus Express" has been broken since April 2015 due to changes to the authentication protocol. This is the first release after a major change to the code. Please be so kind and test this version before putting it into production.

**This fork supports OAUTH authentication with Google, so you can access private photos and albums again! Currently this only works on a blog-wide mode (Google+ Express access level: blog). I am working on a solution for a per user authentication.**

Upgrade notice:
*   The plugin requires at **least PHP 5.4**. Using a version before PHP 5.4 leads to parse errors.
*   If you have shortcodes for single images that have been generated using a prior version of "Photo Express for Google" (0.1) or "Picasa and Google Plus Express" (all versions), you'll have to update them to make photoswipe work correctly. Please have a look into the FAQ.
*   The prefix for all CSS classes has changed from "pe2" to "peg". Please consider this when upgrading from "Picasa and Google Plus Express".
*   The function to store authentication data on a user basis has been removed. The use case for storing it on a per user basis was not clear. If you rely on this function, please post a feature request with your particular use case.


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


= Installation ==

1. Check the requirements: At least PHP 5.4 is needed to run this plugin.
2. Upload `photo-express` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. If you have "Picasa and Google Plus Express" installed, **deactivate it**. Otherwise the plugin might not work. Settings from "Picasa and Google Plus Express" will be automatically migrated on the first activation of Photo Express for Google.
5. Use Settings link under 'Settings' -> 'Photo Express for Google' and set user name and other parameters. If you need **private albums** access (**required for tag searches**) use the link to request access from Google.
6. In the edit post/page screen use the picasa icon next to 'Add Media' for image/album selection dialog
7. Select an album or image to insert into your post/page:
	* To insert a whole album, click the "Album" button so it toggles to "Shortcode", then click on the album you wish to insert
	* To insert an image, click on the album the image is in, then select one or more images.  When done selecting images, click "Insert" to insert all of the selected images