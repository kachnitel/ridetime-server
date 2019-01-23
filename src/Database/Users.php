<?php

namespace Kachnitel\RideTimeServer\Database;

class Users
{
    public function __construct(Connector $connector)
    {
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
    public function getUser(int $id): object
    {
        $query = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user` WHERE `user`.id = :id;';
        // TODO run prepared stmt
        $params = [
            'id' => $id
        ];
        $user = $this->connector->query($query, $params);

        return $user;
    }
}
