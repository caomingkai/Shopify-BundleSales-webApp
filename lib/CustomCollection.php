<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at 8/19/16 11:46 AM UTC+06:00
 *
 * @see https://help.shopify.com/api/reference/customcollection Shopify API Reference for CustomCollection
 */

namespace PHPShopify;


/*
 * --------------------------------------------------------------------------
 * CustomCollection -> Child Resources
 * --------------------------------------------------------------------------
 * @property-read ShopifyResource $Event
 * @property-read ShopifyResource $Metafield
 *
 * @method ShopifyResource Event(integer $id = null)
 * @method ShopifyResource Metafield(integer $id = null)
 *
 */
class CustomCollection extends ShopifyResource
{
    /**
     * @inheritDoc
     */
    protected $resourceKey = 'custom_collection';

    /**
     * @inheritDoc
     */
    protected $childResource = array(
        'Event',
        'Metafield',
    );
}