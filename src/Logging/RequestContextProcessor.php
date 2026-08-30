<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestContextProcessor
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $record;
        }

        $record->extra['request_id'] = (string) $request->attributes->get('_request_id', '-');
        $record->extra['route'] = (string) $request->attributes->get('_route', '-');
        $record->extra['method'] = $request->getMethod();
        $record->extra['path'] = $request->getPathInfo();
        $record->extra['client_ip'] = $request->getClientIp() ?? '-';

        return $record;
    }
}
