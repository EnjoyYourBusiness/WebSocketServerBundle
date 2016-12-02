<?php
/**
 * Created by Enjoy Your Business.
 * Date: 19/11/2015
 * Time: 08:51
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Command;

use EnjoyYourBusiness\WebSocketServerBundle\Server\Bootstrap;
use EnjoyYourBusiness\WebSocketServerBundle\Server\RequestInterceptor;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thuata\ListenerBundle\Command\RunListenerCommand;
use Thuata\ListenerBundle\Component\Listener;

/**
 * Class MeetingServerCommand
 *
 * @package   Eyb\CompanyBundle\Command
 *
 * @author    Emmanuel Derrien <emmanuel.derrien@enjoyyourbusiness.fr>
 * @author    Anthony Maudry <anthony.maudry@enjoyyourbusiness.fr>
 * @author    Loic Broc <loic.broc@enjoyyourbusiness.fr>
 * @author    Rémy Mantéi <remy.mantei@enjoyyourbusiness.fr>
 * @author    Otman Haiti <otman.haiti@enjoyyourbusiness.fr>
 * @author    Nabil Selfaoui <nabil.selfaoui@enjoyyourbusiness.fr>
 * @copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */
class WebSocketServerRunCommand extends RunListenerCommand
{
    const COMMAND_NAME = 'eyb:websocket:run';
    const COMMAND_DESCRIPTION = 'Starts a web socket server for meetings';
    const TEXT_GREETINGS = 'Démarrage du serveur de websockets';

    /**
     * @var int
     */
    private static $defaultPort;

    /**
     * Sets the default port
     *
     * @param int $defaultPort
     */
    public static function setDefaultPort(int $defaultPort)
    {
        self::$defaultPort = $defaultPort;
    }

    /**
     * Gets the port to listen
     *
     * @return int
     */
    private function getPortToListen(): int
    {
        return static::$defaultPort;
    }

    private function getIp()
    {
        return $this->getContainer()->getParameter('enjoy.websocket.client.ip');
    }

    /**
     * Runs the listener
     *
     * @param Listener        $listener
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function runListener(Listener $listener, InputInterface $input, OutputInterface $output)
    {

        $eventsMessageHandler = new Bootstrap($output);
        $eventsMessageHandler->setContainer($this->getContainer());

        $port = $input->getOption(self::OPTION_PORT);

        $server = IoServer::factory(
            new HttpServer(
                new RequestInterceptor(
                    new WsServer(
                        $eventsMessageHandler
                    ),
                    $this->getContainer(),
                    $output
                )
            ),
            $this->getPortToListen()
        );

        $output->writeln(sprintf('<info>Listening on port <comment>%d</comment>, ip <comment>%s</comment></info>', $port, $this->getIp()));

        $server->run();
        $output->writeln('<info>Serveur fermé</info>');
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
        return self::COMMAND_NAME;
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
        return RunListenerCommand::CMD_STOP;
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