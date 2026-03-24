<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\Reason;
use App\Entity\Report;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- CATEGORIES ---
        $categoryNames = ['Tracks', 'Beats', 'Samples', 'Sound Kits', 'Andere'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        // --- TAGS ---
        $tagNames = ['Afro', 'Trap', 'Drill', 'House', 'Detroit', 'RnB', 'Balkan', 'Latin', 'Skrilla', "Drake", "21 Savage"];
        $tags = [];
        foreach ($tagNames as $name) {
            $tag = new Tag();
            $tag->setName($name);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        // --- REASONS ---
        $reasonNames = ['Spam', 'Unangebrachter Inhalt', 'Copyright Verstoß', 'Hassrede', 'Belästigung'];
        $reasons = [];
        foreach ($reasonNames as $name) {
            $reason = new Reason();
            $reason->setName($name);
            $manager->persist($reason);
            $reasons[] = $reason;
        }

        // --- USERS ---
        $usersData = [
            ['admin@example.com', 'AdminUser', 'ROLE_ADMIN', 'Admin bio here.', 'password123'],
            ['alice@example.com', 'Alice', 'ROLE_USER', 'Alice loves photography.', 'password123'],
            ['bob@example.com', 'Bob', 'ROLE_USER', 'Bob is a music nerd.', 'password123'],
            ['carol@example.com', 'Carol', 'ROLE_USER', null, 'password123'],
        ];
        $users = [];
        foreach ($usersData as [$email, $username, $role, $bio, $plainPassword]) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setRoles([$role]);
            $user->setBiography($bio);
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $manager->persist($user);
            $users[] = $user;
        }

        // --- CONTENT ---
        $contentsData = [
            // [title, description, file_path, catIdx, userIdx, tagIdx, image_file]
            ['Midnight Vibes', 'Chill Afro Track mit smoothen Vocals.', 'tracks/midnight_vibes.mp3', 0, 0, 0, 'uploads/midnight_vibes.jpg'],
            ['Trap God Beat', 'Harter Trap Beat mit 808s und Hi-Hats.', 'beats/trap_god.mp3', 1, 1, 1, 'uploads/trap_god.jpg'],
            ['Drill Season', 'Dark UK Drill Instrumental.', 'beats/drill_season.mp3', 1, 2, 2, 'uploads/drill_season.jpg'],
            ['Balkan Fire', 'Energetischer Balkan-inspirierter Track.', 'tracks/balkan_fire.mp3', 0, 3, 6, 'uploads/balkan_fire.jpg'],
            ['808 Bass Sample Pack', 'Fette 808 Basslines für deine Beats.', 'samples/808_bass_pack.zip', 2, 0, 1, 'uploads/808_bass_pack.jpg'],
            ['Detroit Techno Kit', 'Classic Detroit Sound Kit mit Drums.', 'soundkits/detroit_techno.zip', 3, 1, 4, 'uploads/detroit_techno.jpg'],
            ['RnB Melodie', 'Smooth RnB Loop mit Piano und Strings.', 'tracks/rnb_melodie.mp3', 0, 2, 5, 'uploads/rnb_melodie.jpg'],
            ['Latin Heat', 'Feuriger Latin Beat mit Percussion.', 'beats/latin_heat.mp3', 1, 3, 7, 'uploads/latin_heat.jpg'],
            ['Skrilla Flow', null, 'tracks/skrilla_flow.mp3', 0, 0, 8, 'uploads/skrilla_flow.jpg'],
            ['Drill Hi-Hat Loops', 'Crispy Hi-Hat Loops für Drill Beats.', 'samples/drill_hihats.zip', 2, 1, 2, 'uploads/drill_hihats.jpg'],
            ['Trap Drum Kit Vol.1', 'Komplettes Trap Drum Kit mit Claps.', 'soundkits/trap_drums_vol1.zip', 3, 2, 1, 'uploads/trap_drums_vol1.jpg'],
            ['House Groove', 'Deep House Loop mit klassischem Feel.', 'tracks/house_groove.mp3', 0, 3, 3, 'uploads/house_groove.jpg'],
        ];
        $contents = [];
        foreach ($contentsData as [$title, $desc, $path, $catIdx, $userIdx, $tagIdx, $imageFile]) {
            $content = new Content();
            $content->setTitle($title);
            $content->setDescription($desc);
            $content->setFilePath($path);
            $content->setType($categories[$catIdx]);
            $content->setFkUser($users[$userIdx]);
            $content->setFkTag($tags[$tagIdx]);
            $content->setImageFile($imageFile);
            $content->setCreatedAt(new \DateTime(sprintf('-%d days', random_int(1, 30))));
            $manager->persist($content);
            $contents[] = $content;
        }

        // --- CONTENT TAGS ---
        $contentTagPairs = [
            [0, 0],
            [0, 5],  // Midnight Vibes       → Afro, RnB
            [1, 1],
            [1, 8],  // Trap God Beat        → Trap, Skrilla
            [2, 2],
            [2, 10],  // Drill Season         → Drill, 21 Savage
            [3, 6],
            [3, 7],  // Balkan Fire          → Balkan, Latin
            [4, 1],
            [4, 8],  // 808 Bass Sample Pack → Trap, Skrilla
            [5, 4],           // Detroit Techno Kit   → Detroit
            [6, 5],
            [6, 9],  // RnB Melodie          → RnB, Drake
            [7, 7],
            [7, 0],  // Latin Heat           → Latin, Afro
            [8, 8],
            [8, 1],  // Skrilla Flow         → Skrilla, Trap
            [9, 2],           // Drill Hi-Hat Loops   → Drill
            [10, 1],
            [10, 2],  // Trap Drum Kit        → Trap, Drill
            [11, 3],           // House Groove         → House
        ];
        foreach ($contentTagPairs as [$contentIdx, $tagIdx]) {
            $contentTag = new ContentTag();
            $contentTag->setFkContent($contents[$contentIdx]);
            $contentTag->setFkTag($tags[$tagIdx]);
            $manager->persist($contentTag);
        }

        // --- COMMENTS ---
        $commentsData = [
            ['Bester Afro Track den ich je gehört hab!', 0, 1, '-5 days'],
            ['Die 808s sind einfach zu heavy 🔥', 1, 2, '-4 days'],
            ['Genau mein Sound, sehr dark!', 2, 0, '-3 days'],
            ['Balkan Fire haut richtig rein!', 3, 3, '-2 days'],
            ['Das Sample Pack ist Gold wert.', 4, 1, '-1 day'],
            ['Detroit vibes on point 🎹', 5, 2, '-6 hours'],
            ['RnB Melodie perfekt für mein Projekt.', 6, 3, '-10 hours'],
            ['Latin Heat macht gute Laune!', 7, 0, '-8 hours'],
        ];
        $comments = [];
        foreach ($commentsData as [$text, $contentIdx, $userIdx, $ago]) {
            $comment = new Comment();
            $comment->setText($text);
            $comment->setFkUser($users[$userIdx]);
            $comment->setFkContent($contents[$contentIdx]);
            $comment->setCreatedAt(new \DateTime($ago));
            $manager->persist($comment);
            $comments[] = $comment;
        }

        // --- RATINGS ---
        $ratingsData = [
            [5, 0, 1],
            [4, 1, 2],
            [3, 2, 3],
            [5, 3, 0],
            [4, 4, 3],
            [2, 5, 1],
        ];
        foreach ($ratingsData as [$value, $contentIdx, $userIdx]) {
            $rating = new Rating();
            $rating->setValue($value);
            $rating->setFkUser($users[$userIdx]);
            $rating->setFkContent($contents[$contentIdx]);
            $manager->persist($rating);
        }

        // --- FAVORITES ---
        $favoritePairs = [
            [1, 0],
            [1, 3],
            [2, 2],
            [3, 1],
            [0, 3],
            [3, 2],
        ];
        foreach ($favoritePairs as [$userIdx, $contentIdx]) {
            $favorite = new Favorite();
            $favorite->setFkUser($users[$userIdx]);
            $favorite->setFkContent($contents[$contentIdx]);
            $manager->persist($favorite);
        }

        // --- REPORTS ---
        $reportsData = [
            // [message,               status,    ago,       contentIdx, commentIdx, reasonIdx, userIdx]
            ['This looks like spam.', 'pending', '-3 days', 1, null, 0, 2],
            ['Inappropriate comment.', 'resolved', '-2 days', null, 1, 1, 3],
            [null, 'pending', '-1 day', 3, null, 2, 1],
        ];
        foreach ($reportsData as [$message, $status, $ago, $contentIdx, $commentIdx, $reasonIdx, $userIdx]) {
            $report = new Report();
            $report->setMessage($message);
            $report->setStatus($status);
            $report->setCreatedAt(new \DateTime($ago));
            $report->setFkContent($contentIdx !== null ? $contents[$contentIdx] : null);
            $report->setFkComment($commentIdx !== null ? $comments[$commentIdx] : null);
            $report->setFkReason($reasons[$reasonIdx]);
            $report->setFkUser($users[$userIdx]);
            $manager->persist($report);
        }

        $manager->flush();
    }
}