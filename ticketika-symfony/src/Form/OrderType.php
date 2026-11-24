<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Coordonnées de l'acheteur. Les quantités sont transmises séparément. */
class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'attr' => ['class' => 'field', 'autocomplete' => 'given-name']])
            ->add('lastName', TextType::class, ['label' => 'Nom', 'attr' => ['class' => 'field', 'autocomplete' => 'family-name']])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail', 'attr' => ['class' => 'field', 'autocomplete' => 'email']])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Moyen de paiement',
                'expanded' => true,
                'multiple' => false,
                'choices' => ['Mobile money (Orange, MVola, Airtel)' => 'mobile', 'Carte bancaire' => 'carte'],
                'data' => 'mobile',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
