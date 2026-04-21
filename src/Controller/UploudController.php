<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Repository\ContentRepository;
use App\Repository\UserRepository;

final class UploudController extends AbstractController
{
    #[Route('/uploud', name: 'app_uploud')]
    public function index(ContentRepository $contentRepository): Response
    {
        $user = $this->getUser();
        $uploads = $contentRepository->findBy(['fk_user' => $user]);
        print($uploads[0]->getFilePath());
        return $this->render('uploud/index.html.twig', [
            'controller_name' => 'UploudController',
            'user_data' => $user,
            'uploads' => $uploads,
        ]);
    }
}
