<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

//===================Deal with Collection Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      foreach( $_GET['productItem'] as $p){
          echo '<h1> productItem: ' .$p. "</h1>\n";
      }

    }

//===================Deal with Collection Bundle Sales=====================
    if( isset( $_GET['collectionItem'] )){
      foreach( $_GET['collectionItem'] as $p){
          echo '<h1> collectionItem: ' .$p. "</h1>\n";
      }

    }
?>
