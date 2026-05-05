<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Favorite;
use App\Entity\Rating;
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

    #[Route('/admin/benutzer', name: 'app_admin_users')]
    public function adminUsers(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $em->getRepository(User::class)->findAll();
        return $this->render('admin/users.html.twig', [
            'users'     => $users,
            'user_data' => $this->getUser(),
        ]);
    }

    #[Route('/admin/benutzer/{id}/loeschen', name: 'app_admin_delete_user', methods: ['POST'])]
    public function adminDeleteUser(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Benutzer nicht gefunden.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Du kannst dein eigenes Konto nicht löschen.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Delete rows that depend on this user's content first
        $em->createQuery('DELETE FROM App\Entity\ContentTag ct WHERE ct.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Comment cm WHERE cm.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Content c WHERE c.fk_user = :u')->setParameter('u', $user)->execute();

        // Delete rows the user created on other content
        $em->createQuery('DELETE FROM App\Entity\Comment cm WHERE cm.fk_user = :u')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_user = :u')->setParameter('u', $user)->execute();
        $em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_user = :u')->setParameter('u', $user)->execute();

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', sprintf('Benutzer „%s" wurde gelöscht.', $user->getUsername()));
        return $this->redirectToRoute('app_admin_users');
    }
}
