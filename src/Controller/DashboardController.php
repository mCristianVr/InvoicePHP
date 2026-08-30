<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(Request $request, InvoiceRepository $invoiceRepository): Response
    {
        $statusFilter = strtoupper((string) $request->query->get('status', 'ALL'));
        $statuses = ['ALL', 'DRAFT', 'SENT', 'PAID', 'OVERDUE', 'REJECTED'];
        $statusFilter = in_array($statusFilter, $statuses, true) ? $statusFilter : 'ALL';

        $invoices = $invoiceRepository->findBy(
            $statusFilter === 'ALL' ? [] : ['status' => $statusFilter],
            ['issuedAt' => 'DESC', 'id' => 'DESC'],
        );

        $totals = $invoiceRepository->dashboardTotals();

        return $this->render('dashboard/index.html.twig', [
            'invoices' => $invoices,
            'statusFilter' => $statusFilter,
            'statuses' => $statuses,
            'totals' => $totals,
        ]);
    }
}
