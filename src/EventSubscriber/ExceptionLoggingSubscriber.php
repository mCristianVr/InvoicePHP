<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.app')]
        private readonly LoggerInterface $appLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $this->appLogger->error('Unhandled application exception.', [
            'request_id' => (string) $request->attributes->get('_request_id', '-'),
            'route' => (string) $request->attributes->get('_route', '-'),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }
}
