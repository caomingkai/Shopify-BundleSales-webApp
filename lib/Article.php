<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at 8/18/16 3:18 PM UTC+06:00
 *
 * @see https://help.shopify.com/api/reference/article Shopify API Reference for Article
 */

namespace PHPShopify;


/*
 * --------------------------------------------------------------------------
 * Article -> Child Resources
 * --------------------------------------------------------------------------
 * @property-read ShopifyResource $Event
 *
 * @method ShopifyResource Event(integer $id = null)
 *
 */
class Article extends ShopifyResource
{
    /**
     * @inheritDoc
     */
    protected $resourceKey = 'article';

    /**
     * @inheritDoc
     */
    protected $childResource = array(
        'Event',
    );
}