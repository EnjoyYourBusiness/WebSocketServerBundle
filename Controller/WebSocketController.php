<?php
/**
 * Created by Enjoy Your Business.
 * Date: 27/11/2015
 * Time: 16:58
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class WebSocketController
 *
 * @package   Eyb\HomeBundle\WebSocket
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class WebSocketController extends Controller
{
    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * Sets the clients
     *
     * @param \SplObjectStorage $clients
     */
    public function setClients(\SplObjectStorage $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Gets the clients
     *
     * @return mixed
     */
    protected function getClients()
    {
        return $this->clients;
    }
}