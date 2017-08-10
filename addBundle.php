<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;
    $bundleExistFlag = true;  // assume this bundle already exist, ie. it's duplicate

    echo "----------------------------"."\n";
    echo '<a href="/Shopify/3rdapp_public/BundleCheckInject.php">jump to BundleCheckInject.php page.</a>'."\n";
    echo "----------------------------"."\n";

    echo "----------------------------"."\n";
    echo '<a href="/Shopify/3rdapp_public/deleteBundle.php">deleteBundle</a>'."\n";
    echo "----------------------------"."\n";

//===================Deal with Product Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      $p  = $_GET['productItem'];
      $pd = $_GET['productDiscount'];
      $num = sizeof( $p );

//-------- ## 1 ## Put this new added bundle into file: {shopUrl}ShopBundle.txt------
      $BundleID = uniqid(); // unique ID
      // $BundleInfo = $num;
      $BundleInfo = "";
      for($i=0; $i<$num-1; $i++){
        $BundleInfo .=   $p[$i] . ":" . $pd[$i] . ",";
      }
      $BundleInfo .=   $p[$num-1] . ":" . $pd[$num-1];
      $BundleInfo .= "\n";

      $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
      if( !file_exists( $fileName ) ){ // create {shopUrl}ShopBundle.txt
        $bundleExistFlag = false;
        $bundle = $BundleID . "#" . $BundleInfo;
        file_put_contents($fileName, $bundle, LOCK_EX);
      }else{                           // read from productInfo.txt
        $infoAll = file_get_contents($fileName);
        //check if this bundle type already exist
        if(strpos($infoAll, $BundleInfo) === false){
          $bundleExistFlag = false;
          $existBundle = file_get_contents($fileName);
          $existBundle .= $BundleID . "#" . $BundleInfo;
          file_put_contents($fileName, $existBundle, LOCK_EX);
        }
      }
      //print out bundleInfo after update
      $existBundle = file_get_contents($fileName);
      $existBundleArray = explode("\n", $existBundle);
      $numOfBundle = sizeof($existBundleArray) - 1; // because of newline existence of last line

                                                    echo '<h1> 0 --- Num of bundles: ' .$numOfBundle. "</h1>\n";
                                                    for ($i=0; $i < $numOfBundle; $i++) {
                                                       echo '<p> Bundle' .$i. ': ' .$existBundleArray[$i]. "<p>\n";
                                                    }

                                                    echo '<h1> 0 --- All metafields: ' . "</h1>\n";
                                                    echo "<pre>"."\n";
                                                      print_r($shopify->Metafield->get());
                                                    echo "</pre>"."\n";

//--------## 2 ## make REST call to update shop.metafield.bundleInfo for this shop ----------------

      //shop.metafield.bundleInfo.bundleDetail
      $metaBundleDetail = array(
        "namespace" => "bundleInfo",
        "key" =>  "bundleDetail",
        "value" =>  $existBundle,
        "value_type" => "string",
      );
      $bundleDetailMeta = $shopify->Metafield->post($metaBundleDetail);
      // $metafieldID .= $bundleDetailMeta['id'] . "\n";

                                                      // echo "<h1> 1 --- metafieldID :</h1>"."\n";
                                                      // echo "<pre>"."\n";
                                                      //   print_r($metafieldID);
                                                      // echo "</pre>"."\n";
                                                      // echo "<pre>"."\n";
                                                      //
                                                      // echo "----------------------------"."\n";


