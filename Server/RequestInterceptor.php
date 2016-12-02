<?php
/**
 * Created by Enjoy Your Business.
 * Date: 29/11/2016
 * Time: 18:09
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Server;

use EnjoyYourBusiness\websocketserverbundle\Bridge\Application\HeadersInterceptorInterface;
use EnjoyYourBusiness\WebSocketServerBundle\Component\ConnectionRequestStack;
use Guzzle\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * Class RequestInterceptor
 *
 * @package   EnjoyYourBusiness\websocketserverbundle\Server
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Lucien Bruneau <lucien.bruneau@enjoyyourbusiness.fr>
 * @author    Matthieu Prieur <matthieu.prieur@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class RequestInterceptor implements HttpServerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var HttpServerInterface
     */
    private $delegate;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * RequestInterceptor constructor.
     *
     * @param HttpServerInterface         $delegate
     * @param ContainerInterface          $container
     * @param OutputInterface             $output
     */
    public function __construct(HttpServerInterface $delegate, ContainerInterface $container, OutputInterface $output)
    {
        $this->delegate = $delegate;
        $this->container = $container;
        $this->output = $output;
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     *
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     *
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->delegate->onClose($conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     *
     * @param  ConnectionInterface $conn
     * @param  \Exception          $e
     *
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->delegate->onError($conn, $e);
    }

    /**
     * @param \Ratchet\ConnectionInterface          $conn
     * @param \Guzzle\Http\Message\RequestInterface $request null is default because PHP won't let me overload; don't pass null!!!
     *
     * @throws \UnexpectedValueException if a RequestInterface is not passed
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        ConnectionRequestStack::getInstance()->setRequestForConnection($conn, $request);

        $this->delegate->onOpen($conn, $request);
    }

    /**
     * Triggered when a client sends data through the socket
     *
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string                       $msg The message received
     *
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->delegate->onMessage($from, $msg);
    }
}