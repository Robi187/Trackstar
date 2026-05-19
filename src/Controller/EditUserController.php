<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Form\BioType;
use App\Form\UsernameType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserEmailType;
use Symfony\Component\HttpFoundation\Request;
use App\Dto\UserEmailDto;
use App\Dto\UsernameDto;
use App\Dto\UserPasswordDto;
use App\Form\PasswordType;
use App\Form\ProfilePictureType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class EditUserController extends AbstractController
{   
    #[Route('/edit/username', name: 'app_edit_username')]
    public function editUsername(Request $request, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        $currentUsername = $user->getUsername();

        $dto = new UsernameDto();
        $dto->username = $currentUsername;

        $form = $this->createForm(UsernameType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($dto->username === $currentUsername) {
                $this->addFlash('warning', 'Das ist bereits dein aktueller Benutzername.');
            } else {
                $user->setUsername($dto->username);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Benutzername erfolgreich geändert.');
                return $this->redirectToRoute('app_user_management');
            }
        }

        return $this->render('edit_user/edit_username.html.twig', [
            'form' => $form->createView(),
            'user_data' => $this->getUser(),
            'current_username' => $currentUsername,
            ]);
        }

    #[Route('/edit/email', name: 'app_edit_email')]
    public function editEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $currentEmail = $user->getEmail();

        $dto = new UserEmailDto();
        $dto->email = $currentEmail;

        $form = $this->createForm(UserEmailType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dto->email === $currentEmail) {
                $this->addFlash('warning', 'Das ist bereits deine aktuelle E-Mail-Adresse.');
            } else {
                $user->setPendingEmail($dto->email);
                $entityManager->flush();

                $signatureComponents = $verifyEmailHelper->generateSignature(
                    'app_verify_email_change',
                    (string) $user->getId(),
                    $dto->email,
                    ['id' => $user->getId()]
                );

                $email = (new TemplatedEmail())
                    ->from((string) $_SERVER['MAILER_SENDER_EMAIL'])
                    ->to($dto->email)
                    ->subject('TrackStar – Neue E-Mail-Adresse bestätigen')
                    ->htmlTemplate('registration/email_change_confirmation.html.twig')
                    ->context([
                        'signedUrl' => $signatureComponents->getSignedUrl(),
                        'expiresAt' => $signatureComponents->getExpiresAt(),
                    ]);

                $mailer->send($email);

                $notification = (new TemplatedEmail())
                    ->from((string) $_SERVER['MAILER_SENDER_EMAIL'])
                    ->to($currentEmail)
                    ->subject('TrackStar – Änderung deiner E-Mail-Adresse')
                    ->htmlTemplate('registration/email_change_notification.html.twig')
                    ->context(['newEmail' => $dto->email]);

                $mailer->send($notification);

                $this->addFlash('success', 'Wir haben eine Bestätigungs-E-Mail an ' . $dto->email . ' gesendet. Bitte bestätige deine neue E-Mail-Adresse.');
                return $this->redirectToRoute('app_user_management');
            }
        }

        return $this->render('edit_user/edit_email.html.twig', [
            'controller_name' => 'EditUserController',
            'form' => $form->createView(),
            'user_data' => $this->getUser(),
            'current_email' => $currentEmail,
        ]);
    }

    #[Route('/verify/email-change', name: 'app_verify_email_change')]
    public function verifyEmailChange(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $id = $request->query->get('id');

        if (!$id) {
            $this->addFlash('error', 'Ungültiger Bestätigungslink.');
            return $this->redirectToRoute('app_user_management');
        }

        $user = $userRepository->find($id);

        if (!$user || !$user->getPendingEmail()) {
            $this->addFlash('error', 'Benutzer nicht gefunden oder kein Änderungsantrag vorhanden.');
            return $this->redirectToRoute('app_user_management');
        }

        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $user->getId(),
                $user->getPendingEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Der Bestätigungslink ist ungültig oder abgelaufen.');
            return $this->redirectToRoute('app_edit_email');
        }

        $user->setEmail($user->getPendingEmail());
        $user->setPendingEmail(null);
        $entityManager->flush();

        $this->addFlash('success', 'E-Mail-Adresse erfolgreich geändert.');
        return $this->redirectToRoute('app_user_management');
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
            'user_data' => $this->getUser(),
            'current_bio' => $user->getBiography(),
        ]);
    }

    #[Route('/edit/password', name: 'app_edit_password')]
    public function editPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    /** @var User $user */
    $user = $this->getUser();
    $dto = new UserPasswordDto();

    $form = $this->createForm(PasswordType::class, $dto);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Aktuelles Passwort prüfen
        if (!$passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
            $this->addFlash('warning', 'Das aktuelle Passwort ist falsch.');
        } elseif ($passwordHasher->isPasswordValid($user, $dto->newPassword)) {
            $this->addFlash('warning', 'Das neue Passwort darf nicht mit dem alten übereinstimmen.');
        } elseif ($dto->newPassword !== $dto->confirmPassword) {
            $this->addFlash('warning', 'Die neuen Passwörter stimmen nicht überein.');
        } else {
            $hashed = $passwordHasher->hashPassword($user, $dto->newPassword);
            $user->setPassword($hashed);
            $entityManager->flush();
            $this->addFlash('success', 'Passwort erfolgreich geändert.');
            return $this->redirectToRoute('app_user_management');
        }
    }

        return $this->render('edit_user/edit_password.html.twig', [
            'form' => $form->createView(),
            'user_data' => $this->getUser(),
        ]);
    }

    #[Route('/edit/profilbild', name: 'app_edit_profilbild')]
    public function editProfilbild(
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%profile_pictures_directory%')] string $profilePicturesDir,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfilePictureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('profilePicture')->getData();

            $extension = $file->guessExtension() ?? 'jpg';
            $filename = uniqid() . '_' . $user->getId() . '.' . $extension;
            $file->move($profilePicturesDir, $filename);

            // Delete old profile picture if it exists
            $old = $user->getProfilePicture();
            if ($old && file_exists($profilePicturesDir . '/' . basename($old))) {
                unlink($profilePicturesDir . '/' . basename($old));
            }

            $user->setProfilePicture('profile_pictures/' . $filename);
            $entityManager->flush();

            $this->addFlash('success', 'Profilbild erfolgreich geändert.');
            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('edit_user/edit_profilbild.html.twig', [
            'form' => $form->createView(),
            'user_data' => $user,
        ]);
    }
}
