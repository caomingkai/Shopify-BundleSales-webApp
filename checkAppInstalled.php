
<?php

    // Read token for shop, check if it is already install this app
    // File Format:  "shopUrl_1,accessToken_1 /n shopUrl_2,accessToken_2 /n "
    $installed_flag = false;
    $file = './install/merchantToken.txt';
    $lines = explode("\n", file_get_contents($file));

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
      }else{
        $installed_flag = false;
      }
    }

?>
