<?php
  // MUST be the very first thing in your document. Before any HTML tags.
  session_start();
?>

<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<html>
    <head>
        <style>
          .empty { width:50px; }
        </style>
        <title> php-shopify </title>
    </head>
    <body>
        <h1>
            TEST FOR SHOPIFY WITH PHP
        </h1>
<!-- ## 1 ## Existing Bundle display area------------------------------------->
        <div id="existingBundle">
          Existing Bundle Display Area
            <!--php read shopBundle.txt to find if exists bundle, show it-->
            <!--If exist, show it-->
            <!--If NO, it is after 'submit' clicked, the file is created -->
        </div>

<!-- ## 2 ## items selection hidden window------------------------------------>
        <div id="productInfo" style="display:none">
          Item/Discount Select Area
          <!-- need a non-hidden item/discount box pair to give merchants first input -->
          <!-- later, when the 'add pairs' clicked, the next hidden pairs show up -->
          <!-- format: clickable img, title, price -->
          <!-- when merchant put cursor in the input box, the modal window show up -->
          <!-- then he can choose items in the window, also in the window, there is a 'ok' button -->
          <!-- when 'ok' clicked, it send selected items to the input box -->
        </div>
<?php

    require_once __DIR__ . '/vendor/autoload.php';

    $config = array(
          'ShopUrl' => $_SESSION["shopUrl"],
          'AccessToken' => $_SESSION["accessToken"],
    );

          echo '<h1>0. shop + token : </h1>' .  "\n";
          print_r($config);
          echo '<p> ------------------------  </p>' .  "\n";


    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;

    //////////////////////
    $originP = 8672070661;
    $productInfo = $shopify->Product($originP)->get();
    $fileName = 'function_updateinventory_shadowVToOriginPV000000.txt';
    file_put_contents($fileName, print_r($productInfo,true), LOCK_EX);
    //////////////////////
    //////////////////////
// $originP = 8672070661;
// $originV = 37696175173;
// $shadowQty = 2;
//
// $para = array( "inventory_quantity_adjustment" => -$shadowQty );
//
// $variantMetafield = $shopify->Product($originP)->Variant($originV)->put($para);
    //////////////////////
    //////////////////////
    //////////////////////

    //-------------  get customCollection and smartCollection -----------------
    $customCollection = $shopify->CustomCollection->get();
    $smartCollection = $shopify->SmartCollection->get();
    $collections = array_merge($customCollection, $smartCollection);
    $_SESSION["config"] = $config;
    $_SESSION["collections"] = $collections;
                                    // echo "<pre>";
                                    // echo '<h1>customCollection:</h1>'."\n";
                                    // print_r($customCollection);
                                    // echo "</pre>";
                                    //
                                    // echo "<pre>";
                                    // echo '<h1>smartCollection:</h1>'."\n";
                                    // print_r($smartCollection);
                                    // echo "</pre>";
    // check if exists all in smartCollection
    $numOfSmartC = sizeof($smartCollection);
    $all_ExistFlag = false;
    $all_CollectionID = "";
    for( $i = 0; $i < $numOfSmartC; $i++ ){
      if( $smartCollection[$i]['handle'] === 'all' ){
        $all_ExistFlag = true;
        $all_collectionID = $smartCollection[$i]['id'];
        break;
      }
    }

    // remove shadow product from 'all' collection
    if( $all_ExistFlag ){
      $updateInfo = array(
        "published_scope" => "global",
        "rules" => array(
          array(
            "column" => "vendor",
            "relation" => "not_equals",
            "condition" => "Products On Sales"
          )
        ),
      );
      $all_update = $shopify->SmartCollection($all_collectionID)->put($updateInfo);
    }else{
      $all_Info = array(
        "title" => "all",
        "published_scope" => "global",
        "rules" => array(
          array(
            "column" => "vendor",
            "relation" => "not_equals",
            "condition" => "Products On Sales"
          )
        ),
      );
      $all_create = $shopify->SmartCollection($all_CollectionID)->post($all_Info);
      $all_collectionID = $all_create['id'];
    }


    // get 'original' products with query string 'collectionId = all'
    $params = array(
      'collection_id' => $all_collectionID,
    );
    $products = $shopify->Product->get($params);

    // set collection "all", in order to exclude those shadow products
    // 1. GET both CustomCollection and SmartCollection
    // 2. find out the 'all' selection, which handle=='all',
    // 3. Modify it. Add a condition: vendor not_equals 'Products On Sales'
    // 4. $products = $shopify->Product()->get();

    // check if {shopUrl}ProductInfo.txt exists
    $fileName = $_SESSION["shopUrl"] . "ProductInfo.txt";
    $info = "";
    foreach( $products as $p ){
      $info .= $p["id"] . "," . $p["handle"] . "," . $p["title"] . "," . $p["image"]["src"] . "\n";
    }

    foreach( $collections as $c ){
      $info .= $c["id"] . "," . $c["handle"] . "," . $c["title"] . "," . $c["image"]["src"] . "\n";
    }
    file_put_contents($fileName, $info, LOCK_EX);
    file_put_contents($fileName, $info, LOCK_EX);
    $_SESSION["productInfo"] = file_get_contents($fileName);

