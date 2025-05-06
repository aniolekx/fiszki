<?php

namespace App\Controller;

use App\Entity\Flashcard;
use App\Repository\FlashcardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/flashcard')]
class FlashcardController extends AbstractController
{
    #[Route('/', name: 'flashcard_index', methods: ['GET'])]
    public function index(FlashcardRepository $flashcardRepository): Response
    {
        return $this->render('flashcard/index.html.twig', [
            'flashcards' => $flashcardRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'flashcard_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $flashcard = new Flashcard();
        $form = $this->createFormBuilder($flashcard)
            ->add('front')
            ->add('back')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($flashcard);
            $entityManager->flush();

            return $this->redirectToRoute('flashcard_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('flashcard/new.html.twig', [
            'flashcard' => $flashcard,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'flashcard_show', methods: ['GET'])]
    public function show(Flashcard $flashcard): Response
    {
        return $this->render('flashcard/show.html.twig', [
            'flashcard' => $flashcard,
        ]);
    }

    #[Route('/{id}/edit', name: 'flashcard_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Flashcard $flashcard, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder($flashcard)
            ->add('front')
            ->add('back')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('flashcard_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('flashcard/edit.html.twig', [
            'flashcard' => $flashcard,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'flashcard_delete', methods: ['POST'])]
    public function delete(Request $request, Flashcard $flashcard, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$flashcard->getId(), $request->request->get('_token'))) {
            $entityManager->remove($flashcard);
            $entityManager->flush();
        }

        return $this->redirectToRoute('flashcard_index', [], Response::HTTP_SEE_OTHER);
    }
}
