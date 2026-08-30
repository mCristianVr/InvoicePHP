<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.auth')]
        private readonly LoggerInterface $authLogger,
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error !== null) {
            $this->authLogger->warning('Login failed.', [
                'email' => (string) $authenticationUtils->getLastUsername(),
                'error_key' => $error->getMessageKey(),
            ]);
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');

            $this->authLogger->info('Registration submitted.', [
                'email' => strtolower($email),
            ]);

            if ($email === '' || $password === '') {
                $this->authLogger->warning('Registration rejected because of missing fields.', [
                    'email' => strtolower($email),
                ]);
                $this->addFlash('error', 'Email and password are required.');
                return $this->render('auth/register.html.twig');
            }

            try {
                $user = new User($email, '');
                $user->setPassword($hasher->hashPassword($user, $password));
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->authLogger->notice('Registration rejected because email already exists.', [
                    'email' => strtolower($email),
                ]);
                $this->addFlash('error', 'This email is already registered. Try logging in.');

                return $this->render('auth/register.html.twig');
            } catch (\Throwable $exception) {
                $this->authLogger->error('Registration failed with unexpected error.', [
                    'email' => strtolower($email),
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]);
                $this->addFlash('error', 'We could not create your account right now. Please try again in a few minutes.');

                return $this->render('auth/register.html.twig');
            }

            $this->authLogger->info('Registration succeeded.', [
                'email' => strtolower($email),
                'user_id' => $user->id,
            ]);
            $this->addFlash('success', 'Account created successfully. You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('This method should be intercepted by the logout firewall.');
    }
}
