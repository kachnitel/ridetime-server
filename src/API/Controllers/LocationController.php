<?php
namespace RideTimeServer\API\Controllers;

use Doctrine\Common\Collections\Criteria;
use RideTimeServer\API\Filters\EventFilter;
use RideTimeServer\API\Filters\TrailforksFilter;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\User;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\Exception\UserException;

class LocationController extends BaseController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function filter(Request $request, Response $response, array $args): Response
    {
        $filter = (new TrailforksFilter($request->getQueryParam('filter', [])))->getTrailforksFilter();
        $result = $this->getLocationRepository()
            ->remoteFilter($filter);

        return $response->withJson($this->getResultData(
            $result,
            $request
        ));
    }

    protected function getResultData(array $locations, Request $request)
    {
        $related = $request->getQueryParam('related', '');
        $eventFilters = $request->getQueryParam('eventFilter', []);

        $data = (object) [
            'results' => $this->extractDetails($locations)
        ];
        if ($related === 'event') {
            $data->relatedEntities = (object) [
                'event' => $this->extractDetails($this->getEventsInLocations(
                    $locations,
                    $request->getAttribute('currentUser'),
                    $eventFilters
                ))
            ];
        }

        return $data;
    }

    /**
     * @param Location[] $locations
     * @param User $currentUser
     * @param array $filters
     * @return Event[]
     */
    protected function getEventsInLocations(array $locations, ?User $currentUser, array $filters = []): array
    {
        // REVIEW: see Router location routes $cuMiddleware setup
        if ($currentUser === null) {
            return [];
        }
        $filter = new EventFilter($this->getEntityManager());
        $filter->apply($filters);

        return $this->getEventRepository()
            ->matching(
                $filter->getCriteria()
                    ->andWhere(Criteria::expr()->in('location', $locations))
            )
            ->filter(function (Event $event) use ($currentUser) { return $event->isVisible($currentUser); })
            ->getValues();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
     */
    public function trailsByLocation(Request $request, Response $response, array $args): Response
    {
        return $this->trails(
            $request->withQueryParams([
                'filter' => [
                    'rid' => $args['id'],
                    'activitytype' => 1
                ]
            ]),
            $response,
            $args
        );
    }

    public function trails(Request $request, Response $response, array $args): Response
    {
        $filters = $request->getQueryParam('filter');
        if (!$filters) {
            throw new UserException('Filters are required for listing trails');
        }
        $filter = (new TrailforksFilter($filters))->getTrailforksFilter();

        $result = $this->getTrailRepository()
            ->remoteFilter($filter);

        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function routes(Request $request, Response $response, array $args): Response
    {
        $filters = $request->getQueryParam('filter');
        if (!$filters) {
            throw new UserException('Filters are required for listing routes');
        }
        $filter = (new TrailforksFilter($filters))->getTrailforksFilter();

        $result = $this->getRouteRepository()
            ->remoteFilter($filter);

        $trails = [];
        $locations = [];
        foreach ($result as $route) {
            $routeTrails = $route->getRelated()->trail;
            $trails = array_unique(array_merge($trails, $routeTrails), SORT_REGULAR);

            if (!in_array($route->getLocation(), $locations)) {
                $locations[] = $route->getLocation();
            }
        }

        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result),
            'relatedEntities' => (object) [
                'trail' => $this->extractDetails(array_values($trails)),
                'location' => $this->extractDetails($locations)
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
     */
    public function routesByLocation(Request $request, Response $response, array $args): Response
    {
        return $this->routes(
            $request->withQueryParams([
                'filter' => [
                    'rid' => $args['id'],
                    'activitytype' => 1
                ]
            ]),
            $response,
            $args
        );
    }
}
