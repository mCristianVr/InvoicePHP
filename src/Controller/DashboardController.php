<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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
        $actor = $this->currentUser();
        $statusFilter = strtoupper((string) $request->query->get('status', 'ALL'));
        $statuses = ['ALL', 'DRAFT', 'SENT', 'PAID', 'OVERDUE', 'REJECTED'];
        $statusFilter = in_array($statusFilter, $statuses, true) ? $statusFilter : 'ALL';

        $invoices = $invoiceRepository->findVisibleForDashboard($actor, $statusFilter);

        $totals = $invoiceRepository->dashboardTotals($actor);
        $statusCounts = $invoiceRepository->dashboardStatusCounts($actor);

        return $this->render('dashboard/index.html.twig', [
            'invoices' => $invoices,
            'statusFilter' => $statusFilter,
            'statuses' => $statuses,
            'totals' => $totals,
            'statusCounts' => $statusCounts,
        ]);
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
