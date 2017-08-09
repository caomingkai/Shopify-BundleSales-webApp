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
// 1. loop through cart items, to create product hashmap, store info in shop.metafield.cartHashmap, with key(variantID) / quantity pairs
//    when we access it, we could use a loop
// 2. read shop.metafield.bundleInfo.bundleNum / shop.metafield.bundleInfo.bundleDetail
// 3. bundleDetail: 46#4212:0.4,3438:0.8,9842:0.2
//                  47#1232:0.3,3294:0.5,3843,0.8
//
$codeInject = '<script>
                    {% assign BDItems = shop.metafield.bundleInfo.bundleDetail | split:"\n"  %}

<!-- ## 1 ## BDItem is one bundle item containing info -->
                    {% for BDItem in BDItems %}
                        {% assign BDID = BDItem | first %}
                        {% assign BD = BDItem | last %}
                        {% assign BDArray = BD | split:","  %}
                        {% assign min = 1000000 %}

<!-- ## 1.1 ## itemVariantID: find out which variant need to be exchanged for shadow product for this bundle -->
                        {% assign itemVariantID = ""  %}
                        {% assign itemShadowVariantID = ""  %}

<!-- ## 2 ## BDOne is one pair of originProductID:discount  -->
                        {% for BDOne in BDArray %}
                            {% assign BDOneArray = BDOne | split:":"   %}
                            {% assign BDOneProductId = BDOneArray | first  %}
                            {% assign cnt = 0 %}

<!-- ## 3 ## find out if there exists such bundle pattern in cart depending on cnt?=0 , meanwhile, record quantity of this combo -->
                            {% for item in cart.items  %}
                                {% if item.product_id ==  BDOneProductId  %}
                                    {% assign cnt = cnt | plus: 1  %}
<!-- ## 4 ## find out the postion of the variant located in product  -->
                                    {% assign pos = 0 %}
                                    {% for var in item.product.variants %}
                                         {% assign pos = forloop.index0 %}
                                         {% if var.id == item.variant.id  %}
                                              {% break %}
                                         {% endif %}
                                    {%endfor%}
                                    // find out its corresponding shadow productID for this line_item
                                    {% assign itemShadowID = "" %}
                                    {% assign shadowProductArray = shop.metafield.originToShadow[item.product_id] | split: ","  %}
                                    {% for bundleProductPair in shadowProductArray %}
                                        {% assign bundleProductArray = bundleProductPair | split: ":"  %}
                                        {% assign bundleID = bundleProductArray | first  %}
                                        {% assign shadowID = bundleProductArray | last   %}
                                        {% if  bundleID == BDID %}
                                            {% assign itemShadowID = shadowID %}
                                            {% break %}
                                        {% endif %}
                                    {%endfor%}
                                    {% assign itemShadowVariantID = product.variants[pos]
                                    {% assign itemVariantID = itemVariantID | append: item.variant.id | append: "," %}
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

                        {% if min != 0 and min != 1000000 %}
                            // 0. indicate this bundle has corresponding products in cart
                            // 1. make REST call to delete orignial product variant based on itemVariantID
                            // 2. make REST call to add corresponding shadow variant based on itemVariantID
                            //    2.1 loop through itemVariantID to delete current variant item
                            //    2.2 loop through itemProductID, to find its shadow productID, ->  .variants[]
                            for
                        {% endif %}
                    {%endfor%}

              </script>;

//-------------------------------------------------------------------------------
    $para = array(
      "key" => "snippets/bundleCheck.liquid",
      "value" => $codeInject
    );
    $bundleCheckSnippet = $shopify->Theme($themeID)->Asset->put($para) ;



//-----## 2 ## insert the statement: {% include 'bundleCheck' %} to 'theme.liquid' file
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
