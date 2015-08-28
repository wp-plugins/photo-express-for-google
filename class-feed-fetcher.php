<?php

namespace photo_express;


interface Feed_Fetcher {
	/**
	 * Request server with token if defined
	 *
	 * @param string $url    URL for request data
	 * @param boolean $token use token from settings
	 * @return string received data
	 */
	public function get_feed($url);
}