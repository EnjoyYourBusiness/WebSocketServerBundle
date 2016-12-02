<?php
/**
 * Created by Enjoy Your Business.
 * Date: 29/11/2016
 * Time: 18:50
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Controller;
use EnjoyYourBusiness\WebSocketClientBundle\Model\Message;
use Ratchet\ConnectionInterface;


/**
 * Class WebSocketEventController
 *
 * @package   EnjoyYourBusiness\websocketserverbundle\Controller
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Lucien Bruneau <lucien.bruneau@enjoyyourbusiness.fr>
 * @author    Matthieu Prieur <matthieu.prieur@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class WebSocketEventsController extends WebSocketController
{
    private static $events = [];

    /**
     * Registers a event for the message client
     *
     * @param Message $message
     *
     * @return Message
     *
     * @throws \Exception
     */
    public function registerAction(Message $message)
    {
        $event = $message->getEvent();
        $client = $message->getFrom();

        if (!array_key_exists($event, self::$events)) {
            self::$events[$event] = [];
        }

        foreach (self::$events[$event] as $registeredClient) {
            /** @var ConnectionInterface $registeredClient */
            if ($registeredClient === $client) {
                return Message::create(
                    $client,
                    [
                        'data' => [
                            'success'     => true,
                            'return_code' => 'ALREADY_REGISTERED',
                            'registered'  => $event
                        ]
                    ],
                    [$client]
                );
            }
        }

        self::$events[$event][] = $client;

        return Message::create(
            $client,
            [
                'data' => [
                    'success'     => true,
                    'return_code' => 'REGISTERED',
                    'registered'  => $event
                ]
            ],
            [$client]
        );
    }

    /**
     * Triggers an event
     *
     * @param Message $message
     *
     * @return Message
     * @throws \Exception
     */
    public function triggerAction(Message $message)
    {
        $event = $message->getEvent();
        $data = $message->getData();
        $client = $message->getFrom();
        if (!$data) {
            $data = [];
        }

        if (!array_key_exists($event, self::$events)) {
            self::$events[$event] = [];
        }

        return Message::create(
            $client,
            [
                'data' => [
                    'event' => $event,
                    'data' => $data
                ]
            ],
            !empty($message->getTo()) ? $message->getTo() :  self::$events[$event]
        );

    }

    /**
     * Unregisters a client from an event
     *
     * @param Message $message
     *
     * @return Message
     */
    public function unregisterAction(Message $message)
    {
        $event = $message->getEvent();
        $client = $message->getFrom();

        return $this->unregisterClientFromEvent($event, $client);
    }

    /**
     * Unregisters from all events
     *
     * @param Message $message
     *
     * @return Message
     * @throws \Exception
     */
    public function unregisterAllAction(Message $message)
    {
        $client = $message->getFrom();
        $events = [];

        foreach (array_keys(self::$events) as $event) {
            $message = $this->unregisterClientFromEvent($event, $client);

            if ($message->isSuccess()) {
                $events[] = $message->getUnregistered();
            }
        }

        return Message::create(
            $client,
            [
                'data' => [
                    'success'      => true,
                    'return_code'  => 'UNREGISTERED_ALL',
                    'unregistered' => $events
                ]
            ],
            [$client]
        );
    }

    private function unregisterClientFromEvent($event, ConnectionInterface $client)
    {
        if (!array_key_exists($event, self::$events)) {
            self::$events[$event] = [];
        }

        foreach (self::$events[$event] as $key => $registeredClient) {
            /** @var ConnectionInterface $registeredClient */
            if ($registeredClient === $client) {
                self::$events[$event][$key] = null;
                self::$events[$event] = array_filter(self::$events[$event]);

                return Message::create(
                    $client,
                    [
                        'data' => [
                            'success'      => true,
                            'return_code'  => 'UNREGISTERED',
                            'unregistered' => $event
                        ]
                    ],
                    [$client]
                );
            }
        }

        return Message::create(
            $client,
            [
                'data' => [
                    'success'      => true,
                    'return_code'  => 'ALREADY_UNREGISTERED',
                    'unregistered' => $event
                ]
            ],
            [$client]
        );
    }
}