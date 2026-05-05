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
use App\Dto\UserEmailDto;
use App\Dto\UsernameDto;
use App\Dto\UserPasswordDto;
use App\Form\PasswordType;
use App\Form\ProfilePictureType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
    public function editEmail(Request $request, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        $currentEmail = $user->getEmail();

        // DTO mit aktueller Email befüllen
        $dto = new UserEmailDto();
        $dto->email = $currentEmail;

        $form = $this->createForm(UserEmailType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($dto->email === $currentEmail) {
                $this->addFlash('warning', 'Das ist bereits deine aktuelle E-Mail-Adresse.');
            } else {
                $user->setEmail($dto->email);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'E-Mail erfolgreich geändert.');
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

    #[Route('/edit/bio', name: 'app_edit_bio')]
    public function editBio(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(BioType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Bio erfolgreich geändert.');
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

    #[Route('/edit/profilbild/entfernen', name: 'app_remove_profilbild', methods: ['POST'])]
    public function removeProfilbild(
        EntityManagerInterface $entityManager,
        #[Autowire('%profile_pictures_directory%')] string $profilePicturesDir,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $current = $user->getProfilePicture();
        if ($current) {
            $fullPath = $profilePicturesDir . '/' . basename($current);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $user->setProfilePicture(null);
            $entityManager->flush();
            $this->addFlash('success', 'Profilbild erfolgreich entfernt.');
        }

        return $this->redirectToRoute('app_user_management');
    }
}
