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
                "address" => "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/deleteBundle.php",
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


        $fileName = 'function_updateinventory_dataStr.txt';
        file_put_contents($fileName, $dataStr, LOCK_EX);

        $data = json_decode($dataStr,true);
        $items = $data["line_items"];

        $fileName = 'function_updateinventory_data.txt';
        file_put_contents($fileName, print_r($data,true), LOCK_EX);

        $fileName = 'function_updateinventory_items.txt';
        file_put_contents($fileName, print_r($items,true), LOCK_EX);

        //--0-- loop through all items check if 'vendor' is 'product on sale'. If so:
        //--1-- record 'variant_id' and its 'quantity', into new obj: shadowItem{ variant_id => quantity }
        $shadowItems = array();
        foreach( $items as $item ){
            if( $item["vendor"] === "Products On Sales" ){
                $shadowItems[ $item["variant_id"] ] =  $item["quantity"] ;
            }
        }

        $fileName = 'function_updateinventory_shadowItems.txt';
        file_put_contents($fileName, print_r($shadowItems,true), LOCK_EX);

        //--2-- read local database(shadowVToOriginV.txt), into an hashTable obj STO
        $fileName = $shopUrl . "ShadowVToOriginPV.txt";
        $items = explode("\n" , trim(file_get_contents($fileName)) );

        $shadowVToOriginPV = array();
        foreach( $items as $item ){
          $itemTri = explode( "#" , $item );
          $shadowV = $itemTri[0];
          $originP = $itemTri[1];
          $originV = $itemTri[2];
          $shadowVToOriginPV[$shadowV] = array( $originP, $originV);
        }

        $fileName = 'function_updateinventory_shadowVToOriginPV.txt';
        file_put_contents($fileName, print_r($shadowVToOriginPV,true), LOCK_EX);

        $productInfo = "aaaaaaaaaaaaaaaa";
        $fileName = 'function_updateinventory_shadowVToOriginPV11111111.txt';
        file_put_contents($fileName, print_r($productInfo,true), LOCK_EX);

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
