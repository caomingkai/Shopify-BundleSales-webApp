<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;
    $bundleExistFlag = true;  // assume this bundle already exist, ie. it's duplicate

    echo "----------------------------"."\n";
    echo '<a href="/Shopify/3rdapp_public/BundleCheckInject.php" target="_blank" >jump to BundleCheckInject.php page.</a>'."\n";
    echo "----------------------------<br />"."\n";

    echo "----------------------------"."\n";
    echo '<a href="/Shopify/3rdapp_public/deleteBundle.php?type=all" target="_blank" > Delete All Bundles </a>'."\n";
    echo "----------------------------<br />"."\n";

    //===================Display existing bundles =====================
    echo '<table border="1", border-collapse="collapse";>
            <tr>'."\n";
    echo '    <th>Weight</th><th>bundle Type</th><th>discount Type</th><th>bundle ID</th><th>bundle Detail</th>'."\n";
    echo '  </tr>'."\n";

    $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
    if( file_exists( $fileName ) ){//create {shopUrl}productInfo.txt
      $bundleInfoAll = file_get_contents($fileName);
      $bundleInfoArr = explode("\n" , trim($bundleInfoAll) );
      $num = count($bundleInfoArr);
      if( $num > 0 ){
        foreach( $bundleInfoArr as $bundle ){
          $bundleWeight = explode("*" , $bundle )[0];
          $left_1 = explode("*" , $bundle )[1];
          $bundleType = explode("&" , $left_1 )[0];
          $left_2 = explode("&" , $left_1 )[1];
          $discountType = explode("@" , $left_2 )[0];
          $left_3 = explode("@" , $left_2 )[1];
          $bundleId = explode("#" , $left_3 )[0];
          $bundleDetail = explode("#" , $left_3 )[1];
    echo '  <tr>'."\n";
    echo '    <td>'; echo $bundleWeight; echo'</td>'."\n";
    echo '    <td>'; echo $bundleType; echo'</td>'."\n";
    echo '    <td>'; echo $discountType; echo'</td>'."\n";
    echo '    <td>'; echo $bundleId; echo'</td>'."\n";
    echo '    <td>'; echo $bundleDetail; echo'</td>'."\n";
    echo '  </tr>'."\n";
        }
      }
    }
    echo '</table>'."\n";

//===================Deal with Product Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      $p       =    $_GET['productItem'];
      $weight   =   $_GET['weight'];
      $bdlType   =  $_GET['bundleType'];
      $discntType = $_GET['discountType'];
      $discnt  =    $_GET['discount'];
      $num = sizeof( $p );

