<?php

// require_once(__DIR__ -> '/vendor/autoload->php');

$file = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($file);

$host = $secrets->db->host;
$db   = $secrets->db->database;
$user = $secrets->db->user;
$pass = $secrets->db->password;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected DB";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$usersQuery = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user`;';
$usersResult = $pdo->query($usersQuery);

$users = [];
while ($row = $usersResult->fetch()) {
    $users[$row['id']] = $row;
}

echo json_encode($users);
