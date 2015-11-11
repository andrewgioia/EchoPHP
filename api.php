<?php
/**
 * EchoMTG Basic API Wrapper for PHP
 *
 * API documentation: https://www.echomtg.com/api/
 * Source: http://github.com/andrewgioia/echophp
 *
 * Note: Library is in beta and provided as-is
 * Date: 2015-11-09
 * @version 0.1
 */

class EchoPHP {

    /**
     * API constants */
    private $api_host = 'https://www.echomtg.com/api/';

    /**
     * Class variables */
    protected $auth_token;
    protected $auth_message;
    protected $auth_status;

    public $config;
    public $error = false;
    public $debug = array();

    /**
     * @param string $email (user's email address)
     * @param string $password (user's password)
     */
    public function __construct()
    {
        $this->config = parse_ini_file( 'config.local.ini' );
        $this->email = $this->config[ 'email' ];
        $this->password = $this->config[ 'password' ];
    }

    /**
     * Get the authentication token from the session,
     * or create the session and authenticate
     *
     * @return boolean
     */
    public function initSession()
    {
        $session = session_id();
        if ( empty( $session ) )
        {
            session_start();
        }

        if ( ! $_SESSION[ 'echophp_session' ] || $_SESSION[ 'echophp_session' ] == '' )
        {
            $token = $this->authenticate();
            if ( $token )
            {
                $this->auth_token = $token;
                $_SESSION[ 'echophp_session' ] = $token;
                $this->debugInfo( [ 'session' => [ 'login', $_SESSION[ 'echophp_session' ] ] ] );
                return true;
            }
            else
            {
                $this->postError( [
                    'status' => 'error',
                    'message' => 'Unable to authentication, incorrect email or password' ] );
                $this->debugInfo( [ 'session' => [ 'error', $_SESSION[ 'echophp_session' ] ] ] );
                return false;
            }
        }
        else
        {
            $this->auth_token = $_SESSION[ 'echophp_session' ];
            $this->debugInfo( [ 'session' => [ 'saved', $_SESSION[ 'echophp_session' ] ] ] );
            return true;
        }

        return false;
    }

    /**
     * Destroy the authentication token and "log out"
     *
     * @return void
     */
    public function logout()
    {
        session_start();
        unset( $_SESSION[ 'echophp_session' ] );
        session_destroy();
        $this->debugInfo( [ 'session' => $_SESSION[ 'echophp_session' ] ] );
    }

    /**
     * Add X of one card to Inventory
     * @param int $m_id (card's multiverse ID)
     * @param int $quantity (amount to add)
     * @param float $acquired_price (set the purchase price)
     * @param string $acquired_date (set the date of purchase, MM-DD-YYYY
     * @param boolean $foil (flag for whether the card is foil)
     * @return array (response)
     */
    public function addCard(
        $mid = '',
        $quantity = 1,
        $acquired_price = false,
        $acquired_date = false,
        $foil = 0 )
    {
        // check that the multiverse ID is correct
        if ( is_numeric( $mid ) && strlen( $mid ) > 0 )
        {
            $card[ 'mid' ] = $mid;
        }
        else
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'Invalid card multiverse ID' ] );
        }

        // add the remaining fields
        $card[ 'quantity' ] = $quantity;
        $card[ 'foil' ] = $foil;
        if ( $acquired_price ) $card[ 'acquired_price' ] = $acquired_price;
        if ( $acquired_date ) $card[ 'acquired_date' ] = $acquired_date;

        // attempt to add the card
        $response = $this->sendPost(
            'inventory/add/',
            $card,
            true );

        // set some debug logging
        $this->debugInfo( [ 'add_card' => $response ] );

        return $response;
    }

    /**
     * Get user's inventory
     *
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getInventory( $start = 0, $end = 9 )
    {
        // check that start and end are integers
        if ( ! is_int( $start ) || ! is_int( $end ) )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'Invalid card multiverse ID' ] );
        }

        // set the data fields
        $query[ 'start' ] = $start;
        $query[ 'limit' ] = $end;

        // attempt to add the card
        $response = $this->sendPost(
            'inventory/view/',
            $query,
            true,
            false );

        // set some debug logging
        $this->debugInfo( [ 'view_inventory' => $response ] );

        return $response;
    }

    /**
     * Send an authentication request
     *
     * @return string (response token)
     */
    private function authenticate()
    {
        // attempt to login
        $response = $this->sendPost(
            'user/auth/',
            [ 'email' => $this->email, 'password' => $this->password ],
            false,
            true );

        // set some debug logging
        $this->debugInfo( [ 'auth' => $response ] );

        // return the token if we have it
        if ( isset( $response->status ) && $response->status == 'success' )
        {
            return $response->token;
        }
        else
        {
            return false;
        }
    }

    /**
     * Post to an EchoMTG API endpoint
     *
     * @param $endpoint (path to api call)
     * @param $fields (array of data fields to send in the POST)
     * @param $auth (flag for whether this request is authenticated)
     * @param $post (flag for sending as a post, otherwise get)
     * @return array (json response)
     */
    private function sendPost(
        $endpoint = false,
        $fields = array(),
        $auth = false,
        $post = true )
    {
        // build the query from the data fields
        if ( $auth ) { $fields[ 'auth' ] = $this->auth_token; }
        $data = http_build_query( $fields );

        // get the url to post to
        $uri = ( $endpoint )
            ? $this->api_host.$endpoint
            : $this->api_host;

        // set some debug logging
        $type = ( $post ) ? 'post' : 'get';
        $this->debugInfo( [ $type => [ $uri, $data ] ] );

        // create the full request based on post/get status
        $request = ( $post )
            ? $uri
            : $uri.$data;

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt( $ch, CURLOPT_URL, $request );

        // using SSL
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

        // post headers
        if ( $post ) {
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        }

        // query and header options
        curl_setopt( $ch, CURLOPT_HEADER, false ) ;
        curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        // get it
        $response = trim( curl_exec( $ch ) );
        $response = json_decode( $response );

        // close the connection
        curl_close( $ch );

        return $response;
    }

    /**
     * Save to the error property
     *
     * @param array $object (error array with status and message)
     * @return void
     */
    private function postError( $object )
    {
        $this->error = json_encode( $object );
    }

    /**
     * Save to the debug property
     *
     * @param array $object (debug array with status and message)
     * @return void
     */
    private function debugInfo( $object )
    {
        if ( $this->config[ 'debug_mode' ] )
        {
            $this->debug[] = $object;
        }
    }

}
