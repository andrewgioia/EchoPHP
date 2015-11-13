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

        if ( ! isset( $_SESSION[ 'echophp_session' ] ) || $_SESSION[ 'echophp_session' ] == '' )
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
     * MANAGE INVENTORY */

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
        $mid,
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
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'add_card' => $response ] );

        return $response;
    }

    /**
     * Remove card from inventory
     *
     * @param int $eid (echomtg inventory ID for card)
     * @return boolean
     */
    public function removeCard( $eid )
    {
        // check that we have an integer first
        $this->checkInventoryID( $eid );

        // attempt to remove the card
        $response = $this->sendPost(
            'inventory/remove/',
            [ 'inventory_id' => $eid ],
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'remove_card' => $response ] );

        return $response;
    }

    /**
     * Adjust acquisition price of a card in inventory
     *
     * @param int @eid (echo inventory ID for the card)
     * @param float $price (adjusted acquisition price)
     * @return array
     */
    public function adjustAcquiredPrice( $eid, $price )
    {
        // check that we have an integer first
        $this->checkInventoryID( $eid );

        // set the parameters
        $request = [
            'id' => $eid,
            'adjusted_price' => $price ];

        // attempt to remove the card
        $response = $this->sendPost(
            'inventory/adjust/',
            $request,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'adjust_price' => $response ] );

        return $response;
    }

    /**
     * Toggle the foil status of a card in inventory
     *
     * @param int @eid (echo inventory ID for the card)
     * @param boolean $foil (0 for nonfoil, 1 for foil)
     * @return array
     */
    public function toggleFoil( $eid, $foil = 1 )
    {
        // check that we have an integer first
        $this->checkInventoryID( $eid );

        // make sure it's a 0 or 1
        $foil = ( $foil == 0 ) ? $foil : 1;

        // set the parameters
        $request = [
            'id' => $eid,
            'foil' => $foil ];

        // attempt to remove the card
        $response = $this->sendPost(
            'inventory/toggle_foil/',
            $request,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'toggle_foil' => $response ] );

        return $response;
    }

    /**
     * Adjust acquisition price of a card in inventory
     *
     * @param int @eid (echo inventory ID for the card)
     * @param string $date (date of card acquisition, in MM-DD-YYYY)
     * @return array
     */
    public function adjustAcquiredDate( $eid, $date )
    {
        // check that we have an integer first
        $this->checkInventoryID( $eid );

        // format the date correctly
        $date = date( 'm-d-Y', strtotime( $date ) );

        // set the parameters
        $request = [
            'id' => $eid,
            'value' => $date ];

        // attempt to remove the card
        $response = $this->sendPost(
            'inventory/adjust_date/',
            $request,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'adjust_date' => $response ] );

        return $response;
    }


    /**
     * INVENTORY */

    /**
     * Get user's inventory
     *
     * @param int $start
     * @param int $end
     * @param string $sort (price, cmc, foil_price, date_aquired, set)
     * @param string $order (asc, desc)
     * @param string $search (card name to search)
     * @param string $color (Colorless, Multicolor, White, Blue, Black, Red, Green, Land)
     * @param string $type (Planeswalker, Sorcery, Instant, Creature, Artifact,
     *                      Enchantment, Legendary, Land)
     * @param string $set_code (if showing only from a set)
     * @return array
     */
    public function getInventory(
        $start = 0,
        $end = 9,
        $sort = 'date_acquired',
        $order = 'desc',
        $search = false,
        $color = false,
        $type = false,
        $set_code = false )
    {
        // check that start and end are integers
        if ( ! is_int( $start ) || ! is_int( $end ) )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'Invalid card multiverse ID' ] );
        }

        // set the base data fields
        $query[ 'start' ] = $start;
        $query[ 'limit' ] = $end;
        $query[ 'sort' ] = $sort;
        $query[ 'order' ] = $order;

        // searching by card name
        if ( $search ) $query[ 'search' ] = $search;

        // filtering by a color
        $color_options = [
            'Colorless', 'Multicolor', 'White', 'Blue', 'Black', 'Red',
            'Green', 'Land' ];
        if ( $color )
        {
            if ( in_array( $color, $color_options ) )
            {
                $query[ 'color' ] = $color;
            }
        }

        // filtering by type
        $type_options = [
            'Planeswalker', 'Sorcery', 'Instant', 'Creature', 'Artifact',
            'Enchantment', 'Legendary', 'Land' ];
        if ( $type )
        {
            if ( in_array( $type, $type_options ) )
            {
                $query[ 'type' ] = $type;
            }
        }

        // filtering by set code
        if ( $set_code ) $query[ 'set_code' ] = $set_code;

        // attempt to add the card
        $response = $this->sendPost(
            'inventory/view/',
            $query,
            true,
            'get' );

        // set some debug logging
        $this->debugInfo( [ 'view_inventory' => $response ] );

        return $response;
    }

    /**
     * Return the user's inventory statistics
     *
     * @return array
     */
    public function getStats()
    {
        // make the request
        $response = $this->sendPost(
            'inventory/stats/',
            [],
            true,
            'get' );

        // set some debug logging
        $this->debugInfo( [ 'inventory_stats' => $response ] );

        return $response;
    }


    /**
     * CARD REFERENCE */

    /**
     * Search EchoMTG's card list to return the appropriate ID
     *
     * @param string $cardname (card name to search)
     * @param string $setcode (optional set code, otherwise first match returns)
     * @return int $mid
     */
    public function cardReference( $cardname = '', $setcode = false )
    {
        // make sure a card name was passed in
        if ( trim( $cardname ) == '' || strlen( trim( $cardname ) ) == 0 )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'You need to pass a card name to search' ] );
        }

        // set the request fields
        $request[ 'type' ] = 'json';
        $request[ 'name' ] = $cardname; // does not do anything

        // pull in the master list
        $cards = $this->sendPost(
            'data/card_reference/',
            $request,
            true,
            'get' );

        // currently we have to iterate over this object to search
        // the api call needs to take a parameter to search in the future
        $results = [];
        foreach ( $cards->cards as $mid => $card )
        {
            if ( $card->name == $cardname )
            {
                $results[ $mid ] = $card;
            }
        }

        // if we have a set code passed in, return just that row;
        // otherwise return the earliest printing (lowest ID)
        if ( count( $results ) > 0 )
        {
            if ( $setcode )
            {
                foreach ( $results as $id => $result )
                {
                    if ( strtolower( $result->set_code ) == strtolower( $setcode ) )
                    {
                        return $id;
                    }
                }
                $this->postError( [
                    'status' => 'error',
                    'message' => 'No cards matched your search.' ] );
            }
            else
            {
                ksort( $results );
                return array_keys( $results )[ 0 ];
            }
        }
        else
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'No cards matched your search.' ] );
        }
    }


    /**
     * LISTS */

    /**
     * Get a specific list
     *
     * @param int $lid (list ID)
     * @param $html (flag to return preformatted HTML view)
     * @return array (list of lists)
     */
    public function getList( $lid = false, $html = false )
    {
        // validate the list id
        $this->checkListID( $lid );

        // set the fields
        $fields = [
            'list' => $lid,
            'view' => ( $html ) ? 'true' : 'false' ];

        // make the request
        $response = $this->sendPost(
            'lists/get/',
            $fields,
            true,
            'get' );

        // set some debug logging
        $this->debugInfo( [ 'get_list' => $response ] );

        return $response;
    }

    /**
     * Get all of the user's lists
     *
     * @param string $order (optional sort order)
     * @return array
     */
    public function getAllLists( $order = 'last_edited' )
    {
        // set the optional sort order
        $order_options = [ 'created', 'alpha_desc', 'alpha_asc', 'last_edited' ];
        $fields[ 'order' ] = ( ! in_array( $order, $order_options ) )
            ? 'last_edited'
            : $order;

        // send the request
        $response = $this->sendPost(
            'lists/all/',
            $fields,
            true,
            'get' );

        // set some debug logging
        $this->debugInfo( [ 'all_lists' => $response ] );

        // cast as array and return just the lists
        return (array)$response->lists;
    }

    /**
     * Create a new list
     *
     * @param string $name (name of the list)
     * @param string $description (description of the list)
     * @return int $id (of newly created list)
     */
    public function createList( $name = '', $description = '' )
    {
        // make sure we have a name
        if ( trim( $name ) == '' || strlen( trim( $name ) ) == 0 )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'You need to supply a name for the list' ] );
        }

        // set the request fields
        $fields[ 'name' ] = $name;
        $fields[ 'description' ] = $description;

        // send the request
        $response = $this->sendPost(
            'lists/create/',
            $fields,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'create_list' => $response ] );

        // if we have a successful new list, get the ID
        if ( isset( $response->status ) && $response->status == 'success' )
        {
            $all_lists = $this->getAllLists();
            rsort( $all_lists );
            if ( is_array( $all_lists ) && count( $all_lists ) > 0 )
            {
                $newest_list = array_values( $all_lists )[ 0 ];
                return ( isset( $newest_list->id ) )
                    ? $newest_list->id
                    : false;
            }
            else
            {
                $this->postError( [
                    'status' => 'error',
                    'message' => 'Error retreiving new list id; list was created.' ] );
            }
        }
        else
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'Error creating the new list' ] );
        }
    }

    /**
     * Edit the name or description of a list
     *
     * @param int $lid (ID of the list to edit)
     * @param string $name (name of the list)
     * @param string $description (description of the list)
     * @return boolean
     */
    public function editList( $lid, $name = false, $description = false )
    {
        // validate the list id
        $this->checkListID( $lid );

        // if a name is set, make sure it isn't blank
        if ( $name )
        {
            if ( trim( $name ) == '' || strlen( trim( $name ) ) == 0 )
            {
                $this->postError( [
                    'status' => 'error',
                    'message' => 'You need to supply a name for the list' ] );
            }
            else
            {
                $fields[ 'name' ] = $name;
            }
        }

        // no need to validate the description, just pass it if it exists
        if ( $description )
        {
            $fields[ 'description' ] = $description;
        }

        // send the request
        $response = $this->sendPost(
            'lists/edit/',
            $fields,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'edit_list' => $response ] );

        // return true on success, otherwise false
        return $this->returnStatus( $response );
    }

    /**
     * Toggle a list's activated status (active or deactivated)
     *
     * @param int $lid (the ID of the list to toggle)
     * @param boolean $status (0 for deactivated, 1 for active)
     * @return boolean (true for success, false for failure)
     */
    public function toggleListStatus( $lid, $status = 1 )
    {
        // validate the list id
        $this->checkListID( $lid );

        // create the field array
        $fields[ 'status' ] = ( in_array( $status, [ 0, 1 ] ) )
            ? $status
            : 1;

        // send the request
        $response = $this->sendPost(
            'lists/toggle_status/',
            $fields,
            true,
            'post' );

        // set some debug logging
        $this->debugInfo( [ 'toggle_list_status' => $response ] );

        // return true on success, otherwise false
        return $this->returnStatus( $response );
    }


    /**
     * UTILITIES */

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
            'post' );

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
        $auth = true,
        $method = 'post' )
    {
        // build the query from the data fields
        if ( $auth ) { $fields[ 'auth' ] = $this->auth_token; }
        $data = http_build_query( $fields );

        // check for the method
        $method = ( ! in_array( $method, [ 'post', 'put', 'get' ] ) )
            ? 'post'
            : $method;

        // get the url to post to
        $uri = ( $endpoint )
            ? $this->api_host.$endpoint
            : $this->api_host;

        // set some debug logging
        $this->debugInfo( [ $method => [ $uri, $data ] ] );

        // create the full request based on post/get/put status
        $request = ( $method == 'get' )
            ? $uri.$data
            : $uri;

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt( $ch, CURLOPT_URL, $request );

        // using SSL
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

        // post headers
        if ( $method == 'post' ) {
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        }
        // put headers
        else if ( $method == 'put' )
        {
            curl_setopt( $ch, CURLOPT_PUT, 1 );
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

    private function checkInventoryID( $eid )
    {
        if ( ! is_int( $eid ) || $eid < 1 )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'The card ID is not an integer.' ] );
            return false;
        }
    }

    private function checkListID( $lid )
    {
        if ( ! is_int( $lid ) || $lid < 1 )
        {
            $this->postError( [
                'status' => 'error',
                'message' => 'The list ID is not an integer.' ] );
            return false;
        }
    }

    private function returnStatus( $response )
    {
        if ( is_object( $response ) && isset( $response->status ) )
        {
            return ( $response->status == 'success' )
                ? true
                : false;
        }
        else
        {
            return false;
        }
    }
}
