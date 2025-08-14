<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

#[Route('/deck')]
class DeckController extends AbstractController
{
    #[Route('/', name: 'deck_index', methods: ['GET'])]
    public function index(DeckRepository $deckRepository): Response
    {
        $user = $this->getUser();
        return $this->render('deck/index.html.twig', [
            'decks' => $deckRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/new', name: 'deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $deck = new Deck();
        $deck->setUser($this->getUser());
        
        $form = $this->createFormBuilder($deck)
            ->add('name', TextType::class, [
                'required' => true,
                'empty_data' => ''
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'empty_data' => ''
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($deck);
            $entityManager->flush();

            $this->addFlash('success', 'Talia została utworzona.');
            return $this->redirectToRoute('deck_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('deck/new.html.twig', [
            'deck' => $deck,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'deck_show', methods: ['GET'])]
    public function show(Deck $deck): Response
    {
        $this->denyAccessUnlessGranted('view', $deck);
        return $this->render('deck/show.html.twig', [
            'deck' => $deck,
        ]);
    }

    #[Route('/{id}/edit', name: 'deck_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Deck $deck, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $deck);
        
        $form = $this->createFormBuilder($deck)
            ->add('name', TextType::class, [
                'required' => true,
                'empty_data' => ''
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'empty_data' => ''
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Talia została zaktualizowana.');
            return $this->redirectToRoute('deck_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('deck/edit.html.twig', [
            'deck' => $deck,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'deck_delete', methods: ['POST'])]
    public function delete(Request $request, Deck $deck, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $deck);
        
        if ($this->isCsrfTokenValid('delete'.$deck->getId(), $request->request->get('_token'))) {
            $entityManager->remove($deck);
            $entityManager->flush();
            $this->addFlash('success', 'Talia została usunięta.');
        }

        return $this->redirectToRoute('deck_index', [], Response::HTTP_SEE_OTHER);
    }
}
