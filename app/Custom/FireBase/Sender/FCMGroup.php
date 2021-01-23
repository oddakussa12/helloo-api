<?php

namespace App\Custom\FireBase\Sender;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use App\Custom\FireBase\Request\GroupRequest;

/**
 * Class FCMGroup.
 */
class FCMGroup extends HTTPSender
{
    const CREATE = 'create';
    const ADD = 'add';
    const REMOVE = 'remove';

    /**
     * Create a group.
     *
     * @param       $notificationKeyName
     * @param array $registrationIds
     *
     * @return null|string notification_key
     * @throws GuzzleException
     */
    public function createGroup($notificationKeyName, array $registrationIds)
    {
        $request = new GroupRequest(self::CREATE, $notificationKeyName, null, $registrationIds);

        $response = $this->client->request('post', $this->url, $request->build());

        return $this->getNotificationToken($response);
    }

    /**
     * add registrationId to a existing group.
     *
     * @param       $notificationKeyName
     * @param       $notificationKey
     * @param array $registrationIds registrationIds to add
     * @return null|string notification_key
     * @throws GuzzleException
     */
    public function addToGroup($notificationKeyName, $notificationKey, array $registrationIds)
    {
        $request = new GroupRequest(self::ADD, $notificationKeyName, $notificationKey, $registrationIds);
        $response = $this->client->request('post', $this->url, $request->build());

        return $this->getNotificationToken($response);
    }

    /**
     * remove registrationId to a existing group.
     *
     * >Note: if you remove all registrationIds the group is automatically deleted
     *
     * @param       $notificationKeyName
     * @param       $notificationKey
     * @param array $registeredIds registrationIds to remove
     * @return null|string notification_key
     * @throws GuzzleException
     */
    public function removeFromGroup($notificationKeyName, $notificationKey, array $registeredIds)
    {
        $request = new GroupRequest(self::REMOVE, $notificationKeyName, $notificationKey, $registeredIds);
        $response = $this->client->request('post', $this->url, $request->build());

        return $this->getNotificationToken($response);
    }

    /**
     * @param ResponseInterface $response
     * @return null|string notification_key
     *@internal
     *
     */
    private function getNotificationToken(ResponseInterface $response)
    {
        if (! $this->isValidResponse($response)) {
            return null;
        }

        $json = json_decode($response->getBody()->getContents(), true);

        return $json['notification_key'];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function isValidResponse(ResponseInterface $response)
    {
        return $response->getStatusCode() === 200;
    }
}
