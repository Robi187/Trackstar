<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\ContentTag;
use App\Entity\Tag;
use App\Entity\License;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Content;
use App\Repository\ContentRepository;
use App\Repository\CommentRepository;
use App\Repository\CommentLikeRepository;
use App\Repository\RatingRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Report;
use App\Entity\Reason;
use Symfony\Component\String\Slugger\SluggerInterface;


final class ContentController extends AbstractController
{
    #[Route('/content/{id}', name: 'app_content_detail', requirements: ['id' => '\d+'])]
    public function index(int $id, EntityManagerInterface $em, CommentRepository $commentRepository, CommentLikeRepository $likeRepository): Response
    {
        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }
        $user = $this->getUser();
        $isFavorited = $user && $em->getRepository(Favorite::class)
            ->findOneBy(['fk_user' => $user, 'fk_content' => $content]) !== null;

        $userRatingEntity = $user
            ? $em->getRepository(Rating::class)->findOneBy(['fk_content' => $content, 'fk_user' => $user])
            : null;

        $topLevelComments = $commentRepository->findTopLevelByContent($content);

        // Collect all comment IDs (top-level + replies) for like queries
        $allCommentIds = [];
        foreach ($topLevelComments as $c) {
            $allCommentIds[] = $c->getId();
            foreach ($c->getReplies() as $r) {
                $allCommentIds[] = $r->getId();
            }
        }

        $likeCounts  = $likeRepository->countByComments($allCommentIds);
        $likedByUser = $user ? $likeRepository->likedByUser($user, $allCommentIds) : [];

