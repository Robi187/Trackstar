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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Repository\RatingRepository;


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

        $userRatingEntity = $user
            ? $em->getRepository(Rating::class)->findOneBy(['fk_user' => $user, 'fk_content' => $content])
            : null;

        $isOwner = $user && $content->getFkUser() && $content->getFkUser()->getId() === $user->getId();

        return $this->render('content/index.html.twig', [
            'content' => $content,
            'favoriteCount' => $em->getRepository(Favorite::class)->countByContent($content),
            'averageRating' => $em->getRepository(Rating::class)->averageByContent($content),
            'tags' => $em->getRepository(ContentTag::class)->findTagsByContent($content),
            'isFavorited' => $isFavorited,
            'userRating' => $userRatingEntity ? $userRatingEntity->getValue() : 0,
            'isOwner' => $isOwner,
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

    #[Route('/content/{id}/rate', name: 'content_rate', methods: ['POST'])]
    public function rate(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $user = $this->getUser();

        if ($content->getFkUser() && $content->getFkUser()->getId() === $user->getId()) {
            return new JsonResponse(['error' => 'You cannot rate your own content'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $value = (int)($data['value'] ?? 0);

        $existing = $em->getRepository(Rating::class)->findOneBy([
            'fk_user' => $user,
            'fk_content' => $content,
        ]);

        if ($value < 1 || $value > 5 || ($existing && $existing->getValue() === $value)) {
            if ($existing) {
                $em->remove($existing);
            }
            $userRating = 0;
        } elseif ($existing) {
            $existing->setValue($value);
            $userRating = $value;
        } else {
            $rating = new Rating();
            $rating->setFkUser($user);
            $rating->setFkContent($content);
            $rating->setValue($value);
            $em->persist($rating);
            $userRating = $value;
        }

        $em->flush();

        $avg = $em->getRepository(Rating::class)->averageByContent($content);

        return new JsonResponse([
            'userRating' => $userRating,
            'averageRating' => round($avg, 1),
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
