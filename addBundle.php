<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/connectSql.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;
    $bundleExistFlag = false;  // assume this bundle already exist, ie. it's duplicate

    // --- get product Info, used for displayCode Injection ----
    $productInfo = $_SESSION["productInfo"];
    $pInfoArr = explode("\n" , chop($productInfo,"\n"));
    $pInfoHash = array();
    foreach( $pInfoArr as $i=>$pStr ){
      $pArr = explode("," , $pStr);
      $pInfoHash[$pArr[0]]= array(
        "handle" => $pArr[1],
        "title"  => $pArr[2],
        "img" => $pArr[3],
      );
    }

    echo "----------------------------"."\n";
    echo '<a href="/Shopify/3rdapp_public/deleteBundle.php?type=all" target="_blank" > Delete All Bundles </a>'."\n";
    echo "----------------------------<br />"."\n";

//==============================Display existing bundles ==================================
    echo '<table border="1", border-collapse="collapse";>
            <tr>'."\n";
    echo '   <th>#</th> <th>Weight</th><th>bundle Type</th><th>discount Type</th><th>bundle ID</th><th>bundle Detail</th>'."\n";
    echo '  </tr>'."\n";

//--****************************************************************************
//--****************************************************************************
//========================== connect to SQL ====================================

    // Check if Table: $shopBundleTbl exist
    $shopBundleTbl =  "ShopBundle" . $_SESSION["shopId"];
    $bdlTblExistFlg = false;
    $result=$conn->query("SHOW TABLES LIKE '".$shopBundleTbl."'");

    if ( $result->num_rows > 0 ) {
      $bdlTblExistFlg = true;
      $sqlBdlInfo ="SELECT id, weight, bdlType, discntType, bdlId, bdlDetail FROM $shopBundleTbl";
      $result = $conn->query($sqlBdlInfo);
      if ($result->num_rows > 0) {
        while( $row = $result->fetch_assoc() ) {
          echo '  <tr>'."\n";
          echo '    <td>'; echo $row["id"];     echo'</td>'."\n";
          echo '    <td>'; echo $row["weight"]; echo'</td>'."\n";
          echo '    <td>'; echo $row["bdlType"]; echo'</td>'."\n";
          echo '    <td>'; echo $row["discntType"]; echo'</td>'."\n";
          echo '    <td>'; echo $row["bdlId"]; echo'</td>'."\n";
          echo '    <td>'; echo $row["bdlDetail"]; echo'</td>'."\n";
          echo '  </tr>'."\n";
        }
        echo '</table>'."\n";
      }else{
        echo ' <h1>  There is no bundles in this table now </h1>' ."\n";
      }
    }else{
      echo "<h1> There is no table ".$shopBundleTbl." exist </h1>";
    }

