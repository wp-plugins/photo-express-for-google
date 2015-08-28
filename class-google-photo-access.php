<?php
namespace photo_express;

require_once plugin_dir_path(__FILE__).'class-feed-fetcher.php';

if (!class_exists( "Google_Photo_Access" )) {
    class Google_Photo_Access implements Feed_Fetcher
    {
	    private $client_id;
        private $client_secret;
        private $access_token;
        private $refresh_token;
        private $expires_in;

	    private $initialized = false;

	    private $cache_keys;


		function register_auth_settings(){
			//Register a new section
			add_settings_section(
				'peg_oauth_section',			// ID used to identify this section and with which to register options
				__( 'Google OAuth Settings', 'peg' ),		// Title to be displayed on the administration page
				array(&$this, 'oauth_section_callback'),	// Callback used to render the description of the section
				'photo-express'		// Page on which to add this section of options
			);
			//Add the new setting fields

			add_settings_field(
				'peg_client_id',						// ID used to identify the field throughout the theme
				__( 'Google OAuth client ID', 'peg' ),							// The label to the left of the option interface element
				array(&$this, 'client_id_render_callback'),	// The name of the function responsible for rendering the option interface
				'photo-express',	                // The page on which this option will be displayed
				'peg_oauth_section',			// The name of the section to which this field belongs
				array(								// The array of arguments to pass to the callback. In this case, just a description.
					__( 'The OAuth 2 Client-ID used for authorization with Google. You can find the OAuth Client-ID in you Google Developer Console.', 'peg' ),
				)
			);

			add_settings_field(
				'peg_client_secret',						// ID used to identify the field throughout the theme
				__( 'Google OAuth client secret', 'peg' ),							// The label to the left of the option interface element
				array(&$this, 'client_secret_render_callback'),	// The name of the function responsible for rendering the option interface
				'photo-express',	                // The page on which this option will be displayed
				'peg_oauth_section',			// The name of the section to which this field belongs
				array(								// The array of arguments to pass to the callback. In this case, just a description.
					__( 'The OAuth 2 Client-secret used for authorization with Google. You can find the OAuth Client-Secret in you Google Developer Console.', 'peg' ),
				)
			);


			//Register the options so that they are stored in the database
			register_setting('peg_oauth_settings', 'peg_oauth_settings', array(&$this, 'validate_client_credentials'));
		}
	    function client_id_render_callback(){
		    $this->check_init();
		    echo '<input type="text" id="peg_client_id" name="peg_oauth_settings[peg_client_id]" class="regular-text"  value="' . esc_attr($this->client_id) . '" />';
	    }
	    function client_secret_render_callback($args){
		    $this->check_init();
		    echo '<input type="text" id="peg_client_secret" name="peg_oauth_settings[peg_client_secret]" class="regular-text" value="' . esc_attr($this->client_secret) . '"/>';
		    echo '<p class="description">'.$args[0].'</p>';
	    }
	    function oauth_section_callback(){
			echo "<p>Please enter the details listed in the Google Developer Console here</p>";
	    }
	    function is_oauth_configuration_page(){
			return isset($_GET['oAuthStep']) || isset($_POST['oAuthStep']);
	    }
	    function render_oauth_configuration_page(){
		    $this->check_init();
		    $authStep = isset($_POST['oAuthStep']) ? $_POST['oAuthStep'] : $_GET['oAuthStep'];
		    switch ($authStep){
			    case 1:
				    $this->oauth_client_details();
				    break;
			    case 2:
				    $this->oauth_client_consent();
				    break;
		    }
	    }
	    function check_for_revoke(){
		    if (isset($_GET['revoke'])) {
			    //Check if it is an authorized call
			    check_admin_referer('peg_revoke_authorization');
			    $this->revoke_authorization();
			    //Redirect
			    $this->redirect_to_settings();
		    }
	    }
	    function redirect_to_settings(){
		    //First preserve all errors that have been generated
		    set_transient('settings_errors', get_settings_errors(), 30);
		    //Call settings url
		    wp_redirect($this->get_redirect_url().'&settings-updated=true');
	    }
	    function check_for_authorization_code(){
		    //First check if we are receiving an authorization code
		    $currScreen = get_current_screen();
		    if(isset($_GET['page']) && $_GET['page'] == 'photo-express' && isset($_GET['code'])){
			    //Try to get an authorization token
			    $this->request_token($_GET['code']);
			    //redirect
			    $this->redirect_to_settings();
		    }else if(isset($_GET['error'])){
			    //Integrate the error in a human readable message

			    echo $_GET['error'];
		    }

	    }
	    function oauth_client_details(){
			$this->check_init();
		    $auth_js_origin = $this->url_origin();
		    $redirect_url = $this->get_redirect_url();
		    ?>
		    <h3><?php _e('Step 1: Google OAuth Client Details'); ?></h3>
		    <p>Since April 2015 it is necessary to authenticate using the OAuth 2.0 protocol in order to access private Google photo albums. Follow the following steps to do so: </p>
		    <ol>
			    <li>Go to the <a href="https://console.developers.google.com" target="_blank">Google Developer Console</a> and log in with your Google credentials.</li>
			    <li>Click on "Create Project" to create a new project.</li>
			    <li>Choose a meaningful project name for you. For example "Google Photo Wordpress Plugin". Click on "Create".</li>
			    <li>Go to "APIs &amp; auth"->"Consent screen" on the left side</li>
			    <li>Choose you E-Mail address and enter a product name, for example "Google Photo Wordpress Plugin", and save the changes.</li>
			    <li>Go to "APIs &amp; auth"->"Credentials"</li>
			    <li>Click on "Create new Client ID".</li>
			    <li>In the dialog that comes up, choose "Web application"</li>
			    <li>For "Authorized JavaScript origins" enter: <?php echo $auth_js_origin; ?></li>
			    <li>For "Authorized redirect URIs" enter: <?php echo $redirect_url; ?></li>
			    <li>Enter the generated Client-ID and Client-Secret in the form below and continue with step 2.</li>
		    </ol>
		    <h2>Google OAuth Client Details</h2>

		    <form method="post" action="options.php">
			    <?php
			    settings_fields('peg_oauth_settings');
			    do_settings_sections('photo-express');
			    $continuePossible = !empty($this->client_id) && !empty($this->client_secret);
				    ?>

			    <p class="submit">
				    <a href="<?php echo $redirect_url; ?>">Back</a>
				    <input type="submit" <?php echo $continuePossible ? '' : 'class="button-primary"';?> value="<?php _e('Save Details') ?>"/>
				    <?php
				    if($continuePossible){
					    ?>
					    <a href="<?php echo $redirect_url; ?>&oAuthStep=2" class="button-primary">Continue</a>
				    <?php
				    }
				    ?>

			    </p>
		    </form>
	    <?php
	    }

	    /**
	     * Renders the page that redirects to the client consent screen of google.
	     */
	    function oauth_client_consent(){
		    $this->check_init();
		    $googleUrl = 'https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=offline&client_id='.$this->client_id;
		    $googleUrl = $googleUrl.'&redirect_uri='.urlencode($this->get_redirect_url())."&scope=".urlencode('https://picasaweb.google.com/data/ http://picasaweb.google.com/data/');
		    ?>
		    <h3><?php _e('Step 2: Google OAuth Consent'); ?></h3>
		    <p>In the second step you'll need to authorize access to your Picasa pictures for this plugin. This plugin needs two rights in order to work properly. The first one is the general access to private pictures and videos. The second one is the offline access to your pictures. This right makes it possible that the authorization does not have to be manually refreshed but can be refreshed by the plugin automatically.</p>
		    <p>To continue click on this <a href="<?php echo $googleUrl;?>">link</a> that will redirect you to Google for the authorization. After you have authorized access you'll be redirected to this site.</p>
	        <?php
	    }

	    /**
	     * Renders the general information about the current configuration state on the main page.
	     */
	    function render_settings_overview(){
		    ?>
			<h3><?php _e('Google OAuth Configuration','peg'); ?></h3>
			<table class="peg-form-table">
				<?php
				// get our token variable
				$this->check_init();
				if(empty($this->access_token)){
					Common::make_settings_row(
						__('Configure OAuth Access', 'peg')
						,'<a href="'.admin_url('options-general.php').'?page=photo-express&oAuthStep=1" >Click here to configure private access to Google Photo</a>'
						,__('If you want to access private Google Photo albums you will need to configure OAuth Access to Google Photo')
					);
				}else {
					Common::make_settings_row(
						__( 'Google OAuth 2 Access token', 'peg' )
						, $this->access_token
						, __( 'The OAuth 2 Access token used for authorization with Google. It is valid until its expiry date.' )
					);
					Common::make_settings_row(
						__( 'Access token expires in', 'peg' )
						, date('Y-m-d H:i:s (e)',$this->expires_in)
						, __( '' )
					);
					Common::make_settings_row(
						__( 'Google OAuth 2 Refresh Token', 'peg' )
						, $this->refresh_token
						, __( 'The refresh token is used to obtain a new access token if an old one has been expired.' )
					);
					Common::make_settings_row(
						__('Revoke Google OAuth 2 Access token'),
						'<a href="'.wp_nonce_url( admin_url('options-general.php').'?page=photo-express&revoke', 'peg_revoke_authorization').'">Click here to revoke the token</a>'

					);
				}
				?>
			</table>
			<?php

	    }

	    /**
	     * Validates the client id and client secret for the settings page. Both have to be set.
	     * @param $options
	     * @return mixed
	     */
	    function validate_client_credentials($options){
		    if(empty($options['peg_client_id']) || empty($options['peg_client_secret'])){
			    add_settings_error('peg_oauth_settings','settings_updated','You have to enter a client ID and a client secret.','error');
		    }
		    return $options;
	    }
	    function url_origin($use_forwarded_host=false)
	    {
		    $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
		    $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		    $port = $_SERVER['SERVER_PORT'];
		    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		    $host = $_SERVER['SERVER_NAME'] . $port;
		    return $protocol . '://' . $host;
	    }
	    function get_redirect_url(){
		    return admin_url('options-general.php').'?page=photo-express';
	    }
	    private function check_init(){
		    if(!$this->initialized){
			    $options = get_option('peg_oauth_settings');
			    if($options == false){
				    $options = array();
			    }
			    $this->access_token = $options['peg_access_token'];
			    $this->client_id = $options['peg_client_id'];
			    $this->client_secret = $options['peg_client_secret'];
			    $this->refresh_token = $options['peg_refresh_token'];
			    $this->expires_in = $options['peg_expires_in'];
			    $this->initialized = true;
		    }
	    }
	    function uninstall(){
		    $this->revoke_authorization();
		    delete_option('peg_oauth_settings');
	    }
	    function revoke_authorization() {
		    $this->check_init();
		    if ( !empty( $this->access_token ) ) {
			    //Revoke token
			    $tokenToRevoke = $this->access_token;
			    if($this->is_access_token_expired()){
				    $tokenToRevoke = $this->refresh_token;
			    }
			    $response = wp_remote_get('https://accounts.google.com/o/oauth2/revoke?token='.$tokenToRevoke);
			    if(is_wp_error($response)){
				    add_settings_error('peg_oauth_settings', 'revoke_failed', 'The OAuth2 token could not be revoked. An error occurred. Please go to "https://accounts.google.com/b/0/IssuedAuthSubTokens" to revoke it manually. Error Message: '.$response->get_error_message());
			    }else{
				    add_settings_error('peg_oauth_settings','revoke_successful', 'The OAuth2 token has been revoked.', 'updated');

			    }
			    $this->access_token = '';
			    $this->refresh_token = '';
			    $this->expires_in = '';
			    $this->store_authentication_data();
		    }


	    }
	    private function is_access_token_expired(){
		    return !empty($this->access_token) && time()>$this->expires_in;
	    }
	    private function buildOptionsArray(){
		    $options = array(
			    'peg_access_token' => $this->access_token,
				'peg_refresh_token' => $this->refresh_token,
			    'peg_expires_in' => $this->expires_in,
			    'peg_client_id' => $this->client_id,
			    'peg_client_secret' => $this->client_secret
	        );
		    return $options;
	    }
	    private function store_authentication_data(){
		    if(get_option('peg_oauth_settings') == false){
				add_option('peg_oauth_settings');
		    }
		    update_option('peg_oauth_settings',$this->buildOptionsArray());
	    }

		function request_token($authorization_code){
			$this->check_init();
			$response = wp_remote_post('https://www.googleapis.com/oauth2/v3/token', array(
				'method' => 'POST',
				'httpversion' => '1.1',
				'content-type' => 'application/x-www-form-urlencoded',
				'blocking' => true,
				'body' => array(
					'code' => $authorization_code,
					'client_id' =>$this->client_id,
					'client_secret' => $this->client_secret,
					'redirect_uri' => $this->get_redirect_url(),
					'grant_type' => 'authorization_code'
				)
			));

			$json_token = json_decode($response['body']);

			if(isset($json_token->error)){
				add_settings_error('peg_oauth_settings','acquiring_access_token_failure','Could not aquire an OAuth 2 access token. Error message: '.$json_token->error.'. Error description: '.$json_token->error_description);
			}else {
				$this->store_access_token($json_token);
				add_settings_error('peg_oauth_settings','acquiring_access_token_success','Successfully aquired an OAuth 2 access token. You can now access private photo albums.','updated');
			}
		}
	    function store_access_token($json_token){
		    //Store data
		    if(isset($json_token->refresh_token)){
				$this->refresh_token = $json_token->refresh_token;
		    }
		    $this->access_token = $json_token->access_token;
		    $this->expires_in = time() + $json_token->expires_in - 60; //Let it expire a little bit earlier - just in case one minute earlier
		    //Save it to the db
		    $this->store_authentication_data();
	    }
		function refresh_access_token(){
			$response = wp_remote_post('https://www.googleapis.com/oauth2/v3/token', array(
				'method' => 'POST',
				'httpversion' => '1.1',
				'content-type' => 'application/x-www-form-urlencoded',
				'blocking' => true,
				'body' => array(
					'client_id' =>$this->client_id,
					'client_secret' => $this->client_secret,
					'refresh_token' => $this->refresh_token,
					'grant_type' => 'refresh_token'
				)
			));
			//Check if there is an error
			if(is_wp_error($response)){
				error_log('Could not refresh the OAuth 2 access token. Message: '.$response->get_error_message());
			}else {
				$json_token = json_decode( $response['body'] );
				if ( isset( $json_token->error ) ) {
					error_log( 'Could not refresh the OAuth 2 access token. Error message: ' . $json_token->error . '. Error description: ' . $json_token->error_description );
				} else {
					$this->store_access_token( $json_token );
				}
			}
		}
	    public function get_feed($url){
		    global $wp_version;
		    // add Auth later
		    $options = array(
			    'timeout' => 30,
			    'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		    );

		    $this->check_init();
		    if($this->is_access_token_expired()){
			    //Do a token refresh
			    $this->refresh_access_token();
		    }

		    if ( $this->access_token ) {
			    $options['headers'] = array( 'Authorization' => "Bearer $this->access_token" );
		    }

		    $response = wp_remote_get($url, $options);

		    if (is_wp_error($response))
			    return $response;

		    if (200 != $response['response']['code'])
			    return new \WP_Error('http_request_failed', __('Response code is ') . $response['response']['code']);

		    // preg sensitive for \n\n, but we not need any formating inside
		    return (str_replace("\n", '', trim($response['body'])));
	    }
    }// end class Google_Photo_Access

}// end if the class doesn't already exist
?>