<?php

namespace App\Provider\Crawler;

use App\Service\RiotApi;
use Exception;

class MatchCrawler {

    protected $riotApi;

    public function __construct(RiotApi $riotApi)
    {
        $this->riotApi = $riotApi;
    }

    public function crawlMatchList(string $summonerAccountId, int $beginTime = 0) {
        strtotime($beginTime) * 1000; // Riot api uses milliseconds

        try {
            $matches = [];
            $beginIndex = 0;
            $lastMatchHistoryCrawl = strtotime($beginTime) * 1000; // Riot api uses milliseconds

            do {
                $endIndex = $beginIndex + 100;
                $matchlist = $this->riotApi->getMatchlist($summonerAccountId, $beginIndex, $endIndex, $lastMatchHistoryCrawl)["matches"];
                $matchCount = count($matchlist);
                $beginIndex += 100;
                sleep(2); // @todo dont hardcode api limits
                $matches = array_merge($matches, $matchlist);
            } while ($matchCount == 100);
        
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }

        return $matches;
    }

    public function crawlMatch($gameId) {
        // Check if game exists locally
        if (file_exists($_ENV["BASEDIR"] . "/data/matches/$gameId.json")) { 
            return;
        }

        // Get game
        $richGame = $this->riotApi->getMatchData($gameId);

        // Store game
        file_put_contents($_ENV["BASEDIR"] . "/data/matches/$gameId.json", json_encode($richGame));

        sleep(2);
    }

}