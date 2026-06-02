<?php

namespace App\Controller;

use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Favorite;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavoritesController extends AbstractController
{
    #[Route('/favoriten', name: 'app_favorites')]
    public function index(EntityManagerInterface $em, RatingRepository $ratingRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user      = $this->getUser();
        $favorites = $em->getRepository(Favorite::class)->findBy(['fk_user' => $user]);
        $contents  = array_map(fn($f) => $f->getFkContent(), $favorites);
        $ids       = array_map(fn($c) => $c->getId(), $contents);

        $tagsByContent = [];
        foreach ($contents as $content) {
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        return $this->render('favorites/index.html.twig', [
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingRepository->averagesByContentIds($ids),
            'user_data'        => $user,
        ]);
    }

    #[Route('/favoriten/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggle(int $id, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $content = $em->getRepository(Content::class)->find($id);

        if (!$content) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $existing = $em->getRepository(Favorite::class)->findOneBy([
            'fk_user' => $user,
            'fk_content' => $content,
        ]);

        if ($existing) {
            $em->remove($existing);
            $favorited = false;
        } else {
            $fav = new Favorite();
            $fav->setFkUser($user);
            $fav->setFkContent($content);
            $em->persist($fav);
            $favorited = true;
        }

        $em->flush();

        $count = $em->getRepository(Favorite::class)->countByContent($content);

        return new JsonResponse(['favorited' => $favorited, 'count' => $count]);
    }
}
