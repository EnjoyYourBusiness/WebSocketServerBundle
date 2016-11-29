<?php
/**
 * Created by Enjoy Your Business.
 * Date: 27/11/2015
 * Time: 16:33
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Controller;

use EnjoyYourBusiness\WebSocketClientBundle\Model\Message;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;

/**
 * Class WebSocketControllerResolver
 *
 * @package   Eyb\HomeBundle\WebSocket
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Otman Haiti <otman.haiti@enjoyyourbusiness.fr>
 * @author    Nabil Selfaoui <nabil.selfaoui@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class WebSocketControllerResolver extends ControllerResolver
{
    const EXCEPTION_CONTROLLER_NOT_WSCONTROLLER = 'Controller "%s" is not a subclass of "%s"';

    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * @param \SplObjectStorage $clients
     *
     * @return WebSocketControllerResolver
     */
    public function setClients(\SplObjectStorage $clients)
    {
        $this->clients = $clients;

        return $this;
    }

    /**
     * Gets a controller from a message
     *
     * @param Message $message
     *
     * @return callable
     */
    public function getControllerFromMessage(Message $message)
    {
        return $this->createController($message->getAction());
    }

    /**
     * Returns an instantiated controller.
     *
     * @param string $class A class name
     *
     * @return WebSocketController
     *
     * @throws \Exception
     */
    protected function instantiateController($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        if (!$reflectionClass->isSubclassOf(WebSocketController::class)) {
            throw new \Exception(sprintf(self::EXCEPTION_CONTROLLER_NOT_WSCONTROLLER, $class, WebSocketController::class));
        }

        $controller = $reflectionClass->newInstance();
        /* @var WebSocketController $controller */
        $controller->setClients($this->clients ?: new \SplObjectStorage());
        $controller->setContainer($this->container);

        return $controller;
    }
}