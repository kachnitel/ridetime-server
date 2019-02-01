<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;

/**
 * @var Slim\App $app
 */
$app->post('/users', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    /**
     * @var User $user
     * TODO: Validate input!
     */
    $user = new User();
    $user->setName($data['name']);
    $user->setEmail($data['email']);
    $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
    $this->entityManager->persist($user);
    try {
        $this->entityManager->flush();
    } catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
        $errorId = uniqid();

        $this->logger->addWarning('User creation failed', [
            'message' => $e->getMessage(),
            'code' => $e->getErrorCode(),
            'errorId' => $errorId
        ]);

        return $response->withStatus(409)->withJson([
            'error' => 'Error creating user',
            'errorId' => $errorId
            // TODO: determine the conflicting column
        ]);
    }

    $result = (object) [
        'id' => $user->getId(),
        'name' => $user->getName(),
    ];

    return $response->withJson($result)->withStatus(201);
});

$app->get('/users/{id}', function (Request $request, Response $response, array $args) {
    $userId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

    /**
     * @var User $user
     */
    $user = $this->entityManager->find('RideTimeServer\Entities\User', $userId);

    if (empty($user)) {
        // TODO: Throw UserNotFoundException
        return $response->withStatus(404)->withJson([
            'error' => 'User ID:' . $userId . ' not found'
        ]);
    }

    $events = [];
    /** @var Event $event */
    foreach ($user->getEvents() as $event) {
        $events[] = (object) [
            'id' => $event->getId(),
            'datetime' => $event->getDate(),
            'title' => $event->getTitle()
        ];
    }

    $result = (object) [
        'id' => $user->getId(),
        'name' => $user->getName(),
        'events' => $events
    ];

    return $response->withJson($result);
});
