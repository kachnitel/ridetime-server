<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\BaseRepository;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\Tests\API\APITestCase;

class BaseRepositoryTest extends APITestCase
{
    public function testGetMissThrowsException()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->getBaseRepository()->get(666);
    }

    public function testGet()
    {
        $entity = $this->generateUser();
        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);

        $result = $this->getBaseRepository()->get($entity->getId());
        $this->assertSame($entity, $result);
    }

    /**
     * @param string $class Type to initialize repo with
     * @return \PHPUnit\Framework\MockObject\MockObject|BaseRepository
     */
    protected function getBaseRepository(string $class = null)
    {
        return $this->getMockForAbstractClass(
            BaseRepository::class,
            [ // Constructor params
                $this->entityManager,
                $this->entityManager->getClassMetadata($class ?? User::class)
            ]
        );
    }
}
