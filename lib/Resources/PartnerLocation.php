<?php

namespace TracezillaSDK\Resources;

use TracezillaSDK\BaseResource;

class PartnerLocation extends BaseResource {
    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = 'locations';

    /**
     * Static array to keep track of relations
     */
    public static $relations = [
        'partner' => [
            'field' => 'partner_id',
            'class' => Partner::class,
            'includable' => true
        ]
    ];

    /**
     * Static array to cache resource id mappings of resources
     * that have already been loaded by getByNumber
     */
    public static $resourceIdsByNumber = [];

    /**
     * Helper function to find a tag by model name and name. 
     * If it doesn't exist create it automatically
     */
    public function getByNumber(int $locationNumber, array $include = [], bool $forceReload = false) {
        if (!$forceReload && isset(static::$resourceIdsByNumber[$locationNumber])) {
            /**
             * A location for this number have already been loaded
             * return already loaded resource
             */

            return $this->setActiveResourceId(static::$resourceIdsByNumber[$locationNumber]);
        }

        $endpoint = 'location-by-number/' . $locationNumber;

        $resource = $this->connector->getRequest($endpoint, [], $include);

        $data = $resource['data'];

        static::$resourceIdsByNumber[$locationNumber] = $data['id'];

        return $this->setActiveResource($data, $include);
    }

    /**
     * Get partner of location
     */
    public function getPartner(array $include = [], $forceReload = false) {
        return $this->getRelated('partner', $include, $forceReload);
    }
}