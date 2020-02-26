<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\Entities\UserLocation;
use Slim\Http\Request;
use Slim\Http\Response;

class TrackingController extends BaseController
{

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
}
