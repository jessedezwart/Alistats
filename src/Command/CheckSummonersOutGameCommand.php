<?php

namespace App\Command;

use App\Helper\RankHelper;
use App\Provider\Crawler\MatchCrawler;
use App\Provider\DiscordMessageProvider;
use App\Service\DatabaseService;
use App\Service\DiscordService;
use App\Service\RiotApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class CheckSummonersOutGameCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:check-out-game';

    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Check if summoners are out of game and update info if so. This should be run every minute or something.');
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
            if (!$summoner["is_in_game"]) {
                continue;
            }

            // @todo region should not be defined for the whole api class
            //  since we would be creating and destructing classes
            //     "on the walking tyre" (aan de lopende band)
            $riotApi = new RiotApi($summoner["region"]);
            $output->writeln("Getting active game for " . $summoner["name"]);
            $activeGame = $riotApi->getActiveGame($summoner["id"]);
            
            if ($activeGame) {
                $output->writeln($summoner["name"] . " is still in game.");
                continue;
            }

            $output->writeln($summoner["name"] . " is not in game.");
            $dbService->updateSummonerInGame($summoner["id"], false);

            // update info
            $discordService = new DiscordService;
            $discordMessageProvider = new DiscordMessageProvider($discordService);

            $output->writeln("Getting rank for " . $summoner["name"]);
            $ranks = $riotApi->getRank($summoner["id"]);
            
            foreach ($ranks as $rank) {
                
                // Get fancy rank name
                $newRank = RankHelper::getSummarizedRank($rank["tier"], $rank["rank"]);

                // Get current rank from database
                $currentRankAndLp = $dbService->getCurrentRank($summoner["id"], $rank["queueType"]);

                // If no rank is set, update into database and continue
                if (!$currentRankAndLp) {
                    $dbService->updateRank($summoner["id"], $rank["queueType"], $newRank, $rank["leaguePoints"]);
                    continue;
                }

                // If current rank is set but doesnt matches new rank, update in database
                if ($currentRankAndLp["ranking"] != $newRank) {
                    if (RankHelper::isRankHigher($currentRankAndLp["ranking"], $newRank)) {
                        //$discordMessageProvider->sendPromoteMessage($summoner["name"], $newRank, $rank["queueType"]);
                    } else {
                        //$discordMessageProvider->sendDemoteMessage($summoner["name"], $newRank, $rank["queueType"]);
                    }
                    $dbService->updateRank($summoner["id"], $rank["queueType"], $newRank, $rank["leaguePoints"]);
                } elseif ($currentRankAndLp["lp"] != $rank["rank"]) {
                    $dbService->updateRank($summoner["id"], $rank["queueType"], $newRank, $rank["leaguePoints"]);
                }
                
            }

            // Get all matches that can be indexed since last indextime
            try {
                $matchCrawler = new MatchCrawler($riotApi);
                $matches = $matchCrawler->crawlMatchList($summoner["account_id"], $summoner["last_match_history_crawl"]);
            } catch (\Throwable $th) {
                $output->writeln($th->getMessage());
                return Command::FAILURE;
            }

            $matchCount = count($matches);

        
            // Get all the matches and store locally if not already done
            foreach ($matches as $key => $match) {
                $progressCount = $key + 1;
                $output->writeln("Getting game $progressCount/$matchCount");

                $gameId = $match["gameId"];

                $matchCrawler->crawlMatch($gameId);

                sleep(2);
            }

            $dbService->updateLastMatchHistoryCrawl($summoner["id"]);
        }

        return Command::SUCCESS;
    }

}