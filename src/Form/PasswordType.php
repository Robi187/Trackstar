<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;
use App\Dto\UserPasswordDto;

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