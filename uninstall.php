<?php
namespace photo_express;
// Make sure that we are uninstalling
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

require_once plugin_dir_path(__FILE__).'class-google-photo-access.php';
require_once plugin_dir_path(__FILE__).'class-simple-cache.php';
require_once plugin_dir_path(__FILE__).'class-settings-storage.php';

//uninstalls the plugin and delete all options / revoking oauth access
$configuration = new Settings_Storage();
$photo_access = new Google_Photo_Access($configuration);
$photo_access->uninstall();
$cache = new Simple_Cache($configuration,$photo_access);
$cache->clear_cache();
if (is_multisite()){
	$configuration->delete_site_options();
}else{
	$configuration->delete_options();
}
?>