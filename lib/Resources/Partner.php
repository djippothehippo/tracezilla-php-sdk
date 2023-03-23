<?php

namespace TracezillaConnector\Resources;

use TracezillaConnector\BaseResource;

class Partner extends BaseResource {

    /**
     * Static array to keep track of relations
     */
    public static $relations = [
        'price_list_sales' => [
            'field' => 'price_list_sales_id',
            'class' => SalesPriceList::class,
            'includable' => true
        ]
    ];

    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = 'partners';

    /**
     * Get sales price list of partner
     */
    public function getSalesPriceList(array $include = [], $forceReload = false) {
        return $this->getRelated('price_list_sales', $include, $forceReload);
    }
}