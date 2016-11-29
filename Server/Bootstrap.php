<?php
/**
 * Created by Enjoy Your Business.
 * Date: 20/11/2015
 * Time: 17:28
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Server;

use EnjoyYourBusiness\WebSocketClientBundle\Model\Message;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Bootstrap
 *
 * @package   Eyb\HomeBundle\WenSocket
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Otman Haiti <otman.haiti@enjoyyourbusiness.fr>
 * @author    Nabil Selfaoui <nabil.selfaoui@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class Bootstrap implements MessageComponentInterface, ContainerAwareInterface
{
    private static $ip;

    /**
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Contains user id in keys and user connections in values
     *
     * @var array
     */
    protected $usersConnections = [];

    /**
     * Contains connection resourceId in key and user id in value
     *
     * @var array
     */
    protected $usersConnectionsIds = [];

    /**
     * Sets the server ip
     *
     * @param int $ip
     */
    public static function setServerIp(int $ip)
    {
        self::$ip = $ip;
    }

    public static function setServerUrl(string $host)
    {

    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * When a new connection is opened it will be passed to this method
     *
     * @param ConnectionInterface $conn The socket/connection that just connected to your application
     *
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})" . PHP_EOL;
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     *
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     *
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        $message = Message::create(
            $conn,
            [
                'action' => 'EybHomeBundle:WebSocketEvents:unregisterAll'
            ]
        );

        $response = $this->treatMessage($message);

        $this->sendMessage($response);

        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        // Unset connections for logout user
        $logoutUserId = $this->usersConnectionsIds[$conn->resourceId];
        if (is_int($logoutUserId)) {
            $this->unregisterUser($logoutUserId, $conn);
            echo "User {$logoutUserId} has disconnected" . PHP_EOL;
        }

        echo "Connection {$conn->resourceId} has disconnected" . PHP_EOL;
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     *
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     *
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}" . PHP_EOL;

        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     *
     * @param ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string              $msg The message received
     *
     * @return bool
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo sprintf('Received message : %s%s', PHP_EOL, $msg);
        $json = json_decode($msg, true);

        if ($json) {
            // If users key is defined send message for users connections
            if (array_key_exists('users', $json)) {
                $arr = array_intersect_key($this->usersConnections, array_flip($json['users']));
                $to = [];
                foreach ($arr as $val) {
                    $to = array_merge($to, $val);
                }
                if (empty($to)) {
                    echo 'User not connected : message not send' . PHP_EOL;

                    return false;
                }
            } else {
                $to = [];
            }
            $message = Message::create($from, $json, $to);

            $response = $this->treatMessage($message);

            $this->sendMessage($response);
        } else {
            // If message is 'I'm 42 save user 42 and his websocket connection
            $isMessageWhoIm = strpos($msg, 'I\'m') !== false;
            if ($isMessageWhoIm) {
                $userId = intval(filter_var($msg, FILTER_SANITIZE_NUMBER_INT));
                $this->registerUser($userId, $from);
            } else {
                echo 'ERROR message was not a JSON' . PHP_EOL;
            }
        }
    }

    /**
     * @param Message $message
     *
     * @return Message
     *
     * @throws \Exception
     */
    private function treatMessage(Message $message)
    {
        echo sprintf('Controller called : "%s"' . PHP_EOL, $message->getAction());

        $parser = new ControllerNameParser($this->getContainer()->get('kernel'));
        $resolver = new WebSocketControllerResolver($this->getContainer(), $parser);
        $controller = $resolver->getControllerFromMessage($message, $this->clients);

        if (!is_callable($controller)) {
            throw new \Exception('Controller is not callable');
        }

        return call_user_func($controller, $message);
    }

    /**
     * Sends a message
     *
     * @param Message $message
     */
    private function sendMessage(Message $message)
    {
        echo sprintf(
            'Sending Message : "%s" to %d client%s' . PHP_EOL,
            json_encode($message),
            count($message->getTo()),
            count($message->getTo()) > 1 ? 's' : ''
        );

        foreach ($message->getTo() as $dest) {
            $dest->send(json_encode($message));
        }
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Registers a user
     *
     * @param                     $userId
     * @param ConnectionInterface $from
     */
    private function registerUser($userId, ConnectionInterface $from)
    {
        echo sprintf('Registering user %d (%d)' . PHP_EOL, $userId, $from->resourceId);

        $url = $this->container->getParameter('api_authserver_register_mode');

        echo sprintf('Calling URL : %s' . PHP_EOL, $url);

        $resource = curl_init();

        $appName = $this->container->getParameter('appname');

        $headers = [
            'X-Enjoy-Application' => $appName,
            'X-Enjoy-API-KEY'     => $this->container->getParameter('api_authserver_key'),
            'X-Enjoy-API-PASS'    => $this->container->getParameter('api_authserver_pass')
        ];

        $requestHeaders = [];

        foreach ($headers as $header => $value) {
            $requestHeaders[] = sprintf('%s: %s', $header, (string) $value);
        }

        $postData = [
            'name'        => $appName . '_ws_' . $userId . ':' . $from->resourceId,
            'params'      => [
                'application_url'  => $this->container->getParameter('application_ip'),
                'application_port' => $this->container->getParameter('socketPort')
            ],
            'userId'      => $userId,
            'application' => $appName,
            'transport'   => 'websockets'
        ];

        $postDataStr = $this->stringifyData($postData);

        echo sprintf('Creating post : %s' . PHP_EOL, $postDataStr);

        curl_setopt_array($resource, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postDataStr,
            CURLOPT_HTTPHEADER     => $requestHeaders
        ]);

        $response = curl_exec($resource);

        echo 'Reveived data : ' . $response . PHP_EOL;

        echo 'Registering connection' . PHP_EOL;

        $this->usersConnections[$userId][] = $from;
        $this->usersConnectionsIds[$from->resourceId] = $userId;
        echo 'User registered' . PHP_EOL;
    }

    /**
     * Registers a user
     *
     * @param                     $userId
     * @param ConnectionInterface $from
     */
    private function unRegisterUser($userId, ConnectionInterface $from)
    {
        echo sprintf('Unregistering user %d (%d)' . PHP_EOL, $userId, $from->resourceId);

        $appName = $this->container->getParameter('appname');

        $url = sprintf(
            $this->container->getParameter('api_authserver_unregister_mode'),
            (string) $userId,
            'websockets',
            $appName
            );

        echo sprintf('Calling URL : %s' . PHP_EOL, $url);

        $resource = curl_init();

        $headers = [
            'X-Enjoy-Application' => $appName,
            'X-Enjoy-API-KEY'     => $this->container->getParameter('api_authserver_key'),
            'X-Enjoy-API-PASS'    => $this->container->getParameter('api_authserver_pass')
        ];

        $requestHeaders = [];

        foreach ($headers as $header => $value) {
            $requestHeaders[] = sprintf('%s: %s', $header, (string) $value);
        }

        curl_setopt_array($resource, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => $requestHeaders
        ]);

        $response = curl_exec($resource);

        echo sprintf('Response : %s' . PHP_EOL, $response);

        unset($this->usersConnections[$userId]);
        unset($this->usersConnectionsIds[$from->resourceId]);
        echo 'User unregistered' . PHP_EOL;
    }

    /**
     * Stringifies get and post data
     *
     * @param array  $data
     * @param string $level
     *
     * @return string
     */
    public function stringifyData(array $data = [], $level = '')
    {
        $keyValues = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $keyValues[] = $this->stringifyData($value, $key);
            } else {
                if (!empty($level)) {
                    $param = sprintf('%s[%s]', $level, $key);
                } else {
                    $param = $key;
                }
                $keyValues[] = sprintf('%s=%s', $param, urlencode($value));
            }
        }

        return implode('&', $keyValues);
    }
}