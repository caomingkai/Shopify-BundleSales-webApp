<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at: 9/10/16 10:45 AM UTC+06:00
 */

namespace PHPShopify;


class CustomerTest extends TestSimpleResource
{
    /**
     * @inheritDoc
     */
    public $postArray = array(
        "first_name" => "Steve",
        "last_name" => "Lastnameson",
        "email" => "steve.lastnameson@example.com",
    );

    /**
     * @inheritDoc
     */
    public $putArray = array(
        "verified_email" => true,
    );
}