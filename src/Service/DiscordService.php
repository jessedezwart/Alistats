<?php

namespace App\Service;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

class DiscordService {

    protected $webhookUrl;
    protected $httpClient;

    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . "/../../.env");

        $this->webhookUrl = $_ENV["DISCORD_WEBHOOK"];
        $this->httpClient = HttpClient::create();
    }

    public function sendMessage($title, $description, $color, $imageUrl) {
        $response = $this->httpClient->request(
            'POST',
            $this->webhookUrl,
            [
                'json' => [
                    "embeds" => [
                            [
                            "title" => $title,
                            "description" => $description,
                            "color" => $color,
                            "image" => [
                                "url"=> $imageUrl
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

}