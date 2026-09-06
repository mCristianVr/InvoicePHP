<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\InvoiceDraftLineData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceDraftLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Concepto',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Cantidad',
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Precio unitario (EUR)',
                'scale' => 2,
            ])
            ->add('taxRatePercent', NumberType::class, [
                'label' => 'IVA (%)',
                'scale' => 2,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceDraftLineData::class,
        ]);
    }
}
