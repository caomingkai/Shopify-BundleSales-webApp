<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;


//------------ ## -1 ## DELETE metafield ---------------
$fileNameOriginVtoShadowV = $_SESSION["shopUrl"] . "OriginVtoShadowV.txt";
$originVtoShadowV = file_get_contents($fileNameOriginVtoShadowV);
$originVtoShadowVArr = explode( "\n" , $originVtoShadowV);

//====================== Delete All Things =====================================
if(isset($_GET['type']) && $_GET['type'] == 'all' ){

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

      //---------------------  delete all file ------------------------------
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



//====================== Delete things Based on user input ====================
}else{

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
//  bundleInfo.bundleNum | bundleInfo.bundleDetail | originToShadow.originVariantID


//------------ ## 2 ## delete shadow product, based on "shop.BundleToShadowProduct.txt"------
//

//------------ ## 3 ## delete local file of this ShopUrl----------------------------
// "shop.ShopBundle.txt
// "shop.BundleToShadowProduct.txt"
// "shop.ShadowToOriginVariant.txt"
// "shop.MetafieldID.txt"

?>
