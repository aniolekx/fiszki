<?php

namespace App\Service;

use App\Entity\FlashcardProgress;
use DateTime;

class SpacedRepetitionService
{
    /**
     * SuperMemo SM-2 algorithm implementation
     * Quality: 0-5 (0=complete blackout, 5=perfect response)
     */
    public function calculateNextReview(FlashcardProgress $progress, int $quality): void
    {
        if ($quality < 0 || $quality > 5) {
            throw new \InvalidArgumentException('Quality must be between 0 and 5');
        }

        $progress->incrementTotalAttempts();
        $progress->setLastQuality($quality);
        $progress->setLastReviewedAt(new DateTime());

        if ($quality >= 3) {
            // Correct response
            $progress->incrementCorrectAttempts();
            $progress->setConsecutiveCorrect($progress->getConsecutiveCorrect() + 1);
            
            if ($progress->getRepetitions() === 0) {
                $progress->setInterval(1);
            } elseif ($progress->getRepetitions() === 1) {
                $progress->setInterval(6);
            } else {
                $newInterval = round($progress->getInterval() * $progress->getEaseFactor());
                $progress->setInterval($newInterval);
            }
            
            $progress->setRepetitions($progress->getRepetitions() + 1);
        } else {
            // Incorrect response - reset to beginning
            $progress->setConsecutiveCorrect(0);
            $progress->setRepetitions(0);
            $progress->setInterval(1);
        }

        // Update ease factor
        $newEaseFactor = $this->calculateEaseFactor($progress->getEaseFactor(), $quality);
        $progress->setEaseFactor($newEaseFactor);

        // Calculate next review date
        $nextReview = new DateTime();
        $nextReview->modify('+' . $progress->getInterval() . ' days');
        $progress->setNextReviewAt($nextReview);
    }

    private function calculateEaseFactor(float $currentEF, int $quality): float
    {
        // EF' = EF + (0.1 - (5 - q) * (0.08 + (5 - q) * 0.02))
        $newEF = $currentEF + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        
        // Minimum EF is 1.3
        return max(1.3, $newEF);
    }

    /**
     * Get quality rating description
     */
    public function getQualityDescription(int $quality): string
    {
        return match($quality) {
            0 => 'Zupełnie nie pamiętam',
            1 => 'Niepoprawna odpowiedź, ale pamiętam po podpowiedzi',
            2 => 'Niepoprawna odpowiedź, ale wydaje się łatwe',
            3 => 'Poprawna odpowiedź z dużym wysiłkiem',
            4 => 'Poprawna odpowiedź po wahaniu',
            5 => 'Perfekcyjna odpowiedź',
            default => 'Nieznana ocena'
        };
    }

    /**
     * Get cards due for review for a user and deck
     */
    public function getDueCards(array $flashcardProgresses): array
    {
        $dueCards = [];
        $now = new DateTime();

        foreach ($flashcardProgresses as $progress) {
            if ($progress->getNextReviewAt() <= $now) {
                $dueCards[] = $progress;
            }
        }

        // Sort by interval (shorter intervals first - newer cards)
        usort($dueCards, function($a, $b) {
            return $a->getInterval() <=> $b->getInterval();
        });

        return $dueCards;
    }

    /**
     * Calculate statistics for study session
     */
    public function calculateSessionStats(array $reviewedCards): array
    {
        $totalCards = count($reviewedCards);
        $correctCards = 0;
        $totalQuality = 0;
        $newCards = 0;
        $reviewCards = 0;

        foreach ($reviewedCards as $card) {
            if ($card->getLastQuality() >= 3) {
                $correctCards++;
            }
            $totalQuality += $card->getLastQuality();
            
            if ($card->getRepetitions() <= 1) {
                $newCards++;
            } else {
                $reviewCards++;
            }
        }

        return [
            'total' => $totalCards,
            'correct' => $correctCards,
            'accuracy' => $totalCards > 0 ? round(($correctCards / $totalCards) * 100, 1) : 0,
            'averageQuality' => $totalCards > 0 ? round($totalQuality / $totalCards, 1) : 0,
            'newCards' => $newCards,
            'reviewCards' => $reviewCards
        ];
    }
}