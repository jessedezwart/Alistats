<?php

namespace App\Provider;

use App\Helper\QueueHelper;

class DiscordMessageProvider {

    /**
     * @var App\Service\DiscordService
     */
    protected $discordService;

    public function __construct($discordService)
    {
        $this->discordService = $discordService;
    }

    public function sendPromoteMessage($summonerName, $rank, $queue) {
        $queue = QueueHelper::getFancyQueueName($queue);
        $this->discordService->sendMessage(
            sprintf($this->getRandomPromoteTerm(), $summonerName),
            "$summonerName promoted to $rank in $queue. @everyone",
            3066993, // green
            $this->getRankImageUrl($rank)
        );
    }

    public function sendDemoteMessage($summonerName, $rank, $queue) {
        $queue = QueueHelper::getFancyQueueName($queue);
        $this->discordService->sendMessage(
            sprintf($this->getRandomDemoteTerm(), $summonerName),
            "$summonerName demoted to $rank in $queue. @everyone",
            15158332, // red
            $this->getRankImageUrl($rank)
        );
    }

    private function getRankImageUrl($rank) {
        return $_ENV["RANK_IMAGE_HOST"] . str_replace(" ", "_", $rank) . ".png";
    }

    private function getRandomPromoteTerm() {
        $sentences = file($_ENV["BASEDIR"] . "/data/promote_terms.txt", FILE_IGNORE_NEW_LINES);

        // Clean out empty lines
        foreach ($sentences as $key => $sentence) {
            if (!$sentence) unset($sentences[$key]);
        }
        $sentences = array_values($sentences);
        
        $randomKey = rand(0, count($sentences) - 1);
        
        return $sentences[$randomKey];
    }

    private function getRandomDemoteTerm() {
        $sentences = file($_ENV["BASEDIR"] . "/data/demote_terms.txt", FILE_IGNORE_NEW_LINES);

        // Clean out empty lines
        foreach ($sentences as $key => $sentence) {
            if (!$sentence) unset($sentences[$key]);
        }
        $sentences = array_values($sentences);

        // Get random key
        $randomKey = rand(0, count($sentences) - 1);
        
        return $sentences[$randomKey];
    }

}