//======================Deal with Product/Collection Bundle Sales=====================
    if( isset( $_GET['productItem'] )){
      $p       =    $_GET['productItem'];
      $weight   =   $_GET['weight'];
      $bdlType   =  $_GET['bundleType'];
      $discntType = $_GET['discountType'];
      $discnt  =    $_GET['discount'];
      $BundleID = uniqid(); // unique ID
      $num = sizeof( $p );

//--------<<<<< 1 >>>> Put this new added bundle into table: {shopUrl}ShopBundle-----
      $BundleInfo = "";
      for($i=0; $i<$num-1; $i++){
        $BundleInfo .=   $p[$i] . ":" . $discnt[$i] . ",";
      }
      $BundleInfo .=   $p[$num-1] . ":" . $discnt[$num-1];


      if( !$bdlTblExistFlg ){ // create {shopUrl}ShopBundle table
        echo "<h1>shopBundleTbl:".$shopBundleTbl."</h1>";


        // $sqlCreateTbl = "CREATE TABLE $shopBundleTbl (
        // id INT(6) UNSIGNED AUTO_INCREMENT,
        // firstname VARCHAR(30) NOT NULL,
        // lastname VARCHAR(30) NOT NULL,
        // email VARCHAR(50)
        // )";

        $sqlCreateTbl = "CREATE TABLE $shopBundleTbl(
                    id INT(4) UNSIGNED AUTO_INCREMENT  PRIMARY KEY,
                    weight VARCHAR(5) NOT NULL,
                    bdlType VARCHAR(5) NOT NULL,
                    discntType VARCHAR(5) NOT NULL,
                    bdlId VARCHAR(50) NOT NULL ,
                    bdlDetail VARCHAR(50) NOT NULL
                )";
        if(  $conn->query($sqlCreateTbl) === FALSE ){
          echo "<h1> create ".$shopBundleTbl." table failed!  </h1>";
        }else{
          echo "<h1> following are: ".$weight." : ".$bdlType." : ".$discntType ." : ".$BundleID ." : ".$BundleInfo ."</h1>";
          $sqlNewBdl = "INSERT INTO $shopBundleTbl (weight, bdlType, discntType, bdlId, bdlDetail)
          VALUES ( '$weight', '$bdlType', '$discntType', '$BundleID', '$BundleInfo' )";
          $result = $conn->query($sqlNewBdl);
          if( $result === FALSE ){
            echo "<h1> 1- add new bundle failed! </h1>";
          }
        }
      }else{                           // read from shopbundle database
        $sql = "SELECT discntType FROM $shopBundleTbl WHERE bdlDetail = '$BundleInfo' ";
        $result = $conn->query($sql);
        if( $result->num_rows > 0 ){
           if( $sql !== $discntType ){
             $bundleExistFlag = true;
           }
        }
        // --------## 1 ##-------create database: shopbundle database
        if( !$bundleExistFlag ){
          $sqlNewBdl = "INSERT INTO $shopBundleTbl (weight, bdlType, discntType, bdlId, bdlDetail)
          VALUES ( '$weight', '$bdlType', '$discntType', '$BundleID', '$BundleInfo' )";
          $result = $conn->query($sqlNewBdl);
          echo "<h1>-----------</h1>";
          var_dump($result);
          echo "<h1>-----------</h1>";

          if( $result === false ){
            echo "<h1> 2 - add new bundle failed! </h1>";
          }
        }else{
          echo "<h1> 3 - This bundle already exist, didn't add it into database! </h1>";
        }
      }
//--------<<<<< 2 >>>> make REST call to update shop.metafield.bundleInfo for this shop ----------------

      $existBundle = "";
      $sqlBdlInfo ="SELECT weight, bdlType, discntType, bdlId, bdlDetail FROM $shopBundleTbl";

      $result = $conn->query($sqlBdlInfo);
      echo "<h1>----+++++-------</h1>";
      echo "<pre>";
      print_r($conn);
      echo "</pre>";
      echo "<h1>-----+++++------</h1>";

      if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc() ) {
          $existBundle .= $row["weight"] . "*" . $row["bdlType"]. "&" . $row["discntType"]. "@" . $row["bdlId"]. "#" . $row["bdlDetail"] . "\n";
        }
      }
      $existBundleArray = explode("\n", $existBundle);
      $numOfBundle = sizeof($existBundleArray) - 1; // because of newline existence of last line
      //print out bundleInfo after update
                                                    echo '<h1> 0 --- Num of bundles: ' .$numOfBundle. "</h1>\n";
                                                    for ($i=0; $i < $numOfBundle; $i++) {
                                                       echo '<p> Bundle' .$i. ': ' .$existBundleArray[$i]. "<p>\n";
                                                    }

                                                    echo '<h1> 0 --- All metafields: ' . "</h1>\n";
                                                    echo "<pre>"."\n";
                                                      print_r($shopify->Metafield->get());
                                                    echo "</pre>"."\n";


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

        if( $bdlType === "0" ){  // Bundle by product
          make3RestCall( $pairArray, $shopify,$BundleID,$discntType,$existBundleArray,$pInfoHash,$conn);
        }else{                    // Bundle by collection
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

            foreach( $productArr as $product ){
              $productPairArr[$index] = $product['id']. ":" . $discount;
              $index += 1;
            }
          }
          make3RestCall( $productPairArr,$shopify,$BundleID,$discntType,$existBundleArray,$pInfoHash,$conn);
        }
      }else{
                                                        echo "<h1> Submitted bundle already exist! </h1>"."\n";
      }
    }

