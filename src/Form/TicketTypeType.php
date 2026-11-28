<?php

namespace App\Form;

use App\Entity\TicketType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class TicketTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du billet',
                'attr' => ['class' => 'field', 'maxlength' => 80, 'placeholder' => 'Ex. VIP, Adulte, Enfant'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 80)],
            ])
            ->add('priceAr', IntegerType::class, [
                'label' => 'Prix (Ar)',
                'attr' => ['class' => 'field', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['class' => 'field', 'min' => 1],
                'constraints' => [new Assert\Positive()],
            ])
            ->add('note', TextType::class, [
                'label' => 'Note (facultatif)',
                'required' => false,
                'attr' => ['class' => 'field', 'maxlength' => 160, 'placeholder' => 'Ex. Accès prioritaire'],
                'constraints' => [new Assert\Length(max: 160)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TicketType::class]);
    }
}
