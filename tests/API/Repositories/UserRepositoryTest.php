<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\UserRepository;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\User;
use RideTimeServer\Tests\API\APITestCase;

class UserRepositoryTest extends APITestCase
{
    public function testCreate()
    {
        $repo = $this->getUserRepository();

        $data = (object) [
            'name' => 'Joe',
            'email' => 'e@mail.ca',
            'hometown' => 'Whistler, BC',
            'authId' => '123'
        ];
        $user = $repo->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data->name, $user->getName());
        $this->assertEquals($data->email, $user->getEmail());
        $this->assertEquals($data->authId, $user->getAuthId());
        // ... REVIEW: Have a getter in user for an object of all properties?
    }

    public function testUpdate()
    {
        $user = $this->generateUser();
        $locations = [];
        $locations[] = $this->generateLocation(1);
        $locations[] = $this->generateLocation(2);
        foreach ($locations as $location) {
            $this->entityManager->persist($location);
        }
        $this->entityManager->flush($locations);

        $data = (object) [
            'locations' => [1, 2],
            'name' => 'Alois'
        ];

        $this->getUserRepository()->update($user, $data);

        $this->assertEquals($data->locations, array_map(function (Location $location) {
            return $location->getId();
        }, $locations));
    }

    public function testSearch()
    {
        $repo = $this->getUserRepository();

        // Generates 10 users with `name`: User1, User2 ... User10
        array_map(function ($num) use ($repo) {
            $user = $this->generateUser($num);
            $user->setName('User' . $num);
            $repo->saveEntity($user);
            return $user;
         }, range(1, 10));

         $hits = $repo->search('name', 'User1');
         // User1 and User10 should match
         $this->assertCount(2, $hits);
         $this->assertEquals('User1', $hits[0]->getName());
         $this->assertEquals('User10', $hits[1]->getName());
    }

    protected function getUserRepository()
    {
        return new UserRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(User::class)
        );
    }
}