//###################  a function used by BundleByProduct / BundleByCollection #######################################
//-------- make REST call to add shadow product based on submitted bundle---------------------------------------------
//-------- make REST call to add shop.metafield.originToShadow based on new added shadow------------------------------
//-------- make REST call to add shadowVariant.metafield.shadowToOrigin [shadowV <--> originV:originP:originC] -------
//#####################################################################################################################
    function make3RestCall( $pairArray,$shopify,$BundleID,$discntType,$existBundleArray,$pInfoHash,$conn){

      // ----------- create corresponding table for database -------------------
      require_once __DIR__ . '/createDB.php';
      // -----------------------------------------------------------------------

      $bundleToShadowInfo = "";                     // used for database: BundleToShadow
      for( $i=0; $i<sizeof($pairArray); $i++){      // get each originProduct:discount pair

        $pair = explode(":" , $pairArray[$i] );
        $itemID   = $pair[0];
        $discount = $pair[1]+0;

        // GET product info of this variant, convert json to php object
        $originProduct = $shopify->Product($itemID)->get();
        $originProductID = $originProduct['id'];

        // GET collection info of this variant, used for shadowVariant.metafield.shadowToOrigin of BundleByCollection
        $para = array( "product_id" => $originProductID ) ;
        $originCustomCollection = $shopify->CustomCollection->get($para);
        $originSmartCollection = $shopify->SmartCollection->get($para);
        $originCollectionID = "";   // used for shadowVariant.metafield.shadowToOrigin

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

        // modify the price for shadow product ; meanwhile, update $originPtoOriginVInfo
        $originVariantIDArray = array();
        // for database: OriginPtoOriginV
        $originPtoOriginVInfo =  "" ;

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
        // used for database: BundleToShadowP
        $bundleToShadowInfo .=  $shadowProductID . ","   ;

        // add Metafield: shop.metafield.originToShadow
        // add Metafield: shadowVariant.metafield.shadowToOrigin
        for( $j = 0; $j < $numOfVar; $j++ ){
          $originVariantID = $originVariantIDArray[$j];
          $shadowVariantID = $shadowProduct['variants'][$j]['id'];
// ---*** 1***---- add Metafield: shadowVariant.metafield.shadowToOrigin
          $variantPara = array(
                "namespace" => "shadowToOrigin",
                "key" => $shadowVariantID,
                "value" => $originVariantID . ":" . $originProductID . ":" . $originCollectionID,
                "value_type" => "string"
          );
          $variantMetafield = $shopify->Product($shadowProductID)->Variant($shadowVariantID)->Metafield->post($variantPara);

// ---*** 2 ***--- add Metafield: shop.metafield.originToShadow
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
          }else{
            $originToShadowInfo = $BundleID . ":" . $shadowVariantID;
            $para["value"] = $originToShadowInfo;
            $para["value_type"] = "string";
            $thisField = $shopify->Metafield->post($para);
          }
          $metaId = $thisField['id'];
          // --------## 2 ##-------create database: OriginVtoShadowV.
          // Check if exist this originV. If yes, append at end; If no, create new
          $originVtoShadowVInfo =  $BundleID . ":" . $shadowVariantID ;
          $sql_OVSV_old = "SELECT shadowV FROM $originVtoShadowV WHERE originV = '$originVariantID' ";
          $result = $conn->query($sql_OVSV_old);
          echo "-------------------------";
          echo "<pre>";
            var_dump($result);
          echo "</pre>";
          echo "-------------------------";

          if( $result->num_rows === 0 ){ // no such originV, need insert new
            $sql_OVSV = "INSERT INTO $originVtoShadowV (originV, metaId, shadowV )
                          VALUES ('$originVariantID', '$metaId' , '$originVtoShadowVInfo' );";
            if( !$creatTblRslt = $conn->query($sql_OVSV) ){ echo "<h1> value(new) insert into originVtoShadowV failed! </h1>"; }
            else{ echo "<h1> value(new) insert into originVtoShadowV successfully! </h1>"; }
          }else{
            $row = $result->fetch_assoc();
            $originVtoShadowVInfo_new =  $row["shadowV"] . "," . $originVtoShadowVInfo;
            $sql_OVSV = "REPLACE INTO $originVtoShadowV (originV, metaId, shadowV )
                          VALUES ('$originVariantID', '$metaId' , '$originVtoShadowVInfo_new' );";
            if( !$creatTblRslt = $conn->query($sql_OVSV)){ echo "<h1> value(append) insert into originVtoShadowV failed! </h1>"; }
            else{ echo "<h1> value(append) insert into originVtoShadowV successfully! </h1>"; }
          }


          // --------## 3 ##-------create database: ShadowVToOriginPV
          $sql_SVOPV = "INSERT INTO $shadowVToOriginPV (shadowV, originP, originV )
                        VALUES ('$shadowVariantID', '$originProductID', '$originVariantID' );";
          if( !$creatTblRslt = $conn->query($sql_SVOPV)){ echo "<h1> value insert into ShadowVToOriginPV failed! </h1>"; }
          else{ echo "<h1> value insert into ShadowVToOriginPV successfully! </h1>";  }
        }

        // --------## 4 ##-------create database: OriginPtoOriginV
        $sql_OPOV = "INSERT IGNORE INTO $originPtoOriginV (originP, originV )
                      VALUES ('$originProductID', '$originPtoOriginVInfo' );";
        if( !$creatTblRslt = $conn->query($sql_OPOV)){ echo "<h1> create BundleToShadowP table failed! </h1>"; }
        else{ echo "<h1> create BundleToShadowP table  successfully! </h1>";  }

      }//== end 'for' ================================


      // --------## 5 ##-------create database: BundleToShadowP
      $bundleToShadowInfo = substr( $bundleToShadowInfo, 0, -1);
      $sql_BSP = "INSERT INTO $bundleToShadowP (bdl, shadowP )
                    VALUES ('$BundleID', '$bundleToShadowInfo' );";
      if( !$creatTblRslt = $conn->query($sql_BSP)){ echo "<h1> create BundleToShadowP table failed! </h1>"; }
      else{ echo "<h1> create BundleToShadowP table successfully! </h1>";  }

