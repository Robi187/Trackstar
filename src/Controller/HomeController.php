<?php

namespace App\Controller;
use App\Entity\Content;
use App\Entity\ContentTag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function root(): RedirectResponse
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/home', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Tracks',
        ]);
        $contents = json_decode($response->getContent(), true);

        $tagsByContent = [];
        foreach ($contents as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if ($content) {
                $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
            }
        }

        return $this->render('home/index.html.twig', [
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
            'title' => 'Entdecke neue Musik',
        ]);
    }

    #[Route('/entdecke-tracks', name: 'app_tracks')]
    public function exploreTracks(EntityManagerInterface $em): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Tracks',
        ]);
        $contents = json_decode($response->getContent(), true);

        $tagsByContent = [];
        foreach ($contents as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if ($content) {
                $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
            }
        }

        return $this->render('home/index.html.twig', [
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
            'title' => 'Entdecke Tracks',
        ]);
    }

    #[Route('/entdecke-beats', name: 'app_beats')]
    public function exploreBeats(EntityManagerInterface $em): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Beats',
        ]);
        $contents = json_decode($response->getContent(), true);

        $tagsByContent = [];
        foreach ($contents as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if ($content) {
                $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
            }
        }

        return $this->render('home/index.html.twig', [
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
            'title' => 'Entdecke Beats',
        ]);
    }

    #[Route('/entdecke-sound-kits', name: 'app_sound_kits')]
    public function exploreSoundKits(EntityManagerInterface $em): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Sound Kits',
        ]);
        $contents = json_decode($response->getContent(), true);

        $tagsByContent = [];
        foreach ($contents as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if ($content) {
                $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
            }
        }

        return $this->render('home/index.html.twig', [
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
            'title' => 'Entdecke Sound Kits',
        ]);
    }

    #[Route('/entdecke-loop-und-samples', name: 'app_loop_und_samples')]
    public function exploreLoopUndSamples(EntityManagerInterface $em): Response
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => 'Samples',
        ]);
        $contents = json_decode($response->getContent(), true);

        $tagsByContent = [];
        foreach ($contents as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if ($content) {
                $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
            }
        }

        return $this->render('home/index.html.twig', [
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
            'title' => 'Entdecke Loops & Samples',
        ]);
    }
}
