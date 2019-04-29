<?php
namespace RideTimeServer;

use GuzzleHttp\Client;
use RideTimeServer\Entities\NotificationsToken;

class Notifications
{
    const EXPO_URL = 'https://exp.host/--/api/v2/push/send';

    public function sendNotification(array $tokens, string $message, $body = null, $data = null)
    {
        $messages = array_map(function(NotificationsToken $token) use ($message, $body, $data) {
            $item = (object) [
                'to' => $token->getToken(),
                'title' => $message
            ];

            if ($body) {
                $item->body = $body;
            }

            if ($data) {
                $item->data = $data;
            }

            return $item;
        }, $tokens);

        $client = new Client();
        $client->post(self::EXPO_URL, [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json'
            ],
            'json' => $messages
        ]);
    }
}