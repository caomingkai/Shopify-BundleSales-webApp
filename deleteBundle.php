<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/connectSql.php';

//====================== Delete All Things =====================================

if(isset($_GET['type']) && $_GET['type'] == 'all' ){

      $config = $_SESSION["config"];
      PHPShopify\ShopifySDK::config($config);
      $shopify = new PHPShopify\ShopifySDK;

      //---------------------- delete all metafiled ------------------------
      $meta = $shopify->Metafield->get();
      foreach( $meta as $oneItem ){
          $shopify->Metafield($oneItem['id'])->delete();
      }

      //---------------------- delete all shadow products --------------------
      $vendorPara = array(
          'vendor' => 'Products On Sales',
      );
      $allShadowProduct = $shopify->Product->get($vendorPara);
      foreach( $allShadowProduct as $oneShadowProduct ){
          $shopify->Product($oneShadowProduct['id'])->delete();
      }
      $allShadowProduct = $shopify->Product->get($vendorPara);
      echo "<h1>1 ---- All Shadow Products is deleted. Now shadow product number is:" . count($allShadowProduct) . '</h1>'."\n";

      //---------------------  delete all relevant tables from database ------------------------------


      $tables = array(
                  "ShopBundle"    .    $_SESSION["shopId"],
                  "OriginPtoOriginV" . $_SESSION["shopId"],
                  "OriginVtoShadowV" . $_SESSION["shopId"],
                  "ShadowVToOriginPV" .$_SESSION["shopId"],
                  "BundleToShadowP" .  $_SESSION["shopId"],
               );
      echo "<h1>2 ---- Database tables deletion status: </h1>"."\n";

      foreach ($tables as $tbl) {
          $sql = "DROP TABLE IF EXISTS $tbl";
          $result = $conn->query($sql);
          if ($result){
              echo $tbl . ": has been deleted successfully.";
              echo "<br />";
          }else {
              echo $tbl . ": wasn't deleted OR there is no such table.";
              echo "<br />";
          }
      }
      //-----------delete injected code snippet and related statement: "bundleCheck" and "bundleDisplay" ---------------------

      // ===0=== find out the main Theme.liquid, and its ID
      $themes = $shopify->Theme->get();
      $numsOfThemes = count($theme);
      $themeID = 0;
      foreach( $themes as $oneTheme ){
        if($oneTheme['role'] === 'main'){
          $themeID = $oneTheme['id'];
          break;
        }
      }
      // ===0=== delete  "bundleCheck.liquid" and "bundleDisplay.liquid"
      $para = array(
        "asset[key]" => "snippets/bundleDisplay.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $themeContent = $shopify->Theme($themeID)->Asset->delete($para) ;
      echo "<h1> 3 --- bundleDisplay.liquid is deleted successfully </h1>" .  "\n";

      $para = array(
        "asset[key]" => "snippets/bundleCheck.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $themeContent = $shopify->Theme($themeID)->Asset->delete($para) ;
      echo "<h1> 4 --- bundleCheck.liquid deleted  issuccessfully </h1>" .  "\n";


      // ===1=== delete "{% if template == 'cart' %}{% include 'bundleCheck' %}{% endif %}" from "theme.liquid"---
      $para = array(
        "asset[key]" => "layout/theme.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $themeContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;

      // find out </body> tag, in order to insert into this statement: "{% include 'bundleCheck' %}"
      // only if there doesn't exist such insertion before, we insert this code snippet. Otherwise, do nothing.
      $codeStr = "{% if template == 'cart' %}{% include 'bundleCheck' %}{% endif %}";
      $codeStartPos = strpos( $themeContent, $codeStr );
      if( $codeStartPos !== false ){
          $codeLength = strlen( $codeStr );
          $emptyStr = "";
          $themeContentNew = substr_replace( $themeContent , $emptyStr,  $codeStartPos, $codeLength );

          // delete inserted code by replacing it with empty string
          $para = array(
            "key" => "layout/theme.liquid",
            "value" => $themeContentNew
          );
          $themeContentNew = $shopify->Theme($themeID)->Asset->put($para) ;

                                                      echo "<h1> 5 --- {% include 'bundleCheck' %} deleted successfully </h1>" .  "\n";

      }else{
                                                      echo "<h1> 5 --- There is no {% include 'bundleCheck' %} in theme.liquid  </h1>" .  "\n";
      }
      // ===2=== delete "{% include 'bundleDisplay' %}" from "collection.liquid"---
      $para = array(
        "asset[key]" => "templates/collection.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $collectionContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;
      $codeStr = "{% include 'bundleDisplay' %}";
      $codeStartPos = strpos( $collectionContent, $codeStr );
      if( $codeStartPos !== false ){
          $codeLength = strlen( $codeStr );
          $emptyStr = "";
          $collectionContentNew = substr_replace( $collectionContent , $emptyStr,  $codeStartPos, $codeLength );
          $para = array(
            "key" => "templates/collection.liquid",
            "value" => $collectionContentNew
          );
          $collectionContentNew = $shopify->Theme($themeID)->Asset->put($para) ;
                                                      echo "<h1> 6 --- {% include 'bundleDisplay' %} in collection.liquid is deleted successfully </h1>" .  "\n";
      }else{
                                                      echo "<h1> There is no {% include 'bundleDisplay' %} in collection.liquid </h1>" .  "\n";
      }

      // ===3=== delete "bundleDisplay.liquid" from "product.liquid"---
      $para = array(
        "asset[key]" => "templates/product.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $productContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;
      $codeStartPos = strpos( $productContent, $codeStr );
      if( $codeStartPos !== false ){
          $codeLength = strlen( $codeStr );
          $emptyStr = "";
          $productContentNew = substr_replace( $productContent , $emptyStr,  $codeStartPos, $codeLength );
          $para = array(
            "key" => "templates/product.liquid",
            "value" => $productContentNew
          );
          $productContentNew = $shopify->Theme($themeID)->Asset->put($para) ;
                                                      echo "<h1> 7 --- {% include 'bundleDisplay' %} in 'product.liquid' is deleted successfully </h1>" .  "\n";

      }else{
                                                      echo '<h1> 7 --- There is no injected "bundleDisplay" in product.liquid </h1>' .  "\n";
      }



      $para = array(
        "asset[key]" => "layout/theme.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
      );
      $themeContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;


      //--------------------- delete webhooks ---------------------
        $webhooks = $shopify->Webhook->get();
        foreach( $webhooks as $wh ){
            if( $wh["topic"] !== "app/uninstalled"){
                $shopify->Webhook($wh["id"])->delete();
            }
        }
        $webhooks = $shopify->Webhook->get();
        echo '<h1> 8 ---- only app/uninstalled webhook should be left :</h1>' .  "\n";
        echo '<pre>';
          echo print_r($webhooks);
        echo '</pre>';


//====================== Delete partial things Based on user input ====================
}else{
      //------------ ## 1 ## DELETE metafield ---------------
      $fileNameOriginVtoShadowV = $_SESSION["shopUrl"] . "OriginVtoShadowV.txt";
      $originVtoShadowV = file_get_contents($fileNameOriginVtoShadowV);
      $originVtoShadowVArr = explode( "\n" , $originVtoShadowV);

      foreach( $originVtoShadowVArr as $oneLine ){
          $lineItemArr = explode("#",$oneLine);
          echo $lineItemArr[1]."\n";
          $shopify->Metafield($lineItemArr[1])->delete();
      }

      echo '<pre>';
          print_r($shopify->Metafield->get());
      echo '</pre>';
}


//------------ ## 0 ## workflow fo DELETE --------
// 1. when submit 'delete', POST a series of bundleID to this php script
// 2. loop through POST['bundleID'], to produce an array of bundle to be deleted
// 3. read shop.ShopBundle.txt, find out what products related to this bundle
//    3.1 delete shadow product based on the productID found in last Step
//    3.2 delete
//------------ ## 1 ## delete shop.metafield, based on "shop.MetafieldID.txt"--------
//  bundleInfo.bundleDetail | originToShadow.originVariantID( shadowVariantID,shadowProductID,shadowCollection)


//------------ ## 2 ## delete shadow product, based on "shop.BundleToShadowProduct.txt"------
//

//------------ ## 3 ## delete local file of this ShopUrl----------------------------
// "shop.ShopBundle.txt
// "shop.BundleToShadowProduct.txt"
// "shop.ShadowToOriginVariant.txt"
// "shop.MetafieldID.txt"

?>
