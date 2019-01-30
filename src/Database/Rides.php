<?php
namespace RideTimeServer\Database;

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
                          `event`.`location_id`,
                          `event`.`duration`,
                          `event`.`created_by`,
                          `location`.`gps_lat`,
                          `location`.`gps_lon`,
                          `location`.`name` AS `location_name`,
                          `location`.`id` AS `location_id`
                      FROM `ridetime`.`event`
                              INNER JOIN `ridetime`.`location`
                                      ON `location`.`id` = `event`.`location_id`;';

        $rides = $this->connector->query($query);

        $result = [];
        foreach ($rides as $i => $ride) {
            $result[] = (object) [
                'name' => $ride['name'],
                'difficulty' => (int) $ride['difficulty'],
                'location' => (object) [
                    'name' => $ride['location_name'],
                    'gps' => [$ride['gps_lat'], $ride['gps_lon']],
                    'id' => $ride['location_id']
                ],
                'members' => $this->getRideMembers($ride['id']),
                'terrain' => 'trail',
                'route' => $ride['route']
            ];
        }

        return $result;
    }

    public function getRideMembers(int $rideId): array
    {
        $query = 'SELECT `user`.`id`,
                          `user`.`name`,
                          `user`.`profile_pic`
                  FROM `ridetime`.`user`
                          INNER JOIN `ridetime`.`event_members` AS `members`
                                  ON `user`.`id` = `members`.`user_id`
                  WHERE `members`.`event_id` = :rideId;';

        $members = $this->connector->query($query, ['rideId' => $rideId]);

        return $members;
    }
}