//--------## 3 ##  make REST call to add shadow product based on submitted bundle------------------------
//--------## 4 ##  make REST call to add shop.metafield.originToShadow based on new added shadow---------
//--------## 5 ##  update OriginPtoOriginV.txt based on response of GET(originPId) ---------
//--------## 6 ##  update OriginVtoShadowV.txt based on response of POST for the added new shadow variants ---------

      if( !$bundleExistFlag ){ // indicate this bundle is not duplicate
        $pairArray = explode("," , substr($BundleInfo, 0, -1) ); // remove the "\n"
        $bundleToShadowInfo = $BundleID;          // for 'bundleToShadowP.txt'
        $shadowToOriginInfo = "";                 // for 'shadowVToOriginV.txt'
        $originPtoOriginVInfo = "";               // for 'OriginPToOriginV.txt'
        $originVtoShadowVInfo = "";               // for 'OriginVtoShadowV.txt'

        for( $i=0; $i<sizeof($pairArray); $i++){  // get each originProduct:discount pair
          $pair = explode(":" , $pairArray[$i] );
          $itemID   = $pair[0];
          $discount = $pair[1];
                                                        echo "<h1> 2 --- product ID: </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r(  $itemID);
                                                        echo "</pre>"."\n";
                                                        echo "---------------------------- "."\n";

          // GET details of this product item, convert json to php object
          $originProduct = $shopify->Product($itemID)->get();
          $originProductID = $originProduct['id'];
                                                        echo "<h1> 3 --- Original Product </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($originProduct);
                                                        echo "</pre>"."\n";
                                                        echo "----------------------------"."\n";
          // change detail object in terms of price/vendor
          $originProduct['vendor'] = 'Products On Sales';
          $numOfVar = sizeof( $originProduct['variants'] );

          //------------------------------------------------------


          // modify the price for shadow product , update $originPtoOriginVInfo
          $originPtoOriginVInfo .= $originProductID . "#" ;
          $originVariantIDArray = array();

          for( $j = 0; $j < $numOfVar; $j++ ){
            $originProduct['variants'][$j]['price'] = $originProduct['variants'][$j]['price'] * $discount;
            $originVariantItem = $originProduct['variants'][$j]['id'];
            $originVariantIDArray[$j] = $originVariantItem;
            $originPtoOriginVInfo  .=   $originVariantItem . "," ;     // for 'OriginPtoOriginV.txt'
          }
          if( $numOfVar > 0 ){                   // get rid of last ","
            $originPtoOriginVInfo = chop($originPtoOriginVInfo, "," );
          }

          // add shadow product to shop by making POST call
          $shadowProduct = $shopify->Product()->post($originProduct);
          $shadowProductID = $shadowProduct['id'];

          // add Metafield: shop.metafield.originToShadow.originVariantID
          for( $j = 0; $j < $numOfVar; $j++ ){
            $originVariantID = $originVariantIDArray[$j];
            $shadowVariantID = $shadowProduct['variants'][$j]['id'];
            $para = array(
              "namespace" => "originToShadow",
              "key" => $originVariantID
            );
            $thisField = $shopify->Metafield()->get($para);
            $originToShadowInfo = "";
            if( count($thisField) > 0 ){
              $originToShadowInfo = $thisField[0]["value"]. "," . $BundleID . ":" . $shadowVariantID;
              $para["value"] = $originToShadowInfo;
              $para["value_type"] = "string";
              $thisField = $shopify->Metafield()->post($para);
                                                          echo "<h1> 4.1 --- origin to shadow  </h1>"."\n";
                                                          echo "<pre>"."\n";
                                                            print_r($thisField);
                                                          echo "</pre>"."\n";
                                                          echo "---------------------------- "."\n";
            }else{
              $originToShadowInfo = $BundleID . ":" . $shadowVariantID;
              $para["value"] = $originToShadowInfo;
              $para["value_type"] = "string";
              $thisField = $shopify->Metafield()->post($para);
                                                          echo "<h1> 4.2 --- origin to shadow  </h1>"."\n";
                                                          echo "<pre>"."\n";
                                                            print_r($thisField);
                                                          echo "</pre>"."\n";
                                                          echo "---------------------------- "."\n";
            }
            $originVtoShadowVInfo .= $originVariantID . "#" . $thisField['id'] . "#" . $BundleID . ":" . $shadowVariantID . "\n";
            $shadowToOriginInfo .= $shadowVariantID . "," . $originVariantID . "\n" ;
          }
          $bundleToShadowInfo .=  ","  . $shadowProductID ;
          $originPtoOriginVInfo  .=  "\n";
                                                        echo "<h1> 5 --- Shadow Product </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($shadowProduct);
                                                        echo "</pre>"."\n";
                                                        echo "---------------------------- "."\n";
        } // end 'for'

//--------## 6 ## update 'ShadowToOrigin.txt' | 'BundleToShadow.txt' ------------------------------
        // append with necessary "\n"

        $bundleToShadowInfo   .=  "\n";
        $fileNameShadowToOrigin = $_SESSION["shopUrl"] . "shadowVToOriginV.txt";
        $fileNameBundleToShadow = $_SESSION["shopUrl"] . "BundleToShadowP.txt";
        $fileNameOriginPtoOriginV = $_SESSION["shopUrl"] . "OriginPtoOriginV.txt";
        $fileNameOriginVtoShadowV = $_SESSION["shopUrl"] . "OriginVtoShadowV.txt";

        if( !file_exists( $fileNameOriginVtoShadowV ) ){   // create new file for the THREE
          file_put_contents($fileNameShadowToOrigin, $shadowToOriginInfo , LOCK_EX);
          file_put_contents($fileNameOriginPtoOriginV, $originPtoOriginVInfo , LOCK_EX);
          file_put_contents($fileNameOriginVtoShadowV, $originVtoShadowVInfo , LOCK_EX);//-------
          file_put_contents($fileNameBundleToShadow, $bundleToShadowInfo, LOCK_EX);
        }else{                                         // read from existing file
          $shadowToOriginExist = file_get_contents($fileNameShadowToOrigin);
          $shadowToOriginExist .= $shadowToOriginInfo ;
          file_put_contents($fileNameShadowToOrigin, $shadowToOriginExist, LOCK_EX);

          $originPtoOriginVExist = file_get_contents($fileNameOriginPtoOriginV);
          $originPtoOriginVExist .= $originPtoOriginVInfo ;
          file_put_contents($fileNameOriginPtoOriginV, $originPtoOriginVExist, LOCK_EX);

          $bundleToShadowExist = file_get_contents($fileNameBundleToShadow);
          $bundleToShadowExist .= $bundleToShadowInfo . "\n";
          file_put_contents($fileNameBundleToShadow, $bundleToShadowExist, LOCK_EX);

          // specially handle with $originVtoShadowVInfo, since we need to modify content, not just append
          $keyPairNew =explode( "#" , $originVtoShadowVInfo );
          $originVNew = $keyPairNew[0];
          $shadowVStringNew =  $keyPairNew[1];
          $originVtoShadowVExist = file_get_contents($fileNameOriginVtoShadowV);

          $keyPos = strpos($originVtoShadowVExist, $originVNew);
          if( $keyPos != false ){
            $posInsert = $keyPos + strlen($originVNew) + 1;// 1->'#'
            $originVtoShadowVNew = substr_replace($originVtoShadowVExist, $shadowVStringNew . ",", $pos, 0);
          }else{
            $originVtoShadowVNew .= $originVtoShadowVInfo . "\n";
          }
        }


//--------## 5 ## make REST call to add shop.metafield.originToShadow  -----
        // 1. loop through all origin product GET shop.metafield.originToShadow.

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
