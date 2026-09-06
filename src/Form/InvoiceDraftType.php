<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\InvoiceDraftData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceDraftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobDate', DateType::class, [
                'label' => 'Fecha del trabajo',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('customer', CustomerAutocompleteType::class, [
                'label' => 'Cliente',
                'required' => false,
            ])
            ->add('createNewCustomer', CheckboxType::class, [
                'label' => 'Crear cliente nuevo en esta factura',
                'required' => false,
            ])
            ->add('newCustomerName', TextType::class, [
                'label' => 'Nombre cliente nuevo',
                'required' => false,
            ])
            ->add('newCustomerNifCif', TextType::class, [
                'label' => 'NIF/NIE/CIF cliente nuevo',
                'required' => false,
            ])
            ->add('newCustomerEmail', TextType::class, [
                'label' => 'Email cliente nuevo (opcional)',
                'required' => false,
            ])
            ->add('lines', CollectionType::class, [
                'entry_type' => InvoiceDraftLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceDraftData::class,
        ]);
    }
}
