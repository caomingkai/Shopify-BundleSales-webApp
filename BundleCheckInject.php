<?php
    session_start();
    require_once __DIR__ . '/vendor/autoload.php';

    $config = $_SESSION["config"];
    PHPShopify\ShopifySDK::config($config);
    $shopify = new PHPShopify\ShopifySDK;

//-----## 0 ## find out the main Theme.liquid, and its ID
    $themes = $shopify->Theme->get();
    $numsOfThemes = count($theme);
    $themeID = 0;
    foreach( $themes as $oneTheme ){
      if($oneTheme['role'] === 'main'){
        $themeID = $oneTheme['id'];
        break;
      }
    }

//-----## 1 ## insert bundleCheck.liquid to shopify admin Snippet
    // the code snippet to be inserted
    $codeInject = '<script>
                        {% assign flag = false %}
                        {% for item in cart.items %}
                                  {% if item.variant.id == 37696175173 %}
                                {% assign flag = true %}
                                  {% endif %}
                        {% endfor %}

                        {% if flag==true %}
                              document.getElementById("test").innerHTML = "has product: a,1";
                        {% else %}
                              document.getElementById("test").innerHTML = "dont hava product: a,1";
                        {% endif %}
                          function addToCart() {
                              var xhttp = new XMLHttpRequest();
                              xhttp.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                  document.getElementById("test").innerHTML =
                                    "ADD e SUCCEED!";
                                }
                              };
                              xhttp.open("POST", "/cart/add", true);
                              xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                              xhttp.send("id=37144461061&add=");
                          }

                  </script>';

//-----## 1.1 ## updated code snippet to be inserted
// 1. loop through cart items, to create product hashmap, store info in shop.metafields.cartHashmap, with key(variantID) / quantity pairs
//    when we access it, we could use a loop
// 2. read shop.metafields.bundleInfo.bundleNum / shop.metafields.bundleInfo.bundleDetail
// 3. bundleDetail: 46#4212:0.4,3438:0.8,9842:0.2
//                  47#1232:0.3,3294:0.5,3843,0.8
//
$codeInject = '<script>
//--  ## 0 ## when this liquid file requested again by "location.reload()", we will check cart.item to check if there exist shadow product -->
//--   if there exists, copy cart.item, change shadow variants to origin variants, and create my own data structure for next steps -->
//--   Also, record beginning cart data cartBeginData. After get the cartFinalData from cartOriginData, compare cartFinalData with cartBeginData to see   -->
//--   if the cartFinalData are the same with cartBeginData. If YES, no AJAX call; if NO,  call AJAX   -->

                    {% assign cartBeginData  = "" %}
                    {% assign cartFinalData  = "" %}
                    {% assign cartOriginData = "" %}
                    {% for item in cart.items %}
                        {% assign itemOriginV = item.variant.metafields[0].shadowToOrigin[item.variant_id]  %}
                        {% if itemOriginV != 0  %}
                            {% assign cartOriginData = itemOriginV      %}
                        {% else %}
                            {% assign cartOriginData = item.variant_id  %}
                        {% endif %}
                        {% assign cartOriginData = cartOriginData | append: item.product_id | append: item.quantity | append: "\n" %}
                        {% assign cartBeginData = item.variant_id | append: item.quantity | append: "\n" %}
                    {%endfor%}

//--  ## 1 ## split cartOriginData into array -->
                    {% assign cartOriginArr = cartOriginData | strip | split: "\n"  %}

//--  ## 2 ## bundle is one bundle_item containing info -->
                    {% for bundle in Bundles %}
                        {% assign BDID = bundle | first %}
                        {% assign BD = bundle | last %}
                        {% assign BDArray = BD | split:","  %}
                        {% assign min = 1000000 %}

//--  ## 3 ## BDOne is one pair of originProductID:discount  -->
                        {% for BDOne in BDArray %}
                            {% assign BDOnePair = BDOne | split:":"   %}
                            {% assign BDOneProductId = BDOnePair | first  %}
                            {% assign cnt = 0 %}

