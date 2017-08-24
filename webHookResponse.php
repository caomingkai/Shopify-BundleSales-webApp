<?php

    if( isset( $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] )){

      require_once __DIR__ . '/webhookFun.php';

      define('SHOPIFY_APP_SECRET', 'd999981624124eb6b1a902a063a9e8ea');

      $server_variable = '<pre>' . print_r($_SERVER, true) . '</pre>';
      $fileName = 'server.txt';
      file_put_contents($fileName, $server_variable, LOCK_EX);

      function verify_webhook($data, $hmac_header)
      {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
        return hash_equals($hmac_header, $calculated_hmac);
      }

      $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
      $data = file_get_contents('php://input');
      $verified = verify_webhook($data, $hmac_header);

      if( $verified ){
        if( $_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "products/create"){
          // deal with new added product

        }elseif( $_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "orders/create"){
          updateInventory($data, $shopify, $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] );


        }elseif($_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "orders/cancelled"){
          updateInventory($data, $shopify, $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] );


        }elseif($_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "app/uninstalled" ){
          // create connection to mysql
          $servername = "shopifybundle.crzkedo145qb.us-west-1.rds.amazonaws.com";
          $username = "caomingkai";
          $password = "moon2181";
          $dbname = "shopifybundle";
          $conn = new mysqli($servername, $username, $password, $dbname);

          // Check connection
          if ($conn->connect_error) {
              die("Connection failed: " . $conn->connect_error);
              echo "<h1>Cannot access to 'shopifybundle' database </h1> " . "\n";
          }else{
              $shopDomain = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
              $sqlDel = "DELETE FROM shopToken WHERE domain='$shopDomain'";
              $result = $conn->query($sqlDel);
              // In cases the query failed.
              if(!$result){
                  echo "<h1>Cannot delete token of current shop, since cannot access to 'shopToken' table. </h1> " . "\n";
              }
          }
          $conn->close();
        }
      }
    }
?>
