<?php
//======================== fun setwebhook ======================================
//==============================================================================
    function setwebhook($shopify){

//--1--  set 'products/create' webhook for product_creat and app_uninstall ------
        $para = array(
                "topic" => "products/create",
                "address" => "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/webhookResponse.php",
                "format" => "json"
        );
        $shopify->Webhook->post($para);

//--2--  set 'app/uninstalled' webhook for product_creat and app_uninstall ------
        $para = array(
                "topic" => "app/uninstalled",
                "address" => "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/webhookResponse.php",
                "format" => "json"
        );
        $shopify->Webhook->post($para);

//--3--  set 'orders/create' webhook for product_creat and app_uninstall ------
        $para = array(
                "topic" => "orders/create",
                "address" => "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/webhookResponse.php",
                "format" => "json"
        );
        $shopify->Webhook->post($para);

//--4--  set 'orders/cancelled' webhook for product_creat and app_uninstall ------
        $para = array(
                "topic" => "orders/cancelled",
                "address" => "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/webhookResponse.php",
                "format" => "json"
        );
        $shopify->Webhook->post($para);


    }








//======================== fun updateInventory ====================================================
//=================================================================================================
    function updateInventory($dataStr, $shopify, $shopUrl ){

        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/connectSql.php';
        define('SHOPIFY_APP_SECRET', 'd999981624124eb6b1a902a063a9e8ea');

        // GET shopId and token from database: shopToken
        $sqlToken = "SELECT domain, token, shopId FROM shopToken";
        $result = $conn->query($sqlToken);
        $merchantHash = array();

        if( $result->num_rows === 0 ){
            echo "<h1>Cannot access to 'shopToken' table </h1> " . "\n";
        }else{
            // Query succeeded, get the  content in it
            if ($result->num_rows > 0) {
              while( $row = $result->fetch_assoc() ) {
                  $merchantHash[$row['domain']] = array( $row['token'] , $row['shopId']);
              }
            }
        }
        // config shopify obj with the accessToken obtained
        $accessToken = $merchantHash[$shopUrl][0];
        $config = array(
              'ShopUrl' => $shopUrl,
              'AccessToken' => $accessToken,
        );
        PHPShopify\ShopifySDK::config($config);
        $shopify = new PHPShopify\ShopifySDK;

        //--0-- loop through all items check if 'vendor' is 'product on sale'. If so:
        //--1-- record 'variant_id' and its 'quantity', into new obj: shadowItem{ variant_id => quantity }
        $data = json_decode($dataStr,true);
        $items = $data["line_items"];
        $shadowItems = array();
        foreach( $items as $item ){
            if( $item["vendor"] === "Products On Sales" ){
                $shadowItems[ $item["variant_id"] ] =  $item["quantity"] ;
            }
        }

        //--2-- read local database(shadowVToOriginV.txt), into an hashTable obj STO
        $shadowVToOriginPV = "ShadowVToOriginPV" . $merchantHash[$shopUrl][1];
        $sql = "SELECT shadowV, originP, originV FROM $shadowVToOriginPV";
        $result = $conn->query($sql);

        $shadowVToOriginPV =  array();
        if( $result->num_rows === 0 ){
            echo "<h1>Cannot access to ". $shadowVToOriginPV ." table </h1> " . "\n";
        }else{
            // Query succeeded, get the  content in it
            if ($result->num_rows > 0) {
              while( $row = $result->fetch_assoc() ) {
                  $shadowVToOriginPV[$row['shadowV']] = array( $row['originP'] , $row['originV']);
              }
            }
        }

        foreach( $shadowItems as $shadowVar=>$shadowQty ){
            $originP = $shadowVToOriginPV[$shadowVar][0];
            $originV = $shadowVToOriginPV[$shadowVar][1];
            $para = array();
            if( $data["cancel_reason"] !== null ){
                // this is a "cancelled order" : need to add back quantity
                $para = array( "inventory_quantity_adjustment" => $shadowQty );
            }else{
                // this is a "new created order" : need to minus quantity
                $para = array( "inventory_quantity_adjustment" => -$shadowQty );
            }
                          // $oP = 8672070661;
                          // $productInfo = $shopify->Product($oP)->get();
                          // $fileName = 'function_updateinventory_shadowVToOriginPV000000.txt';
                          // file_put_contents($fileName, print_r($productInfo,true), LOCK_EX);
            $shopify->Product($originP)->Variant($originV)->put($para);
        }
    }
?>
