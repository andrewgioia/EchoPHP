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
    public $error = false;

    /**
     * @param string $email (user's email address)
     * @param string $password (user's password)
     */
    public function __construct( $email, $password )
    {
        $this->email = $email;
        $this->password = $password;
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
                return true;
            }
            else
            {
                $this->postError( [
                    'status' => 'error',
                    'message' => 'Unable to authentication, incorrect email or password' ] );
                return false;
            }
        }
        else
        {
            $this->auth_token = $_SESSION[ 'echophp_session' ];
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
    }


    /**
     * Add X of one card to Inventory
     * @param $m_id (card's multiverse ID)
     * @param $quantity (amount to add)
     * @param $acquired (set the purchase price)
     * @param $foil (flag for whether the card is foil)
     * @return object (response)
     */
    public function addCard( $mid = '', $quantity = 1, $acquired = false, $foil = 0 )
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
        if ( $acquired )
        {
            $card[ 'acquired_price' ] = $acquired;
        }

        $response = $this->sendPost(
            'inventory/add/',
            $card,
            true );

        return $response;
    }


    /**
     * Send an authentication request
     *
     * @return string (response token)
     */
    private function authenticate()
    {
        $response = $this->sendPost(
            'user/auth/',
            [ 'email' => $this->email, 'password' => $this->password ] );
        $response = json_decode( $response );

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
     * @return obj (json response)
     */
    private function sendPost( $endpoint = false, $fields = array(), $auth = false )
    {
        // build the query from the data fields
        if ( $auth ) { $fields[ 'auth' ] = $this->auth_token; }
        //$fields[ 'type' ] = 'curl';
        $data = http_build_query( $fields );

        // get the url to post to
        $post = ( $endpoint )
            ? $this->api_host.$endpoint
            : $this->api_host;

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt( $ch, CURLOPT_URL, $post.$data );    // using GET for now

        // using SSL
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

        // query and header options
        //curl_setopt( $ch, CURLOPT_POST, 1 );           // using GET for now
        //curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ); //
        curl_setopt( $ch, CURLOPT_HEADER, false ) ;
        curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        // get it
        $response = trim( curl_exec( $ch ) );

        // close the connection
        curl_close( $ch );

        return $response;
    }


    /**
     * Print a supplied error to the screen
     *
     * @param @object (error array with status and message)
     * @return boolean
     */
    private function postError( $object )
    {
        $this->error = json_encode( $object );
        return true;
    }

}
