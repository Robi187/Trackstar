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
 
class UploadController extends AbstractController
{
    #[Route('/upload', name: 'app_upload')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        Security $security
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
 
        $content = new Content();
        $form = $this->createForm(ContentUploadType::class, $content);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle audio/beat file upload
            $audioFile = $form->get('audioFile')->getData();
            if ($audioFile) {
                $originalFilename = pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $audioFile->guessExtension();
 
                $audioFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
 
                $content->setFilePath('uploads/' . $newFilename);
            }
 
            // Handle cover image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
 
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
 
                $content->setImageFile('images/' . $newFilename);
            }
 
            $content->setCreatedAt(new \DateTime());
            $content->setDownloadCount(0);
            $content->setFkUser($security->getUser());
 
            $em->persist($content);
            $em->flush();
 
            $this->addFlash('success', 'Dein Upload war erfolgreich!');
            return $this->redirectToRoute('app_upload');
        }
 
        return $this->render('upload/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}