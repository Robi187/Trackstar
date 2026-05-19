<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use App\Validator\Constraints\NoSpaces;
use Symfony\Component\Validator\Constraints\Regex;



class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new NotBlank(message: 'Bitte gib einen Benutzernamen ein.'),
                    new NoSpaces(),
                    new Regex(
                        pattern: '/^[a-zA-Z0-9_.-]+$/',
                        message: 'Der Benutzername darf nur Buchstaben, Zahlen, _, . und - enthalten.'
                    ),
                    new Length(
                        min: 3,
                        minMessage: 'Der Benutzername muss mindestens {{ limit }} Zeichen lang sein.',
                        max: 50,
                        maxMessage: 'Der Benutzername darf maximal {{ limit }} Zeichen lang sein.',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail',
                'constraints' => [
                    new NotBlank(message: 'Bitte gib eine E-Mail-Adresse ein.'),
                    new Email(message: 'Bitte gib eine gültige E-Mail-Adresse ein.'),

                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Passwort',
                ],
                'second_options' => [
                    'label' => 'Passwort wiederholen',
                ],
                'invalid_message' => 'Die Passwörter stimmen nicht überein.',
                'constraints' => [
                    new NotBlank(
                        message: 'Bitte gib ein Passwort ein.',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Das Passwort muss mindestens {{ limit }} Zeichen lang sein.',
                        max: 4096,
                    ),
                    new Regex(
                        pattern: '/[A-Z]/',
                        message: 'Das Passwort muss mindestens einen Großbuchstaben enthalten.'
                    ),
                    new Regex(
                        pattern: '/[0-9]/',
                        message: 'Das Passwort muss mindestens eine Zahl enthalten.'
                    ),
                    new Regex(
                        pattern: '/[\W_]/',
                        message: 'Das Passwort muss mindestens ein Sonderzeichen enthalten.'
                    ),
                    new NoSpaces(),
                    

                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
