<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Report;
use App\Entity\Reason;

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

    #[Route('/admin/benutzer/{id}/sperren', name: 'app_admin_ban_user', methods: ['POST'])]
    public function adminBanUser(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Benutzer nicht gefunden.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Du kannst dich nicht selbst sperren.');
            return $this->redirectToRoute('app_admin_users');
        }

        $duration = trim($request->request->get('duration', '7 days'));
        if (!preg_match('/^\d+\s+(day|days|week|weeks|month|months)$/', $duration)) {
            $duration = '7 days';
        }

        $user->setBannedUntil(new \DateTimeImmutable('+' . $duration));

        $contents = $em->getRepository(Content::class)->findBy(['fk_user' => $user]);
        foreach ($contents as $content) {
            $content->setIsSuspended(true);
        }

        $em->flush();

        $this->addFlash('success', sprintf(
            'Benutzer „%s" wurde gesperrt. %d Inhalte wurden ausgeblendet.',
            $user->getUsername(),
            count($contents)
        ));
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/benutzer/{id}/entsperren', name: 'app_admin_unban_user', methods: ['POST'])]
    public function adminUnbanUser(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Benutzer nicht gefunden.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setBannedUntil(null);

        $contents = $em->getRepository(Content::class)->findBy([
            'fk_user'     => $user,
            'isSuspended' => true,
        ]);
        foreach ($contents as $content) {
            $content->setIsSuspended(false);
        }

        $em->flush();

        $this->addFlash('success', sprintf('Sperre für „%s" wurde aufgehoben.', $user->getUsername()));
        return $this->redirectToRoute('app_admin_users');
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

        // Reports zuerst (Foreign Key!)
        // 1. Reports auf Content des Users
$em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();

// 2. Reports auf Kommentare des Users (eigene Kommentare)
$em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_comment IN (SELECT cm FROM App\Entity\Comment cm WHERE cm.fk_user = :u)')->setParameter('u', $user)->execute();

// 3. Reports auf Kommentare die ANDERE auf dem Content des Users hinterlassen haben
$em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_comment IN (SELECT cm FROM App\Entity\Comment cm WHERE cm.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u))')->setParameter('u', $user)->execute();

// 4. Reports die der User selbst erstellt hat
$em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_user = :u')->setParameter('u', $user)->execute();

// 5. CommentLikes auf Kommentare des Contents des Users
$em->createQuery('DELETE FROM App\Entity\CommentLike cl WHERE cl.fk_comment IN (SELECT cm FROM App\Entity\Comment cm WHERE cm.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u))')->setParameter('u', $user)->execute();

// 6. CommentLikes auf eigene Kommentare des Users
$em->createQuery('DELETE FROM App\Entity\CommentLike cl WHERE cl.fk_comment IN (SELECT cm FROM App\Entity\Comment cm WHERE cm.fk_user = :u)')->setParameter('u', $user)->execute();

// 7. CommentLikes die der User selbst vergeben hat
$em->createQuery('DELETE FROM App\Entity\CommentLike cl WHERE cl.fk_user = :u')->setParameter('u', $user)->execute();

// 8. ContentTags
$em->createQuery('DELETE FROM App\Entity\ContentTag ct WHERE ct.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();

// 9. Kommentare auf Content des Users (von anderen)
// Replies zuerst (Kommentare die auf andere Kommentare verweisen)
$em->createQuery('DELETE FROM App\Entity\Comment cm WHERE cm.fk_parent IS NOT NULL AND cm.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();

// Dann Top-Level Kommentare auf Content des Users
$em->createQuery('DELETE FROM App\Entity\Comment cm WHERE cm.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();

// 10. Favorites, Ratings auf Content des Users
$em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();
$em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_content IN (SELECT c FROM App\Entity\Content c WHERE c.fk_user = :u)')->setParameter('u', $user)->execute();

// 11. Content selbst
$em->createQuery('DELETE FROM App\Entity\Content c WHERE c.fk_user = :u')->setParameter('u', $user)->execute();

// 12. Eigene Kommentare des Users (auf fremdem Content)
$em->createQuery('DELETE FROM App\Entity\Comment cm WHERE cm.fk_user = :u')->setParameter('u', $user)->execute();

// 13. Eigene Favorites, Ratings des Users
$em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_user = :u')->setParameter('u', $user)->execute();
$em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_user = :u')->setParameter('u', $user)->execute();

// 14. User selbst
$em->remove($user);
$em->flush();

        $this->addFlash('success', sprintf('Benutzer „%s" wurde gelöscht.', $user->getUsername()));
        return $this->redirectToRoute('app_admin_users');
    }

    // ── MELDUNGEN ──────────────────────────────────────────────────────────────

    #[Route('/admin/meldungen', name: 'app_admin_reports')]
    public function adminReports(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reports = $em->getRepository(Report::class)
            ->findBy(['status' => 'open'], ['created_at' => 'DESC']);

        return $this->render('admin/reports.html.twig', [
            'reports'   => $reports,
            'user_data' => $this->getUser(),
        ]);
    }

    #[Route('/admin/meldungen/{id}/schliessen', name: 'app_admin_report_close', methods: ['POST'])]
    public function adminReportClose(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $report = $em->getRepository(Report::class)->find($id);
        if ($report) {
            $report->setStatus('closed');
            $em->flush();
        }
        $this->addFlash('success', 'Meldung als erledigt markiert.');
        return $this->redirectToRoute('app_admin_reports');
    }

    #[Route('/admin/meldungen/{id}/inhalt-loeschen', name: 'app_admin_report_delete_content', methods: ['POST'])]
    public function adminReportDeleteContent(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $report = $em->getRepository(Report::class)->find($id);
        if ($report) {
            $content = $report->getFkContent();
            $em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_content = :c')
                ->setParameter('c', $content)->execute();
            $em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_comment IN (SELECT c FROM App\Entity\Comment c WHERE c.fk_content = :c)')
                ->setParameter('c', $content)->execute();
            $em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.fk_content = :c')
                ->setParameter('c', $content)->execute();
            $em->createQuery('DELETE FROM App\Entity\ContentTag ct WHERE ct.fk_content = :c')
                ->setParameter('c', $content)->execute();
            $em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_content = :c')
                ->setParameter('c', $content)->execute();
            $em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_content = :c')
                ->setParameter('c', $content)->execute();
            $em->remove($content);
            $em->flush();
            $this->addFlash('success', 'Inhalt wurde gelöscht.');
        }
        return $this->redirectToRoute('app_admin_reports');
    }

    #[Route('/admin/meldungen/{id}/kommentar-loeschen', name: 'app_admin_report_delete_comment', methods: ['POST'])]
    public function adminReportDeleteComment(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $report = $em->getRepository(Report::class)->find($id);
        if ($report) {
            $comment = $report->getFkComment();
            if ($comment) {
                // Alle Reports auf diesen Kommentar schließen
                $em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_comment = :c')
                    ->setParameter('c', $comment)->execute();
                $em->remove($comment);
                $em->flush();
                $this->addFlash('success', 'Kommentar wurde gelöscht.');
            }
        }
        return $this->redirectToRoute('app_admin_reports');
    }
}
