<?php
/**
 * Created by PhpStorm.
 * User: thake
 * Date: 13.06.15
 * Time: 01:09
 */

namespace photo_express;

require_once plugin_dir_path(__FILE__).'class-feed-fetcher.php';

class Simple_Cache {
	/**
	 * @var Settings_Storage
	 */
	private $configuration;
	private $feed_fetcher;
	function __construct($configuration, $feed_fetcher)
	{
		$this->configuration = $configuration;
		$this->feed_fetcher = $feed_fetcher;

		//Add an action that handles the cached urls if the expiration time changes.
		add_action('update_option_peg_general_settings', array(&$this, 'check_for_cache_invalidation'),10,2);
		//Add an action that handles post/page updating so that cache entries will be invalidated
		add_action( 'save_post', array(&$this, 'clear_cached_urls_for_post') );
	}
	function is_cache_activated(){
		return $this->configuration->get_option('peg_cache_activated');
	}
	function clear_cached_urls_for_post($post_id){
		if($this->is_cache_activated()) {
			$post_content = get_post_field( 'post_content', $post_id );
			$pattern      = get_shortcode_regex();

			if ( preg_match_all( '/' . $pattern . '/s', $post_content, $matches )
			     && array_key_exists( 2, $matches )
			     && ( in_array( 'pe2-gallery', $matches[2] ) || in_array( 'peg-gallery', $matches[2] ) )
			) {
				$cached_urls = array();
				for ( $i = 0; $i < sizeof( $matches[2] ); $i ++ ) {
					if ( $matches[2][ $i ] == 'pe2-gallery' || $matches[2][ $i ] == 'peg-gallery' ) {
						$cached_urls[$this->get_transient_key( shortcode_parse_atts( $matches[3][ $i ] )['album'] )] = true;
					}
				}
				$this->clear_cached_urls( $cached_urls );
			}
		}
	}
	function clear_cache(){
		$already_cached_urls = get_option( 'peg_cache_keys' ,array());
		$this->clear_cached_urls($already_cached_urls);
	}
	function clear_cached_urls($keys){
		$all_cached_urls = get_option('peg_cache_keys',array());
		foreach($keys as $hash_key => $value){
			delete_transient($hash_key);
			unset($all_cached_urls[$hash_key]);
		}
		if(empty($all_cached_urls)){
			delete_option('peg_cache_keys');
		}else{
			update_option('peg_cache_keys', $all_cached_urls);
		}

	}
	function get_transient_key($url){
		return 'peg_cache_'. hash('md5', $url);
	}
	function check_for_cache_invalidation($old, $new){
		//Clearing the cache is needed if caching has been deactivated or if it is active and the expiration time has been reduced.
		if((!$new['peg_cache_activated'] && $old['peg_cache_activated'])
		   || ($new['peg_cache_activated']
		       && ((($old['peg_cache_expiration_time'] > $new['peg_cache_expiration_time']) && $new['peg_cache_expiration_time'] != 0)
		           || ($new['peg_cache_expiration_time'] != 0 && $old['peg_cache_expiration_time'] == 0)))){
			$this->clear_cache();
		}
	}
	/**
	 * Request server with token if defined
	 *
	 * @param string $url    URL for request data
	 * @param boolean $token use token from settings
	 * @return string received data
	 */
	function get_feed($url) {
		//first check if we're caching requests
		$caching_needed = $this->is_cache_activated();
		$hash_key = '';
		$result = '';
		if($caching_needed){
			//Check for a cache hit
			$hash_key = $this->get_transient_key($url);
			$result = get_transient($hash_key);
		}
		if(empty($result) || !$result) {
			$result = $this->feed_fetcher->get_feed( $url );
			if($caching_needed && !is_wp_error($result)){
				set_transient($hash_key, $result, $this->configuration->get_option('peg_cache_expiration_time'));
				//Add the $hash_key to the set of keys that are cached so that cached data can be cleared
				if(!isset($this->cache_keys)) {
					$this->cache_keys = get_option( 'peg_cache_keys' ,array());
				}
				$this->cache_keys[$hash_key] = true;
				update_option('peg_cache_keys', $this->cache_keys);
			}
		}


		return $result;
	}
}