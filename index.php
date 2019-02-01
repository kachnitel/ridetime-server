<?php

declare(strict_types=1);

// use RideTimeServer\Entities\User;
// use RideTimeServer\Entities\Event;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\RouterLoader;

/**
 * Bootstrapped in
 * @var \Doctrine\ORM\EntityManager $entityManager
 */
$entityManager;

// TODO: Replace w/ a proper class
require_once __DIR__ . '/bootstrap.php';

// ADD
// $user = new User();
// $user->setName('Joe');
// $user->setEmail('kachna@dev.ca');
// $user->setPassword(password_hash('r1d3', PASSWORD_BCRYPT));
// $entityManager->persist($user);
// $entityManager->flush();

// DELETE
// $user1 = $entityManager->find('RideTimeServer\Entities\User', 1);
// $entityManager->remove($user1);
// $entityManager->flush();

// LIST ALL USERS
// $userRepo = $entityManager->getRepository('RideTimeServer\Entities\User');
// $users = $userRepo->findAll();
// foreach ($users as $user) {
//     var_dump($user->getId(), $user->getName());
// }

// SEARCH BY NAME
// $userRepo = $entityManager->getRepository('RideTimeServer\Entities\User');
// $res = $userRepo->findByName('Joe');
// foreach ($res as $value) {
//     var_dump($value->getId());
// }

// ADD EVENT
// /**
//  * @var User
//  */
// $user = $entityManager->find('RideTimeServer\Entities\User', 1);
// $event = new Event;
// $event->setTitle('Rideee');
// $event->setDescription('Much ride');
// $event->setDate(new DateTime("now"));
// $event->setCreatedBy($user);
// $entityManager->persist($event);
// $user->addEvent($event);
// $entityManager->persist($user);
// $entityManager->flush();

// // ADD MEMBER TO EVENT
// $event = $entityManager->find('RideTimeServer\Entities\Event', 1);
// $user = $entityManager->find('RideTimeServer\Entities\User', 1);
// $event->addUser($user);
// $entityManager->persist($event);
// $entityManager->flush();
// die();

// LIST EVENT MEMBERS
// $event = $entityManager->find('RideTimeServer\Entities\Event', 1);
// $members = $event->getUsers();
// foreach ($members as $key => $member) {
//     var_dump([$member->getId(), $member->getName()]);
// }

/////////////////////////

$configFile = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configFile, true);

$slimConfig = $config['slim'];

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile, true);

$slimConfig['db'] = $secrets['db'];

/**
 * @var \Slim\App $app
 */
$app = new \Slim\App([ 'settings' => $slimConfig ]);

$container = $app->getContainer();

$container['logger'] = function($c) use ($config) {
    $logger = new \Monolog\Logger($config['appName']);

    $file_handler = new \Monolog\Handler\StreamHandler($config['logPath']);
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['entityManager'] = function ($c) use ($entityManager) {
    return $entityManager;
};

// require_once 'app/routes.php';
$router = new RouterLoader($app);

$router->initRoutes();

/**
 * Request logger middleware
 *
 * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
 * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
 * @param  callable                                 $next     Next middleware
 *
 * @return \Psr\Http\Message\ResponseInterface
 */
$app->add(function (Request $request, Response $response, callable $next) use ($container) {
    $container['logger']->addInfo($request->getMethod() . ' ' . $request->getUri()->getPath());

    $response = $next($request, $response);

    return $response;
});

$app->run();