//--  ## 4 ## find out if there exists such bundle pattern in cart depending on cnt?=0 , meanwhile, record quantity of this combo -->
                            {% for itemStr in cartOriginArr  %}
                                {% assign itemArr = itemStr | split: ":"  %}
                                {% if itemArr[1] ==  BDOneProductId  %}
                                    {% assign cnt = cnt | plus: itemArr[2]  %}
                                {% endif %}
                            {%endfor%}
                            {% if cnt == 0  %}
                                {% break %}
                            {% else %}
                                {% if cnt < min  %}
                                    {% assign min = cnt %}
                                {% endif %}
                            {% endif %}
                        {%endfor%}

//--  ## 5 ## if there exist such bundle, delete origin variant in cartOriginArr, record added shadow variant in shadowFinalData  -->
//--  Meanwhile, record origin variant after deletion in originFinalData. Then combine shadowFinalData and originFinalData into cartFinalData  -->
                        {% if min != 1000000 %}
                            {% assign shadowFinalData = ""  %}
                            {% assign originFinalData = ""  %}
                            {% for BDOne in BDArray %}
                                {% assign minTemp = min %}
                                {% assign BDOnePair = BDOne | split:":"   %}
                                {% assign BDOneProductId = BDOnePair | first  %}
                                {% for itemStr in cartOriginArr  %}
                                    {% assign itemArr = itemStr | split: ":"  %}
                                    {% if itemArr[1] ==  BDOneProductId  %}

//--  ## 6 ##  find corresponding shadow variant for origin variant, based on bundleID  -->
                                        {% assign originToShadowArr = shop.metafields.originToShadow[itemArr[0]] | split: ","  %}
                                        {% for OTSStr in originToShadowArr  %}
                                              {% assign OTSPair = OTSStr | split: ":"  %}
                                              {% if BDID == OTSPair[0]  %}
                                                  {% assign shadowVarID = OTSPair[1]  %}
                                              {% endif %}
                                        {%endfor%}

//--  ## 7 ##  delete  origin variant in cartOriginArr, based on the found shadowID, record updated variant in originFinalData and added shadow variant in shadowFinalData  -->
                                        {% assign cartItemIndex = forloop.index0  %}
                                        {% assign cartOriginDataTemp = ""  %}
                                        {% if itemArr[2] < minTemp  %}

//--  ## 8 ##  update cartOriginArr, since there are origin deleted, and the quantity are now changed -->
                                            {% for itemStr in cartOriginArr  %}
                                                {% if forloop.index0 != cartItemIndex  %}
                                                    {% assign cartOriginDataTemp = cartOriginDataTemp | append: itemStr | append:"\n" %}
                                                {% endif %}
                                            {%endfor%}
                                            {% assign cartOriginArr = cartOriginDataTemp | strip | split:"\n" %}

                                            {% assign originFinalData = originFinalData | append: itemArr[0]  | append: 0          | append: "\n" %}
                                            {% assign shadowFinalData = shadowFinalData | append: shadowVarID | append: itemArr[2] | append: "\n" %}
                                            {% assign minTemp = minTemp | minus: itemArr[2] %}
                                        {% else %}
                                            {% assign quantityNew = itemArr[2] | minus: minTemp  %}
//--  ## 8 ##  update cartOriginArr, since there are origin deleted, and the quantity are now changed -->
                                            {% for itemStr in cartOriginArr  %}
                                                {% if forloop.index0 != cartItemIndex  %}
                                                    {% assign cartOriginDataTemp = cartOriginDataTemp | append: itemStr | append:"\n" %}
                                                {% else %}
                                                    {% assign itemStrChanged = itemArr[0] | append : itemArr[1] | append: quantityNew %}
                                                    {% assign cartOriginDataTemp = cartOriginDataTemp | append: itemStrChanged | append:"\n" %}
                                                {% endif %}
                                            {%endfor%}
                                            {% assign cartOriginArr = cartOriginDataTemp | strip | split:"\n" %}

                                            {% assign originFinalData = originFinalData | append: itemArr[0]  | append: quantityNew | append: "\n" %}
                                            {% assign shadowFinalData = shadowFinalData | append: shadowVarID | append: minTemp     | append: "\n" %}
                                        {% endif %}
                                    {% endif %}
                                {%endfor%}
                            {%endfor%}
//--  ## 9 ##  此处：将shadowFinalData与originFinalData合成cartFinalData（quantity==0的舍弃） -->
                            {% assign cartFinalData = cartFinalData | append: originFinalData | append: shadowFinalData %}
                        {% endif %}

                    {%endfor%}
