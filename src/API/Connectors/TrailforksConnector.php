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

    public function locations(string $filter, array $fields): array
    {
        $filter .= ';bottom::ridingarea';
        $query = [
            'filter' => $filter,
            'fields' => join(',', $fields),
            'rows' => 20
        ];

        return $this->doRequest('regions', $query)->data;
    }

    public function getLocation(int $id, $fields = [])
    {
        $query = [
            'id' => $id,
            'fields' => join(',', $fields),
            'scope' => 'full'
        ];

        return $this->doRequest('region', $query)->data;
    }

    public function getTrail(int $id, $fields = [])
    {
        $query = [
            'id' => $id,
            'fields' => join(',', $fields),
            'scope' => 'full'
        ];

        return $this->doRequest('trail', $query)->data;
    }

    /**
     * REVIEW: look into removing and leaving only "trails()" and "routes()" - see locations()
     *
     * @param integer $locationId
     * @param array $fields
     * @return array|null
     */
    public function getLocationTrails(int $locationId, $fields = [])
    {
        return $this->getLocationChildren('trails', $locationId, $fields);
    }

    public function getLocationRoutes(int $locationId, $fields = [])
    {
        return $this->getLocationChildren('routes', $locationId, $fields);
    }

    protected function getLocationChildren(string $type, int $locationId, $fields = [])
    {
        $filter = 'rid::' . $locationId;
        $query = [
            'filter' => $filter,
            'fields' => join(',', $fields),
            'scope' => 'full',
            'rows' => 100
        ];
        return $this->doRequest($type, $query)->data;
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