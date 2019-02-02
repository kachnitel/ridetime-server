<?php
namespace RideTimeServer\API\Endpoints;

use Monolog\Logger;

interface EndpointInterface
{
    /**
     * Add an entity
     *
     * @param array $data
     * @param Logger $logger
     * @return void
     */
    public function add(array $data, Logger $logger);

    /**
     * Get entity detail
     *
     * @param integer $id
     * @return void
     */
    public function getDetail(int $id);
}
