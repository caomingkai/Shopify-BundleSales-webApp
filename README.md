# Custom Bundle Sales Web Application


 Using PHP Shopify SDK:https://github.com/phpclassic/php-shopify/blob/master/README.md

 ## I. OAuth process
 ### Related File:
   + install/install.php
   + merchantToken.txt --- **write** --- keep record of shop and its accessToken
   + productInfo.txt --- **write**   --- keep record of productInfo for this shop
   + collectionInfo.txt --- **write** --- keep record of collectionInfo for this shop
   + assetInfo.txt --- **write**     --- keep record of assetInfo for this shop
 ### Work Flow:
   1. Merchant find this app in appstore, click "GET".
      + The browser would direct them to the url left by me in Shopify Parter panel, which is: http:mingkaicao.AmazonWebService.com//Shopify/3rdapp_public/install/install.php.
      + Meanwhile, the browser send following parameters with the URL: ?shop=...&hmac=...&timestamp=...
   2. The app backend server receives this GET request with those parameters. First it will check if this shop has already installed this app or not, by check its database(merchantToken.txt).
      + If there exist such shop with the same name, redirect merchant to the index webpage using header() function.
      +  If there doesn't exist this shop in database, redirect merchant back to Shopify server to ask them for authorization, with following parameters: client_id(apiKey), scope, redirect_uri, with shopUrl rendering such url: https://".$shopUrl."/admin/OAuth/authorize?client_id=".$apiKey."&scope=".$scopes."&redirect_uri=".$appUrl."install/install.php
   3. The merchant is redirected to the above URL, and click OK/Cancel to authorize this app to read some info about his store. When 'OK' is clicked, they are directed to the redirect_uri specified in above URL.
   4. Now the merchant is redirected back again to PHP script on the app backend server, which actually is the same script as the former one.
      + Meanwhile, Shopify server send several parameters with the redirect_uri as follows: code/ hmac/ timestamp, etc.
      + The script now apply with this temporary code and its apiKey/SharedSecret/shopURL for a permanent AccessToken from Shopify server.
      + When Shopify server responses back, the server could store the AccessToken in database for later use.
   5. In the end, with the AccessToken, this file requests related product/collect/assets info from this shop. And store these info in local database, in order that later the merchant could make selection from it.

 ## II. App Front-end
 ### Function:
   Give merchants a portal to set their bundle sales, by collection/specific product/percentage/fixed number.
 ### Related File:
   + index.php  --- render the first page after merchants click the app icon in their admin panel
   + addBundle.php  --- add new bundle sales
      + update shopBundle.txt / addedProduct.txt
      + make REST calls to Shopify server to modify shop.metafield for this shop
      + make REST calls to Shopify server to add **"shadow products"** with sales price
      + update and inject bundle_detect.liquid and other code snippet into assets on Shopify server for this shop
   + deleteBundle.php --- delete specific bundle
      + same as deleteBundle.php
   + productInfo.txt  ---**read**
   + collectionInfo.txt   --- **read**
   + shopBundle.txt  --- **read/write** --- keep record of bundleInfo for this shop. Content format:
      + unique bundleID , item num, productID and discount pairs
      + 1,3,19202,0.5,12021,0.8,19393,0.8
 ### Work Flow:
   1. when merchants click app icon in their admin panel, direct them to this frontend page, which is actually a php file rendering into html.
   2. First, check database, see if there already exists bundle sales. If so, display bundle info at top; if not, display nothing. Also, on display panel, there should be 'delete' button for merchant to use, which would send AJAX call to deleteBundle.php. Then the deleteBundle.php deal with bundle deletion. And update shopBundle.txt / addedProduct.txt etc.
   3. In the php file, read product/collection information from local database, for merchants later select from to make their bundle sales combo. And write these info and checkbox into a <div></div> block.
   4. When merchants click 'choose item', it will open a modal window with info read from <div> block, making selection.
   5. Merchants continue adding items and their discounts, then click 'submit'. This would send AJAX call to addBundle.php.
   6. A bundle updating status box should be displayed to let merchant know it's good to go now. it can implemented using response.

 ## III. App Back-end
 ### Function:
   + deal with newly **added/deleted POST** bundle from front-end, calculate new price for **shadow products** in bundle sales. _Releted file_: addBundle.php
   + prefix unique BundleID with received POST parameters, store it in local database. _Releted file_:  shopBundle.txt
   + make RESTful POST call to Shopify server, to update **shop.metafield** of this shop
   + make RESTful POST call to Shopify server, to add **shadow products** for corresponding bundle sales
   + keep record of added shadow products of a shop, based on response from POST adding call.  _Releted file_: addedProduct.txt
   +
   + deal with webhook responses from Shopify server, in case product_added/ order_completed events happen.
 ### Related File:
   + index.php  --- render the first page after merchants click the app icon in their admin panel
   + addBundle.php  --- add new bundle sales
      + update shopBundle.txt / addedProduct.txt
      + make REST calls to Shopify server to modify shop.metafield for this shop
      + make REST calls to Shopify server to add **"shadow products"** with sales price
      + update and inject bundle_detect.liquid and other code snippet into assets on Shopify server for this shop
   + deleteBundle.php --- delete specific bundle
      + same as deleteBundle.php
   + productInfo.txt  ---**read**
   + collectionInfo.txt   --- **read**
   + shopBundle.txt  --- **read/write** --- keep record of bundleInfo for this shop. Content format:
      + unique bundleID , item num, productID and discount pairs
      + 1,3,19202,0.5,12021,0.8,19393,0.8
 ### Work Flow:


 ## IIII. Related shopify object for current shop
 ### shop.metafield
    1. bundleInfo(namespace)->(key, value)
    sdfsfsdsdfsdfs  
        + bundleNum, int( i.e. 2 )
        + bundleDetail, string ( bundleID , item num, productID and discount pairs,bundleID , item num, productID and discount pairs )
    2. cartProductHash(namespace)->(key, value)
        + hashNum, int ( i.e. 3 )
        + hashDetail, string(productID,num,productID,num,productID,num)
    3. matchedProduct(namespace)->(key, value)
        + matchedNum, int ( i.e. 2 )
        + matchedDetail, string(productID,num,productID,num)

 ### bundleCheck.liquid code snippet
    1.
