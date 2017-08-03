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
    // Get all product list (GET request)
    $collections = $shopify->CustomCollection->get();

    $products = $shopify->Product->get();
    $_SESSION["collections"] = $collections;
    $_SESSION["products"] = $products;


//-----------------------Outter wrapper table------------------------
    echo '<table><tr>';
//------------------This is for Product Bunlde Selection-------------
    echo '<td>';
        echo '<form action="restCall.php", method="get">';
        echo '<fieldset><legend>Product Bundle:</legend>';
        echo '<table border="1", border-collapse="collapse"; >';
        echo '  <tr><th>Select</th>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Image</th>
                </tr>';
        $itemNum = 0;
        foreach($products as $k => $p){
          $itemNum = $k;
          echo '<tr>
                    <td><input id="product'.$k.'" onchange="check()" type="checkbox" name="productItem[]" value="'.$p['id'].'">' .$p['title'].'
                        <br>
                        <div id="discountP'.$k.'"style="display:none" >
                            <samll> percentage discount</samll>
                            <input type="text" name="disount[]>
                        </div>"  </td>
                    <td>' .$p['id']. '</td>
                    <td>' .$p['title']. '</td>
                    <td><img src=" ' .$p['image']['src']. ' "; style="width:128px;height:128px;"></td>
                </tr>';
        }
        echo '</table>';
        echo '<input type="submit" value="Submit"> ';
        echo '</fieldset>';
        echo '</form>';
    echo '</td>';

//------------------This is deliberately left empty  ------------------
    echo '<td>';
        echo '<div class="empty"> </div>';
    echo '</td>';

//------------------This is for Collection Bunlde Selection-------------
    echo '<td>';
        echo '<form action="restCall.php", method="get">';
        echo '<fieldset><legend>Collection Bundle:</legend>';
        echo '<table border="1">';
        echo '  <tr><th>Select</th>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Image</th>
                </tr>';
        foreach($collections as $p){
          echo '<tr>
                    <td><input type="checkbox" name="collectionItem[]" value="'.$p['id'].'">' .$p['title'].'</td>
                    <td>' .$p['id']. '</td>
                    <td>' .$p['title']. '</td>
                    <td><img src=" ' .$p['image']['src']. ' "; style="width:128px;height:128px;"></td>
                </tr>';
        }
        echo '</table>';
        echo '<input type="submit" value="Submit"> ';
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

//--------------------- display $collection obj---------------------
    echo '<h1>2.  $collections below : </h1>' . "\n";
    echo "<pre>";
    print_r($collections);
    echo "</pre>";
    echo '<p> ------------------------  </p>' .  "\n";
    echo "</pre>";

//--------------------- display $collection obj---------------------
    echo '<h1>3.  $products below : </h1>' . "\n";
    echo "<pre>";
    print_r($products);
    echo "</pre>";
    echo '<p> ------------------------  </p>' .  "\n";


?>

        <h1>
          <?php
            echo $itemNum;
          ?>
            This is bottom
        </h1>

        <script>
        <?php
          echo "function check() {"."\n";
              echo 'for( $k=0; $k<'.$itemNum.'; $k++){'."\n";
                  echo 'if(document.getElementById("product'.$k. '").checked){'."\n";
                      echo 'document.getElementById("discountP'.$k. '").style.display = "inline";'."\n";
                  echo '}else{'."\n";
                      echo 'document.getElementById("discountP'.$k. '").style.display = "none";'."\n";
                  echo '}'."\n";
              echo '}'."\n";
          echo '}'."\n";
        ?>
        </script>
    </body>
</html>
