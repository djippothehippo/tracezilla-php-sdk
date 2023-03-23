<?php

namespace TracezillaConnector\Resources;

use TracezillaConnector\BaseResource;

class Sku extends BaseResource {
    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = 'skus';

    /**
     * Static array to cache resource id mappings of resources
     * that have already been loaded by getByNumber
     */
    public static $resourceIdsBySkuCode = [];

    /**
     * Helper function to find a tag by model name and name. 
     * If it doesn't exist create it automatically
     */
    public function getBySkuCode(string $skuCode, array $include = [], bool $forceReload = false) {
        if (!$forceReload && isset(static::$resourceIdsBySkuCode[$skuCode])) {
            /**
             * A location for this number have already been loaded
             * return already loaded resource
             */

            return $this->setActiveResourceId(static::$resourceIdsBySkuCode[$skuCode]);
        }

        $endpoint = 'sku-by-sku-code/' . $skuCode;

        $resource = $this->connector->getRequest($endpoint, ['include' => implode(',', $include)]);

        $data = $resource['data'];

        static::$resourceIdsBySkuCode[$skuCode] = $data['id'];

        return $this->setActiveResource($data, $include);
    }
}