<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

//===================Deal with Collection Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      $p  = $_GET['productItem'];
      $pd = $_GET['productDiscount'];
      $num = sizeof( $p );

//--------put this new added bundle into file: {shopUrl}ShopBundle.txt----------
      $BundleID = uniqid() . ","; // unique ID
      $BundleInfo = $num;
      for($i=0; $i<$num; $i++){
        $BundleInfo .= "," . $p[$i] . "," . $pd[$i];
      }
      $BundleInfo .= "\n";

      $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
      if( !file_exists( $fileName ) ){ // create {shopUrl}ShopBundle.txt
        $bundle = $BundleID . $BundleInfo;
        file_put_contents($fileName, $bundle, LOCK_EX);
      }else{                           // read from productInfo.txt
        $infoAll = file_get_contents($fileName);
        //--------Pending: should check if this bundle is the same with existing ones---
        //--------     if($infoAll.contains($BundleInfo)){do nothing}      ---
        //--------Pending: If it's different with existing ones, then process to below---
        $existBundle = file_get_contents($fileName);
        $existBundle .= $BundleID . $BundleInfo;
        file_put_contents($fileName, $existBundle, LOCK_EX);
      }
//--------print out this new added bundle --------------------------------------
      for ($i=0; $i < $num; $i++) {
         echo '<h1> productItem: ' .$p[$i]. "</h1>\n";
         echo '<h1> productDiscount: ' .$pd[$i]. "</h1>\n";
      }

    }

//===================Deal with Collection Bundle Sales=====================
    if( isset( $_GET['collectionItem'] )){
      foreach( $_GET['collectionItem'] as $p){
          echo '<h1> collectionItem: ' .$p. "</h1>\n";
      }

    }
?>
