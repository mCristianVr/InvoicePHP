<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Form\Model\CustomerData;
use App\Repository\CustomerRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customers')]
#[IsGranted('ROLE_USER')]
final class CustomerController extends AbstractController
{
    #[Route('', name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        $actor = $this->currentUser();

        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAllVisibleTo($actor),
            'showOwnerColumn' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $actor = $this->currentUser();
        $data = new CustomerData();
        $form = $this->createForm(CustomerType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = new Customer($actor, $data->name, $data->nifCif);
            $customer->updateDetails(
                $data->name,
                $data->nifCif,
                $data->address,
                $data->postalCode,
                $data->city,
                $data->province,
                $data->email,
                $data->phone,
            );

            try {
                $entityManager->persist($customer);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $form->get('nifCif')->addError(new FormError('Ya existe un cliente con este NIF/CIF para tu cuenta.'));

                return $this->render('customer/new.html.twig', [
                    'form' => $form,
                ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $this->addFlash('success', 'Cliente creado correctamente.');

            return $this->redirectToRoute('app_customer_show', ['id' => $customer->id]);
        }

        $status = ($form->isSubmitted() && !$form->isValid()) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('customer/new.html.twig', [
            'form' => $form,
        ], new Response(status: $status));
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(int $id, CustomerRepository $customerRepository): Response
    {
        $customer = $customerRepository->findOneVisibleTo($this->currentUser(), $id);
        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CustomerRepository $customerRepository, EntityManagerInterface $entityManager): Response
    {
        $customer = $customerRepository->findOneVisibleTo($this->currentUser(), $id);
        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        $form = $this->createForm(CustomerType::class, CustomerData::fromEntity($customer));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CustomerData $data */
            $data = $form->getData();
            $customer->updateDetails(
                $data->name,
                $data->nifCif,
                $data->address,
                $data->postalCode,
                $data->city,
                $data->province,
                $data->email,
                $data->phone,
            );

            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $form->get('nifCif')->addError(new FormError('Ya existe un cliente con este NIF/CIF para tu cuenta.'));

                return $this->render('customer/edit.html.twig', [
                    'customer' => $customer,
                    'form' => $form,
                ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $this->addFlash('success', 'Cliente actualizado correctamente.');

            return $this->redirectToRoute('app_customer_show', ['id' => $customer->id]);
        }

        $status = ($form->isSubmitted() && !$form->isValid()) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ], new Response(status: $status));
    }

    #[Route('/{id}/delete', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, CustomerRepository $customerRepository, EntityManagerInterface $entityManager): Response
    {
        $customer = $customerRepository->findOneVisibleTo($this->currentUser(), $id);
        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        $token = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('delete_customer_' . $customer->id, $token)) {
            $this->addFlash('error', 'Token de seguridad invalido.');

            return $this->redirectToRoute('app_customer_show', ['id' => $customer->id]);
        }

        // Once invoices reference customers, this deletion must be guarded against active references.
        $entityManager->remove($customer);
        $entityManager->flush();

        $this->addFlash('success', 'Cliente eliminado.');

        return $this->redirectToRoute('app_customer_index');
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('No authenticated user found.');
        }

        return $user;
    }
}
