<?php
namespace Kachnitel\RideTimeServer\Database;

/**
 * Rides
 *
 * @TODO:
 *  Ride->members should be an array of base User
 *  {id,name,profile_pic_url}
 */
class Rides
{
    public function __construct(Connector $connector)
    {
        /**
         * @var Connector
         */
        $this->connector = $connector;
    }

    /**
     * Return list of rides
     *
     * @return array
     */
    public function getRides(): array
    {
        $query = 'SELECT `event`.`id`,
                          `event`.`created`,
                          `event`.`start_time`,
                          `event`.`name`,
                          `event`.`description`,
                          `event`.`route`,
                          `event`.`difficulty`,
                          `event`.`location` AS `location_id`,
                          `event`.`duration`,
                          `event`.`created_by`,
                          `location`.`gps_lat`,
                          `location`.`gps_lon`,
                          `location`.`name` AS `location_name`
                      FROM `ridetime`.`event`
                              INNER JOIN `ridetime`.`location`
                                      ON `location`.`id` = `event`.`location`;';

        $rides = $this->connector->query($query);

        $users = new Users($this->connector);

        $result = [];
        foreach ($rides as $i => $ride) {
            $result[] = (object) [
                'name' => $ride['name'],
                'difficulty' => (int) $ride['difficulty'],
                'location' => $ride['location_name'],
                'locationGps' => [$ride['gps_lat'], $ride['gps_lon']],
                // 'members' => [1016, 1018], // TODO: separate query w/ details eventually
                'members' => $users->getUsers([1016, 1017], ['id', 'name', 'profile_pic']),
                'terrain' => 'trail',
                'route' => $ride['route']
            ];
        }

        return $result;
    }
}