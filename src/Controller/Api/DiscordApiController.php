<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api/discord")
 */
class DiscordApiController extends AbstractController
{

    /**
     * @Route("/event", methods={"POST"})
     */
    public function processIncomingWebhook(): Response
    {
        return $this->render('home.html.twig');
    }
}