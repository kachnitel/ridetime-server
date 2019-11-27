<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\UserRepository;
use RideTimeServer\Entities\User;
use RideTimeServer\Tests\API\APITestCase;

class UserRepositoryTest extends APITestCase
{
    public function testCreate()
    {
        $repo = new UserRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(User::class)
        );

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
}
