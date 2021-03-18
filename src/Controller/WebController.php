<?php

namespace App\Controller;

use App\Provider\GuildDataProvider;
use App\Service\DatabaseService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class WebController extends AbstractController
{

    /**
     * @Route("/")
     */
    public function renderHome(): Response
    {
        return $this->render('home.html.twig');
    }

    /**
     * @Route("/{guildId}/overview")
     */
    public function renderOverview(int $guildId): Response
    {
        // @todo get guild from db
        $dbService = new DatabaseService(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"]
        );

        $guild = $dbService->getGuild($guildId);

        $guildDataProvider = new GuildDataProvider($guildId);

        $guildDataProvider->getAverageRankPerDay();

        return $this->render('overview.html.twig', ["guild" => $guild]);
    }

    /**
     * @Route("/{guildId}/ranked_ladder")
     */
    public function renderRankedLadder(int $guildId): Response
    {
        // @todo get guild from db
        $dbService = new DatabaseService(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"]
        );

        $guild = $dbService->getGuild($guildId);
        $guildMembers = $dbService->getGuildMembers($guildId);
        foreach ($guildMembers as &$guildMember) {
            $guildMember["currentRank"]["RANKED_SOLO_5X5"] = $dbService->getCurrentRank($guildMember["id"], "RANKED_SOLO_5X5");
            $guildMember["currentRank"]["RANKED_FLEX_SR"] = $dbService->getCurrentRank($guildMember["id"], "RANKED_FLEX_SR");
        }


        return $this->render('ranked_ladder.html.twig', ["guild" => $guild, "guildMembers" => $guildMembers]);
    }
}