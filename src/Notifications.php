<?php
namespace RideTimeServer;

use GuzzleHttp\Client;
use RideTimeServer\Entities\NotificationsToken;

class Notifications
{
    const EXPO_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * REVIEW: static
     * Send a notification to the EXPO API
     * @see https://docs.expo.io/versions/v32.0.0/guides/push-notifications/#http2-api
     *
     * @param NotificationsToken[] $tokens Tokens of recipient(s)
     * @param string $title The title to display in the notification
     * @param string $body The message to display in the notification
     * @param mixed $data A JSON object delivered to your app
     * @param string $channelId Android notification channel ID
     * @return void
     */
    public function sendNotification(
        array $tokens,
        string $title,
        string $body = null,
        $data = null,
        string $channelId = null
    ) {
        $messages = array_map(function(NotificationsToken $token) use ($title, $body, $data, $channelId) {
            $item = (object) [
                'to' => $token->getToken(),
                'title' => $title
            ];

            if ($body) {
                $item->body = $body;
            }

            if ($data) {
                $item->data = $data;
            }

            if ($channelId) {
                $item->channelId = $channelId;
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