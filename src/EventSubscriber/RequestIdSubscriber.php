<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $incomingRequestId = trim((string) $request->headers->get('X-Request-Id', ''));

        $requestId = $incomingRequestId;
        if ($requestId === '' || !preg_match('/^[a-zA-Z0-9._-]{6,128}$/', $requestId)) {
            $requestId = bin2hex(random_bytes(16));
        }

        $request->attributes->set('_request_id', $requestId);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $requestId = (string) $event->getRequest()->attributes->get('_request_id', '');
        if ($requestId !== '') {
            $event->getResponse()->headers->set('X-Request-Id', $requestId);
        }
    }
}
