<?php
 
namespace App\Form;
 
use App\Entity\Category;
use App\Entity\Content;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
 
class ContentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'attr' => [
                    'placeholder' => 'z.B. Summer Vibes Beat',
                    'class' => 'ts-input',
                ],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib einen Titel ein.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Erzähl etwas über deinen Track...',
                    'rows' => 4,
                    'class' => 'ts-input',
                ],
            ])
            ->add('type', EntityType::class, [
                'label' => 'Kategorie',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Kategorie wählen...',
                'attr' => ['class' => 'ts-input'],
                'constraints' => [
                    new NotBlank(message: 'Bitte wähle eine Kategorie.'),
                ],
            ])
            ->add('fk_tag', EntityType::class, [
                'label' => 'Tag',
                'class' => Tag::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tag wählen (optional)...',
                'attr' => ['class' => 'ts-input'],
            ])
            ->add('audioFile', FileType::class, [
                'label' => 'Audio-Datei',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'ts-file-input', 'accept' => 'audio/*,.mp3,.wav,.flac,.aiff'],
                'constraints' => [
                    new NotBlank(message: 'Bitte lade eine Audio-Datei hoch.'),
                    new File(
                        maxSize: '50M',
                         mimeTypes: [
                            'audio/mpeg',
                            'audio/mp3',
                            'audio/wav',
                            'audio/x-wav',
                            'audio/wave',
                            'audio/vnd.wave',
                            'audio/flac',
                            'audio/x-flac',
                            'audio/aiff',
                            'audio/x-aiff',
                            'audio/ogg',
                            'application/octet-stream', // Fallback bei falsch erkannten Dateien
                        ],
                        mimeTypesMessage: 'Bitte lade eine gültige Audio-Datei hoch (MP3, WAV, FLAC, AIFF).',
                    ),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Titelbild',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'ts-file-input', 'accept' => 'image/*'],
                'constraints' => [
                    new NotBlank(message: 'Bitte lade ein Titelbild hoch.'),
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Bitte lade eine gültige Bilddatei hoch (JPG, PNG, WEBP).',
                    ),
                ],
            ])
        ;
    }
 
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
        ]);
    }
}