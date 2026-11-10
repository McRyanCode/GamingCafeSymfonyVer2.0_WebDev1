<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    #[Route('/events/prove-your-skill', name: 'app_event_prove_skill')]
    public function proveYourSkill(): Response
    {
        return $this->render('event/prove_your_skill.html.twig');
    }
}