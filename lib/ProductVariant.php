<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at 8/18/16 1:50 PM UTC+06:00
 *
 * @see https://help.shopify.com/api/reference/product_variant Shopify API Reference for Product Variant
 */

namespace PHPShopify;


/*
 * --------------------------------------------------------------------------
 * ProductVariant -> Child Resources
 * --------------------------------------------------------------------------
 * @property-read ShopifyResource $Metafield
 *
 * @method ShopifyResource Metafield(integer $id = null)
 *
 */
class ProductVariant extends ShopifyResource
{
    /**
     * @inheritDoc
     */
    protected $resourceKey = 'variant';

    /**
     * @inheritDoc
     */
    protected $childResource = array(
        'Metafield',
    );
}