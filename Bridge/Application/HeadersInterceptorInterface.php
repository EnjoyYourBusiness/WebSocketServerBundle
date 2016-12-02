<?php
/**
 * Created by Enjoy Your Business.
 * Date: 29/11/2016
 * Time: 19:17
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Bridge\Application;

use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Interface HeadersIntercaptorInterface
 *
 * @package   EnjoyYourBusiness\websocketserverbundle\Bridge\Application
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Lucien Bruneau <lucien.bruneau@enjoyyourbusiness.fr>
 * @author    Matthieu Prieur <matthieu.prieur@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
interface HeadersInterceptorInterface
{
    public function treatHeaders(array $headers, ConnectionInterface $connection, OutputInterface $output);
}