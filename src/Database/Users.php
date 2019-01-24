<?php
namespace Kachnitel\RideTimeServer\Database;

class Users
{
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
    public function getUsers(): array
    {
        $usersQuery = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user`;';
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
    public function getUser(int $id): ?object
    {
        $query = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user` WHERE `user`.id = :id;';
        $params = [
            'id' => $id
        ];
        $userResult = $this->connector->query($query, $params);

        if (!array_key_exists(0, $userResult)) {
            return null;
        }
        $user = (object) $userResult[0];

        // if $friends param
        $user->friends = $this->getFriendsForUser($id);

        return $user;
    }

    public function getFriendsForUser(int $id): array
    {
        $query = 'SELECT id, name, email, phone, profile_pic, cover_pic
            FROM ridetime.`user` INNER JOIN ridetime.friends
            ON `user`.id = friends.friend_id
            WHERE `friends`.user_id = :id;';

        $params = [
            'id' => $id
        ];

        return $this->connector->query($query, $params);
    }
}
