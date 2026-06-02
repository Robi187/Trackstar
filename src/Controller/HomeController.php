<?php

namespace App\Controller;
use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Rating;
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

        if (!is_array($raw) || empty($raw)) {
            return [[], [], []];
        }

        $contents      = [];
        $tagsByContent = [];

        foreach ($raw as $contentData) {
            if (!is_array($contentData) || !isset($contentData['id'])) {
                continue;
            }

            $content = $em->getRepository(Content::class)->find($contentData['id']);
            if (!$content || $content->isSuspended()) {
                continue;
            }

            $contents[] = $contentData;
            $tagsByContent[$contentData['id']] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        $ids              = array_column($contents, 'id');
        $ratingsByContent = $em->getRepository(Rating::class)->averagesByContentIds($ids);

        return [$contents, $tagsByContent, $ratingsByContent];
    }

    #[Route('/home', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $allContents   = $em->getRepository(Content::class)->findAll();
        $contents      = [];
        $tagsByContent = [];

        foreach ($allContents as $content) {
            if ($content->isSuspended()) {
                continue;
            }
            $contents[] = [
                'id'             => $content->getId(),
                'title'          => $content->getTitle(),
                'description'    => $content->getDescription(),
                'file_path'      => $content->getFilePath(),
                'category'       => $content->getType() ? $content->getType()->getName() : null,
                'created_at'     => $content->getCreatedAt()->format('Y-m-d H:i:s'),
                'user'           => $content->getFkUser() ? $content->getFkUser()->getUsername() : null,
                'image_path'     => $content->getImageFile(),
                'download_count' => $content->getDownloadCount(),
            ];
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        $ids              = array_column($contents, 'id');
        $ratingsByContent = $em->getRepository(Rating::class)->averagesByContentIds($ids);

        return $this->render('home/index.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingsByContent,
            'title'            => 'Entdecke neue Musik',
        ]);
    }

    #[Route('/entdecke-tracks', name: 'app_tracks')]
    public function exploreTracks(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent, $ratingsByContent] = $this->buildContents($em, 'Tracks');
        return $this->render('home/tracks.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingsByContent,
            'title'            => 'Entdecke Tracks',
        ]);
    }

    #[Route('/entdecke-beats', name: 'app_beats')]
    public function exploreBeats(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent, $ratingsByContent] = $this->buildContents($em, 'Beats');
        return $this->render('home/beats.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingsByContent,
            'title'            => 'Entdecke Beats',
        ]);
    }

    #[Route('/entdecke-sound-kits', name: 'app_sound_kits')]
    public function exploreSoundKits(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent, $ratingsByContent] = $this->buildContents($em, 'Sound Kits');
        return $this->render('home/soundkits.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingsByContent,
            'title'            => 'Entdecke Sound Kits',
        ]);
    }

    #[Route('/entdecke-loop-und-samples', name: 'app_loop_und_samples')]
    public function exploreLoopUndSamples(EntityManagerInterface $em): Response
    {
        [$contents, $tagsByContent, $ratingsByContent] = $this->buildContents($em, 'Samples');
        return $this->render('home/samples.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingsByContent,
            'title'            => 'Entdecke Loops & Samples',
        ]);
    }
}