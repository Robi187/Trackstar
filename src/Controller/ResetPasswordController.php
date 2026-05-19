<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = trim($request->query->get('email', ''));
        $exists = $email !== '' && $userRepository->findOneBy(['email' => $email]) !== null;

        return new JsonResponse(['exists' => $exists]);
    }

    #[Route('/passwort-vergessen', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer,
        ValidatorInterface $validator,
    ): Response {
        $emailInput = null;
        $emailError = null;

        if ($request->isMethod('POST')) {
            $emailInput = trim($request->request->get('email', ''));

            $violations = $validator->validate($emailInput, [
                new NotBlank(message: 'Bitte gib deine E-Mail-Adresse ein.'),
                new Email(message: 'Bitte gib eine gültige E-Mail-Adresse ein.'),
            ]);

            if (count($violations) > 0) {
                $emailError = $violations[0]->getMessage();
            } else {
                $user = $userRepository->findOneBy(['email' => $emailInput]);

                if ($user) {
                    $signatureComponents = $verifyEmailHelper->generateSignature(
                        'app_reset_password',
                        (string) $user->getId(),
                        $user->getEmail(),
                        ['id' => $user->getId()]
                    );

                    $email = (new TemplatedEmail())
                        ->from((string) $_SERVER['MAILER_SENDER_EMAIL'])
                        ->to($user->getEmail())
                        ->subject('TrackStar – Passwort zurücksetzen')
                        ->htmlTemplate('registration/reset_password_email.html.twig')
                        ->context([
                            'signedUrl' => $signatureComponents->getSignedUrl(),
                            'expiresAt' => $signatureComponents->getExpiresAt(),
                        ]);

                    $mailer->send($email);
                }

                $this->addFlash('success', 'Wir haben eine Link zum Passwortzurücksetzten an die angegebene Email-Adresse geschickt.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'emailInput' => $emailInput,
            'emailError' => $emailError,
        ]);
    }

    #[Route('/passwort-zuruecksetzen', name: 'app_reset_password')]
    public function resetPassword(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($request->isMethod('GET')) {
            $id = $request->query->get('id');

            if (!$id) {
                $this->addFlash('error', 'Ungültiger Link.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $userRepository->find($id);

            if (!$user) {
                $this->addFlash('error', 'Ungültiger Link.');
                return $this->redirectToRoute('app_forgot_password');
            }

            try {
                $verifyEmailHelper->validateEmailConfirmationFromRequest(
                    $request,
                    (string) $user->getId(),
                    $user->getEmail()
                );
            } catch (VerifyEmailExceptionInterface $e) {
                $this->addFlash('error', 'Der Link ist ungültig oder abgelaufen. Bitte starte den Vorgang erneut.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $request->getSession()->set('reset_password_user_id', $user->getId());
        }

        $userId = $request->getSession()->get('reset_password_user_id');

        if (!$userId) {
            $this->addFlash('error', 'Bitte starte den Vorgang erneut.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Die Passwörter stimmen nicht überein.');
            } else {
                $user = $userRepository->find($userId);

                if (!$user) {
                    return $this->redirectToRoute('app_forgot_password');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();

                $request->getSession()->remove('reset_password_user_id');

                $this->addFlash('success', 'Passwort erfolgreich zurückgesetzt. Du kannst dich jetzt anmelden.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
