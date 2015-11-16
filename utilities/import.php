<?php

require '../api.php';
$echo = new EchoPHP();

/**
 * Authenticate for this session.
 * For debugging, force an authentication each time.
 */
if ( $echo->config->debug_mode )
{
    session_start();
    unset( $_SESSION[ 'echophp_session' ] );
    session_destroy();
}
$echo->initSession();

/**
 * Handle the .csv file upload and add cards individually
 */
if ( ! empty( $_FILES ) )
{
    // validate the file upload
    try
    {
        // if $_FILES is undefined/missing/corrupt, treat it as invalid
        if ( ! isset( $_FILES[ 'file' ] ) )
        {
            throw new \RuntimeException( 'Something is wrong with the file.' );
        }
        else if ( ! isset( $_FILES[ 'file' ][ 'error' ] )
                 || is_array( $_FILES[ 'file' ][ 'error' ] ) )
        {
            throw new \RuntimeException( 'Something is wrong with the file.' );
        }
        else
        {
            $file = $_FILES[ 'file' ];
        }

        // check for any error with the upload
        switch ( $file[ 'error' ] ) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException( 'Please select a file to upload!' );
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException(
                    'Oops, that file was too big to handle.' );
            default:
                throw new \RuntimeException(
                    'Sorry, it looks like there was a problem with the upload.' );
        }

        // check for filesize I guess
        if ( $file[ 'size' ] > 20000000 ) {
            throw new \RuntimeException(
                'Oops, that file is too big. Please upload one less than 20MB.');
        }

        // check/set the mime type ourselves
        $finfo = new \finfo( FILEINFO_MIME_TYPE );
        $ext = in_array(
            $finfo->file( $file[ 'tmp_name' ] ),
            [ 'text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv' ] );
        if ( $ext === false ) {
            throw new \RuntimeException(
                'It looks like you didn\'t upload a CSV file.');
        }

        // rename and move the file!
        $new_file_name = sha1_file( $file[ 'tmp_name' ] ).".csv";
        $new_file_path = $echo->config[ 'path' ].$new_file_name;

        if ( ! move_uploaded_file( $file[ 'tmp_name' ], $new_file_path ) ) {
            throw new \RuntimeException( 'There was a problem copying the file.' );
        }

    } catch ( \RuntimeException $e ) {

        return $e;

    }

    // check if we're just outputting the MIDs
    $ids_only = false;
    if ( isset( $_POST[ 'ids_only' ] ) && $_POST[ 'ids_only' ] == '1' )
    {
        $ids_only = true;
    }

    // get the file contents
    $contents = file_get_contents( $new_file_path );

    // make sure it's UTF-8 encoded
    if ( ! mb_check_encoding( $contents, 'UTF-8' )
        || ! ( $contents === mb_convert_encoding( mb_convert_encoding(
            $contents, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32' ) ) ) {
        $contents = mb_convert_encoding( $contents, 'UTF-8' );
    }

    // break out each line of the file
    $rows = str_getcsv( $contents, PHP_EOL );
    $cards = [];

    // iterate over the rows and submit the cards
    foreach ( $rows as $row )
    {
        // parse out the card values
        $card = str_getcsv( $row, ',' );
        $name = $card[ 0 ];
        $set = $card[ 1 ];
        $quantity = $card [ 2 ];
        $price = $card[ 3 ];
        $date = $card[ 4 ];

        // get the multiverse ID
        $mid = $echo->cardReference( $name, $set );

        // if we're good, add the card or build the print to screen
        if ( $mid )
        {
            if ( ! $ids_only )
            {
                $response = $echo->addCard( $mid, $quantity, $price, $date );
                if ( ! $response )
                {
                    $errors[ 'adds'] = [ $mid, $name, $set, $quantity, $price, $date ];
                }
                else
                {
                    $success[] = [ $mid, $name, $set, $quantity, $price, $date ];
                }
            }
            else
            {
                $csv[] = [ $mid, $name, $set, $quantity, $price, $date ];
            }
        }
        else
        {
            $errors[ 'mids' ] = $card;
        }
    }

    // show the csv list if we have that set
    if ( $ids_only )
    {
        echo "<h5>Copy this as a new CSV:</h5><blockquote><pre><strong>id,name,set,quantity,price,date</strong>\n";
        foreach ( $csv as $i => $card )
        {
            echo implode( ',', $card ).'<br />';
        }
        echo "</pre></blockquote>";
    }

}

?>

<!-- Import tool -->
<html>
<head>
    <title>Import cards to EchoMTG</title>
    <style type="text/css">
        body { font-family: Helvetica, arial, sans-serif; padding: 20px 25px;
            border-top: 3px solid #006593; margin: 0; }
        label { display: block; font-weight: bold; margin: 0 0 10px; }
        fieldset { border: 1px solid #aaa; border-radius: 5px; padding: 0 20px; }
        legend { color: #007db6; font-size: 19px; font-weight: bold; padding: 0 10px; }
        p { margin: 20px 0; }
        a { color: #007db6; }
        input[type="submit"] { -webkit-appearance: none; font-size: 15px;
            border-radius: 5px; border: none; background: #007db6; color: #fff;
            padding: 6px 12px 7px; border-bottom: 2px solid #006593; cursor: pointer; }
        blockquote { background: #eee; margin: 0 0 50px; padding: 10px 25px;
            border-left: 3px solid #ccc; border-radius: 3px; line-height: 1.3em; }
    </style>
</head>
<body>
    <h2>Import cards to your EchoMTG inventory</h2>
    <p>
        Select your .csv file below then click the "Import" button. Make sure your .csv is formatted the same way as the <a href="import_template.csv">template file</a>.
    </p>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Upload cards by CSV</legend>
            <p>
                <label for="file">Select a CSV file to upload:</label>
                <input type="file" name="file" />
            </p>
            <p>
                <input type="checkbox" name="ids_only" value="1" /> Create CSV of Multiverse IDs only (i.e., don't add the cards!)
            </p>
            <p>
                <input type="submit" value="Import" />
            </p>
        </fieldset>
    </form>
</body>
</html>
