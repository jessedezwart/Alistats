<?php

namespace App\Command;

use App\Helper\RankHelper;
use App\Provider\Crawler\MatchCrawler;
use App\Service\DatabaseService;
use App\Service\RiotApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\ProgressBar;

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

        // Get all user data
        $output->writeln("Getting rank for " . $summoner["name"]);
        $ranks = $riotApi->getRank($summoner["id"]);

        foreach ($ranks as $rank) {
            $newRank = RankHelper::getSummarizedRank($rank["tier"], $rank["rank"]);
            $dbService->updateRank($summoner["id"], $rank["queueType"], $newRank, $rank["leaguePoints"]);
        }

        // Get match list
        $output->writeln("Get whole available matchlist");
        try {
            $matchCrawler = new MatchCrawler($riotApi);
            $matches = $matchCrawler->crawlMatchList($summoner["accountId"]);
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
            return Command::FAILURE;
        }

        $matchCount = count($matches);
        $output->writeln("Get all available matches");
        $progressBar = new ProgressBar($output, $matchCount);
        $progressBar->start();

        // Get all the matches and store locally if not already done
        foreach ($matches as $match) {
            $gameId = $match["gameId"];
            $matchCrawler->crawlMatch($gameId);

            $progressBar->advance();
        }

        return Command::SUCCESS;
    }
}