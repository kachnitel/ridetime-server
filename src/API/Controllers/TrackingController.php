<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Providers\UserLocationProvider;
use RideTimeServer\Entities\UserLocation;
use Slim\Http\Request;
use Slim\Http\Response;

class TrackingController extends BaseController
{
    public function list(Request $request, Response $response, array $args): Response
    {
        $repo = $this->getEntityManager()->getRepository(UserLocation::class);
        $provider = new UserLocationProvider($repo);
        $provider->setUser($request->getAttribute('currentUser'));

        $results = $provider->list();
        return $response->withJson((object) ['results' => $this->extractDetails($results)]);
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody());

        foreach ($data as $record) {
            $ul = new UserLocation();
            $ul->setUser($request->getAttribute('currentUser'));
            $ul->setGpsLat($record->gps->lat);
            $ul->setGpsLon($record->gps->lon);
            $ul->setSessionId($record->sessionId);
            $ul->setTimestamp((new \DateTime())->setTimestamp($record->timestamp));
            $ul->setVisibility($record->visibility);
            if ($record->visibility === UserLocation::VISIBILITY_EVENT) {
                $ul->setEvent(
                    $this->getEventRepository()->get($record->event)
                );
            }

            $this->getEntityManager()->persist($ul);
        }

        $this->getEntityManager()->flush();

        return $response->withStatus(201);
    }

    public function clear(Request $request, Response $response, array $args): Response
    {
        $repo = $this->getEntityManager()->getRepository(UserLocation::class);

        $hits = $repo->findBy(['user' => $request->getAttribute('currentUser')]);
        foreach ($hits as $record) {
            $this->getEntityManager()->remove($record);
        }
        $this->getEntityManager()->flush();

        return $response->withStatus(204);
    }
}
