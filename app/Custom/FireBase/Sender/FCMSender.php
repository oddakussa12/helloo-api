<?php

namespace App\Custom\FireBase\Sender;

use App\Custom\FireBase\Message\Topics;
use Psr\Http\Message\ResponseInterface;
use App\Custom\FireBase\Request\Request;
use App\Custom\FireBase\Message\Options;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use App\Custom\FireBase\Message\PayloadData;
use App\Custom\FireBase\Response\GroupResponse;
use App\Custom\FireBase\Response\TopicResponse;
use App\Custom\FireBase\Response\DownstreamResponse;
use App\Custom\FireBase\Message\PayloadNotification;

/**
 * Class FCMSender.
 */
class FCMSender extends HTTPSender
{
    const MAX_TOKEN_PER_REQUEST = 1000;

    /**
     * send a downstream message to.
     *
     * - a unique device with is registration Token
     * - or to multiples devices with an array of registrationIds
     *
     * @param string|array $to
     * @param Options|null $options
     * @param PayloadNotification|null $notification
     * @param PayloadData|null $data
     *
     * @return DownstreamResponse|null
     * @throws GuzzleException
     */
    public function sendTo($to, Options $options = null, PayloadNotification $notification = null, PayloadData $data = null)
    {
        $response = null;

        if (is_array($to) && !empty($to)) {
            $partialTokens = array_chunk($to, self::MAX_TOKEN_PER_REQUEST, false);
            foreach ($partialTokens as $tokens) {
                $request = new Request($tokens, $options, $notification, $data);

                $responseGuzzle = $this->post($request);

                $responsePartial = new DownstreamResponse($responseGuzzle, $tokens);
                if (!$response) {
                    $response = $responsePartial;
                } else {
                    $response->merge($responsePartial);
                }
            }
        } else {
            $request = new Request($to, $options, $notification, $data);
            $responseGuzzle = $this->post($request);

            $response = new DownstreamResponse($responseGuzzle, $to);
        }

        return $response;
    }

    /**
     * Send a message to a group of devices identified with them notification key.
     *
     * @param                          $notificationKey
     * @param Options|null $options
     * @param PayloadNotification|null $notification
     * @param PayloadData|null $data
     *
     * @return GroupResponse
     * @throws GuzzleException
     */
    public function sendToGroup($notificationKey, Options $options = null, PayloadNotification $notification = null, PayloadData $data = null)
    {
        $request = new Request($notificationKey, $options, $notification, $data);

        $responseGuzzle = $this->post($request);

        return new GroupResponse($responseGuzzle, $notificationKey);
    }

    /**
     * Send message devices registered at a or more topics.
     *
     * @param Topics $topics
     * @param Options|null $options
     * @param PayloadNotification|null $notification
     * @param PayloadData|null $data
     *
     * @return TopicResponse
     * @throws GuzzleException
     */
    public function sendToTopic(Topics $topics, Options $options = null, PayloadNotification $notification = null, PayloadData $data = null)
    {
        $request = new Request(null, $options, $notification, $data, $topics);

        $responseGuzzle = $this->post($request);

        return new TopicResponse($responseGuzzle, $topics);
    }

    /**
     * @param Request $request
     *
     * @return null|ResponseInterface
     * @throws GuzzleException
     * @internal
     *
     */
    protected function post($request)
    {
        try {
            $responseGuzzle = $this->client->request('post', $this->url, $request->build());
        } catch (ClientException $e) {
            $responseGuzzle = $e->getResponse();
        }

        return $responseGuzzle;
    }
}
