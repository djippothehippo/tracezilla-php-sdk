<?php
namespace TracezillaConnector;

use TracezillaConnector\Exceptions\ResourceNotLoaded;

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
     * Loaded resources
     */
    protected $loadedResources = [];

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
    protected $resourcePointerId = '';

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
    public function loadedResourceId() {
        return $this->resourcePointerId;
    }

    /**
     * Helper function get base endpoint of resource url
     */
    public function baseEndpoint() {
        return self::BASE_ENDPOINT;
    }

    /**
     * Helper function get base endpoint of resource url
     */
    public function resourceEndpoint() {
        return $this->baseEndpoint() . '/' . $this->loadedResourceId();
    }

    /**
     * 
     */
    public function get(string $resourceId, $include = [], bool $forceRefresh = false) {
        /**
         * Set the resource pointer of the current resource
         */
        $this->resourcePointerId = $resourceId;

        /**
         * Check to see if a valid resource has already been loaded
         */
        if (!$forceRefresh && isset($this->loadedResources[$resourceId]) && 
            $this->hasNeededIncludes($include, $this->loadedResources[$resourceId]['include'])) {
            return $this->loadedResources[$resourceId]['resource'];
        }

        /**
         * Try to fetch the resource
         */
        $resource = $this->connector->getRequest($this->resourceEndpoint($resourceId));

        /**
         * Store the loaded resource in the cache for fast fetch next time
         */
        $this->setLoadedResource($resourceId, $resource, $include);

        return $this;
    }

    /**
     * Helper function to set a loaded resource in the object cache
     */
    public function setLoadedResource($resourceId, $data, array $include = []) {
        $this->loadedResources[$resourceId] = [
            'include' => $include,
            'resource' => $data
        ];
    }

    /**
     * Check if the loaded resource has the needed includes for the request
     */
    public function hasNeededIncludes($neededIncludes, $providedIncludes) {
        return !array_diff($neededIncludes, $providedIncludes);
    }

    /**
     * Return already loaded resource as array
     */
    public function resource() {
        if (!$this->resourcePointerId || !$this->loadedResources[$this->resourcePointerId]) {
            throw new ResourceNotLoaded("The resource you are looking for has not been loaded!");
        }

        return $this->loadedResources[$this->resourcePointerId]['resource'];
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
        $resource = $this->connector->postRequest(self::BASE_ENDPOINT, $data);
        $this->setLoadedResource($resource['id'], $resource);
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