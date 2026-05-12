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
use Doctrine\ORM\EntityRepository; 
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Range;

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
            ->add('bpm', IntegerType::class, [
                'label' => 'Tempo (BPM)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => '100 BPM',
                    'class' => 'ts-input',
                    'min' => 1,
                    'max' => 999,
                ],
                'constraints' => [
                    new Range(
                        min: 40,
                        max: 300,
                        notInRangeMessage: 'BPM muss zwischen {{ min }} und {{ max }} liegen.',
                    ),
                ],
            ])
            ->add('fk_tag', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'mapped' => false,  // nicht direkt auf Content gemappt
                'required' => false,
                'label' => 'Tags',
            ])
            ->add('audioFile', FileType::class, [
                'label' => 'Audio-Datei',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'ts-file-input', 'accept' => 'audio/*,.mp3,.wav,.flac,.aiff'],
                'constraints' => [
                    new NotBlank(message: 'Bitte lade eine Audio-Datei hoch.'),
                    new File(
                        maxSize: '200M',
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
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/octet-stream',
                        ],
                        mimeTypesMessage: 'Bitte lade eine gültige Audio-Datei (MP3, WAV, FLAC, AIFF) oder ZIP-Datei hoch.',
                    ),
                ],
            ])
            ->add('license', ChoiceType::class, [
                'label' => 'Lizenz',
                'required' => false,
                'placeholder' => 'Lizenz wählen...',
                'choices' => [
                    'CC BY – Namensnennung 4.0 International' => 'CC BY',
                    'CC BY-SA – Namensnennung-Share Alike 4.0 International' => 'CC BY-SA',
                    'CC BY-ND – Namensnennung-Keine Bearbeitungen 4.0 International' => 'CC BY-ND',
                    'CC BY-NC – Namensnennung-Nicht kommerziell 4.0 International' => 'CC BY-NC',
                    'CC BY-NC-SA – Namensnennung-Nicht kommerziell-Share Alike 4.0 International' => 'CC BY-NC-SA',
                    'CC BY-NC-ND – Namensnennung-Nicht kommerziell-Keine Bearbeitungen 4.0 International' => 'CC BY-NC-ND',
                ],
                'attr' => ['class' => 'ts-input', 'id' => 'license-select'],
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