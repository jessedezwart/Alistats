<?php

namespace App\Command;

use App\Service\DatabaseService;
use App\Service\RiotApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class AddUserCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:add';

    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Adds a new user.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The summoner name of the user you want to add.');
        $this->addArgument('region', InputArgument::REQUIRED, 'The region of the user you want to add. Available regions: ' . implode(", ", RiotApi::REGIONS));
        $this->addArgument('guild_id', InputArgument::OPTIONAL, 'ID of the Discord guild');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Validate region
        if (!in_array(strtoupper($input->getArgument("region")), array_keys(RiotApi::REGIONS))) {
            $output->writeln("Region not valid. Available regions: " . implode(", ", RiotApi::REGIONS));
            return Command::FAILURE;
        }

        // Get user via riot api
        $riotApi = new RiotApi($input->getArgument("region"));
        try {
            $summoner = $riotApi->getSummoner($input->getArgument("name"));
        } catch (\Throwable $th) {
            $output->writeln("Summoner was not found.");
            return Command::FAILURE;
        }

        // Set up database connection
        $dbService = new DatabaseService(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"]
        );

        // Add to database
        $dbService->addSummoner(
            $summoner["id"],
            $summoner["accountId"],
            $summoner["puuid"],
            $summoner["name"],
            strtoupper($input->getArgument("region")),
            $input->getArgument("guild_id"),
        );
        return Command::SUCCESS;
    }
}