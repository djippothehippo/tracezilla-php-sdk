<?php
namespace TracezillaConnector;

use GuzzleHttp;
use TracezillaConnector\Exceptions\ResourceCouldNotBefound;

class TracezillaConnector {
    /**
     * Base url of tracezilla
     */
    protected $baseUrl = "";

    /**
     * Team slug of team in tracezilla
     */
    protected $teamSlug = "";

    /**
     * Api key to use for authentication
     */
    protected $apiKey = "";

    /**
     * GuzzleHttp client
     */
    protected $client;

    /**
     * Intiate connector
     */
    public function __construct(string $baseUrl, string $teamSlug, string $apiKey)
    {
        $this->baseUrl                  = $baseUrl;
        $this->teamSlug                 = $teamSlug;
        $this->apiKey                   = $apiKey;

        $this->client = new GuzzleHttp\Client(['base_uri' => $this->baseUrl . '/api/v1/' . $this->teamSlug . '/']);
    }

    /**
     * Get default http header
     */
    public function defaultHttpHeader()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Helper function to create endpoint url
     */
    public function endpointUrl($endpoint)
    {
        if (substr($endpoint, 0, 5) === 'https') {
            return $endpoint;
        }

        return $this->baseUrl . '/api/v1/' . $this->teamSlug . '/' . $endpoint;
    }

    public function getRequest($endpoint, $query = [])
    {        
        $url = $this->endpointUrl($endpoint);

        $response = $this->client->request('GET', $url, [
            'headers' => $this->defaultHttpHeader(),
            'query' => $query,
        ]);

        return json_decode($response->getBody());
    }

    public function putRequest($endpoint, $data, $query = [])
    {
        $url = $this->endpointUrl($endpoint);

        $response = $this->client->request('GET', $url, [
            'headers' => $this->defaultHttpHeader(),
            'query' => $query,
            'body' => json_encode($data),
            'defaults' => ['exceptions' => false],
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 422) {
            throw new \Exception($response->getBody()->getContents());
        }

        if (in_array($response->getStatusCode(), [200, 201, 204])) {
            return json_decode($response->getBody()->getContents(), true);
        }
    }

    public function postRequest($endpoint, $data, $query = [])
    {

    }

    public function deleteRequest($endpoint)
    {
    }

    public function getInventory() {
        print_r($this->getRequest('inventory'));
    }

    public function getLocationByNumber($locationNumber) {
        return $this->getRequest("shortcuts/location-by-number/$locationNumber");
    }


    public function getTagByName($modelName, $tagName) {
        return $this->putRequest("shortcuts/tag-by-name/$modelName", ['tag_name' => $tagName]);
    }

    /**
     * Instantiate new resource classes
     */
    public function __call($methodName, $arguments) {
        $className = "TracezillaConnector\\Resources\\$methodName";

        if (!class_exists($className)) {
            throw new ResourceCouldNotBefound("A resource with the name $methodName could not be found!");
        }

        return new $className($this);
    }
}