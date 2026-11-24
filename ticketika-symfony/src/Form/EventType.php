<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => "Nom de l'événement", 'attr' => ['class' => 'field']])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'query_builder' => static fn ($repository) => $repository->createQueryBuilder('c')->where('c.active = :active')->setParameter('active', true)->orderBy('c.name', 'ASC'),
                'attr' => ['class' => 'field'],
            ])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['class' => 'field', 'rows' => 4]])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => ['class' => 'field'],
            ])
            ->add('place', TextType::class, ['label' => 'Lieu', 'attr' => ['class' => 'field']])
            ->add('capacity', IntegerType::class, ['label' => 'Capacité totale', 'attr' => ['class' => 'field', 'min' => 1]])
            ->add('priceAr', IntegerType::class, [
                'label' => 'Prix du billet standard (Ar)',
                'mapped' => false,
                'attr' => ['class' => 'field', 'min' => 0],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Event::class]);
    }
}
