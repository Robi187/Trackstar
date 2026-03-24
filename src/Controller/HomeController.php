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
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Tracks',
        ]);
        $contents = json_decode($response->getContent(), true);
        return $this->render('home/index.html.twig', [
            'contents' => $contents,
        ]);
    }

    #[Route('/entdecke-tracks', name: 'app_tracks')]
    public function exploreTracks(): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Tracks',
        ]);
        $contents = json_decode($response->getContent(), true);

        return $this->render('home/tracks.html.twig', [
            'contents' => $contents,

        ]);
    }

    #[Route('/entdecke-beats', name: 'app_beats')]
    public function exploreBeats(): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Beats',
        ]);
        $contents = json_decode($response->getContent(), true);
        return $this->render('home/beats.html.twig', [
            'contents' => $contents,

        ]);
    }

    #[Route('/entdecke-sound-kits', name: 'app_sound_kits')]
    public function exploreSoundKits(): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Sound Kits',
        ]);
        $contents = json_decode($response->getContent(), true);

        return $this->render('home/soundkits.html.twig', [
            'contents' => $contents,

        ]);
    }

    #[Route('/entdecke-loop-und-samples', name: 'app_loop_und_samples')]
    public function exploreLoopUndSamples(): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Samples',
        ]);
        $contents = json_decode($response->getContent(), true);

        return $this->render('home/samples.html.twig', [
            'contents' => $contents,

        ]);
    }
}
