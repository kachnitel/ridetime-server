<?php
namespace RideTimeServer\API\Connectors;

use GuzzleHttp\Client;
use Emarref\Guzzle\Middleware\ParamMiddleware;
use GuzzleHttp\HandlerStack;
use function GuzzleHttp\json_decode;
use GuzzleHttp\Exception\ClientException;
use RideTimeServer\Exception\RTException;

class TrailforksConnector
{
    const API_URL = 'https://www.trailforks.com/api/1/';

    protected $client;

    public function __construct(string $clientId, string $clientSecret)
    {
        $paramMiddleware = ParamMiddleware::create([
            'app_id' => $clientId,
            'app_secret' => $clientSecret
        ]);

        $stack = HandlerStack::create();
        $stack->push($paramMiddleware);


        $this->client = new Client([
            'base_uri' => self::API_URL,
            'handler' => $stack
        ]);
    }

    /**
     * Filter locations within $range from $latLon
     *
     * @param array $latLon [lat, lon]
     * @param integer $range In km
     * @return array
     */
    public function getLocationsNearby(array $latLon, int $range, $fields = [])
    {
        $filter = "nearby_range::{$range};lat::{$latLon[0]};lon::{$latLon[1]};bottom::ridingarea";
        $query = [
            'filter' => $filter,
            'fields' => join(',', $fields),
            'rows' => 20
        ];

        return $this->doRequest('regions', $query)->data;
    }

    /**
     * Bounding box filtered locations
     *
     * bbox filter is in the format of
     * top-left lat/lon and bottom-right lat/lon
     * values seperated by commas.
     *
     * Example: bbox::49.33,-122.973,49.322,-122.957
     *
     * @param float[] $bbox
     * @return array
     */
    public function getLocationsBBox(array $bbox, $fields = [])
    {
        $boundary = join(',', $bbox);
        $filter = "bbox::{$boundary};bottom::ridingarea";
        $query = [
            'filter' => $filter,
            'fields' => join(',', $fields),
            'rows' => 20
        ];

        return $this->doRequest('regions', $query)->data;
    }

    public function searchLocations(string $search, $fields = [])
    {
        $filter = "search::{$search};bottom::ridingarea";
        $query = [
            'filter' => $filter,
            'fields' => join(',', $fields),
            'rows' => 20
        ];

        return $this->doRequest('regions', $query)->data;
    }

    protected function doRequest(string $endpoint, array $query)
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $query
            ]);
        } catch (ClientException $th) {
            $this->handleConnectionError($th);
        }

        return json_decode($response->getBody());
    }

    /**
     * @throws RTException
     * @param ClientException $th
     * @return void
     */
    protected function handleConnectionError(ClientException $th)
    {
        $url = $th->getRequest()->getUri();
        parse_str($url->getQuery(), $query);
        if (!empty($query['app_secret'])) {
            $query['app_secret'] = substr($query['app_secret'], 0, 3) + '...' + substr($query['app_secret'], -3);
        }

        $e = new RTException('Trailforks API connection failed', 0, $th);
        $e->setData([
            'url' => (string) $url,
            'message' => $th->getMessage(),
            'response' => [
                'code' => $th->getResponse()->getStatusCode(),
                'body' => json_decode($th->getResponse()->getBody())
            ]
        ]);

        throw $e;
    }
}