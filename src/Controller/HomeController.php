<?php

namespace App\Controller;
use App\Controller\ContentController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/entdecke-tracks', name: 'app_home')]
    public function exploreTracks(): Response
    {
        $response =  $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Tracks',
        ]);
        $contents = json_decode($response->getContent(), true);
        var_dump($contents);

        return $this->render('home/tracks.html.twig', [
            'contents' => $contents,

        ]);
    }
}
