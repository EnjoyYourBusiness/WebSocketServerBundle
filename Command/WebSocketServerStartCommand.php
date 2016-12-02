<?php
/**
 * Created by Enjoy Your Business.
 * Date: 29/11/2016
 * Time: 15:58
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Command;
use Thuata\ListenerBundle\Command\StartListenerCommand;


/**
 * Class WebSocketServerStartCommand
 *
 * @package   EnjoyYourBusiness\websocketserverbundle\Command
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Lucien Bruneau <lucien.bruneau@enjoyyourbusiness.fr>
 * @author    Matthieu Prieur <matthieu.prieur@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class WebSocketServerStartCommand extends StartListenerCommand
{
    const COMMAND_NAME = 'eyb:websocket:start';
    const COMMAND_DESCRIPTION = 'Starts a web socket server for meetings';

    /**
     * Gets the port to listen
     *
     * @return int
     */
    private function getPortToListen()
    {
        return (int) $this->getContainer()->getParameter('enjoy_socket_port');
    }

    /**
     * Gets the command name
     *
     * @return string
     */
    public function getCommandName() : string
    {
        return self::COMMAND_NAME;
    }

    /**
     * Gets the command description
     *
     * @return string
     */
    public function getCommandDescription() : string
    {
        return self::COMMAND_DESCRIPTION;
    }

    /**
     * Gets the run listener command name
     *
     * @return string
     */
    protected function getRunListeningCommandName() : string
    {
        return WebSocketServerRunCommand::COMMAND_NAME;
    }

    /**
     * Gets the help for the command
     *
     * @return string
     */
    protected function getCommandHelp() : string
    {
        return '';
    }

    /**
     * Gets the stop command message
     *
     * @return string
     */
    protected function getStopCommandMessage() : string
    {
        return WebSocketServerRunCommand::CMD_STOP;
    }

    /**
     * Gets the default port to listen
     *
     * @return int
     */
    protected function getDefaultPort(): int
    {
        return $this->getPortToListen();
    }
}