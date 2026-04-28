<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavoritesController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites')]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('favorites/index.html.twig', [
            'controller_name' => 'FavoritesController',
            'user_data' => $user,
        ]);
    }
}
