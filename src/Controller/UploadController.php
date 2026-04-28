<?php
 
namespace App\Controller;
 
use App\Entity\Content;
use App\Form\ContentUploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\ContentTag;
use App\Entity\Tag;


 
class UploadController extends AbstractController
{
    #[Route('/upload', name: 'app_upload')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        Security $security,
        LoggerInterface $logger
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
 
        $content = new Content();
        $form = $this->createForm(ContentUploadType::class, $content);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $logger->error('Form abgeschickt');
            if (!$form->isValid()) {
                // Gibt alle Validierungsfehler aus
                foreach ($form->getErrors(true) as $error) {
                    $logger->error('Form error: ' . $error->getMessage() . ' (field: ' . $error->getOrigin()->getName() . ')');
                }
            }
            if ($form->isValid()) {
                $user = $security->getUser();
                $userId = $user->getId();

                // Kategorie → Unterordner
                $category = $content->getType();
                $categoryName = strtolower($category->getName());

                $folderMap = [
                    'beat'     => 'beats',
                    'beats'    => 'beats',
                    'sample'   => 'samples',
                    'samples'  => 'samples',
                    'soundkit' => 'soundkits',
                    'soundkits'=> 'soundkits',
                    'track'    => 'tracks',
                    'tracks'   => 'tracks',
                ];

                $subfolder = $folderMap[$categoryName] ?? 'misc';
                $uploadBase = $this->getParameter('uploads_directory'); // z.B. public/uploads

                // Audio-Datei
                $audioFile = $form->get('audioFile')->getData();
                if ($audioFile) {
                    $originalFilename = pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '_' . $userId . '.' . $audioFile->guessExtension();

                    $audioFile->move(
                        $uploadBase . '/' . $subfolder,  // → public/uploads/beats
                        $newFilename
                    );

                    $content->setFilePath($subfolder . '/' . $newFilename);
                    // → uploads/beats/drill-season_bob_3.wav
                }

                // Cover-Bild
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '_' . $userId . '.' . $imageFile->guessExtension();

                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    $content->setImageFile('images/' . $newFilename);
                }
                $selectedTags = $form->get('fk_tag')->getData();
                foreach ($selectedTags as $tag) {
                    $contentTag = new ContentTag();
                    $contentTag->setFkContent($content);
                    $contentTag->setFkTag($tag);
                    $em->persist($contentTag);
                }

                $bpm = $form->get('bpm')->getData();
                if ($bpm) {
                    $tagName = $bpm . ' BPM';

                    $bpmTag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
                    if (!$bpmTag) {
                        $bpmTag = new Tag();
                        $bpmTag->setName($tagName);
                        $em->persist($bpmTag);
                    }

                    $bpmContentTag = new ContentTag();
                    $bpmContentTag->setFkContent($content);
                    $bpmContentTag->setFkTag($bpmTag);
                    $em->persist($bpmContentTag);
                }
                $content->setCreatedAt(new \DateTime());
                $content->setDownloadCount(0);
                $content->setFkUser($user);

                $em->persist($content);
                $em->flush();

                $this->addFlash('success', 'Dein Upload war erfolgreich!');
                return $this->redirectToRoute('app_upload');
            }
        }
 
        return $this->render('upload/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}