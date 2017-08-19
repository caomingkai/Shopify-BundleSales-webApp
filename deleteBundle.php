<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

//======================== App Uninstalled =====================================
if(isset($_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ) ){
  define('SHOPIFY_APP_SECRET', 'd999981624124eb6b1a902a063a9e8ea');
  function verify_webhook($data, $hmac_header)
  {
    $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
    return hash_equals($hmac_header, $calculated_hmac);
  }
  $verified = verify_webhook($data, $hmac_header);
  $fileName = 'TrueOrFalse.txt';
  file_put_contents($fileName, $verified, LOCK_EX);

  $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
  $fileName = 'hmac_header.txt';
  file_put_contents($fileName, $hmac_header, LOCK_EX);

  $data = file_get_contents('php://input');
  $fileName = '11111111.txt';
  file_put_contents($fileName, $data, LOCK_EX);

  echo "<pre>";
    print_r($data);
  echo "</pre>";



}



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
      echo "All Shadow Products is deleted. Now shadow product number is:" . count($allShadowProduct) . "\n";

      //---------------------  delete all files ------------------------------
      $files = [
                  $_SESSION["shopUrl"] . "ShopBundle.txt",
                  $_SESSION["shopUrl"] . "shadowVToOriginV.txt",
                  $_SESSION["shopUrl"] . "BundleToShadowP.txt",
                  $_SESSION["shopUrl"] . "OriginPtoOriginV.txt",
                  $_SESSION["shopUrl"] . "OriginVtoShadowV.txt",
               ];

      foreach ($files as $file) {
          if (file_exists($file)) {
              $res = unlink($file);
              if(!$res){
                echo $file . " wasn't been deleted correctly.";
              }
          } else {
              echo $file . " doesn't exist.";
          }
      }

      //--------------------- delete injected code snippet ---------------------
      // find out the main Theme.liquid, and its ID
      $themes = $shopify->Theme->get();
      $numsOfThemes = count($theme);
      $themeID = 0;
      foreach( $themes as $oneTheme ){
        if($oneTheme['role'] === 'main'){
          $themeID = $oneTheme['id'];
          break;
        }
      }

      // get the cotent of main Theme
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

                                                      echo '<h1>$shopify below : </h1>' .  "\n";
                                                      echo "<pre>";
                                                      print_r ($themeContentNew);
                                                      echo "</pre>";
                                                      echo '<p> ------------------------  </p>' .  "\n";
      }else{
                                                      echo '<h1> There is no injected code  </h1>' .  "\n";
      }



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
