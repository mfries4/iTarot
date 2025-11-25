<?php

namespace App\Form;

use App\Entity\GameList;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class GameListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $players = $options['players'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la liste',
                'constraints' => [new NotBlank(['message' => 'Le nom est obligatoire'])]
            ])
            ->add('players', EntityType::class, [
                'class' => Player::class,
                'choices' => $players,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
                'label' => 'Joueurs de la liste',
                'mapped' => false,
                'constraints' => [new NotBlank(['message' => 'Sélectionnez au moins 3 joueurs'])]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameList::class,
            'players' => [],
        ]);
    }
}
