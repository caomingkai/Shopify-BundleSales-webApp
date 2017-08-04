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
<!-- ##1## Existing Bundle display area------------------------------------->
        <div id="existingBundle">
          Existing Bundle Display Area
            <!--php read shopBundle.txt to find if exists bundle, show it-->
            <!--If exist, show it-->
            <!--If NO, it is after 'submit' clicked, the file is created -->
        </div>

<!-- ##2## items selection hidden window------------------------------------>
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
    $collections = $shopify->CustomCollection->get();
    $products = $shopify->Product->get();

    $_SESSION["config"] = $config;
    $_SESSION["collections"] = $collections;
    $_SESSION["products"] = $products;

    // set collection "all", in order to exclude those shadow products
    // 1. GET both CustomCollection and SmartCollection
    // 2. find out the 'all' selection, which handle=='all',
    // 3. Modify it. Add a condition: vendor not_equals 'Products On Sales'
    // 3.1 try to see if condition: tag not_equals 'salesBy3rd' work?
    // 4. $products = $shopify->Product()->get();
    $collectionSet = array(
      "title" => "all",
      "rules" => array(
        array(
          "column" => "vendor",
          "relation" => "equals",
          "condition" => "Cult Products"
        )
      ),
    );
    // check if {shopUrl}ProductInfo.txt exists
    $fileName = $_SESSION["shopUrl"] . "ProductInfo.txt";
    if( !file_exists( $fileName ) ){//create {shopUrl}productInfo.txt
      foreach( $products as $p ){
        $info .= $p["id"] . "," . $p["title"] . "," . $p["image"]["src"] . "\n";
        file_put_contents($fileName, $info, LOCK_EX);
      }
    }else{      // read from productInfo.txt
      $file = "productInfo.txt";
      $infoAll = file_get_contents($fileName);
      echo "<pre>";
      echo $infoAll;
      echo "</pre>";
    }



//-----------------------Outter wrapper table------------------------
    echo '<table><tr>';
//------------------ ##2## This is for Product Bunlde Selection-------------
    echo '<td>';
        echo '<form action="addBundle.php", method="get">';
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
                    <td><input id="product'.$k.'" onchange="check(event)" type="checkbox" name="productItem[]" value="'.$p['id'].'">' .$p['title'].'<br>'.'</td>
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
        echo '<form action="addBundle.php", method="get">';
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
            echo "<pre>";
            print_r($shopify);;
            echo "</pre>";
          ?>
            This is bottom
        </h1>

        <script>
        <?php
          echo "function check(event) {"."\n";
            echo "if( event.target.nextElementSibling.nextElementSibling == null ){"."\n";
              echo "if( event.target.checked ){ "."\n";
                echo 'x = document.createElement("input");'."\n";
                echo 'x.setAttribute("type", "text");'."\n";
                echo 'x.setAttribute("class", "productDiscount");'."\n";
                echo 'x.setAttribute("name", "productDiscount[]");'."\n";
                echo 'x.setAttribute("value", "1");'."\n";
                echo 'event.target.closest("td").appendChild(x);'."\n";
              echo "}"."\n";
            echo "}else{"."\n";
              echo "if( !event.target.checked ){ "."\n";
                echo 'checkboxParent = event.target.closest("td");'."\n";
                echo 'checkboxParent.removeChild(checkboxParent.lastChild);'."\n";
              echo "}"."\n";
            echo "}"."\n";
          echo '}'."\n";


          echo "function validCheck() {"."\n";

          echo '}'."\n";
        ?>
        </script>
    </body>
</html>
