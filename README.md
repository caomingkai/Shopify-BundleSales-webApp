# Custom Bundle Sales Web Application

 WorkFlowy: https://workflowy.com/s/HdnT.62VuPVlGWh

 Using PHP Shopify SDK:https://github.com/phpclassic/php-shopify/blob/master/README.md

 ## I. OAuth process
 ### Related File:
   1. install/install.php
   1. merchantToken.txt --- **write** --- keep record of shop and its accessToken
   1. productInfo.txt --- **write**   --- keep record of productInfo for this shop, used as selecting items when making a bundle
   1. collectionInfo.txt --- **write** --- keep record of collectionInfo for this shop, used as selecting items
   1. assetInfo.txt --- **write**     --- used for inserting bundleCheck.liquid into assets, and append it to cart.liquid
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
   1. index.php  --- render the first page after merchants click the app icon in their admin panel
   1. addBundle.php  --- add new bundle sales
      + update shopBundle.txt / shadowToOrigin.txt
      + make REST calls to Shopify server to modify shop.metafield for this shop
      + make REST calls to Shopify server to add **"shadow products"** with sales price
      + update and inject bundle_detect.liquid and other code snippet into assets on Shopify server for this shop
   1. deleteBundle.php --- delete specific bundle
      + same as deleteBundle.php
   1. productInfo.txt  ---**read**
   1. collectionInfo.txt   --- **read**
   1. shopBundle.txt  --- **read/write** --- keep record of bundleInfo for this shop. Content format:
      + unique bundleID , item num, productID and discount pairs
      + 1,3,19202,0.5,12021,0.8,19393,0.8
 ### Work Flow:
   1. when merchants click app icon in their admin panel, direct them to this frontend page, which is actually a php file rendering into html.
   2. First, check database, see if there already exists bundle sales. If so, display bundle info at top; if not, display nothing. Also, on display panel, there should be 'delete' button for merchant to use, which would send AJAX call to deleteBundle.php. Then the deleteBundle.php deal with bundle deletion. And update shopBundle.txt / shadowToOrigin.txt etc.
   3. In the php file, read product/collection information from local database, for merchants later select from to make their bundle sales combo. And write these info and checkbox into a <div></div> block.
   4. When merchants click 'choose item', it will open a modal window with info read from <div> block, making selection.
   5. Merchants continue adding items and their discounts, then click 'submit'. This would send AJAX call to addBundle.php.
   6. A bundle updating status box should be displayed to let merchant know it's good to go now. it can implemented using response.

 ## III. App Back-end
 ### Function:
   1. deal with newly **added/deleted POST** bundle from front-end, calculate new price for **"shadow products"** in bundle sales. _Releted file_: addBundle.php
   1. prefix unique BundleID with received POST parameters, store it in local database. _Releted file_:  shopBundle.txt
   1. make RESTful POST call to Shopify server, to update **shop.metafield** of this shop
   1. make RESTful POST call to Shopify server, to add **"shadow products"** for corresponding bundle sales
   1. keep record of added shadow products of a shop, based on response from POST adding call.  _Releted file_: shadowToOrigin.txt
   1. (_Pending_) insert bundleCheck.liquid into Assets of this shop ( Maybe could be done for only one time, not each time )
   1. deal with webhook responses from Shopify server, in case product_added/ order_completed events happen.
      + if order_completed webhook, read response to see completed product quantity to update original product inventory
      + if product_added webhook, see if it belongs to a certain bundle sale, add **"shadow products"** for this product. Meanwhile, update productInfo.txt / collectionInfo.txt / shadowToOrigin.txt

 ### Related File:
   1. addBundle.php  --- add new bundle sales
      + update shopBundle.txt / shadowToOrigin.txt
      + make REST calls to Shopify server to modify shop.metafield.bundleInfo for this shop
      + make REST calls to Shopify server to add **"shadow products"** with sales price
      + make REST calls to Shopify server to add shop.metafield.originToShadow about added shadow productID and its bundleID into original product
      + (_Pending_) update and inject bundle_detect.liquid and other code snippet into assets on Shopify server for this shop
   1. deleteBundle.php --- delete specific bundle
      + same as addBundle.php
   1. webhookHandler.php  --- receive webhook events, call inventoryUpdate.php to update inventory, and update productInfo.txt / collectionInfo.txt / shadowToOrigin.txt
   1. inventoryUpdate.php  --- when customers have paid an order, update original products inventory base on webhook response
   1. productInfo.txt  ---**write based on webhook**  --- could be updated due to webhook events
   1. collectionInfo.txt   --- **write based on webhook**  --- could be updated due to webhook events
   1. shopBundle.txt  --- **read/write** --- updated by addBundle.php / deleteBundle.php
   1. shadowToOrigin.txt --- **read/write** --- keep track of added shadow products, update original product inventory
      + format : shadowProductID, originalProductID
   1. bundleToShadow.txt --- **read/write** --- in case certain bundle deletion or app uninstall
      + format : bundleID, shadowProductID_1, shadowProductID_2, ...

 ### Work Flow:
   1. first read into shopBundle.txt / shadowToOrigin.txt, for later use
   2. receive newly POST bundleInfo, check request type: add/ delete/ add&delete ?
   2. when add Bundle:
      + create unique bundleID, and append it with POST parameters; update shopBundle.txt
      + make REST calls to update shop.metafield.bundleInfo
      + make REST calls to add **"shadow products"** in current shop
      + make REST calls to shop.metafield.originToShadow
      + update bundleToShadow.txt / shadowToOrigin.txt
   3. when delete Bundle:
      + read POST parameter['bundleID'], find out which bundle need to be deleted
      + delete it from shopBundle.txt
      + make REST calls to update shop.metafield.bundleInfo
      + make REST calls to update shop.metafield.originToShadow
      + make REST calls to delete **"shadow products"** in current shop, based on bundleToShadow.txt,
      + delete it from bundleToShadow.txt / shadowToOrigin.txt
   4. Webhook: when merchants edit/add a new product, tigger product create/update/delete events, Shopify server POST json data to this app backend file webhookHandler.php. webhookHandler.php do the following things:
      + if _add_ a new product, First, add this product into productInfo.txt. Second, check if this product falls into a certain collection which belongs to a bundle. If so, do following:
          + make REST calls to add **"shadow products"** in current shop
          + make REST calls to shop.metafield.originToShadow
          + update bundleToShadow.txt / shadowToOrigin.txt
      + if _edit_ an existing product, check if the following things are modified:
          + [08/04/17] Basically, as long as there are changes, old shadow product should be deleted, and add new shadow product.
          + if **price** is changed, update productInfo.txt / collection.txt
          + [08/04/17] update shop.metafield.originToShadow, i.e. delete old mapping, update new mapping
          + [08/04/17] update bundleToShadow.txt / shadowToOrigin.txt
          And If this product belongs to a certain bundle, have to call REST call to change its shadow product's price
          + if **collection** is changed, update productInfo.txt / collection.txt;
          And If this product belongs to a certain bundle, have to call REST call to delete its shadow product belonging to this corresponding bundle; loop check if now it belongs to a new bundle, have to call REST to add new shadow product, update bundleToShadow.txt / shadowToOrigin.txt
      + if _delete_ a product, do the following:
          + update productInfo.txt / collection.txt
          + If it belongs to a certain bundle, have to call REST call to delete its shadow product belonging to this corresponding bundle;  Also update bundleToShadow.txt / shadowToOrigin.txt
    5. Webhook: update product inventory after customers pay off their order. This event also triggers a webhook POST from Shopify server.
        + loop through all line items in the order, check if there exists shadow products.
        + If YES, for each of such shadow products, do the following things:
           + do a math to update the current quantity of this shadow product in productInfo.txt.
           + based on shadowToOrigin.txt, find out its original product,
           + make REST call to update the quantity of the original product in Shopify server.
        + If No, do nothing.
 ### Files relationship
   1. shopBundle.txt is the source info of shopify.metafield.bundleInfo
   3. shadowToOrigin.txt <----> shopify.metafield.originToShadow, they have opposite key:value pairs
   4. bundleToShadow.txt ---> shadowToOrigin.txt , from this we could find all involved origin products

 ## IIII. Related shopify object for current shop
 ### shop.metafield
   1. bundleInfo(namespace)->(key, value)
   bundle_detect.liquid will first read this to find out existing bundles, used to detect available bundles from products in cart.
      + bundleNum, int( e.g  2 )
      + bundleDetail, string ( bundleID , item num, productID and discount pairs, bundleID , item num, productID and discount pairs )
   2. originToShadow(namespace)->(key, value)
   When bundle_detect.liquid find out there exists a bundle, i.e. find out certain products needed to be changed into its **shadow** counterpart. We read this metafield of product, based on bundleID find out productID of its **shadow product**, then make cart AJAX to delete orginal, and add shadow product.
      + originalProductID_1, str( bundleID, shadowProductID, bundleID, shadowProductID, )
      + originalProductID_2, str( bundleID, shadowProductID, bundleID, shadowProductID, )
   2. cartProductHash(namespace)->(key, value)
   act like a HashMap variable, used by bundle_detect.liquid to read/write/store for following code to check if there exists bundl
      + hashNum, int ( e.g  3 )
      + hashDetail, string(productID,num,productID,num,productID,num)
   3. matchedProduct(namespace)->(key, value)
   when products in cart match certain bundle sales, save them in this variable. Then based on these info, make AJAX delete&add call to shopify server.
      + matchedNum, int ( e.g  2 )
      + matchedDetail, string(productID,bundleID,num,productID,bundleID,num;)

 ### bundleCheck.liquid code snippet
   1. read shopify.metafield.bundleInfo to know existing bundles
   2. go through all items in cart, make a record of **product HashMap** of ( productID, quantity ) pairs
      + check metafield.originProduct of each item, if it has such key and value, then it's a shadow product, we need to change it back to its original product, and product the product HashMap. In case products in cart make up a certain bundle, but later customer delete one of them, so we need to find out available bundle again.
      + O(n) time complexity
   1. loop through each existing bundles. for a certain bundle, do the following:
      + "Exist?" check if cart product HashMap matches this certain bundle
      + if YES ---> "How many?" keep decrementing by 1 from product HashMap's keyValue pairs, until one of the key's value of the HashMap become 0. Meanwhile, record the times of decrement, which is the quantity of this certain bundle.
      + when a key(productID) in the product HashMap become 0, remove this keyValue pair from current product HashMap for following faster manipulation.
      + if NO  ---> go to next iteration of the loop
   1. after finishing the loop of all bundle patterns, we get the final bundle patterns with (productID, quantity) pairs Array for different bundle patterns. Store it in shop.metafield.matchedDetail. It's best to make AJAX call as we deal with each bundle pattern, since metafield don't have too much flexible set/put fashion.
   1. we process matched products as we check certain bundle exist. **do the "delete origin" and "add shadow" AJAX call** to Shopify server. Since each change in cart(add/delete) will send POST call to Shopify server, here is the chance the liquid template be interpreted into <script> with a series of AJAX call to Shopify Server. So right after the html is sent to customer's browser, the AJAX would call to Shopify server right away, leading to the price change of related product.
      + "delete origin AJAX" : based on shop.metafield.matchedNum and shop.metafield.matchedDetail, we could get the origin productID, and its quantity. Make a series of AJAX to delete them.
      + "add shadow AJAX"  : in the last step, we find out original productID, bundleID and quantity, we now refer to shop.metafield.originToShadow to find out the shadow product. Make series of AJAX to add shadow products.

 ### shadow product
   1. it should be added with special vendor/tag information, in favor of giving merchants a way to hide all shadow products from storefront webpage.
   2. other than the price attribute is different from original products, others information should be stay the same.
   3. should have it's own metafield
        + metafield.originProduct
           + have record of it's original product in case when a shadow product added but then it cannot make up a certain bundle due to its sibling deleted by customer later. That case, bundleCheck.liquid would change all shadow product back to their origin products
