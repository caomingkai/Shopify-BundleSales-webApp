<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;

    echo '<h1>$shopify below : </h1>' .  "\n";
    echo "<pre>";
    $themeID = $shopify->Theme->get()[0]['id'];
    print_r($shopify->Theme($themeID)->Asset->get());
    echo "</pre>";
    echo '<p> ------------------------  </p>' .  "\n";

    // ##### 0 ##### see if can inject seccessfully
    // 1. find out the cart.liquid which contain the for-loop
    // 1.1 could firstly find out few possible fileName, and then check their content, if some one have keywords, then that's the file. set flag
    // 1.2 if those possible file still don't have keywords, set flag false.
    // 1.3 next we keep looping through templete/snippet/section to find file bearing name with 'cart', then look in content to find keywords

    // 2. create bundleCheck.liquid

    // 3. append {{ include bundleCheck.liquid }} this sentence at the end of target cart.liquid file.

    // ##### 1 ##### directly write a liquid file, and put it in shopAdmin, implement those functions. see if can function well.

    // 4. convert bundleCheck.liquid into php file, do the appending to cart.liquid

?>
