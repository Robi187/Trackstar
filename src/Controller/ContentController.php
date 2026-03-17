<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Content;


final class ContentController extends AbstractController
{
    #[Route('/content/{id}', name: 'app_content')]
    public function index(int $id, EntityManagerInterface $em): Response
    {
        $content = $em->getRepository(Content::class)->find($id);

        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }

        return $this->render('content/index.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route('/content/{category}', name: 'app_content')]
    public function getContentByCategory(string $category_name, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Question::class)->findByName($category_name);
        $content = $em->getRepository(Content::class)->findByCategory($category->);

        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }

        return $this->render('content/index.html.twig', [
            'content' => $content,
        ]);
    }
}
