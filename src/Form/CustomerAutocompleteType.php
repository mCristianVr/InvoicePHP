<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField(alias: 'customer')]
final class CustomerAutocompleteType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Customer::class,
            'placeholder' => 'Busca por nombre, ID, NIF/CIF o email',
            'choice_label' => static fn (Customer $customer): string => sprintf('%s (%s)', $customer->name, $customer->nifCif),
            // 'id' lets numeric queries match the internal customer id, per product request.
            'searchable_fields' => ['id', 'name', 'nifCif', 'email'],
            'query_builder' => function (CustomerRepository $repository): QueryBuilder {
                $user = $this->security->getUser();

                // Defense in depth: an unauthenticated context must never leak any customer.
                if (!$user instanceof User) {
                    return $repository->createQueryBuilder('c')->andWhere('1 = 0');
                }

                return $repository->createVisibleToActorQueryBuilder($user)->orderBy('c.name', 'ASC');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
