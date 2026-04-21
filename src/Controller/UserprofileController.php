<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ContentRepository;
use App\Entity\User;
use App\Repository\UserRepository;

final class UserprofileController extends AbstractController
{
    #[Route('/Benutzer/{username}', name: 'app_userprofile')]
    public function index(ContentRepository $contentRepository, UserRepository $userRepository, string $username): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        $contents = $contentRepository->findBy(['fk_user' => $user]);

        return $this->render('userprofile/index.html.twig', [
            'controller_name' => 'UserprofileController',
            'contents' => $contents,
            'user_data' => $user,
        ]);
    }
}
