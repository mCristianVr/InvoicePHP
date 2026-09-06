<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\User;
use App\Form\InvoiceDraftType;
use App\Form\Model\InvoiceDraftData;
use App\Form\Model\InvoiceDraftLineData;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceRepository;
use App\Validator\SpanishTaxId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invoices')]
#[IsGranted('ROLE_USER')]
final class InvoiceController extends AbstractController
{
    #[Route('/new', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CustomerRepository $customerRepository,
        InvoiceRepository $invoiceRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $actor = $this->currentUser();
        $invoiceDate = new \DateTimeImmutable('today');
        $customers = $customerRepository->findAllVisibleTo($actor);

        $choices = [];
        $customerSearchIndex = [];
        $customerOptions = [];
        foreach ($customers as $customer) {
            $label = sprintf('%s (%s)', $customer->name, $customer->nifCif);
            $searchText = implode(' ', [
                strtolower($customer->name),
                strtolower($customer->nifCif),
                strtolower($customer->email ?? ''),
                strtolower($customer->city ?? ''),
            ]);

            $choices[$label] = (int) $customer->id;
            $customerSearchIndex[(int) $customer->id] = $searchText;
            $customerOptions[] = [
                'id' => (int) $customer->id,
                'label' => $label,
                'search' => $searchText,
            ];
        }

        $data = new InvoiceDraftData();
        $form = $this->createForm(InvoiceDraftType::class, $data, [
            'customer_choices' => $choices,
            'customer_search_index' => $customerSearchIndex,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $this->resolveCustomerFromForm($form, $actor, $data, $customerRepository);
            if (!$customer instanceof Customer) {
                return $this->render('invoice/new.html.twig', [
                    'form' => $form,
                    'customersCount' => count($customers),
                ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $draftNumber = $this->generateDraftNumber($invoiceRepository);
            $invoice = new Invoice(
                $draftNumber,
                $invoiceDate,
                'EUR',
                $customer,
                null,
                $data->jobDate ?? $invoiceDate,
            );

            foreach ($data->lines as $line) {
                if (!$line instanceof InvoiceDraftLineData) {
                    continue;
                }

                $unitPriceCents = (int) round(($line->unitPrice ?? 0.0) * 100, 0, PHP_ROUND_HALF_UP);
                $taxRateBasisPoints = (int) round(($line->taxRatePercent ?? 0.0) * 100, 0, PHP_ROUND_HALF_UP);
                $invoice->addItem(new InvoiceItem($line->description, $line->quantity, $unitPriceCents, $taxRateBasisPoints));
            }

            if ($invoice->items->isEmpty()) {
                $form->addError(new FormError('Debes anadir al menos una linea de concepto.'));

                return $this->render('invoice/new.html.twig', [
                    'form' => $form,
                    'customersCount' => count($customers),
                    'customerOptions' => $customerOptions,
                    'invoiceDate' => $invoiceDate,
                ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            if ($customer->id === null) {
                $entityManager->persist($customer);
            }

            $entityManager->persist($invoice);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Factura en borrador %s creada correctamente.', $invoice->invoiceNumber));

            return $this->redirectToRoute('app_dashboard');
        }

        $status = ($form->isSubmitted() && !$form->isValid()) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('invoice/new.html.twig', [
            'form' => $form,
            'customersCount' => count($customers),
            'customerOptions' => $customerOptions,
            'invoiceDate' => $invoiceDate,
        ], new Response(status: $status));
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('No authenticated user found.');
        }

        return $user;
    }

    private function generateDraftNumber(InvoiceRepository $invoiceRepository): string
    {
        do {
            $candidate = sprintf('DRAFT-%s-%04d', (new \DateTimeImmutable())->format('YmdHis'), random_int(0, 9999));
        } while ($invoiceRepository->invoiceNumberExists($candidate));

        return $candidate;
    }

    private function resolveCustomerFromForm(
        FormInterface $form,
        User $actor,
        InvoiceDraftData $data,
        CustomerRepository $customerRepository,
    ): ?Customer {
        if ($data->createNewCustomer) {
            return $this->createInlineCustomerFromForm($form, $actor, $data, $customerRepository);
        }

        if ($data->customerId === null || $data->customerId <= 0) {
            $form->get('customerId')->addError(new FormError('Debes seleccionar un cliente o crear uno nuevo.'));

            return null;
        }

        $customer = $customerRepository->findOneVisibleTo($actor, (int) $data->customerId);
        if (!$customer instanceof Customer) {
            $form->get('customerId')->addError(new FormError('Cliente no valido para tu cuenta.'));

            return null;
        }

        return $customer;
    }

    private function createInlineCustomerFromForm(
        FormInterface $form,
        User $owner,
        InvoiceDraftData $data,
        CustomerRepository $customerRepository,
    ): ?Customer {
        $name = trim((string) ($data->newCustomerName ?? ''));
        $nifCif = strtoupper(trim((string) ($data->newCustomerNifCif ?? '')));
        $email = $data->newCustomerEmail !== null ? trim($data->newCustomerEmail) : null;
        $email = $email !== '' ? strtolower($email) : null;

        if ($name === '') {
            $form->get('newCustomerName')->addError(new FormError('El nombre del cliente nuevo es obligatorio.'));
        }

        if ($nifCif === '') {
            $form->get('newCustomerNifCif')->addError(new FormError('El NIF/NIE/CIF del cliente nuevo es obligatorio.'));
        } elseif (!SpanishTaxId::isValid($nifCif)) {
            $form->get('newCustomerNifCif')->addError(new FormError('El NIF/NIE/CIF del cliente nuevo no es valido.'));
        } elseif ($customerRepository->ownerHasNifCif($owner, $nifCif)) {
            $form->get('newCustomerNifCif')->addError(new FormError('Ya existe un cliente con este NIF/NIE/CIF en tu cuenta.'));
        }

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $form->get('newCustomerEmail')->addError(new FormError('El email del cliente nuevo no es valido.'));
        }

        if (count($form->getErrors(true)) > 0) {
            return null;
        }

        $customer = new Customer($owner, $name, $nifCif);
        $customer->updateDetails($name, $nifCif, null, null, null, null, $email, null);

        return $customer;
    }
}
