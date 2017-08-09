<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;

//-------- ## 0 ## workflow fo DELETE --------
// 1. when submit 'delete', POST a series of bundleID to this php script
// 2. loop through POST['bundleID'], to produce an array of bundle to be deleted
// 3. read shop.ShopBundle.txt, find out what products related to this bundle
//    3.1 delete shadow product based on the productID found in last Step
//    3.2 delete
//-------- ## 1 ## delete shop.metafield, based on "shop.MetafieldID.txt"--------
//  bundleInfo.bundleNum | bundleInfo.bundleDetail | originToShadow.originVariantID


//-------- ## 2 ## delete shadow product, based on "shop.BundleToShadowProduct.txt"------
//

//-------- ## 3 ## delete local file of this ShopUrl----------------------------
// "shop.ShopBundle.txt
// "shop.BundleToShadowProduct.txt"
// "shop.ShadowToOriginVariant.txt"
// "shop.MetafieldID.txt"

?>
