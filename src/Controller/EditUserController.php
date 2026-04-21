<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Form\BioType;
use App\Form\UsernameType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserEmailType;
use Symfony\Component\HttpFoundation\Request;

final class EditUserController extends AbstractController
{   
    #[Route('/edit/username', name: 'app_edit_username')]
    public function editUsername(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UsernameType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('edit_user/edit_username.html.twig', [
            'controller_name' => 'EditUserController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/email', name: 'app_edit_email')]
    public function editEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserEmailType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_user_management');
        }
        else {
            return $this->render('edit_user/edit_email.html.twig', [
            'controller_name' => 'EditUserController',
            'form' => $form->createView(),
        ]);
        }
    }

    #[Route('/edit/bio', name: 'app_edit_bio')]
    public function editBio(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(BioType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('edit_user/edit_bio.html.twig', [
            'controller_name' => 'EditUserController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/password', name: 'app_edit_password')]
    public function editPassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(PasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('edit_user/edit_password.html.twig', [
            'controller_name' => 'EditUserController',
            'form' => $form->createView(),
        ]);
    }
}
