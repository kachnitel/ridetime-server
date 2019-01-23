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
    public function getUsers() {
      $usersQuery = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user`;';
      $users = $this->connector->query($usersQuery);

      return $users;
  }
}