<?php
namespace TracezillaSDK;

use TracezillaSDK\Exceptions\ResourceNotLoaded;
use TracezillaSDK\Helpers\Arr;

class BaseResource {

    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = '';

    /**
     * TracezillaSDK
     */
    protected $connector;

    /**
     * Static array to keep track of relations
     */
    public static $relations = [];

    /**
     * Loaded resources
     */
    protected static $loadedResources = [];

    /**
     * Loaded results
     */
    protected $results = [];

    /**
     * Loaded resource
     */
    protected $nextPageUrl = '';

    /**
     * Id of the resource that is currently being worked on
     */
    protected $activeResourceId = '';

    /**
     * 
     */
    public function __construct(TracezillaSDK $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get loaded resource id
     */
    public function getActiveResourceId() {
        return $this->activeResourceId;
    }

    /**
     * Helper function get base endpoint of resource url
     */
    public function baseEndpoint() {
        return static::BASE_ENDPOINT;
    }

    /**
     * Helper function get base endpoint of resource url
     */
    public function resourceEndpoint() {
        return $this->baseEndpoint() . '/' . $this->getActiveResourceId();
    }

    /**
     * 
     */
    public function get(string $resourceId, $include = [], bool $forceRefresh = false) {
        /**
         * Set the resource pointer of the current resource
         */
        $this->activeResourceId = $resourceId;

        /**
         * Check to see if a valid resource has already been loaded
         */
        if (!$forceRefresh && isset(static::$loadedResources[$resourceId]) && 
            $this->hasNeededIncludes($include, static::$loadedResources[$resourceId]['include'])) {
            return $this;
        }

        /**
         * Try to fetch the resource
         */
        $resource = $this->connector->getRequest($this->resourceEndpoint($resourceId), [], $include);

        /**
         * Store the loaded resource in the cache for fast fetch next time
         */
        $this->setActiveResource($resource['data'], $include);

        return $this;
    }

    /**
     * Get related resource
     */
    public function getRelated($relationName, array $include = [], bool $forceReload = false) {
        return (new $this::$relations[$relationName]['class']($this->connector))
            ->get($this->data($this::$relations[$relationName]['field']), $include, $forceReload);
    }

    /**
     * Add loaded resource to static cache
     */
    public static function addLoadedResource(array $data, array $include = []) {
        foreach ($include as $relationName) {
            $nameParts = explode('.', $relationName);

            if (count($nameParts) > 1 || 
                empty($data[$relationName]) || 
                !isset(static::$relations[$relationName]) || 
                !isset(static::$relations[$relationName]['class'])) {
                continue;
            }

            $subIncludes = [];

            foreach ($include as $relationName2) {
                $nameParts2 = explode('.', $relationName2);

                if (count($nameParts2) > 1 && $nameParts2[0] === $relationName) {
                    array_shift($nameParts2);

                    $subIncludes[] = implode('.', $nameParts2);
                }
            }

            static::$relations[$relationName]['class']::addLoadedResource($data[$relationName], $subIncludes);

            unset($data[$relationName]);
        }

        static::$loadedResources[$data['id']] = [
            'include' => $include,
            'resource' => $data
        ];
    }

    /**
     * Helper function to set a loaded resource in the object cache
     */
    public function setActiveResource(array $data, array $include = []) {
        static::addLoadedResource($data, $include);

        $this->activeResourceId = $data['id'];

        return $this;
    }

    /**
     * Helper function to get the active resource as array
     */
    public function getActiveResource() {
        return static::$loadedResources[$this->activeResourceId]['resource'];
    }

    /**
     * Helper function to set a the active resource id
     */
    public function setActiveResourceId($resourceId) {
        $this->activeResourceId = $resourceId;

        return $this;
    }

    /**
     * Check if the loaded resource has the needed includes for the request
     */
    public function hasNeededIncludes($neededIncludes, $providedIncludes) {
        return !array_diff($neededIncludes, $providedIncludes);
    }

    /**
     * Return active resource as array
     */
    public function data(string $path = '', $defaultValue = null) {
        if (!$this->activeResourceId || !isset(static::$loadedResources[$this->activeResourceId])) {
            throw new ResourceNotLoaded("The resource you are looking for has not been loaded!");
        }

        $resource = static::$loadedResources[$this->activeResourceId]['resource'];

        return $path ? Arr::from($resource)->get($path, $defaultValue) : $resource;
    }

    /**
     * Return id of active resource
     */
    public function id() {
        if (!$this->activeResourceId || !isset(static::$loadedResources[$this->activeResourceId])) {
            throw new ResourceNotLoaded("The resource you are looking for has not been loaded!");
        }

        return $this->activeResourceId;
    }

    /**
     * Return active resource
     */
    public function resource() {
        return static::$loadedResources[$this->id()]['resource'];
    }

    /**
     * Return results
     */
    public function results() {
        return $this->results;
    }

    /**
     * Get initial index request
     */
    public function index(array $query = [], array $include = []) {
        /**
         * Try to fetch the resource
         */
        $this->results = null;
        $this->nextPageUrl = null;

        $response = $this->connector->getRequest($this->baseEndpoint(), $query, $include);

        if ($response) {
            $this->results = $response['data'];

            if (isset($response['links']['next_page']) && !empty($response['links']['next_page'])) {
                $this->nextPageUrl = $response['links']['next_page'];
            }
        }

        return $this;
    }

    /**
     * 
     */
    public function nextPage() {
        /**
         * Try to fetch the resource
         */
        $this->results = null;
        $this->nextPageUrl = null;

        if (!$this->nextPageUrl) {
            return $this;
        }

        $response = $this->connector->getRequest($this->nextPageUrl);

        if ($response) {
            $this->results = $response['data'];

            if (isset($response['links']['next_page']) && !empty($response['links']['next_page'])) {
                $this->nextPageUrl = $response['links']['next_page'];
            }
        }

        return $this;
    }

    /**
     * Helper function to create new resource
     */
    public function store($data) {
        $resource = $this->connector->postRequest(static::BASE_ENDPOINT, $data);
        $this->setActiveResource($resource);
        return $this;
    }

    /**
     * Helper function to update existing resource
     */
    public function update($data) {
        $resource = $this->data();

        $data = array_merge($resource, $data);

        $this->connector->putRequest($this->resourceEndpoint(), $data);
    }

    public function delete() {        
        $this->connector->deleteRequest($this->resourceEndpoint());
    }
}