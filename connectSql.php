<?php

//~~~~~~~~~~~Open Connection to Mysql ( Note: not close it yet!!)~~~~~~~~~~~~~~~
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    $servername = "shopifybundle.crzkedo145qb.us-west-1.rds.amazonaws.com";
    $username = "caomingkai";
    $password = "moon2181";
    $dbname = "shopifybundle";
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        echo "<h1>Cannot access to 'shopifybundle' database </h1> " . "\n";
    }


//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>
