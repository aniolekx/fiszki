<?php

namespace App\Controller;

use App\Repository\DeckRepository;
use App\Repository\FlashcardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(DeckRepository $deckRepository, FlashcardRepository $flashcardRepository): Response
    {
        $user = $this->getUser();
        $decks = $deckRepository->findBy(['user' => $user]);
        
        // Pobierz wszystkie fiszki z talii użytkownika
        $flashcards = [];
        $totalFlashcards = 0;
        foreach ($decks as $deck) {
            $deckFlashcards = $deck->getFlashcards()->toArray();
            $flashcards = array_merge($flashcards, $deckFlashcards);
            $totalFlashcards += count($deckFlashcards);
        }
        
        return $this->render('dashboard/index.html.twig', [
            'decks' => $decks,
            'flashcards' => $flashcards,
            'total_flashcards' => $totalFlashcards,
            'total_decks' => count($decks),
        ]);
    }
} 