//-----------------------Existing bundle Info--------------------------------
//---------------------------------------------------------------------------
    echo '<table border="1", border-collapse="collapse";>
            <tr>'."\n";
    echo '    <th>Weight</th><th>bundle Type</th><th>discount Type</th><th>bundle ID</th><th>bundle Detail</th>'."\n";
    echo '  </tr>'."\n";

    $fileName = $_SESSION["shopUrl"] . "ShopBundle.txt";
    if( file_exists( $fileName ) ){//create {shopUrl}productInfo.txt
      $bundleInfoAll = file_get_contents($fileName);
      $bundleInfoArr = explode("\n" , trim($bundleInfoAll) );
      $num = count($bundleInfoArr);
      if( $num > 0 ){
        foreach( $bundleInfoArr as $bundle ){
          $bundleWeight = explode("*" , $bundle )[0];
          $left_1 = explode("*" , $bundle )[1];
          $bundleType = explode("&" , $left_1 )[0];
          $left_2 = explode("&" , $left_1 )[1];
          $discountType = explode("@" , $left_2 )[0];
          $left_3 = explode("@" , $left_2 )[1];
          $bundleId = explode("#" , $left_3 )[0];
          $bundleDetail = explode("#" , $left_3 )[1];
    echo '  <tr>'."\n";
    echo '    <td>'; echo $bundleWeight; echo'</td>'."\n";
    echo '    <td>'; echo $bundleType; echo'</td>'."\n";
    echo '    <td>'; echo $discountType; echo'</td>'."\n";
    echo '    <td>'; echo $bundleId; echo'</td>'."\n";
    echo '    <td>'; echo $bundleDetail; echo'</td>'."\n";
    echo '  </tr>'."\n";
        }
      }
    }
    echo '</table>'."\n";

//-----------------------Outter wrapper table--------------------------------
//---------------------------------------------------------------------------
    echo '<table><tr>';
//------------------ ## 3 ## This is for Product Bunlde Selection-------------
    echo '<td>';
        echo '<form action="addBundle.php", method="get" target="_blank">';
        echo '<fieldset><legend>Product Bundle:</legend>';
        echo '<div>
                  <label> Weight: </label><input name="weight" type="number" min=0 max=10 value=1 />
              </div>';
        echo '<hr />';
        echo '<div>
                  <p> Bundle Type: </p>
                  <label> 0: </label><input name="bundleType" type="radio" value=0 checked />
              </div>';
        echo '<hr />';
        echo '<div>
                  <p> Discount Type: </p>
                  <label> 0: </label><input name="discountType" type="radio" value=0 checked />
                  <label> 1: </label><input name="discountType" type="radio" value=1  />
              </div>';

        echo '<table border="1", border-collapse="collapse"; >';
        echo '  <tr><th>Select</th>
                    <th>Parameters</th>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Image</th>
                </tr>';
        $itemNum = 0;
        foreach($products as $k => $p){
          $itemNum = $k;
          echo '<tr>
                    <td><input id="product'.$k.'" onchange="check(event)" type="checkbox" name="productItem[]" value="'.$p['id'].'">' .$p['title'].'<br>'.'</td>
                    <td></td>
                    <td>' .$p['id']. '</td>
                    <td>' .$p['title']. '</td>
                    <td><img src=" ' .$p['image']['src']. ' "; style="width:52px;height:52px;"></td>
                </tr>';
        }
        echo '</table>';
        echo '<input type="submit" value="Submit"> ';
        echo '</fieldset>';
        echo '</form>';
    echo '</td>';

    //This is deliberately left empty
    echo '<td>';
        echo '<div class="empty"> </div>';
    echo '</td>';

