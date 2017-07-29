<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at 8/17/16 10:39 PM UTC+06:00
 *
 * @see https://help.shopify.com/api/reference/page Shopify API Reference for Page
 */

namespace PHPShopify;


/*
 * --------------------------------------------------------------------------
 * Page -> Child Resources
 * --------------------------------------------------------------------------
 * @property-read ShopifyResource $Event
 * @property-read ShopifyResource $Metafield
 *
 * @method ShopifyResource Event(integer $id = null)
 * @method ShopifyResource Metafield(integer $id = null)
 *
 */
class Page extends ShopifyResource
{
    /**
     * @inheritDoc
     */
    protected $resourceKey = 'page';

    /**
     * @inheritDoc
     */
    protected $childResource = array(
        'Event',
        'Metafield',
    );
}