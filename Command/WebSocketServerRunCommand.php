<?php
/**
 * Created by Enjoy Your Business.
 * Date: 19/11/2015
 * Time: 08:51
 * Copyright 2014 Enjoy Your Business - RCS Bourges B 800 159 295 ©
 */

namespace EnjoyYourBusiness\WebSocketServerBundle\Command;

use EnjoyYourBusiness\WebSocketServerBundle\Server\Bootstrap;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
class WebSocketServerRunCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'eyb:websocket:run';
    const COMMAND_DESCRIPTION = 'Starts a web socket server for meetings';
    const ID_SERVICE_FACTORY = 'eyb_base.factory.service';
    const TEXT_GREETINGS = 'Démarrage du serveur de websockets';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    private function getPortToListen()
    {
        return (int) $this->getContainer()->getParameter('enjoy_socket_port');
    }

    private function getIp()
    {
        return $this->getContainer()->getParameter('enjoy_socket_ip');
    }

    /**
     * Executes the command instructions
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventsMessagehandler = new Bootstrap();
        $eventsMessagehandler->setContainer($this->getContainer());

        $output->writeln('<info>Démarrage</info>');

        $output->writeln(sprintf('<info>Will listen on port <comment>%d</comment>, ip <comment>%s</comment></info>', $this->getPortToListen(), $this->getIp()));

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $eventsMessagehandler
                )
            ),
            $this->getPortToListen()
        );

        $output->writeln(sprintf('<info>Listening on port <comment>%d</comment>, ip <comment>%s</comment></info>', $this->getPortToListen(), $this->getIp()));

        $server->run();
        $output->writeln('<info>Serveur fermé</info>');
    }
}