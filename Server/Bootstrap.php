<?php
/**
 * Created by Enjoy Your Business.
 * Date: 20/11/2015
 * Time: 17:28
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Server;

use EnjoyYourBusiness\WebSocketClientBundle\Model\Message;
use EnjoyYourBusiness\websocketserverbundle\Bridge\Application\HeadersInterceptorInterface;
use EnjoyYourBusiness\WebSocketServerBundle\Bridge\Symfony\WebSocketControllerResolver;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var OutputInterface
     */
    private $output;

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
     *
     * @param OutputInterface             $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->clients = new \SplObjectStorage;
        $this->output->writeln('Created bootstrap');
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

        $this->output->writeln(sprintf('New connection! (<fg=cyan>%s</>)', $conn->resourceId));
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
                'action' => 'WebSocketServerBundle:WebSocketEvents:unregisterAll'
            ]
        );

        $response = $this->treatMessage($message, $conn);

        $this->sendMessage($response);

        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        // Unset connections for logout user
        $logoutUserId = $this->usersConnectionsIds[$conn->resourceId];
        if (is_int($logoutUserId)) {
            $this->unRegisterUser($logoutUserId, $conn);
            $this->output->writeln(sprinf('User (<fg=cyan>%s)</>has <fg=yellow>disconnected</>', $logoutUserId));
            $this->output->writeln(sprintf('User (<fg=cyan>%s)</>has disconnected', $logoutUserId));
        }

        $this->output->writeln('Connection {$conn->resourceId} has disconnected');
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
        $this->output->writeln(sprintf('An <bg=red;fg=white>error</> has occurred: <fg=red>%s</>', $e->getMessage()));

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
        $this->output->writeln('Received message :');
        $this->output->writeln(sprintf('<fg=cyan>%s</>', $msg));
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
                    $this->output->writeln('User not connected : message not send');

                    return false;
                }
            } else {
                $to = [];
            }
            $message = Message::create($from, $json, $to);

            $response = $this->treatMessage($message, $from);

            $this->sendMessage($response);
        } else {
            // If message is 'I'm 42 save user 42 and his websocket connection
            $isMessageWhoIm = strpos($msg, 'I\'m') !== false;
            if ($isMessageWhoIm) {
                $userId = intval(filter_var($msg, FILTER_SANITIZE_NUMBER_INT));
                $this->registerUser($userId, $from);
            } else {
                $this->output->writeln('<bg=red;fg=white>Error : message was not a JSON</>');
            }
        }
    }

    /**
     * Treats a message
     *
     * @param Message             $message
     * @param ConnectionInterface $from
     *
     * @return Message
     * @throws \Exception
     */
    private function treatMessage(Message $message, ConnectionInterface $from)
    {
        $this->output->writeln(sprintf('Controller called : <fg=cyan>"%s</>"', $message->getAction()));

        $parser = new ControllerNameParser($this->getContainer()->get('kernel'));
        $resolver = new WebSocketControllerResolver($this->getContainer(), $parser);
        $controller = $resolver->getControllerFromMessage($message, $from);

//        if (is_array($controller)) {
//            $callable = sprintf('%s::%s', get_class($controller[0]), $controller[1]);
//        } else {
//            $callable = $controller;
//        }

//        var_dump('===');
//        var_dump($callable);
//        var_dump('===');

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
        $this->output->writeln(sprintf(
            'Sending Message : <fg=cyan>"%s"</> to <fg=yellow>%d</> client%s',
            json_encode($message),
            count($message->getTo()),
            count($message->getTo()) > 1 ? 's' : ''
        ));

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
        $this->output->writeln(sprintf('Registering user <fg=cyan>%d</> (<fg=yellow>%d</>)', $userId, $from->resourceId));

        $url = $this->container->getParameter('api_authserver_register_mode');

        $this->output->writeln(sprintf('Calling URL : <fg=cyan>%s</>', $url));

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

        $this->output->writeln(sprintf('Creating post : <fg=green>%s</>', $postDataStr));

        curl_setopt_array($resource, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postDataStr,
            CURLOPT_HTTPHEADER     => $requestHeaders
        ]);

        $response = curl_exec($resource);

        $this->output->writeln(sprintf('Reveived data : <fg=green>%s</>', $response));

        $this->output->writeln('Registering connection');

        $this->usersConnections[$userId][] = $from;
        $this->usersConnectionsIds[$from->resourceId] = $userId;
        $this->output->writeln('User registered');
    }

    /**
     * Registers a user
     *
     * @param                     $userId
     * @param ConnectionInterface $from
     */
    private function unRegisterUser($userId, ConnectionInterface $from)
    {
        $this->output->writeln(sprintf('Unregistering user <fg=cyan>%d</> (<fg=yellow>%d</>)', $userId, $from->resourceId));

        $appName = $this->container->getParameter('appname');

        $url = sprintf(
            $this->container->getParameter('api_authserver_unregister_mode'),
            (string) $userId,
            'websockets',
            $appName
            );

        $this->output->writeln(sprintf('Calling URL : <fg=cyan>%s</>', $url));

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

        $this->output->writeln(sprintf('Response : <bg=white;fg=black>%s</>', $response));

        unset($this->usersConnections[$userId]);
        unset($this->usersConnectionsIds[$from->resourceId]);
        $this->output->writeln('User unregistered');
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