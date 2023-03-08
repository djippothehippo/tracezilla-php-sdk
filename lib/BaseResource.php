<?php
namespace TracezillaConnector;

use TracezillaConnector\Exceptions\ResourceNotLoaded;
use TracezillaConnector\Helpers\Arr;

class BaseResource {

    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = '';

    /**
     * TracezillaConnector
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
    public function __construct(TracezillaConnector $connector)
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
            return static::$loadedResources[$resourceId]['resource'];
        }

        /**
         * Try to fetch the resource
         */
        $resource = $this->connector->getRequest($this->resourceEndpoint($resourceId));

        /**
         * Store the loaded resource in the cache for fast fetch next time
         */
        $this->setActiveResource($resourceId, $resource, $include);

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
     * Helper function to set a loaded resource in the object cache
     */
    public function setActiveResource($resourceId, $data, array $include = []) {
        static::$loadedResources[$resourceId] = [
            'include' => $include,
            'resource' => $data
        ];

        $this->activeResourceId = $resourceId;

        return $this;
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

        return $path ? (new Arr($resource))->get($path, $defaultValue) : $resource;
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
     * Return results
     */
    public function results() {
        return $this->results;
    }

    /**
     * Get initial index request
     */
    public function index($query) {
        /**
         * Try to fetch the resource
         */
        $this->results = null;
        $this->nextPageUrl = null;

        $response = $this->connector->getRequest($this->baseEndpoint(), $query);

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
        $this->setActiveResource($resource['id'], $resource);
        return $this;
    }

    /**
     * Helper function to update existing resource
     */
    public function update($data) {
        $resource = $this->resource();

        $data = array_merge($resource, $data);

        $this->connector->putRequest($this->resourceEndpoint(), $data);
    }

    public function delete() {        
        $this->connector->deleteRequest($this->resourceEndpoint());
    }
}