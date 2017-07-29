<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    if( isset( $_GET['productItem'] )){
      echo '<h1> productItem: ' . $_GET['productItem'] . "</h1>\n";
      echo '<h1> test Shopify Obj: ' . $products . "</h1>\n";

    }

    if( isset( $_GET['collectionItem'] )){
      echo '<h1> collectionItem: ' . $_GET['collectionItem'] . "</h1>\n";
    }
    // $config = array(
    //       'ShopUrl' => $_SESSION["shopUrl"],
    //       'AccessToken' => $_SESSION["accessToken"],
    // );
    //
    // PHPShopify\ShopifySDK::config($config);
    // $shopify = new PHPShopify\ShopifySDK;
    // $myJSON = json_encode($shopify);
    //
    // $collections = $shopify->CustomCollection->get();
    //
    //
    // $products = $shopify->Product->get();




?>
