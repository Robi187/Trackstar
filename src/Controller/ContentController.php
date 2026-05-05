<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\ContentTag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Content;
use App\Repository\ContentRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;


final class ContentController extends AbstractController
{
    #[Route('/content/{id}', name: 'app_content_detail', requirements: ['id' => '\d+'])]
    public function index(int $id, EntityManagerInterface $em): Response
    {
        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }
        $user = $this->getUser();
        $isFavorited = $user && $em->getRepository(Favorite::class)
            ->findOneBy(['fk_user' => $user, 'fk_content' => $content]) !== null;

        return $this->render('content/index.html.twig', [
            'content' => $content,
            'favoriteCount' => $em->getRepository(Favorite::class)->countByContent($content),
            'averageRating' => $em->getRepository(Rating::class)->averageByContent($content),
            'tags' => $em->getRepository(ContentTag::class)->findTagsByContent($content),
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/content/{category_name}', name: 'app_content_category', requirements: ['category_name' => '[a-zA-Z]+'])]
    public function getContentByCategory(string $category_name, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->findOneBy(['name' => $category_name]);

        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], 404);
        }

        // Hole die Inhalte basierend auf der Kategorie ID
        $contents = $em->getRepository(Content::class)->findBy(['type' => $category]);

        if (!$contents) {
            return new JsonResponse(['error' => 'Content not found'], 404);
        }

        // Erstelle ein Array mit den Daten, die als JSON zurückgegeben werden sollen
        $contentArray = [];
        foreach ($contents as $content) {
            $contentArray[] = [
                'id' => $content->getId(),
                'title' => $content->getTitle(),
                'description' => $content->getDescription(),
                'file_path' => $content->getFilePath(),
                'category' => $content->getType() ? $content->getType()->getName() : null, // Beispiel für die Kategorie
                'created_at' => $content->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => $content->getFkUser() ? $content->getFkUser()->getUsername() : null, // Beispiel für den User
                'image_path' => $content->getImageFile(),
            ];
        }

        // Gib das Array als JSON zurück
        return new JsonResponse($contentArray);
    }


    #[Route('/content/{id}/download', name: 'content_download')]
    public function download(Content $content, EntityManagerInterface $em): BinaryFileResponse
    {   
        var_dump($content->getFilePath());
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $content->getFilePath();
        var_dump($filePath);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Datei nicht gefunden.');
        }

        $content->incrementDownloadCount();
        $em->flush();

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }

    #[Route('/suche', name: 'app_search')]
    public function search(Request $request, ContentRepository $contentRepository, EntityManagerInterface $em): Response
    {
        $query = trim($request->query->get('q', ''));
        $results = $query !== '' ? $contentRepository->search($query) : [];

        $tagsByContent = [];
        foreach ($results as $content) {
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'tagsByContent' => $tagsByContent,
        ]);
    }

    #[Route('/deine-inhalte', name: 'app_deine_inhalte')]
    public function uploads(ContentRepository $contentRepository, EntityManagerInterface $em): Response
    {   
        $tagsByContent = [];
        $user = $this->getUser();
        $contents = $contentRepository->findBy(['fk_user' => $user]);
        foreach ($contents as $content) {
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }
        return $this->render('deine_inhalte/index.html.twig', [
            'user_data' => $user,
            'contents' => $contents,
            'tagsByContent' => $tagsByContent,
        ]);
    }
}
