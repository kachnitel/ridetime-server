<?php
namespace RideTimeServer\Tests\API\Providers;

use RideTimeServer\API\Providers\UserLocationProvider;
use RideTimeServer\Entities\UserLocation;
use RideTimeServer\MembershipManager;
use RideTimeServer\Tests\API\APITestCase;

class UserLocationProviderTest extends APITestCase
{
    public function testListFriend()
    {
        $repo = $this->entityManager->getRepository(UserLocation::class);

        $ul = new UserLocation();
        $ul->setUser($this->generateUser());
        $ul->setGpsLat(rand(0, 999999999) / 1000000);
        $ul->setGpsLon(rand(0, 999999999) / 1000000);
        $ul->setTimestamp(new \DateTime());
        $ul->setSessionId(uniqid());

        $user = $this->generateUser();
        $user->addFriend($ul->getUser())->accept();

        $this->entityManager->persist($ul);
        $this->entityManager->flush();

        $provider = new UserLocationProvider($repo);
        $provider->setUser($user);

        $this->assertContains($ul, $provider->list());
    }

    public function testListNoFriend()
    {
        $repo = $this->entityManager->getRepository(UserLocation::class);

        $ul = new UserLocation();
        $ul->setUser($this->generateUser());
        $ul->setGpsLat(rand(0, 999999999) / 1000000);
        $ul->setGpsLon(rand(0, 999999999) / 1000000);
        $ul->setTimestamp(new \DateTime());
        $ul->setSessionId(uniqid());

        $user = $this->generateUser();

        $this->entityManager->persist($ul);
        $this->entityManager->flush();

        $provider = new UserLocationProvider($repo);
        $provider->setUser($user);

        $this->assertNotContains($ul, $provider->list());
    }

    public function testListEventMember()
    {
        $repo = $this->entityManager->getRepository(UserLocation::class);

        $ul = new UserLocation();
        $ul->setUser($this->generateUser());
        $ul->setGpsLat(rand(0, 999999999) / 1000000);
        $ul->setGpsLon(rand(0, 999999999) / 1000000);
        $ul->setTimestamp(new \DateTime());
        $ul->setSessionId(uniqid());
        $ul->setEvent($this->generateEvent());
        $ul->setVisibility(UserLocation::VISIBILITY_EVENT);

        $user = $this->generateUser();
        $ms = (new MembershipManager())
            ->join($ul->getEvent(), $user)
            ->accept();
        $user->addEvent($ms);

        $this->entityManager->persist($ul);
        $this->entityManager->flush();

        $provider = new UserLocationProvider($repo);
        $provider->setUser($user);

        $this->assertContains($ul, $provider->list());
    }

    public function testListEventNoMember()
    {
        $repo = $this->entityManager->getRepository(UserLocation::class);

        $ul = new UserLocation();
        $ul->setUser($this->generateUser());
        $ul->setGpsLat(rand(0, 999999999) / 1000000);
        $ul->setGpsLon(rand(0, 999999999) / 1000000);
        $ul->setTimestamp(new \DateTime());
        $ul->setSessionId(uniqid());
        $ul->setEvent($this->generateEvent());
        $ul->setVisibility(UserLocation::VISIBILITY_EVENT);

        $user = $this->generateUser();

        $this->entityManager->persist($ul);
        $this->entityManager->flush();

        $provider = new UserLocationProvider($repo);
        $provider->setUser($user);

        $this->assertNotContains($ul, $provider->list());
    }

    public function testListEmergency()
    {
        $repo = $this->entityManager->getRepository(UserLocation::class);

        $ul = new UserLocation();
        $ul->setUser($this->generateUser());
        $ul->setGpsLat(rand(0, 999999999) / 1000000);
        $ul->setGpsLon(rand(0, 999999999) / 1000000);
        $ul->setTimestamp(new \DateTime());
        $ul->setSessionId(uniqid());
        $ul->setVisibility(UserLocation::VISIBILITY_EMERGENCY);

        $user = $this->generateUser();

        $this->entityManager->persist($ul);
        $this->entityManager->flush();

        $provider = new UserLocationProvider($repo);
        $provider->setUser($user);

        $this->assertContains($ul, $provider->list());
    }
}
