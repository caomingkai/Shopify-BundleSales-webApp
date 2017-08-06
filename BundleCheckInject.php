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
