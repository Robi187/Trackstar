<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Validator\Constraints\NoSpaces;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newPassword', PasswordType::class, [
                'label' => 'Neues Passwort',
                'always_empty' => true,
                'required' => false,
                'constraints' => [
                    new NotBlank(message: 'Bitte gib ein neues Passwort ein.'),
                    new Length(min: 6, minMessage: 'Das Passwort muss mindestens {{ limit }} Zeichen lang sein.', max: 4096),
                    new Regex(pattern: '/[A-Z]/', message: 'Das Passwort muss mindestens einen Großbuchstaben enthalten.'),
                    new Regex(pattern: '/[0-9]/', message: 'Das Passwort muss mindestens eine Zahl enthalten.'),
                    new Regex(pattern: '/[\W_]/', message: 'Das Passwort muss mindestens ein Sonderzeichen enthalten.'),
                    new NoSpaces(),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Passwort bestätigen',
                'always_empty' => true,
                'required' => false,
                'constraints' => [
                    new NotBlank(message: 'Bitte bestätige dein neues Passwort.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