//------------------## 4 ## This is for Collection Bunlde Selection-------------
    echo '<td>';
        echo '<form action="addBundle.php", method="get" target="_blank">';
        echo '<fieldset><legend>Collection Bundle:</legend>';
        echo '<div>
                  <label> Weight: </label><input name="weight" type="number" min=0 max=10 value=1 />
              </div>';
        echo '<hr />';
        echo '<div>
                  <p> Bundle Type: </p>
                  <label> 1: </label><input name="bundleType" type="radio" value=1 checked />
              </div>';
        echo '<hr />';
        echo '<div>
                  <p> Discount Type: </p>
                  <label> 0: </label><input name="discountType" type="radio" value=0 checked />
                  <label> 1: </label><input name="discountType" type="radio" value=1  />
              </div>';
        echo '<table border="1">';
        echo '  <tr><th>Select</th>
                    <th>Parameters</th>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Image</th>
                </tr>';
        foreach($collections as $k=>$c){
          if( $c['title'] !== 'all' ){ // not show the 'all' collection, no meaninng.
            echo '<tr>
                      <td><input id="collection'.$k.'" onchange="check(event)" type="checkbox" name="productItem[]" value="'.$c['id'].'">' .$c['title'].'<br>'.'</td>
                      <td></td>
                      <td>' .$c['id']. '</td>
                      <td>' .$c['title']. '</td>
                      <td><img src=" ' .$c['image']['src']. ' "; style="width:52px;height:52px;"></td>
                  </tr>';
          }
        }
        echo '</table>';
        echo '<input type="submit" onclick="validCheck()" value="Submit"> ';
        echo '</fieldset>';
        echo '</form>';
    echo '</td>';
    echo '</tr></table>';


                            //--------------------- display shop obj---------------------------
                                echo '<h1>1.  $shopify below : </h1>' .  "\n";
                                echo "<pre>";
                                print_r($shopify->Metafield);
                                echo "</pre>";
                                echo '<p> ------------------------  </p>' .  "\n";

                            //--------------------- display $collections obj---------------------
                                echo '<h1>2.  $collections below : </h1>' . "\n";
                                echo "<pre>";
                                print_r($collections);
                                echo "</pre>";
                                echo '<p> ------------------------  </p>' .  "\n";
                                echo "</ pre>";

                            //--------------------- display $product obj---------------------
                                echo '<h1>3.  $products below : </h1>' . "\n";
                                echo "<pre>";
                                print_r($products);
                                echo "</pre>";
                                echo '<p> ------------------------  </p>' .  "\n";


?>

        <h1>
          <?php
            echo "<pre>";
            print_r($shopify);;
            echo "</pre>";
          ?>
            This is bottom
        </h1>

        // js function: when click checkbox, automatically add a input box; when unclick, remove the input box below it.
        <script>
        <?php
          echo "function check(event) {"."\n";
          echo '  secendTd= event.target.closest("td").nextElementSibling;'."\n";
          echo '  if( secendTd.childElementCount == 0 ){'."\n";
          echo "     if( event.target.checked ){ "."\n";
          // discount label
          echo '        x = document.createElement("LABEL");'."\n";
          echo '        text = document.createTextNode("Discount input:");'."\n";
          echo '        x.appendChild(text);'."\n";
          echo '        secendTd.appendChild(x);'."\n";
          // discount input
          echo '        x = document.createElement("input");'."\n";
          echo '        x.setAttribute("type", "txt");'."\n";
          echo '        x.setAttribute("name", "discount[]");'."\n";
          echo '        x.setAttribute("value", "0");'."\n";
          echo '        secendTd.appendChild(x);'."\n";
          echo "     }"."\n";

          echo "  }else{"."\n";
          echo "     if( !event.target.checked ){ "."\n";
          echo '        while (secendTd.firstChild) {'."\n";
          echo '            secendTd.removeChild(secendTd.firstChild);'."\n";
          echo "        }"."\n";
          echo "     }"."\n";
          echo "  }"."\n";
          echo '}'."\n";

          echo "function validCheck() {"."\n";
          echo '}'."\n";
        ?>
        </script>
    </body>
</html>