//-------- ## 1 ## Put this new added bundle into file: {shopUrl}ShopBundle.txt------
      $BundlePara = $weight . "*" .  $bdlType . "&" . $discntType ;
      $BundleID = uniqid(); // unique ID
      $BundleInfo = "";
      for($i=0; $i<$num-1; $i++){
        $BundleInfo .=   $p[$i] . ":" . $discnt[$i] . ",";
      }
      $BundleInfo .=   $p[$num-1] . ":" . $discnt[$num-1];
      $BundleInfo .= "\n";

      $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
      if( !file_exists( $fileName ) ){ // create {shopUrl}ShopBundle.txt
        $bundleExistFlag = false;
        $bundle = $BundlePara . "@" . $BundleID . "#" . $BundleInfo;
        file_put_contents($fileName, $bundle, LOCK_EX);
      }else{                           // read from productInfo.txt
        $infoAll = file_get_contents($fileName);
        //check if this bundle type already exist
        if(strpos($infoAll, $BundleInfo) === false){
          $bundleExistFlag = false;
          $existBundle = file_get_contents($fileName);
          $existBundle .= $BundlePara . "@" . $BundleID . "#" . $BundleInfo;
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
//--------## 4 ##  make REST call to add shadowVariant.metafield.shadowToOrigin [shadowV <--> originV:originP:originC] ---------
      if( !$bundleExistFlag ){ // indicate this bundle is not duplicate
        $pairArray = explode("," , substr($BundleInfo, 0, -1) ); // remove the "\n"
        $bundleToShadowInfo = $BundleID . "#";          // for 'bundleToShadowP.txt'
        $shadowToOriginInfo = "";                 // for 'shadowVToOriginV.txt'
        $originPtoOriginVInfo = "";               // for 'OriginPToOriginV.txt'
        $originVtoShadowVInfo = "";               // for 'OriginVtoShadowV.txt'
                                                      // echo "-----------------------------------------------"."\n";
                                                      // echo "<pre>";
                                                      // // print_r($pairArray);
                                                      // var_dump($discntType) ;
                                                      //
                                                      // echo "</pre>";
                                                      // echo "-----------------------------------------------"."\n";

        if( $bdlType === "0" ){
                                                      // echo "this is Bundle by product"."\n";
          make3RestCall( $pairArray, $bundleToShadowInfo, $originPtoOriginVInfo, $originVtoShadowVInfo,$shopify,$BundleID,$discntType);
        }else{
                                                      // echo "this is Bundle by collection"."\n";
          $collectionPairArr = $pairArray;
          $productPairArr = array();
          $index = 0;

          foreach( $collectionPairArr as  $collectionPairStr ){
            $collectionPair = explode( ":" , $collectionPairStr );
            $collectionID = $collectionPair[0];
            $discount     = $collectionPair[1];

            //GET all products belongs to this collectionID
            $params = array( 'collection_id' => $collectionID );
            $productArr = $shopify->Product->get($params);
                                                            // echo "-----------------------------------------------"."\n";
                                                            // echo "<pre>";
                                                            // // print_r($pairArray);
                                                            // var_dump($collectionID) ;
                                                            //
                                                            // echo "</pre>";
                                                            // echo "-----------------------------------------------"."\n";

            foreach( $productArr as $product ){
              $productPairArr[$index] = $product['id']. ":" . $discount;
              $index += 1;
            }
          }

          make3RestCall( $productPairArr, $bundleToShadowInfo, $originPtoOriginVInfo, $originVtoShadowVInfo,$shopify,$BundleID,$discntType);
        }
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

//         for( $i=0; $i<sizeof($pairArray); $i++){  // get each originProduct:discount pair
//           $pair = explode(":" , $pairArray[$i] );
//           $itemID   = $pair[0];
//           $discount = $pair[1];
//                                                         echo "<h1> 2 --- product ID: </h1>"."\n";
//                                                         echo "<pre>"."\n";
//                                                           print_r(  $itemID);
//                                                         echo "</pre>"."\n";
//                                                         echo "---------------------------- "."\n";
//
//           // GET details of this product item, convert json to php object
//           $originProduct = $shopify->Product($itemID)->get();
//           $originProductID = $originProduct['id'];
//                                                         echo "<h1> 3 --- Original Product </h1>"."\n";
//                                                         echo "<pre>"."\n";
//                                                           print_r($originProduct);
//                                                         echo "</pre>"."\n";
//                                                         echo "----------------------------"."\n";
//           // change detail object in terms of price/vendor
//           $originProduct['vendor'] = 'Products On Sales';
//           $numOfVar = sizeof( $originProduct['variants'] );
//
//           //------------------------------------------------------
//
//
//           // modify the price for shadow product , update $originPtoOriginVInfo
//           $originPtoOriginVInfo .= $originProductID . "#" ;
//           $originVariantIDArray = array();
//
//           for( $j = 0; $j < $numOfVar; $j++ ){
//             if( $discntType == 0 ){
//               $originProduct['variants'][$j]['price'] = $originProduct['variants'][$j]['price'] * $discount;
//             }else{
//               $originProduct['variants'][$j]['price'] = $originProduct['variants'][$j]['price'] - $discount;
//             }
//             $originVariantItem = $originProduct['variants'][$j]['id'];
//             $originVariantIDArray[$j] = $originVariantItem;
//             $originPtoOriginVInfo  .=   $originVariantItem . "," ;     // for 'OriginPtoOriginV.txt'
//           }
//           if( $numOfVar > 0 ){                   // get rid of last ","
//             $originPtoOriginVInfo = chop($originPtoOriginVInfo, "," );
//           }
//
//           // add shadow product to shop by making POST call
//           $shadowProduct = $shopify->Product()->post($originProduct);
//           $shadowProductID = $shadowProduct['id'];
//
//           // add Metafield: originVariant.metafield.originToShadow.originVariantID
//           // add Metafield: shadowVariant.metafield.shadowToOrigin.shadowVariantID
//           for( $j = 0; $j < $numOfVar; $j++ ){
//             $originVariantID = $originVariantIDArray[$j];
//             $shadowVariantID = $shadowProduct['variants'][$j]['id'];
//             // --- 1---- add Metafield: shadowVariant.metafield.shadowToOrigin
//             $variantPara = array(
//                   "namespace" => "shadowToOrigin",
//                   "key" => $shadowVariantID,
//                   "value" => $originVariantID . ":" . $originProductID,
//                   "value_type" => "string"
//             );
//             $variantMetafield = $shopify->Product($shadowProductID)->Variant($shadowVariantID)->Metafield->post($variantPara);
//                                                         echo "<h1> 4.0 -- [Variant metafield] --  </h1>"."\n";
//                                                         echo "<pre>"."\n";
//                                                           print_r($variantMetafield);
//                                                         echo "</pre>"."\n";
//                                                         echo "---------------------------- "."\n";
//             // --- 2 --- add Metafield: shop.metafield.originToShadow
//             $para = array(
//               "namespace" => "originToShadow",
//               "key" => $originVariantID
//             );
//             $thisField = $shopify->Metafield()->get($para);
//             $originToShadowInfo = "";
//             if( count($thisField) > 0 ){
//               $originToShadowInfo = $thisField[0]["value"]. "," . $BundleID . ":" . $shadowVariantID;
//               $para["value"] = $originToShadowInfo;
//               $para["value_type"] = "string";
//               $thisField = $shopify->Metafield->post($para);
//                                                           echo "<h1> 4 -- [existed field] -- origin to shadow  </h1>"."\n";
//                                                           echo "<pre>"."\n";
//                                                             print_r($thisField);
//                                                           echo "</pre>"."\n";
//                                                           echo "---------------------------- "."\n";
//             }else{
//               $originToShadowInfo = $BundleID . ":" . $shadowVariantID;
//               $para["value"] = $originToShadowInfo;
//               $para["value_type"] = "string";
//               $thisField = $shopify->Metafield->post($para);
//                                                           echo "<h1> 4 -- [new field] -- origin to shadow  </h1>"."\n";
//                                                           echo "<pre>"."\n";
//                                                             print_r($thisField);
//                                                           echo "</pre>"."\n";
//                                                           echo "---------------------------- "."\n";
//             }
//             $originVtoShadowVInfo .= $originVariantID . "#" . $thisField['id'] . "#" . $BundleID . ":" . $shadowVariantID . "\n";
//             $shadowToOriginInfo .= $shadowVariantID . "#" . $originVariantID . "\n" ;
//           }
//           $bundleToShadowInfo .=  ","  . $shadowProductID ;
//           $originPtoOriginVInfo  .=  "\n";
//                                                         echo "<h1> 5 --- Shadow Product </h1>"."\n";
//                                                         echo "<pre>"."\n";
//                                                           print_r($shadowProduct);
//                                                         echo "</pre>"."\n";
//                                                         echo "---------------------------- "."\n";
//         } // end 'for'
//
// //--------## 6 ##  update OriginPtoOriginV.txt based on response of GET(originPId) ---------
// //--------## 7 ##  update OriginVtoShadowV.txt based on response of POST for the added new shadow variants ---------
// //--------## 8 ## update 'ShadowToOrigin.txt' | 'BundleToShadow.txt' ------------------------------
//         // append with necessary "\n"
//
//         $bundleToShadowInfo   .=  "\n";
//         $fileNameShadowToOrigin = $_SESSION["shopUrl"] . "shadowVToOriginV.txt";
//         $fileNameBundleToShadow = $_SESSION["shopUrl"] . "BundleToShadowP.txt";
//         $fileNameOriginPtoOriginV = $_SESSION["shopUrl"] . "OriginPtoOriginV.txt";
//         $fileNameOriginVtoShadowV = $_SESSION["shopUrl"] . "OriginVtoShadowV.txt";
//
//         if( !file_exists( $fileNameOriginVtoShadowV ) ){   // create new file for the THREE
//           file_put_contents($fileNameShadowToOrigin, $shadowToOriginInfo , LOCK_EX);
//           file_put_contents($fileNameOriginPtoOriginV, $originPtoOriginVInfo , LOCK_EX);
//           file_put_contents($fileNameOriginVtoShadowV, $originVtoShadowVInfo , LOCK_EX);//-------
//           file_put_contents($fileNameBundleToShadow, $bundleToShadowInfo, LOCK_EX);
//         }else{                                         // read from existing file
//           $shadowToOriginExist = file_get_contents($fileNameShadowToOrigin);
//           $shadowToOriginExist .= $shadowToOriginInfo ;
//           file_put_contents($fileNameShadowToOrigin, $shadowToOriginExist, LOCK_EX);
//
//           $originPtoOriginVExist = file_get_contents($fileNameOriginPtoOriginV);
//           $originPtoOriginVExist .= $originPtoOriginVInfo ;
//           file_put_contents($fileNameOriginPtoOriginV, $originPtoOriginVExist, LOCK_EX);
//
//           $bundleToShadowExist = file_get_contents($fileNameBundleToShadow);
//           $bundleToShadowExist .= $bundleToShadowInfo . "\n";
//           file_put_contents($fileNameBundleToShadow, $bundleToShadowExist, LOCK_EX);
//
//           // specially handle with $originVtoShadowVInfo, since we need to modify content, not just append
//           $originVtoShadowVExist = file_get_contents($fileNameOriginVtoShadowV);
//           $originVtoShadowVLinesRaw =explode( "\n" , $originVtoShadowVInfo );
//           $originVtoShadowVLines = array_slice($originVtoShadowVLinesRaw, 0, count($originVtoShadowVLinesRaw)-1 ); // remove last empty item
//           foreach( $originVtoShadowVLines as $originVtoShadowVOneLine ){
//               $triPairNew =explode( "#" , $originVtoShadowVOneLine );
//               $metaNew = $triPairNew[1];
//               $shadowVStringNew =  $triPairNew[2];
//               $metaPos = strpos($originVtoShadowVExist, $metaNew);
//               if( $metaPos != false ){
//                 $posInsert = $metaPos + strlen($metaNew) + 1;// 1->'#'
//                 $originVtoShadowVExist = substr_replace($originVtoShadowVExist, $shadowVStringNew . ",", $posInsert, 0);
//               }else{
//                 $originVtoShadowVExist .= $originVtoShadowVOneLine . "\n";
//               }
//           }
//           file_put_contents($fileNameOriginVtoShadowV, $originVtoShadowVExist , LOCK_EX);
//         }

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

//###################  a function used by BundleByProduct / BundleByCollection #######################################
//-------- make REST call to add shadow product based on submitted bundle---------------------------------------------
//-------- make REST call to add shop.metafield.originToShadow based on new added shadow------------------------------
//-------- make REST call to add shadowVariant.metafield.shadowToOrigin [shadowV <--> originV:originP:originC] -------
//#####################################################################################################################
    function make3RestCall( $pairArray, $bundleToShadowInfo, $originPtoOriginVInfo, $originVtoShadowVInfo,$shopify,$BundleID,$discntType){
        // echo "<pre>"."\n";
        // print_r( $pairArray);
        // echo "</pre>"."\n";

      for( $i=0; $i<sizeof($pairArray); $i++){  // get each originProduct:discount pair
        $pair = explode(":" , $pairArray[$i] );
        $itemID   = $pair[0];
        $discount = $pair[1];
                                                      echo "<h1> 2 --- product ID: </h1>"."\n";
                                                      echo "<pre>"."\n";
                                                        print_r(  $itemID);
                                                      echo "</pre>"."\n";
                                                      echo "---------------------------- "."\n";

        // GET product info of this variant, convert json to php object
        $originProduct = $shopify->Product($itemID)->get();
        $originProductID = $originProduct['id'];
                                                      echo "<h1> 3 --- Original Product </h1>"."\n";
                                                      echo "<pre>"."\n";
                                                        print_r($originProduct);
                                                      echo "</pre>"."\n";
                                                      echo "----------------------------"."\n";

        // GET collection info of this variant, convert json to php object
        $para = array( "product_id" => $originProductID ) ;
        $originCustomCollection = $shopify->CustomCollection->get($para);
        $originSmartCollection = $shopify->SmartCollection->get($para);
        $originCollectionID = "";

        if( sizeof($originCustomCollection) > 0){
          foreach( $originCustomCollection as $customCollectItem){
            $originCollectionID .= $customCollectItem['id'] . ",";
          }
        }
        if(sizeof($originSmartCollection) == 0 ){
          $originCollectionID = substr($originCollectionID, 0, -1);
        }else{
          foreach( $originSmartCollection as $smartCollectItem){
            $originCollectionID .= $smartCollectItem['id'] . ",";
          }
          $originCollectionID = substr($originCollectionID, 0, -1);
        }

        // change detail object in terms of price/vendor
        $originProduct['vendor'] = 'Products On Sales';
        $numOfVar = sizeof( $originProduct['variants'] );

        //------------------------------------------------------


        // modify the price for shadow product , update $originPtoOriginVInfo
        $originPtoOriginVInfo .= $originProductID . "#" ;
        $originVariantIDArray = array();

        for( $j = 0; $j < $numOfVar; $j++ ){
          if( $discntType === "0" ){
            $originProduct['variants'][$j]['price'] = $originProduct['variants'][$j]['price'] * $discount;
          }else{
            $newPrice = $originProduct['variants'][$j]['price'] - $discount;
            if( $newPrice > 0){
              $originProduct['variants'][$j]['price'] = $newPrice;
            }else{
              $originProduct['variants'][$j]['price'] = 0;
            }
          }
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

        // add Metafield: originVariant.metafield.originToShadow.originVariantID
        // add Metafield: shadowVariant.metafield.shadowToOrigin.shadowVariantID
        for( $j = 0; $j < $numOfVar; $j++ ){
          $originVariantID = $originVariantIDArray[$j];
          $shadowVariantID = $shadowProduct['variants'][$j]['id'];
          // --- 1---- add Metafield: shadowVariant.metafield.shadowToOrigin
          $variantPara = array(
                "namespace" => "shadowToOrigin",
                "key" => $shadowVariantID,
                "value" => $originVariantID . ":" . $originProductID . ":" . $originCollectionID,
                "value_type" => "string"
          );
          $variantMetafield = $shopify->Product($shadowProductID)->Variant($shadowVariantID)->Metafield->post($variantPara);
                                                      echo "<h1> 4.0 -- [Variant metafield] --  </h1>"."\n";
                                                      echo "<pre>"."\n";
                                                        print_r($variantMetafield);
                                                      echo "</pre>"."\n";
                                                      echo "---------------------------- "."\n";
          // --- 2 --- add Metafield: shop.metafield.originToShadow
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
            $thisField = $shopify->Metafield->post($para);
                                                        echo "<h1> 4 -- [existed field] -- origin to shadow  </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($thisField);
                                                        echo "</pre>"."\n";
                                                        echo "---------------------------- "."\n";
          }else{
            $originToShadowInfo = $BundleID . ":" . $shadowVariantID;
            $para["value"] = $originToShadowInfo;
            $para["value_type"] = "string";
            $thisField = $shopify->Metafield->post($para);
                                                        echo "<h1> 4 -- [new field] -- origin to shadow  </h1>"."\n";
                                                        echo "<pre>"."\n";
                                                          print_r($thisField);
                                                        echo "</pre>"."\n";
                                                        echo "---------------------------- "."\n";
          }
          $originVtoShadowVInfo .= $originVariantID . "#" . $thisField['id'] . "#" . $BundleID . ":" . $shadowVariantID . "\n";
          $shadowToOriginInfo .= $shadowVariantID . "#" . $originVariantID . "\n" ;
        }
        $bundleToShadowInfo .=  $shadowProductID . ","   ;
        $originPtoOriginVInfo  .=  "\n";
                                                      echo "<h1> 5 --- Shadow Product </h1>"."\n";
                                                      echo "<pre>"."\n";
                                                        print_r($shadowProduct);
                                                      echo "</pre>"."\n";
                                                      echo "---------------------------- "."\n";
      } // end 'for'

    //--------## 6 ##  update OriginPtoOriginV.txt based on response of GET(originPId) ---------
    //--------## 7 ##  update OriginVtoShadowV.txt based on response of POST for the added new shadow variants ---------
    //--------## 8 ## update 'ShadowToOrigin.txt' | 'BundleToShadow.txt' ------------------------------
      // append with necessary "\n"

      $bundleToShadowInfo = substr_replace( $bundleToShadowInfo, "\n", -1, 1);
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
        $bundleToShadowExist .= $bundleToShadowInfo ;
        file_put_contents($fileNameBundleToShadow, $bundleToShadowExist, LOCK_EX);

        // specially handle with $originVtoShadowVInfo, since we need to modify content, not just append
        $originVtoShadowVExist = file_get_contents($fileNameOriginVtoShadowV);
        $originVtoShadowVLinesRaw =explode( "\n" , $originVtoShadowVInfo );
        $originVtoShadowVLines = array_slice($originVtoShadowVLinesRaw, 0, count($originVtoShadowVLinesRaw)-1 ); // remove last empty item
        foreach( $originVtoShadowVLines as $originVtoShadowVOneLine ){
            $triPairNew =explode( "#" , $originVtoShadowVOneLine );
            $metaNew = $triPairNew[1];
            $shadowVStringNew =  $triPairNew[2];
            $metaPos = strpos($originVtoShadowVExist, $metaNew);
            if( $metaPos != false ){
              $posInsert = $metaPos + strlen($metaNew) + 1;// 1->'#'
              $originVtoShadowVExist = substr_replace($originVtoShadowVExist, $shadowVStringNew . ",", $posInsert, 0);
            }else{
              $originVtoShadowVExist .= $originVtoShadowVOneLine . "\n";
            }
        }
        file_put_contents($fileNameOriginVtoShadowV, $originVtoShadowVExist , LOCK_EX);
      }
    }
//#####################################################################################################################
//#####################################################################################################################

?>
