<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserManagementController extends AbstractController
{
    #[Route('/kontoüberischt', name: 'app_user_management')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        return $this->render('user_management/index.html.twig', [
            'controller_name' => 'UserManagementController',
            'user_data' => $user,
        ]);
    }
}
