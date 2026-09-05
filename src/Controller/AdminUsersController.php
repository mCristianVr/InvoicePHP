<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminUsersController extends AbstractController
{
    #[Route('/admin/users', name: 'app_admin_users', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $this->handleRoleUpdate($request, $entityManager);

            return $this->redirectToRoute('app_admin_users');
        }

        /** @var list<User> $users */
        $users = $entityManager->getRepository(User::class)->findBy([], ['email' => 'ASC']);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'manageableRoles' => User::manageableRoles(),
        ]);
    }

    private function handleRoleUpdate(Request $request, EntityManagerInterface $entityManager): void
    {
        $userId = (int) $request->request->get('user_id', 0);
        if ($userId <= 0) {
            $this->addFlash('error', 'Usuario no valido.');

            return;
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Usuario no encontrado.');

            return;
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('manage_roles_' . $userId, $csrfToken)) {
            $this->addFlash('error', 'Token de seguridad invalido.');

            return;
        }

        $roles = $request->request->all('roles');
        if (!is_array($roles)) {
            $roles = [];
        }

        $roleList = [];
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleList[] = $role;
            }
        }

        $user->replaceManageableRoles($roleList);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Permisos actualizados para %s.', $user->getUserIdentifier()));
    }
}
