# Customed Bundle Sales Web Application


 Using PHP Shopify SDK:https://github.com/phpclassic/php-shopify/blob/master/README.md

 ## I. Oauth process
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
      +  If there doesn't exist this shop in database, redirect merchant back to Shopify server to ask them for authorization, with following parameters: client_id(apiKey), scope, redirect_uri, with shopUrl rendering such url: https://".$shopUrl."/admin/oauth/authorize?client_id=".$apiKey."&scope=".$scopes."&redirect_uri=".$appUrl."install/install.php
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
   + index.php
   + deleteBundle.php
   + addBundle.php
   + productInfo.txt  ---**read**
   + collectionInfo.txt   --- **read**
   + shopBundle.txt  --- **read/write** --- keep record of bundleInfo for this shop
      + content format:
      + unique bundleID , item num, productID and discount pairs
      + 1,3,19202,0.5,12021,0.8,19393,0.8
 ### Work Flow:
   1. when merchants click app icon in their admin panel, direct them to this frontend page, which is actually a php file rendering into html.
   2. First, check database, see if there already exists bundle sales. If so, display bundle info at top; if not, display nothing. Also, on display panel, there should be 'delete' button for merchant to use, which would send AJAX call to deleteBundle.php. Then the deleteBundle.php deal with bundle deletion. And update shopBundle.txt /
   3. In the php file, read product/collection information from local database, for later merchants select from to make their bundle sales combo. And write these info and checkbox into a <div></div> block.
   4. When merchants click 'choose item', it will open a modal window with info read from <div> block, making selection.
   5. Merchants continue adding items and their discounts, then click submit

 ## III. App Back-end
