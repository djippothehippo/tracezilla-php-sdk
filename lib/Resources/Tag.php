<?php

namespace TracezillaSDK\Resources;

use TracezillaSDK\BaseResource;

class Tag extends BaseResource {
    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = 'tags';

    /**
     * Static array to cache resource id mappings of resources
     * that have already been loaded by firstOrCreateByName
     */
    public static $resourceIdsByModelAndName = [];

    /**
     * Helper function to find a tag by model name and name. 
     * If it doesn't exist create it automatically
     */
    public function firstOrCreateByName(string $modelName, string $tagName, string $colorCode = 'info', bool $forceReload = false) {
        if (!isset(static::$resourceIdsByModelAndName[$modelName])) {
            static::$resourceIdsByModelAndName[$modelName] = [];
        }

        if (!$forceReload && isset(static::$resourceIdsByModelAndName[$modelName][$tagName])) {
            /**
             * A tag for this model and name have already been loaded
             * return already loaded resources
             */

            return $this->setActiveResourceId(static::$resourceIdsByModelAndName[$modelName][$tagName]);
        }

        $endpoint = 'tag-by-name/' . $modelName;

        $resource = $this->connector->putRequest($endpoint, [
            'tag_name' => $tagName
        ]);

        $data = $resource['data'];

        foreach ($data['models'] as $modelName) {
            static::$resourceIdsByModelAndName[$modelName][$tagName] = $data['id'];
        }

        return $this->setActiveResource($data, ['models']);
    }
}