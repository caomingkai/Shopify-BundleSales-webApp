<?php
    if( isset( $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] )){

      require_once __DIR__ . '/vendor/autoload.php';
      require_once __DIR__ . '/webhookFun.php';
      define('SHOPIFY_APP_SECRET', 'd999981624124eb6b1a902a063a9e8ea');

      $file =  __DIR__ . '/install/merchantToken.txt';
      $lines = explode("\n", file_get_contents($file));
      $merchantHash = array();
      forEach($lines as $oneLine ){
        $keyValueArray = explode(",", $oneLine );
        $merchantHash[$keyValueArray[0]] = $keyValueArray[1];
      }
      $accessToken = $merchantHash[$shopUrl];

      $config = array(
            'ShopUrl' => $shopUrl,
            'AccessToken' => $accessToken,
      );
      PHPShopify\ShopifySDK::config($config);
      $shopify = new PHPShopify\ShopifySDK;



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

      if( $_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "products/create"){
        $fileName = 'product_added_data.txt';
        file_put_contents($fileName, $data, LOCK_EX);
      }elseif( $_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "orders/create"){
        $fileName = 'order_created_data.txt';
        file_put_contents($fileName, print_r($data,true), LOCK_EX);
        updateInventory($data, $shopify, $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] );
      }elseif($_SERVER['HTTP_X_SHOPIFY_TOPIC'] === "orders/cancelled"){
        $fileName = 'order_cancelled_data.txt';
        file_put_contents($fileName, $data, LOCK_EX);
        updateInventory($data, $shopify, $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] );
      }

    }
?>
