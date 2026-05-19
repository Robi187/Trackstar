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

    /**
     * Hilfsmethode: JSON-Inhalte laden, gesperrte rausfiltern, Tags aufbauen.
     */
    private function buildContents(EntityManagerInterface $em, string $categoryName): array
    {
        $response = $this->forward('App\Controller\ContentController::getContentByCategory', [
            'category_name' => $categoryName,
        ]);
        $raw = json_decode($response->getContent(), true);

        $contents      = [];
        $tagsByContent = [];

        foreach ($raw as $contentData) {
            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if (!$content || $content->isSuspended()) {
                continue; // gesperrte Inhalte überspringen
            }
            $contents[] = $contentData;
            $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        return [$contents, $tagsByContent];
    }

    #[Route('/home', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent] = $this->buildContents($em, 'Tracks');
        return $this->render('home/index.html.twig', [
            'contents'      => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }

    #[Route('/entdecke-tracks', name: 'app_tracks')]
    public function exploreTracks(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent] = $this->buildContents($em, 'Tracks');
        return $this->render('home/tracks.html.twig', [
            'contents'      => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }

    #[Route('/entdecke-beats', name: 'app_beats')]
    public function exploreBeats(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent] = $this->buildContents($em, 'Beats');
        return $this->render('home/beats.html.twig', [
            'contents'      => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }

    #[Route('/entdecke-sound-kits', name: 'app_sound_kits')]
    public function exploreSoundKits(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent] = $this->buildContents($em, 'Sound Kits');
        return $this->render('home/soundkits.html.twig', [
            'contents'      => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }

    #[Route('/entdecke-loop-und-samples', name: 'app_loop_und_samples')]
    public function exploreLoopUndSamples(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent] = $this->buildContents($em, 'Samples');
        return $this->render('home/samples.html.twig', [
            'contents'      => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }
}