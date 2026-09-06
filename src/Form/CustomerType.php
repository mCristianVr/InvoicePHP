<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\CustomerData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre o razon social',
            ])
            ->add('nifCif', TextType::class, [
                'label' => 'NIF / NIE / CIF',
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Direccion',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Codigo postal',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ciudad',
                'required' => false,
            ])
            ->add('province', TextType::class, [
                'label' => 'Provincia',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefono',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerData::class,
        ]);
    }
}
