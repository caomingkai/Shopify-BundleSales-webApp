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
  require_once __DIR__ . '/../webhookFun.php';

//####################### global variants########################################
    $installed_flag = false;
    // $appUrl = "http://testphp-env.us-west-1.elasticbeanstalk.com/";
    $appUrl = "https://9a6e602f.ngrok.io/Shopify/3rdapp_public/";
    $apiKey = "cce15a09be6e4a0525ba0f0b0ca14341";
    $secretKey = "d999981624124eb6b1a902a063a9e8ea";
    $shopUrl = "";
    $accessToken = "";
//############ read accessToken from 'merchantToken.txt'########################

    require_once __DIR__ . '/../connectSql.php';

    $sqlToken = "SELECT domain, token FROM shopToken";
    $result = $conn->query($sqlToken);
    $merchantHash = array();

    // In cases the query failed.
    if(!$result){
        echo "Query: " . $sqlToken . "\n";
        echo "Errno: " . $conn->errno . "\n";
        echo "Error: " . $conn->error . "\n";
        echo "<h1>Cannot access to 'shopToken' table </h1> " . "\n";
    }else{
        // Query succeeded, get the  content in it
        if ($result->num_rows > 0) {
          while( $row = $result->fetch_assoc() ) {
              $merchantHash[$row['domain']] = $row['token'];
          }
        }
    }
    $result->free();
    $conn->close();
//############### check if app is installed on this shop #############################

    if(isset( $_GET['shop']) ){
      $shopUrl = $_GET['shop'];
      if( array_key_exists($shopUrl ,$merchantHash) ){
        $installed_flag = true;
        $accessToken = $merchantHash[$shopUrl];;
      }else{
        $installed_flag = false;
      }
    }

//############### Installation Phase OR normal Operation Phase #############################

    //=============already installed , directly manipulate shop object==========
    if( $installed_flag ){
        // echo "true";
        $_SESSION["accessToken"] = $accessToken;
        $_SESSION["shopUrl"] = $_GET['shop'];

        header("Location: /Shopify/3rdapp_public/index.php");
        exit();
    //==============haven't install, have to install first =====================
    }else{
        // echo "false";
        // Installation Step 1: askForAuthorization
        // when merchant click "get", it is the APP URL in shopify partner panel that direct merchant to this script.
        // we need to redirect merchant to the oauth page to let merchant to grant authorization to the app.
        if( isset($_GET['shop'])  && !isset($_GET['code']) ){
            $shopUrl = $_GET['shop'];
            $scopes = "read_products,write_products,read_themes,write_themes,read_checkouts, write_checkouts,read_orders, write_orders";

            $installUrl = "https://".$shopUrl."/admin/oauth/authorize?client_id=".$apiKey."&scope=".$scopes."&redirect_uri=".$appUrl."install/install.php";
            header("Location: $installUrl");
            exit();
        }

        // Installation Step 2: getAccessToken
        // after merchant grant anthorization, and click "install" button
        if( isset($_GET['shop'])  && isset($_GET['code']) ){
            $shopUrl = $_GET['shop'];
            // config for $accessToken
            $config = array(
                'ShopUrl' => $shopUrl,
                'ApiKey' => $apiKey,
                'SharedSecret' => $secretKey,
            );
            PHPShopify\ShopifySDK::config($config);
            $accessToken = \PHPShopify\AuthHelper::getAccessToken();

            // config for $shopify
            $config = array(
                  'ShopUrl' => $shopUrl,
                  'AccessToken' => $accessToken,
            );
            PHPShopify\ShopifySDK::config($config);
            $shopify = new PHPShopify\ShopifySDK;
            $shop = $shopify->Shop->get();
            $shopId = $shop['id'];
            $_SESSION["shopId"] = $shopId;

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sqlTokenInsert =  "INSERT INTO shopToken ".
                               "(domain,token,shopId)"."VALUES ".
                               "('$shopUrl','$accessToken','$shopId')";;

            if( !$result = $conn->query($sqlTokenInsert) ){
              // echo "<h1> Cannot add current shopToken into 'shopToken' table </h1> " . "\n";
            }
            $conn->close();

            $_SESSION["accessToken"] = $accessToken;
            $_SESSION["shopUrl"] = $shopUrl;

            //========================== set up webhook=========================

            setwebhook($shopify);
            //=====================insert bundleCheck snippet===================
            require_once __DIR__ . '/../BundleCheckInject.php';
            header("Location: /Shopify/3rdapp_public/index.php");
            exit();
        }
    }




?>

<!--
    </body>
</html> -->
