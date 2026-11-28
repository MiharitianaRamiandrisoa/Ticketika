<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => "Nom de l'événement",
                'attr' => ['class' => 'field', 'maxlength' => 180],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'query_builder' => static fn ($repository) => $repository->createQueryBuilder('c')->where('c.active = :active')->setParameter('active', true)->orderBy('c.name', 'ASC'),
                'attr' => ['class' => 'field'],
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'field', 'rows' => 4, 'maxlength' => 10000],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 10000)],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => ['class' => 'field'],
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('place', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'field', 'maxlength' => 120],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
            ])
            ->add('ticketTypes', CollectionType::class, [
                'label' => false,
                'entry_type' => TicketTypeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'entry_options' => ['label' => false],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Event::class]);
    }
}
