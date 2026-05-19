<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;
use App\Dto\UserPasswordDto;
use App\Validator\Constraints\NoSpaces;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', SymfonyPasswordType::class, [
                'label' => 'Aktuelles Passwort',
                'always_empty' => true,
                'required' => false,
            ])
            ->add('newPassword', SymfonyPasswordType::class, [
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
            ->add('confirmPassword', SymfonyPasswordType::class, [
                'label' => 'Passwort bestätigen',
                'always_empty' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPasswordDto::class,
        ]);
    }
}