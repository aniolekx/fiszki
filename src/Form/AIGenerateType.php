<?php

namespace App\Form;

use App\Entity\Deck;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AIGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => 'Tekst do analizy',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Wklej tekst edukacyjny (1000-10000 znaków), z którego chcesz wygenerować fiszki...',
                    'class' => 'form-control',
                    'data-character-counter' => 'true',
                    'maxlength' => 10000
                ],
                'help' => 'Tekst musi zawierać od 1000 do 10000 znaków',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Proszę wprowadzić tekst'
                    ]),
                    new Assert\Length([
                        'min' => 1000,
                        'max' => 10000,
                        'minMessage' => 'Tekst musi zawierać co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Tekst nie może przekraczać {{ limit }} znaków'
                    ])
                ]
            ])
            ->add('deck', EntityType::class, [
                'label' => 'Talia docelowa',
                'class' => Deck::class,
                'choices' => $options['decks'],
                'choice_label' => 'name',
                'placeholder' => '-- Utwórz nową talię --',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Wybierz talię, do której zostaną dodane fiszki, lub zostaw puste aby utworzyć nową'
            ])
            ->add('generate', SubmitType::class, [
                'label' => 'Generuj fiszki',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg',
                    'data-loading-text' => 'Generowanie...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'decks' => []
        ]);
        
        $resolver->setAllowedTypes('decks', 'array');
    }
}