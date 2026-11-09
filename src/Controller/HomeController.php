<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $featuredGames = [
            [
                'category' => 'COMPETITIVE FPS',
                'title' => 'Counter-Strike 2',
                'description' => 'The legendary tactical shooter experience with updated graphics and server sub-tick precision.',
                'image' => 'pc.png'
            ],
            [
                'category' => 'COMMUNITY FAVORITE',
                'title' => 'Minecraft',
                'description' => 'Explore infinite world creation, survival servers, and custom multiplayer mini-games.',
                'image' => 'pc2.png'
            ],
            [
                'category' => 'FIGHTING GAME',
                'title' => 'Tekken 8',
                'description' => 'Experience next-gen 3D fighting action with intense heat mechanics and fluid combat.',
                'image' => 'ps3.png'
            ],
            [
                'category' => 'LATEST UPDATES',
                'title' => 'Call of Duty: Black',
                'description' => 'Keep up to date with the most recent map packs and season drops.',
                'image' => 'pc4.png'
            ],
            [
                'category' => 'TACTICAL SHOOTER',
                'title' => 'Valorant',
                'description' => 'Master precise gunplay and unique character abilities in intense 5v5 tactical matches.',
                'image' => 'pc7.png'
            ],
            [
                'category' => 'ACTION & HEROES',
                'title' => 'Marvel Tokon',
                'description' => 'Assemble your favorite Marvel heroes and clash in epic multiplayer team battles.',
                'image' => 'ps5.png'
            ],
        ];

        return $this->render('home/index.html.twig', [
            'featured_games' => $featuredGames,
        ]);
    }
}