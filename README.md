# Customed Bundle Sales Web Application


 Using PHP Shopify SDK:https://github.com/phpclassic/php-shopify/blob/master/README.md

 ## I. Oauth process
 ### Related File:
 install/install.php
 merchantToken.txt
 productInfo.txt
 collectionInfo.txt
 assetInfo.txt

 ### Work Flow:
 1. Merchant find this app in appstore, click "GET".
 1.1 The browser would direct them to the url left by me in Shopify Parter panel, which is: http:mingkaicao.AmazonWebService.com//Shopify/3rdapp_public/install/install.php.
 1.2 Meanwhile, the browser send following parameters with the URL: ?shop=...&hmac=...&timestamp=...

 2. The app backend server receives this GET request with those parameters.
   2.1 First it will check if this shop has already installed this app or not, by check its database(merchantToken.txt).
   2.1.1 If there exist such shop with the same name, redirect merchant to the index webpage using header() function.
   2.1.2 If there doesn't exist this shop in database, redirect merchant back to Shopify server to ask them for authorization, with following parameters: client_id(apiKey), scope, redirect_uri, with shopUrl rendering such url: https://".$shopUrl."/admin/oauth/authorize?client_id=".$apiKey."&scope=".$scopes."&redirect_uri=".$appUrl."install/install.php

 3. The merchant is redirected to the above URL, and click OK/Cancel to authorize this app to read some info about his store. When 'OK' is clicked, they are directed to the redirect_uri specified in above URL.

 4. Now the merchant is redirected back again to PHP script on the app backend server, which actually is the same script as the former one.
 4.1 Meanwhile, Shopify server send several parameters with the redirect_uri as follows: code/ hmac/ timestamp, etc.
 4.2 The script now apply with this temporary code and its apiKey/SharedSecret/shopURL for a permanent AccessToken from Shopify server.
 4.3 When Shopify server responses back, the server could store the AccessToken in database for later use.

 5. In the end, with the AccessToken, this file requests related product/collect/assets info from this shop. And store these info in local database, in order that later the merchant could make selection from it.

 ## II. App Frontend
 ### Function:
 Give merchants a portal to set their bundle sales, by collection/specific product/percentage/fixed number.
 ### Related File:
 index.php
 ### Work Flow:
 1. when merchants click app icon in their admin panel, direct them to this frontend page, which is actually a php file rendering into html.
 2. In the php file, read product/collection/asset information from local database, for later merchants select from to make their bundle sales combo.
 3. Put these info in a <div></div> for later use.
 ## III. App Frontend
