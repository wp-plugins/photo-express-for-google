<?php

namespace photo_express;


class SSL_Enforcer implements Feed_Fetcher {


	/**
	 * @var Feed_Fetcher
	 */
	private $real_fetcher;

	function __construct( $real_fetcher ) {
		$this->real_fetcher = $real_fetcher;
	}

	/**
	 * Request server with token if defined
	 *
	 * @param string $url URL for request data
	 * @param boolean $token use token from settings
	 *
	 * @return string received data
	 */
	public function get_feed( $url ) {
		$url = convert_to_https($url);
		return $this->real_fetcher->get_feed($url);
	}

}