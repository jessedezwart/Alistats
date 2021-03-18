<?php

namespace App\Helper;


class RankHelper {
    const TIER_ORDER = [
        "IRON",
        "BRONZE",
        "SILVER",
        "GOLD",
        "PLATINUM",
        "DIAMOND",
        "MASTER",
        "GRANDMASTER",
        "CHALLENGER"
    ];

    const RANK_ORDER = [
        "V",
        "III",
        "II",
        "I"
    ];

    public static function getRankWorth($tier, $rank, $lp) {
        $tierWorth = array_search($tier, self::TIER_ORDER) * 400;
        $rankWorth = array_search($rank, self::RANK_ORDER) * 100;

        return $tierWorth + $rankWorth + $lp;
    }

    public static function isRankHigher($oldRank, $newRank) {
        $oldRank = self::getUnsummarizedRank($oldRank);
        $newRank = self::getUnsummarizedRank($newRank);

        $oldTierWorth = array_search($oldRank[0], self::TIER_ORDER);
        $newTierWorth = array_search($newRank[0], self::TIER_ORDER);

        if ($oldTierWorth < $newTierWorth) {
            return true;
        } elseif ($oldTierWorth > $newTierWorth) {
            return false;
        }

        $oldRankWorth = array_search($oldRank[1], self::RANK_ORDER);
        $newRankWorth = array_search($newRank[1], self::RANK_ORDER);

        if ($oldRankWorth < $newRankWorth) {
            return true;
        }
        
        return false;
    }

    public static function getSummarizedRank($tier, $rank) {
        return "$tier $rank";
    }

    public static function getUnsummarizedRank($rank) {
        return explode(" ", $rank);
    }
}