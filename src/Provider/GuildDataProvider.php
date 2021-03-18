<?php

namespace App\Provider;

use App\Helper\RankHelper;
use App\Service\DatabaseService;
use Carbon\Carbon;

class GuildDataProvider {

    protected int $guildId;
    protected DatabaseService $dbService;

    public function __construct($guildId)
    {
        $this->guildId = $guildId;
        $this->dbService = new DatabaseService(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"]
        );
    }

    // for every day, get most recent rank from unique summoners and get average
    // @todo should be per guild and cleaned up a bit
    public function getAverageRankPerDay() {
        // get the full rank history
        $rankHistory = $this->dbService->getRankHistory();

        foreach ($rankHistory as &$rankEntry) {
            $unsummarizedRank = RankHelper::getUnsummarizedRank($rankEntry["ranking"]);
            $rankEntry["rankWorth"] = RankHelper::getRankWorth($unsummarizedRank[0], $unsummarizedRank[1], $rankEntry["lp"]);

            // convert datetime to timestamp for easy handling
            $rankEntry["timestamp"] = new Carbon($rankEntry["timestamp"]);
        }

        unset($rankEntry); // unset because of weird behaviour when var name gets reused

        $beginTime = $rankHistory[0]["timestamp"];


        $endTime = $rankHistory[array_key_last ($rankHistory)]["timestamp"];

        $daysSinceBeginTime = $beginTime->diffInDays($endTime);
        
        $selectedEntries = [];
        $averageWorthPerDay = [];
        for ($day=0; $day <= $daysSinceBeginTime; $day++, $beginTime->addDay()) { 

            $dateTime = $beginTime->toDateString();

            $selectedEntries[$dateTime] = [];
            
            $viableEntries = [];

            foreach ($rankHistory as $rankEntry) {
                if ($rankEntry["timestamp"]->isAfter($beginTime)) {
                    break;
                }

                $viableEntries[] = $rankEntry;
            }

            // Reverse entries and select the first for each entry
            $viableEntries = array_reverse($viableEntries);

            foreach ($viableEntries as $entry) {
                if (!array_key_exists($entry["summoner_id"], $selectedEntries[$dateTime])) {
                    $selectedEntries[$dateTime][$entry["summoner_id"]] = $entry;
                }
            }

            $averageWorthPerDay[$dateTime] = array_sum(array_column($selectedEntries[$dateTime], "rankWorth")) / count($selectedEntries[$dateTime]);

        }


        return $averageWorthPerDay;
        
    }

    

}