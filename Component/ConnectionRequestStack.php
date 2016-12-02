<?php
/**
 * Created by Enjoy Your Business.
 * Date: 30/11/2016
 * Time: 10:35
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Component;
use Guzzle\Http\Message\Request;
use Ratchet\ConnectionInterface;


/**
 * Class ConnectionRequestStack
 *
 * @package   EnjoyYourBusiness\websocketserverbundle\Component
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Lucien Bruneau <lucien.bruneau@enjoyyourbusiness.fr>
 * @author    Matthieu Prieur <matthieu.prieur@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class ConnectionRequestStack
{
    /**
     * @var array
     */
    private $requests = [];

    /**
     * @return ConnectionRequestStack
     */
    public static function getInstance()
    {
        static $instance;

        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * ConnectionRequestStack constructor.
     */
    private function __construct()
    {
    }

    /**
     * Sets a request for a connection
     *
     * @param ConnectionInterface $connection
     * @param Request             $request
     */
    public function setRequestForConnection(ConnectionInterface $connection, Request $request)
    {
        $this->requests[$connection->resourceId] = $request;
    }

    /**
     * Gets a request for a connection
     *
     * @param ConnectionInterface $connection
     *
     * @return Request
     *
     * @throws \Exception
     */
    public function getRequestForConnection(ConnectionInterface $connection): Request
    {
        if (!array_key_exists($connection->resourceId, $this->requests) or !($this->requests[$connection->resourceId] instanceof Request)) {
            throw new \Exception(sprintf('No request found for connection "%s"', $connection->resourceId));
        }

        return $this->requests[$connection->resourceId];
    }

    /**
     * Forgets a connection
     *
     * @param ConnectionInterface $connection
     */
    public function forgetConnection(ConnectionInterface $connection)
    {
        unset($this->requests[$connection->resourceId]);
    }
}