        return $this->render('content/index.html.twig', [
            'content'      => $content,
            'favoriteCount' => $em->getRepository(Favorite::class)->countByContent($content),
            'averageRating' => $em->getRepository(Rating::class)->averageByContent($content),
            'tags'         => $em->getRepository(ContentTag::class)->findTagsByContent($content),
            'isFavorited'  => $isFavorited,
            'userRating'   => $userRatingEntity ? $userRatingEntity->getValue() : null,
            'comments'     => $topLevelComments,
            'likeCounts'   => $likeCounts,
            'likedByUser'  => $likedByUser,
            'reasons'        => $em->getRepository(Reason::class)->findAll(),
        ]);
    }

    #[Route('/comment/{id}/melden', name: 'comment_report', methods: ['POST'])]
    public function reportComment(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }

        $comment = $em->getRepository(Comment::class)->find($id);
        if (!$comment) {
            return new JsonResponse(['error' => 'Nicht gefunden'], 404);
        }

        // Doppelmeldung verhindern
        $existing = $em->getRepository(Report::class)->createQueryBuilder('r')
            ->where('r.fk_user = :user')
            ->andWhere('r.fk_comment = :comment')
            ->andWhere("r.status != 'closed'")
            ->setParameter('user', $user)
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            return new JsonResponse(['error' => 'Bereits gemeldet'], 409);
        }

        $reasonId = $request->request->get('reason_id');
        $reason = $em->getRepository(Reason::class)->find($reasonId);
        if (!$reason) {
            return new JsonResponse(['error' => 'Ungültiger Grund'], 400);
        }

        $report = new Report();
        $report->setFkComment($comment);
        $report->setFkUser($user);
        $report->setFkReason($reason);
        $report->setMessage($request->request->get('message', null));
        $report->setStatus('open');
        $report->setCreatedAt(new \DateTime());

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/content/{id}/comment', name: 'content_comment', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function comment(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Content not found');
        }

        $text = trim($request->request->get('text', ''));
        if ($text !== '') {
            $comment = new Comment();
            $comment->setText($text);
            $comment->setCreatedAt(new \DateTime());
            $comment->setFkUser($user);
            $comment->setFkContent($content);

            $parentId = (int) $request->request->get('parent_id', 0);
            if ($parentId > 0) {
                $parent = $em->getRepository(Comment::class)->find($parentId);
                if ($parent && $parent->getFkContent() === $content && $parent->getFkParentComment() === null) {
                    $comment->setFkParentComment($parent);
                }
            }

            $em->persist($comment);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'id'             => $comment->getId(),
                    'text'           => $comment->getText(),
                    'username'       => $comment->getFkUser()->getUsername(),
                    'profilePicture' => $comment->getFkUser()->getProfilePicture(),
                    'createdAt'      => $comment->getCreatedAt()->format('d.m.Y H:i'),
                    'parentId'       => $comment->getFkParentComment()?->getId(),
                    'userId'         => $comment->getFkUser()->getId(),
                ]);
            }
        }

        return $this->redirectToRoute('app_content_detail', ['id' => $id]);
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteComment(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $comment = $em->getRepository(Comment::class)->find($id);
        if (!$comment || $comment->getFkUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $contentId = $comment->getFkContent()->getId();
        $em->remove($comment);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['deleted' => true]);
        }

        return $this->redirectToRoute('app_content_detail', ['id' => $contentId]);
    }

    #[Route('/comment/{id}/like', name: 'comment_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCommentLike(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $comment = $em->getRepository(Comment::class)->find($id);
        if (!$comment) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $like = $em->getRepository(CommentLike::class)->findOneBy(['fk_user' => $user, 'fk_comment' => $comment]);
        if ($like) {
            $em->remove($like);
            $liked = false;
        } else {
            $like = new CommentLike();
            $like->setFkUser($user);
            $like->setFkComment($comment);
            $em->persist($like);
            $liked = true;
        }
        $em->flush();

        $count = $em->getRepository(CommentLike::class)->count(['fk_comment' => $comment]);

        return new JsonResponse(['liked' => $liked, 'count' => $count]);
    }

    #[Route('/content/{category_name}', name: 'app_content_category', requirements: ['category_name' => '[a-zA-Z]+'])]
    public function getContentByCategory(string $category_name, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->findOneBy(['name' => $category_name]);

        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], 404);
        }

        // Hole die Inhalte basierend auf der Kategorie ID
        $contents = $em->getRepository(Content::class)->findBy(['type' => $category]);

        if (!$contents) {
            return new JsonResponse(['error' => 'Content not found'], 404);
        }

        // Erstelle ein Array mit den Daten, die als JSON zurückgegeben werden sollen
        $contentArray = [];
        foreach ($contents as $content) {
            $contentArray[] = [
                'id'             => $content->getId(),
                'title'          => $content->getTitle(),
                'description'    => $content->getDescription(),
                'file_path'      => $content->getFilePath(),
                'category'       => $content->getType() ? $content->getType()->getName() : null,
                'created_at'     => $content->getCreatedAt()->format('Y-m-d H:i:s'),
                'user'           => $content->getFkUser() ? $content->getFkUser()->getUsername() : null,
                'image_path'     => $content->getImageFile(),
                'download_count' => $content->getDownloadCount(),
            ];
        }

        // Gib das Array als JSON zurück
        return new JsonResponse($contentArray);
    }


    #[Route('/content/{id}/download', name: 'content_download')]
    public function download(Content $content, EntityManagerInterface $em): BinaryFileResponse
    {   
        var_dump($content->getFilePath());
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $content->getFilePath();
        var_dump($filePath);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Datei nicht gefunden.');
        }

        $content->incrementDownloadCount();
        $em->flush();

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }

    #[Route('/content/{id}/rate', name: 'content_rate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rate(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        if ($content->getFkUser() === $user) {
            return new JsonResponse(['error' => 'Cannot rate own content'], 403);
        }

        $value = (int) $request->request->get('value');
        if ($value < 1 || $value > 5) {
            return new JsonResponse(['error' => 'Invalid value'], 400);
        }

        $rating = $em->getRepository(Rating::class)->findOneBy(['fk_content' => $content, 'fk_user' => $user]);
        if (!$rating) {
            $rating = new Rating();
            $rating->setFkUser($user);
            $rating->setFkContent($content);
            $em->persist($rating);
        }
        $rating->setValue($value);
        $em->flush();

        $average = $em->getRepository(Rating::class)->averageByContent($content);

        return new JsonResponse(['average' => round($average, 2)]);
    }

    #[Route('/content/{id}/rate', name: 'content_unrate', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function unrate(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $rating = $em->getRepository(Rating::class)->findOneBy(['fk_content' => $content, 'fk_user' => $user]);
        if ($rating) {
            $em->remove($rating);
            $em->flush();
        }

        $average = $em->getRepository(Rating::class)->averageByContent($content);

        return new JsonResponse(['average' => round($average, 2)]);
    }
  
    #[Route('/suche', name: 'app_search')]
    public function search(Request $request, ContentRepository $contentRepository, RatingRepository $ratingRepository, EntityManagerInterface $em): Response
    {
        $query = trim($request->query->get('q', ''));

        $filters = [
            'date_range' => $request->query->get('date_range', 'all'),
            'categories' => $request->query->all('categories') ?: [],
            'tags'       => array_values(array_filter(array_map('trim', explode(',', $request->query->get('tags', ''))))),
            'min_rating' => $request->query->get('min_rating', ''),
            'sort'       => $request->query->get('sort', 'newest'),
        ];

        $hasSearch = $query !== ''
            || $filters['date_range'] !== 'all'
            || !empty($filters['categories'])
            || !empty($filters['tags'])
            || $filters['min_rating'] !== ''
            || $filters['sort'] !== 'newest';

        $results = $hasSearch ? $contentRepository->searchFiltered($query, $filters) : [];

        $ids = array_map(fn($c) => $c->getId(), $results);
        $tagsByContent    = [];
        foreach ($results as $content) {
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        return $this->render('search/index.html.twig', [
            'query'            => $query,
            'results'          => $results,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingRepository->averagesByContentIds($ids),
            'filters'          => $filters,
            'hasSearch'        => $hasSearch,
        ]);
    }

    #[Route('/deine-inhalte', name: 'app_deine_inhalte')]
    public function uploads(ContentRepository $contentRepository, RatingRepository $ratingRepository, EntityManagerInterface $em): Response
    {
        $user     = $this->getUser();
        $contents = $contentRepository->findBy(['fk_user' => $user]);
        $ids      = array_map(fn($c) => $c->getId(), $contents);

        $tagsByContent = [];
        foreach ($contents as $content) {
            $tagsByContent[$content->getId()] = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        }

        return $this->render('deine_inhalte/index.html.twig', [
            'user_data'        => $user,
            'contents'         => $contents,
            'tagsByContent'    => $tagsByContent,
            'ratingsByContent' => $ratingRepository->averagesByContentIds($ids),
        ]);
    }

    #[Route('/content/{id}/bearbeiten', name: 'app_content_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content || $content->getFkUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $existingTags = $em->getRepository(ContentTag::class)->findTagsByContent($content);
        $selectedTags = [];
        $bpmValue = null;
        foreach ($existingTags as $contentTag) {
            $tag = $contentTag->getFkTag();
            $name = $tag->getName();
            if (preg_match('/^(\d{1,3})\s*BPM$/i', $name, $matches)) {
                $bpmValue = (int) $matches[1];
                continue;
            }
            $selectedTags[] = $tag;
        }

        $form = $this->createForm(\App\Form\ContentUploadType::class, $content, [
            'is_edit' => true,
        ]);
        $form->get('fk_tag')->setData($selectedTags);
        $form->get('bpm')->setData($bpmValue);
        $form->handleRequest($request);

        $currentAudioExtension = strtolower(pathinfo($content->getFilePath() ?? '', PATHINFO_EXTENSION));
        $hasExistingAudio = (bool) $content->getFilePath();

        if ($form->isSubmitted()) {
            $audioFile = $form->get('audioFile')->getData();
            $category = $form->get('type')->getData();

            if ($category) {
                $name = strtolower($category->getName());
                $isSoundkit = str_contains($name, 'soundkit') || str_contains($name, 'sound kit');
                $newFileExtension = $audioFile ? strtolower($audioFile->getClientOriginalExtension()) : null;
                $existingIsZip = $hasExistingAudio && $currentAudioExtension === 'zip';

                if ($audioFile) {
                    if ($isSoundkit && $newFileExtension !== 'zip') {
                        $form->get('audioFile')->addError(
                            new \Symfony\Component\Form\FormError('Für Sound Kits bitte nur eine ZIP-Datei hochladen.')
                        );
                    } elseif (!$isSoundkit && $newFileExtension === 'zip') {
                        $form->get('audioFile')->addError(
                            new \Symfony\Component\Form\FormError('ZIP-Dateien sind nur für die Kategorie "Sound Kits" erlaubt.')
                        );
                    }
                } else {
                    if ($hasExistingAudio) {
                        if ($isSoundkit && !$existingIsZip) {
                            $form->get('audioFile')->addError(
                                new \Symfony\Component\Form\FormError('Für Sound Kits wird eine neue ZIP-Datei benötigt.')
                            );
                        } elseif (!$isSoundkit && $existingIsZip) {
                            $form->get('audioFile')->addError(
                                new \Symfony\Component\Form\FormError('Für diese Kategorie wird eine neue Audiodatei (MP3, WAV, FLAC, AIFF) benötigt.')
                            );
                        }
                    } else {
                        $form->get('audioFile')->addError(
                            new \Symfony\Component\Form\FormError('Bitte lade eine Audiodatei hoch.')
                        );
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $audioFile = $form->get('audioFile')->getData();
            $imageFile = $form->get('imageFile')->getData();

            if ($audioFile) {
                $userId = $user->getId();
                $originalFilename = pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $ext = $audioFile->guessExtension();
                if (!$ext || $ext === 'bin') {
                    $ext = $audioFile->getClientOriginalExtension();
                }

                $categoryName = strtolower($content->getType()->getName());
                $folderMap = [
                    'beat' => 'beats',
                    'beats' => 'beats',
                    'sample' => 'samples',
                    'samples' => 'samples',
                    'soundkit' => 'soundkits',
                    'soundkits' => 'soundkits',
                    'track' => 'tracks',
                    'tracks' => 'tracks',
                ];
                $subfolder = $folderMap[$categoryName] ?? 'misc';
                $uploadBase = $this->getParameter('uploads_directory');

                $newFilename = $safeFilename . '_' . $userId . '.' . $ext;
                $audioFile->move($uploadBase . '/' . $subfolder, $newFilename);
                $content->setFilePath($subfolder . '/' . $newFilename);
            }

            if ($imageFile) {
                $userId = $user->getId();
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '_' . $userId . '.' . $imageFile->guessExtension();

                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $content->setImageFile('images/' . $newFilename);
            }

            foreach ($existingTags as $contentTag) {
                $em->remove($contentTag);
            }
            $em->flush();

            $selectedTags = $form->get('fk_tag')->getData() ?: [];
            foreach ($selectedTags as $tag) {
                if (preg_match('/^\d{1,3}\s*BPM$/i', $tag->getName())) {
                    continue;
                }
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

            $em->persist($content);
            $em->flush();

            $this->addFlash('success', 'Inhalt erfolgreich aktualisiert.');
            return $this->redirectToRoute('app_deine_inhalte');
        }

        /** @var License[] $licenses */
        $licenses = $em->getRepository(License::class)->findAll();
        $licenseMap = [];
        foreach ($licenses as $license) {
            $licenseMap[$license->getId()] = [
                'shortCode' => $license->getShortCode(),
                'fullName' => $license->getFullName(),
                'description' => $license->getDescription(),
            ];
        }

        return $this->render('upload/index.html.twig', [
            'form' => $form->createView(),
            'licenseData' => $licenseMap,
            'pageTitle' => 'Inhalt bearbeiten',
            'pageSubtitle' => 'Passe deinen Track an und speichere die Änderungen.',
            'submitText' => 'Speichern',
            'existingImage' => $content->getImageFile(),
            'existingAudioName' => $content->getFilePath() ? basename($content->getFilePath()) : null,
        ]);
    }

    #[Route('/content/{id}/delete', name: 'app_content_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteContent(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Inhalt nicht gefunden');
        }

        if ($content->getFkUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_content = :content')
            ->setParameter('content', $content)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\Report r WHERE r.fk_comment IN (SELECT c FROM App\Entity\Comment c WHERE c.fk_content = :content)')
            ->setParameter('content', $content)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.fk_content = :content')
            ->setParameter('content', $content)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\ContentTag ct WHERE ct.fk_content = :content')
            ->setParameter('content', $content)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\Favorite f WHERE f.fk_content = :content')
            ->setParameter('content', $content)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\Rating r WHERE r.fk_content = :content')
            ->setParameter('content', $content)
            ->execute();

        $em->remove($content);
        $em->flush();

        $this->addFlash('success', 'Inhalt erfolgreich gelöscht.');
        return $this->redirectToRoute('app_deine_inhalte');
    }

    #[Route('/content/{id}/melden', name: 'content_report', methods: ['POST'])]
    public function report(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $content = $em->getRepository(Content::class)->find($id);
        if (!$content) {
            return new JsonResponse(['error' => 'Nicht gefunden'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }

        // Doppelmeldung verhindern - nur wenn die Report noch offen ist
        $qb = $em->getRepository(Report::class)->createQueryBuilder('r');
        $existing = $qb
            ->where('r.fk_user = :user')
            ->andWhere('r.fk_content = :content')
            ->andWhere("r.status != 'closed'")
            ->setParameter('user', $user)
            ->setParameter('content', $content)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($existing) {
            return new JsonResponse(['error' => 'Bereits gemeldet'], 409);
        }

        $reasonId = $request->request->get('reason_id');
        $reason = $em->getRepository(Reason::class)->find($reasonId);
        if (!$reason) {
            return new JsonResponse(['error' => 'Ungültiger Grund'], 400);
        }

        $report = new Report();
        $report->setFkContent($content);
        $report->setFkUser($user);
        $report->setFkReason($reason);
        $report->setMessage($request->request->get('message', null));
        $report->setStatus('open');
        $report->setCreatedAt(new \DateTime());

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