//--------## 6 ##  insert display code snippet based on updated bundleInfo ---------
      $codeDisplay = '<!------------------------ Bundle Dispaly Area Start ----------------------------->';
      $codeDisplay .= '<form action="/cart/add" method="get" style="width:100%; ">
                          <div class="head-title" style="text-align:center;" > Bundle Sales List </div>';
      if( count($existBundleArray) > 0){
        array_pop($existBundleArray);
      }
      foreach( $existBundleArray as $i=>$bundle ){
          // --- get bundleInfo ----
          $left_1 = explode("*" , $bundle )[1];
          $bdlTp = explode("&" , $left_1 )[0];
          $left_2 = explode("&" , $left_1 )[1];
          $discntTp = explode("@" , $left_2 )[0];
          $left_3 = explode("@" , $left_2 )[1];
          $bdlId = explode("#" , $left_3 )[0];
          $bdlItem = explode("#" , $left_3 )[1];
          $bdlItemArr= explode("," , $bdlItem);
          $codeDisplay .= '   <div id="container" style="text-align:center; width:100%; " >
                                    <div style="float:left; border:1px solid green;  width:10%;" > bundle No:' . $i . ' :</div>
                                    <div class="inner-wrapper"  style="float:right; clear:right; border:1px solid green;  width:88%;" >';
          $partUrl = "";
          foreach( $bdlItemArr as $j=>$bdlItemPair ){
            $itemId = explode(":" , $bdlItemPair)[0];
            $itemDiscnt = explode(":" , $bdlItemPair)[1];
            $itemImgSrc = $pInfoHash[$itemId]["img"];
            $itemTitle =  $pInfoHash[$itemId]["title"];
            $itemHandle = $pInfoHash[$itemId]["handle"];

            $discntSav = $itemDiscnt;
            $itemDiscntStr = "save "  . "$" . $discntSav;
            if( $bdlTp === "0"){ $partUrl = "all/products/"; }
            if( $discntTp === "0" ){
              $discntSav = (1-$itemDiscnt)*100;
              $itemDiscntStr = "save "  . $discntSav . "%";
            }

              $codeDisplay .= '         <div class="img-plus" style="float:left; border:1px solid red;  width:5%; " >
                                            <img src="" alt="plus sign" />
                                        </div> ';

              $codeDisplay .= '         <div class="img-title-price" style="float:left; border:1px solid red; width:15%;">
                                           <a href="/collections/' . $partUrl . $itemHandle . '">
                                               <img src="' . $itemImgSrc . '" width=50% height=50% alt="' . $itemTitle . '">
                                               <p>' . $itemTitle . '</p>
                                               <p>' . $itemDiscntStr . '</p>
                                               <input type="hidden" name=id[] value="" >
                                           </a>
                                        </div>';
          }
          $codeDisplay .= '             <div class="save-cart" style="float:right; border:1px solid red;  width:10%; ">
                                            THIS IS CART
                                        </div>
                                    </div>
                                  </div> <br />';
      }
      $codeDisplay .= ' </form>
      <!------------------------ Bundle Dispaly Area End ----------------------------->';
      // echo '<pre>';
      // print_r( $codeDisplay);
      // echo '</pre>';
      $themes = $shopify->Theme->get();
      $numsOfThemes = count($theme);
      $themeID = 0;
      foreach( $themes as $oneTheme ){
        if($oneTheme['role'] === 'main'){
          $themeID = $oneTheme['id'];
          break;
        }
      }

      $para = array(
        "key" => "snippets/bundleDisplay.liquid",
        "value" => $codeDisplay
      );
      $bundleCheckSnippet = $shopify->Theme($themeID)->Asset->put($para) ;

      // find out collection.liquid, based on themeID already got
      $para = array(
        "asset[key]" => "templates/collection.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $collectionContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;

      // find out product.liquid, based on themeID already got
      $para = array(
        "asset[key]" => "templates/product.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $productContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;

      // append to the end
      $statementInject = "{% include 'bundleDisplay' %}" ;
      $codeExistPost = strpos( $collectionContent, "{% include 'bundleDisplay' %}");
      if( $codeExistPost === false ){
          $collectionContent .= $statementInject;
          // insert statement  "{% include 'bundleCheck' %}", right after <head> tag
          $para = array(
            "key" => "templates/collection.liquid",
            "value" => $collectionContent
          );
          $collectionContentNew = $shopify->Theme($themeID)->Asset->put($para) ;
      }else{
                                                      echo '<h1> 2. already injected </h1>' .  "\n";
      }
      $codeExistPost = strpos( $productContent, "{% include 'bundleDisplay' %}");
      if( $codeExistPost === false ){
          $productContent .= $statementInject;
          // insert statement  "{% include 'bundleCheck' %}", right after <head> tag
          $para = array(
            "key" => "templates/product.liquid",
            "value" => $productContent
          );
          $productContentNew = $shopify->Theme($themeID)->Asset->put($para) ;
      }else{
                                                      echo '<h1> 2. already injected </h1>' .  "\n";
      }
                                                            // echo '<h1>$shopify below : </h1>' .  "\n";
                                                            // echo "<pre>";
                                                            // print_r ($themeContentNew);
                                                            // echo "</pre>";
                                                            // echo '<p> ------------------------  </p>' .  "\n";

    }
//#####################################################################################################################
//#####################################################################################################################
?>
