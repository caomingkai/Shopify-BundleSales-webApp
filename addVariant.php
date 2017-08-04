<?php
    header("Access-Control-Allow-Origin: *");
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/checkAppInstalled.php';


    echo "Cross Domain Request Succeed!"."\n";
    var_dump($lines);
    echo "<div></div>";
    var_dump($installed_flag);
    echo "<div></div>";

    if($installed_flag){
      echo "This shop have installed my app!"."\n";

      $config = array(
            'ShopUrl' => $_GET["shop"],
            'AccessToken' => $accessToken,
      );

            echo '<h1>0. shop + token : </h1>' .  "\n";
            print_r($config);
            echo '<p> ------------------------  </p>' .  "\n";


      PHPShopify\ShopifySDK::config($config);
      $shopify = new PHPShopify\ShopifySDK;

      $productID = $_GET['p_id'];
      $variantID = $_GET['v_id'];
      $product =  $shopify->Product($productID)->get();
      $variantOrigin = $shopify->Product($productID)->Variant($variantID)->get();
      echo "<pre>";
        print_r( $product );
      echo "</pre>";


      $productAdded = array(
          "title" => "Burton Custom Freestlye 151",
          "body_html" => "<strong>Good snowboard!<\/strong>",
          "vendor" => "Burton",
          "product_type" => "Snowboard",
          );
      $newAddedProduct = $shopify->Product->post($productAdded);
      echo "<pre>";
        print_r( $newAddedProduct );
      echo "</pre>";
echo '<p> ----------------------------------------  </p>' .  "\n";
      $variantAdded = array(
            "option1" =>  "4",
            "option2" =>  "bbbb",
            "option3" =>  "bundle sales",
            "price"   =>  "1999.00",
      );


     $productInfo = $shopify->Product($productID)->Variant->post($variantAdded);

      echo "<pre>";
        print_r( $productInfo );
      echo "</pre>";

      echo '<p> ----------- Next line shows added variant info -------------  </p>' .  "\n";
      // $productInfoOrigin
      //Get variants of a product (using Child resource)
      $products = $shopify->Product($productID)->Variant->get();

    }
//===================Deal with Collection Bundle Sales=====================

?>
