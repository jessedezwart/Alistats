<?php

namespace App\Service;

use Exception;
use mysqli;

class DatabaseService {

    protected $conn;

    public function __construct($host, $username, $password, $name)
    {
        $this->conn = new mysqli(
            $host,
            $username,
            $password,
            $name
        );

        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getCurrentRank($summonerId, $queueType) {
        // Check if summoner already exists
        $stmt = $this->conn->prepare("SELECT `ranking`,`lp` FROM rank_history WHERE summoner_id=? AND queue_type=? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("ss", $summonerId, $queueType);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($result)) {
            return $result[0];
        }
        
        return;
    }

    public function updateRank($summonerId, $queueType, $rank, $lp) {
        $stmt = $this->conn->prepare("INSERT INTO rank_history (summoner_id, queue_type, ranking, lp) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $summonerId, $queueType, $rank, $lp);
        $stmt->execute();
    }

    public function addSummoner($summonerId, $accountId, $puuid, $name, $region, $guildId) {
        // Check if summoner already exists
        $stmt = $this->conn->prepare("SELECT * FROM summoners WHERE id=?");
        $stmt->bind_param("s", $summonerId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Summoner already exists in database.");
        }

        // Insert
        $stmt = $this->conn->prepare("INSERT INTO summoners (id, account_id, puuid, guild_id, name, region) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $summonerId, $accountId, $puuid, $guildId, $name, $region);
        $stmt->execute();

        if ($err = $this->conn->error) {
            throw new Exception($err);
        }
    }

    public function getSummoners() {
        $query = "SELECT * FROM summoners";
        $result = $this->conn->query($query);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateSummonerInGame($summonerId, bool $inGame) {
        $inGame = (int)$inGame;
        $time = time();

        $stmt = $this->conn->prepare("UPDATE summoners SET is_in_game=?, last_checked_in_game=FROM_UNIXTIME(?) WHERE id=?");
        $stmt->bind_param("iis", $inGame, $time, $summonerId);
        $stmt->execute();
    }

    public function updateLastMatchHistoryCrawl($summonerId) {
        $time = time();

        $stmt = $this->conn->prepare("UPDATE summoners SET last_match_history_crawl=FROM_UNIXTIME(?) WHERE id=?");
        $stmt->bind_param("is", $time, $summonerId);
        $stmt->execute();
    }

    public function getGuild($discordId) {
        $stmt = $this->conn->prepare("SELECT * FROM guilds WHERE discord_id=?");
        $stmt->bind_param("i", $discordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($result)) {
            return $result[0];
        }
        
        return;
    }

    public function getGuildMembers($guildId) {
        // Check if summoner already exists
        $stmt = $this->conn->prepare("SELECT id,name,is_in_game FROM summoners WHERE guild_id=?;");
        $stmt->bind_param("i", $guildId);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        
        return $result;
    }
 
    // @todo should be per guild
    public function getRankHistory() {
        // Check if summoner already exists
        $stmt = $this->conn->prepare("SELECT summoner_id, queue_type, ranking, lp, timestamp FROM rank_history ORDER BY timestamp ASC;");
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        
        return $result;
    }
    

    public function __destruct()
    {
        $this->conn->close();
    }
}