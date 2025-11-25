<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $players = $options['players'];
        
        $builder
            ->add('playedAt', DateTimeType::class, [
                'label' => 'Date de la partie',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire'])
                ]
            ])
            ->add('taker', EntityType::class, [
                'class' => Player::class,
                'choices' => $players,
                'choice_label' => 'name',
                'label' => 'Qui a pris ?',
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez indiquer qui a pris'])
                ]
            ])
            ->add('ally', EntityType::class, [
                'class' => Player::class,
                'choices' => $players,
                'choice_label' => 'name',
                'label' => 'Allié (uniquement pour 5 joueurs)',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Aucun (3 ou 4 joueurs)'
            ])
            ->add('contractType', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'Petite' => 'petite',
                    'Garde' => 'garde',
                    'Garde sans le chien' => 'garde_sans',
                    'Garde contre le chien' => 'garde_contre',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de contrat est obligatoire'])
                ]
            ])
            ->add('oudlers', ChoiceType::class, [
                'label' => 'Nombre de bouts',
                'choices' => [
                    '0 bout' => 0,
                    '1 bout' => 1,
                    '2 bouts' => 2,
                    '3 bouts' => 3,
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de bouts est obligatoire'])
                ]
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points marqués par le preneur',
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 91],
                'constraints' => [
                    new NotBlank(['message' => 'Les points sont obligatoires']),
                    new Range(['min' => 0, 'max' => 91, 'notInRangeMessage' => 'Les points doivent être entre 0 et 91'])
                ]
            ])
            ->add('petitAuBout', CheckboxType::class, [
                'label' => 'Petit au bout',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('poigneeType', ChoiceType::class, [
                'label' => 'Poignée',
                'choices' => [
                    'Aucune' => null,
                    'Simple (10 atouts)' => 'simple',
                    'Double (13 atouts)' => 'double',
                    'Triple (15 atouts)' => 'triple',
                ],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'placeholder' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
            'players' => [],
        ]);
    }
}
