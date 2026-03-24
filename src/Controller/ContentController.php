<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Content;


final class ContentController extends AbstractController
{
    #[Route('/content/{id}', name: 'app_content_detail', requirements: ['id' => '\d+'])]
    public function index(int $id, EntityManagerInterface $em): Response
    {
        $content = $em->getRepository(Content::class)->find($id);
        
        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }
        var_dump($content);
        return $this->render('content/index.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route('/content/{category_name}', name: 'app_content_category', requirements: ['category_name' => '[a-zA-Z]+' ])]
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
                'tag' => $content->getFkTag() ? $content->getFkTag()->getName() : null, // Beispiel für das Tag
                'image_path' => $content->getImageFile(),
            ];
        }

        // Gib das Array als JSON zurück
        return new JsonResponse($contentArray);
    }
}
