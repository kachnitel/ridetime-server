<?php
namespace RideTimeServer\Database;

class Users
{
    protected $defaultColumns = [
        'id',
        'name',
        'email',
        'phone',
        'profile_pic',
        'cover_pic'
    ];

    public function __construct(Connector $connector)
    {
        /**
         * @var Connector
         */
        $this->connector = $connector;
    }

    /**
     * Return list of users
     *
     * @return array
     */
    public function getUsers(array $ids = [], array $columns = []): array
    {
        $usersQuery = 'SELECT ' . $this->getColumns($columns) . ' FROM ridetime.`user`;';
        $users = $this->connector->query($usersQuery);

        return $users;
    }

    /**
     * Return an user with details (rides, home locations)
     * TODO: Make rides/locations available with a parameter?
     *
     * @param int $id
     * @return object // TODO User object?
     */
    public function getUser(int $id, array $columns = []): ?object
    {
        $query = 'SELECT ' . $this->getColumns($columns) . ' FROM ridetime.`user` WHERE `user`.id = :id;';
        $params = [
            'id' => $id
        ];
        $userResult = $this->connector->query($query, $params);

        if (!array_key_exists(0, $userResult)) {
            return null;
        }
        $user = (object) $userResult[0];

        // if $friends param
        $user->friends = $this->getUserDetail($id, 'friends');
        $user->homeLocations = $this->getUserDetail($id, 'home_locations');
        $user->events = $this->getUserDetail($id, 'events');

        return $user;
    }

    /**
     * Get detail from a related table
     *
     * @param integer $id
     * @param string $detail one of ['friends','home_locations','events']
     * @return array
     */
    public function getUserDetail(int $id, string $detail): array
    {
        $queries = [
            'friends' => 'SELECT `id`,
                    `name`,
                    `profile_pic`,
                    `cover_pic`
                FROM `ridetime`.`user` AS `friend`
                    INNER JOIN `ridetime`.`friends` AS `friends`
                        ON `friend`.`id` = `friends`.`friend_id`
                WHERE `friends`.`user_id` = :id;',
            'home_locations' => 'SELECT `id`,
                    `name`,
                    `gps_lat`,
                    `gps_lon`
                FROM `ridetime`.`location` AS `location`
                    INNER JOIN `ridetime`.`user_locations`
                        ON `user_locations`.`location_id` = `location`.`id`
                WHERE `user_locations`.`user_id` = :id;',
            'events' => 'SELECT `id`,
                    `start_time`,
                    `name`,
                    `difficulty`,
                    `location_id`,
                    `duration`
                FROM `ridetime`.`event` AS `events`
                    INNER JOIN `ridetime`.`event_members` AS `members`
                        ON `members`.`event_id` = `events`.`id`
                WHERE `members`.`user_id` = :id;'
        ];

        $params = [
            'id' => $id
        ];

        return $this->connector->query($queries[$detail], $params);
    }

    protected function getColumns(array $columns = []): string
    {
        return join(empty($columns) ? $this->defaultColumns : $columns, ',');
    }
}
