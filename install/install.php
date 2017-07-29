<?php
  /*----------------------------------------------------------------------------
  Note:::
  1. before the header("Location: ..."), there should not be output before it.
  2. that is to say, NO 'echo', NO <html> </html>
  3. most Important: Even NO whitespace before '<?php'
  ----------------------------------------------------------------------------*/

  // MUST be the very first thing in your document. Before any HTML tags.
  session_start();
  require_once __DIR__ . '/../vendor/autoload.php';

//####################### global variants########################################
    $installed_flag = false;
    // $appUrl = "http://testphp-env.us-west-1.elasticbeanstalk.com/";
    $appUrl = "http://d5498b14.ngrok.io/Shopify/3rdapp_public/";
    $apiKey = "cce15a09be6e4a0525ba0f0b0ca14341";
    $secretKey = "d999981624124eb6b1a902a063a9e8ea";
    $shopUrl = " ";
    $accessToken = " ";

//############ read accessToken from 'merchantToken.txt'########################

    // Read token for shop, check if it is already install this app
    // File Format:  "shopUrl_1,accessToken_1 /n shopUrl_2,accessToken_2 /n "
    $file = 'merchantToken.txt';
    $lines = explode("\n", file_get_contents($file));

          // echo "lines below "."\n";
          // var_dump($lines);
          // echo '<p> ------------------------  </p>' .  "\n";

    $merchantHash = array();
    forEach($lines as $oneLine ){
      $keyValueArray = explode(",", $oneLine );
      $merchantHash[$keyValueArray[0]] = $keyValueArray[1];
    }
    if(isset( $_GET['shop']) ){

      $shopUrl = $_GET['shop'];
      if( array_key_exists($shopUrl ,$merchantHash) ){
        $installed_flag = true;
        $accessToken = $merchantHash[$shopUrl];

              // echo '<p>  Step 1(1): read accessToken from merchantToken.txt && $accessToken exists </p>' .  "\n";
              // echo "<p>shopUrl below:</p>"."\n";
              // var_dump($shopUrl);
              // echo "<p>accessToken below:</p>"."\n";
              // var_dump($accessToken);
              // echo '<p> ------------------------  </p>' .  "\n";
      }else{
        $installed_flag = false;
              // echo '<p>  Step 1(0): read accessToken from merchantToken.txt && NO $accessToken exists </p>' .  "\n";
              // var_dump($shopUrl);
              // echo '<p> ------------------------  </p>' .  "\n";
      }
    }


//############### Installation OR normal Operation #############################

    //=============already installed , directly manipulate shop object==========
    if( $installed_flag ){

      $_SESSION["accessToken"] = $accessToken;
      $_SESSION["shopUrl"] = $_GET['shop'];
      header("Location: /Shopify/3rdapp_public/index.php");
      exit();
    //==============haven't install, have to install first =====================
    }else{
      // ------------- Installation Step 1: askForAuthorization-----------------
      // when merchant click "get", it is the APP URL in shopify partner panel that direct merchant to this script.
      // we need to redirect merchant to the oauth page to let merchant to grant authorization to the app.
      if( isset($_GET['shop'])  && !isset($_GET['code']) ){
          $shopUrl = $_GET['shop'];
          $scopes = "read_orders,read_products,write_products";

          $instalUrl = "https://".$shopUrl."/admin/oauth/authorize?client_id=".$apiKey."&scope=".$scopes."&redirect_uri=".$appUrl."install/install.php";
          header("Location: $instalUrl");
          exit();
      }

      // ------------- Installation Step 2: getAccessToken----------------------
      // after merchant grant anthorization, and click "install" button
      if( isset($_GET['shop'])  && isset($_GET['code']) ){

          $config = array(
              'ShopUrl' => $_GET['shop'],
              'ApiKey' => $apiKey,
              'SharedSecret' => $secretKey,
          );
          PHPShopify\ShopifySDK::config($config);
          $accessToken = \PHPShopify\AuthHelper::getAccessToken();

          $current = file_get_contents($file);
          $current .= $_GET['shop']. "," .$accessToken."\n";
          file_put_contents($file, $current,LOCK_EX);

                // echo '<h1> Step 2(0): have not installed this app </h1>' .  "\n";
                // echo "<h1> -----upper border----- </h1>"."\n";
                // echo "<h1> this should be accessToken: " . $accessToken . " ,shound not be blank</h1>"."\n";
                // echo "<h1> -----middle border----- </h1>"."\n";
                // echo "<h1> this should be current text content: " . $current . " ,shound not be blank</h1>"."\n";
                // echo "<h1> -----lower border----- </h1>"."\n";

          // store $accessToken as global varible, for main php page use
          $_SESSION["accessToken"] = $accessToken;
          $_SESSION["shopUrl"] = $_GET['shop'];

          header("Location: /Shopify/3rdapp_public/index.php");
          exit();

      }
    }


?>

<!--
    </body>
</html> -->
