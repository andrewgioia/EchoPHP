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
if ( isset( $_POST ) )
{
    // do something
}

?>

<!-- Import tool -->
<html>
<head>
    <title>Import cards to EchoMTG</title>
</head>
<body>
    <h2>Import cards to your EchoMTG inventory</h2>
    <p>
        Select your .csv file below then click the "Import" button. Make sure your .csv is formatted the same way as the <a href="import_template.csv">template file</a>.
    </p>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <input type="file" name="csv" />
        <input type="submit" value="Import" />
    </form>
</body>
</html>