//--  ## 10 ##  compare "cartBeginData" and "cartFinalData", make delete/add AJAX call based on comparason result -->
                    {% if cartBeginData != cartFinalData %}
                        {% assign cartBeginArr = cartBeginData | strip | split:"\n" %}
                        {% assign cartFinalArr = cartFinalData | strip | split:"\n" %}

//--  ## 11 ##  find element in cartBeginArr but not in cartFinalArr : these are origin variants to be deleted -->
                        {% assign toBeDeleted = "" %}
                        {% for cartBeginItem in cartBeginArr %}
                            {% assign flag = false %}
                            {% for cartFinalItem in cartFinalArr %}
                                {% if cartFinalItem == cartBeginItem %}
                                    {% assign flag = true %}
                                {% endif %}
                            {% endfor %}

                            {% if flag == false %}
                                {% assign toBeDeleted = toBeDeleted | append: cartBeginItem | append:"\n" %}
                            {% endif %}
                        {% endfor %}

//--  ## 12 ##  find element in cartFinalArr but not in cartBeginArr : these are shadow variants to be added -->
                        {% assign toBeAdded = "" %}
                        {% for cartFinalItem in cartFinalArr %}
                            {% assign flag = false %}
                            {% for cartBeginItem in cartBeginArr %}
                                {% if cartBeginItem == cartFinalItem %}
                                    {% assign flag = true %}
                                {% endif %}
                            {% endfor %}

                            {% if flag == false %}
                                {% assign toBeAdded = toBeAdded | append: cartFinalItem | append:"\n" %}
                            {% endif %}
                        {% endfor %}
                        {% assign toBeDeletedArr = toBeDeleted | strip | split:"\n" %}
                        {% assign toBeAddedArr   = toBeAdded   | strip | split:"\n" %}

//--  ## 13 ##  Make AJAX call to delete/add arr, based on toBeDeletedArr/toBeAddedArr -->
                        {% for toBeDeleteItem in toBeDeletedArr %}
                            var xhttp = new XMLHttpRequest();
                            xhttp.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                    document.getElementById("test").innerHTML =
                                      "Delete SUCCEED!";
                                }
                            };
                            xhttp.open("POST", "/cart/update.js", false);
                            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xhttp.send("id={{toBeDeleteItem[0]}}&quantity=0");
                        {% endfor %}

                        {% for toBeAddedItem in toBeAddedArr %}
                            var xhttp = new XMLHttpRequest();
                            xhttp.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                    document.getElementById("test").innerHTML =
                                      "ADD SUCCEED!";
                                }
                            };
                            xhttp.open("POST", "/cart/add.js", false);
                            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xhttp.send("id={{toBeAddedItem[0]}}&quantity={{toBeAddedItem[1]}}");
                        {% endfor %}

                    {% else %}
                        alert("No change, So no need to call AJAX!");
                    {% endif %}

              </script>';

//-------------------------------------------------------------------------------
    $para = array(
      "key" => "snippets/bundleCheck.liquid",
      "value" => $codeInject
    );
    $bundleCheckSnippet = $shopify->Theme($themeID)->Asset->put($para) ;



//-----## 2  ## insert the statement: {% include "bundleCheck" %} to "theme.liquid" file  -->
    // find out theme.liquid, based on themeID already got
    $para = array(
      "asset[key]" => "layout/theme.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
    );
    $themeContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;
    // find out <head> tag, in order to insert into this statement: "{% include 'bundleCheck' %}"
    $posOfHead = strpos( $themeContent, '<head>');
    $statementInject = "\n" . "  {% include 'bundleCheck' %}";
    $themeContentNew = substr_replace( $themeContent , $statementInject,  $posOfHead+6, 0 );
    // insert statement  "{% include 'bundleCheck' %}", right after <head> tag
    $para = array(
      "key" => "layout/theme.liquid",
      "value" => $themeContentNew
    );
    $themeContentNew = $shopify->Theme($themeID)->Asset->put($para) ;

                                                echo '<h1>$shopify below : </h1>' .  "\n";
                                                echo "<pre>";
                                                print_r ($themeContentNew);
                                                echo "</pre>";
                                                echo '<p> ------------------------  </p>' .  "\n";


?>
