<?php

namespace App\Command;

use App\Helper\RankHelper;
use App\Provider\DiscordMessageProvider;
use App\Service\DatabaseService;
use App\Service\DiscordService;
use App\Service\RiotApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class CheckSummonersInGameCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:check-in-game';

    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Check if summoners are in game. This should be run every 15 minutes or something.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up database connection
        $dbService = new DatabaseService(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"]
        );

        // Get all summoner info
        $summoners = $dbService->getSummoners();
       
        foreach ($summoners as $summoner) {
            // @todo region should not be defined for the whole api class
            //  since we would be creating and destructing classes
            //     "on the walking tyre" (aan de lopende band)
            $riotApi = new RiotApi($summoner["region"]);
            $output->writeln("Getting active game for " . $summoner["name"]);
            $activeGame = $riotApi->getActiveGame($summoner["id"]);
            
            if ($activeGame) {
                $output->writeln($summoner["name"] . " is in game.");
                $dbService->updateSummonerInGame($summoner["id"], true);
            } else {
                $output->writeln($summoner["name"] . " is not in game.");
            }
            
        }

        return Command::SUCCESS;
    }

}