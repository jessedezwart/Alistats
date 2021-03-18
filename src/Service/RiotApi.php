<?php

namespace App\Service;

use Exception;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;

class RiotApi {

    const REGIONS = [
        "RU" => "RU1",
        "KR" => "KR1",
        "BR" => "BR1",
        "OC" => "OC1",
        "JP" => "JP1",
        "NA" => "NA1",
        "EUNE" => "EUN1",
        "EUW" => "EUW1",
        "TR" => "TR1",
        "LA1" => "LA1",
        "LA2" => "LA2"
    ];

    protected $apiKey;
    protected $httpClient;
    protected $region;
    protected $baseUrl;

    public function __construct($region)
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . "/../../.env");

        $this->apiKey = $_ENV["API_KEY"];
        $this->baseUrl = $_ENV["API_BASE_URL"];
        $this->httpClient = HttpClient::create();

        $this->region = self::REGIONS[strtoupper($region)];
    }

    protected function getData($endpoint, $urlArgument = "", $extraArguments = []) {
        $response = $this->httpClient->request(
            'GET',
            "https://" . $this->region . "." . $this->baseUrl . $endpoint . $urlArgument,
            [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
                'query' => $extraArguments
            ]
        );

        return json_decode($response->getContent(true), true);
    }

    public function getSummoner($summonerName) {
        return $this->getData("summoner/v4/summoners/by-name/", $summonerName, []);
    }

    public function getRank($summonerId) {
        return $this->getData("league/v4/entries/by-summoner/", $summonerId);
    }

    public function getActiveGame($summonerId) {
        try {
            $activeGame = $this->getData("spectator/v4/active-games/by-summoner/", $summonerId);
        } catch (\Throwable $th) {
            if (strpos($th->getMessage(), "404")) {
                return;
            }
        }

        return $activeGame;
    }

    public function getMatchlist($encryptedAccountId, int $beginIndex = 0, int $endIndex = 100, $beginTime = 0) {
        if ($beginIndex < 0 || (($endIndex - $beginIndex) > 100)) {
            throw new Exception("Index out of bounds.");
        }

        return $this->getData("match/v4/matchlists/by-account/", $encryptedAccountId, ["beginIndex" => $beginIndex, "endIndex" => $endIndex, "beginTime" => $beginTime]);
    }

    public function getMatchData($gameId) {
        return $this->getData("match/v4/matches/", $gameId);
    }

}