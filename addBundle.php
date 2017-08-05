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

//-------- ## 1 ## Put this new added bundle into file: {shopUrl}ShopBundle.txt------
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
//--------## 2 ## make REST call to update shop.metafield.bundleInfo for this shop ----------------
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
//--------## 3 ## make REST call to add shadow product based on submitted bundle-----
      if( !$bundleExistFlag ){ // indicate this bundle is not duplicate
        $pairArray = explode("," , substr($BundleInfo, 0, -1) ); // remove the "\n"
        $shadowToOriginInfo = "";                         // for 'shadowToOrigin.txt'
        $bundleToShadowInfo = substr($BundleID, 0, -1) ;  // rip off ',', for 'shadowToOrigin.txt'

                                                        echo "<h1>" . $pairArray[0] * 2 .  "</h1>"."\n";
        for( $i=1; $i<$pairArray[0] * 2; $i+=2){
          $itemID = $pairArray[$i];
                                                        echo "<h1>   itemID: </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r(  $itemID);
                                                        echo "</pre>"."\n";
                                                        echo "<h1> ---------------------------- </h1>"."\n";
          $discount = $pairArray[$i+1];
          // GET details of this product item, convert json to php object
          $originProduct = $shopify->Product($itemID)->get();
          $originProductID = $originProduct['id'];
                                                        echo "<h1> Original Product </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($originProduct);
                                                        echo "</pre>"."\n";
                                                        echo "<h1> ---------------------------- </h1>"."\n";
          // change detail object  in terms of price/vendor
          $originProduct['vendor'] = 'Products On Sales';
          $numOfVar = sizeof( $originProduct['variants'] );
          for( $j = 0; $j < $numOfVar; $j++ ){
            $originProduct['variants'][$j]['price'] = $originProduct['variants'][$j]['price'] * $discount;
          }

          // POST detail object to add shadow product to shop
          $shadowProduct = $shopify->Product()->post($originProduct);
          $shadowProductID = $shadowProduct['id'];
          $shadowToOriginInfo .= $shadowProductID . "," . $originProductID . "\n" ;
          $bundleToShadowInfo .=  ","  . $shadowProductID ;
                                                        echo "<h1> Shadow Product </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($shadowProduct);
                                                        echo "</pre>"."\n";
                                                        echo "<h1> ---------------------------- </h1>"."\n";
        } // end 'for'

//--------## 4 ## update 'ShadowToOrigin.txt' | 'BundleToShadow.txt' -----
        $fileNameShadowToOrigin = $_SESSION["shopUrl"] . "ShadowToOrigin.txt";
        $fileNameBundleToShadow = $_SESSION["shopUrl"] . "BundleToShadow.txt";

        if( !file_exists( $fileNameShadowToOrigin ) ){ // create new file for both
          file_put_contents($fileNameShadowToOrigin, $shadowToOriginInfo , LOCK_EX);
          file_put_contents($fileNameBundleToShadow, $bundleToShadowInfo . "\n", LOCK_EX);
        }else{                                         // read from existing file
          $shadowToOriginExist = file_get_contents($fileNameShadowToOrigin);
          $shadowToOriginExist .= $shadowToOriginInfo ;
          file_put_contents($fileNameShadowToOrigin, $shadowToOriginExist, LOCK_EX);

          $bundleToShadowExist = file_get_contents($fileNameBundleToShadow);
          $bundleToShadowExist .= $bundleToShadowInfo . "\n";
          file_put_contents($fileNameBundleToShadow, $bundleToShadowExist, LOCK_EX);
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
