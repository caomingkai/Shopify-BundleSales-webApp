<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;
    $bundleExistFlag = true;  // assume this bundle already exist, ie. it's duplicate

//===================Deal with Product Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      $p  = $_GET['productItem'];
      $pd = $_GET['productDiscount'];
      $num = sizeof( $p );

//--------1. Put this new added bundle into file: {shopUrl}ShopBundle.txt------
      $BundleID = uniqid() . ","; // unique ID
      $BundleInfo = $num;
      for($i=0; $i<$num; $i++){
        $BundleInfo .= "," . $p[$i] . "," . $pd[$i];
      }
      $BundleInfo .= "\n";

      $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
      if( !file_exists( $fileName ) ){ // create {shopUrl}ShopBundle.txt
        $bundleExistFlag = false;
        $bundle = $BundleID . $BundleInfo;
        file_put_contents($fileName, $bundle, LOCK_EX);
      }else{                           // read from productInfo.txt
        $infoAll = file_get_contents($fileName);
        //check if this bundle type already exist
        if(strpos($infoAll, $BundleInfo) === false){
          $bundleExistFlag = false;
          $existBundle = file_get_contents($fileName);
          $existBundle .= $BundleID . $BundleInfo;
          file_put_contents($fileName, $existBundle, LOCK_EX);
        }
      }
      //print out bundleInfo after update
      $existBundle = file_get_contents($fileName);
      $existBundleArray = explode("\n", $existBundle);
      $numOfBundle = sizeof($existBundleArray) - 1; // because of newline existence of last line

      echo '<h1> Num of bundles: ' .$numOfBundle. "</h1>\n";
      for ($i=0; $i < $numOfBundle; $i++) {
         echo '<h1> Bundle' .$i. ': ' .$existBundleArray[$i]. "</h1>\n";
      }
//--------2. make REST call to update shop.metafield.bundleInfo for this shop ----------------
      //shop.metafield.bundleInfo.bundleNum
      $metaBundleNum = array(
        "namespace" => "bundleInfo",
        "key" =>  "bundleNum",
        "value" =>  $numOfBundle,
        "value_type" => "integer",
      );
      $shopify->Metafield->post($metaBundleNum);

      //shop.metafield.bundleInfo.bundleDetail
      $metaBundleDetail = array(
        "namespace" => "bundleInfo",
        "key" =>  "bundleDetail",
        "value" =>  $existBundle,
        "value_type" => "string",
      );
      $shopify->Metafield->post($metaBundleDetail);

      echo "<pre>"."\n";
        print_r($shopify->Metafield->get());
      echo "</pre>"."\n";

//--------3. make REST call to add shadow product based on submitted bundle-----
      if( !$bundleExistFlag ){ // indicate this bundle is not duplicate
        $pairArray = explode("," , substr($BundleInfo, 0, -1) ); // remove the "\n"
          echo "<h1>" . $pairArray[0] * 2 .  "</h1>"."\n";
        for( $i=1; $i < $pairArray[0] * 2; $i=$i+2){
          $itemID = $pairArray[$i];
          $discount = $pairArray[$i+1];
          // GET details of this product item, convert json to php object
          $originProduct = $shopify->Product($itemID)->get();
                  echo "<h1> Original Product </h1>"."\n";
                  echo "<pre>"."\n";
                    print_r($originProduct);
                  echo "</pre>"."\n";
                  echo "<h1> ---------------------------- </h1>"."\n";
          // change detail object  in terms of price/vendor
          $originProduct['vendor'] = 'Products On Sales';
          $numOfVar = sizeof( $originProduct['variants'] );
          for( $i = 0; $i < $numOfVar; $i++ ){
            $originProduct['variants'][$i]['price'] = $originProduct['variants'][$i]['price'] * $discount;
          }

          // POST detail object to add shadow product to shop
          $shadowProduct = $shopify->Product()->post($originProduct);
                    echo "<h1> Shadow Product </h1>"."\n";
                    echo "<pre>"."\n";
                      print_r($shadowProduct);
                    echo "</pre>"."\n";
                    echo "<h1> ---------------------------- </h1>"."\n";
          // update shadowToOrigin.txt / bundleToShadow.txt

        }
      }else{
        echo "<h1> Submitted bundle already exist! </h1>"."\n";
      }
    }

//===================Deal with Collection Bundle Sales==========================
    if( isset( $_GET['collectionItem'] )){
      foreach( $_GET['collectionItem'] as $p){
          echo '<h1> collectionItem: ' .$p. "</h1>\n";
      }

    }
?>
