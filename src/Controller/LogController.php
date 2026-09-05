<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogController extends AbstractController
{
    #[Route('/logs', name: 'app_logs', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $logDir = $this->resolveLogDirectory();
        $files = $this->discoverLogFiles($logDir);

        if ($files === []) {
            return $this->render('log/index.html.twig', [
                'logDir' => $logDir,
                'files' => [],
                'selectedFile' => null,
                'lineOptions' => [50, 100, 200, 500],
                'selectedLines' => 100,
                'logLines' => [],
                'errorMessage' => sprintf('No se encontraron archivos .log en %s', $logDir),
            ]);
        }

        $lineOptions = [50, 100, 200, 500];
        $selectedLines = (int) $request->query->get('lines', 100);
        if (!in_array($selectedLines, $lineOptions, true)) {
            $selectedLines = 100;
        }

        $selectedFile = (string) $request->query->get('file', '');
        if (!isset($files[$selectedFile])) {
            $selectedFile = array_key_first($files) ?? '';
        }

        $errorMessage = null;
        $logLines = [];

        if ($selectedFile !== '') {
            $path = $files[$selectedFile];

            try {
                $logLines = $this->tailFileLines($path, $selectedLines);
            } catch (\Throwable $exception) {
                $errorMessage = 'No se pudo leer el archivo seleccionado.';
            }
        }

        return $this->render('log/index.html.twig', [
            'logDir' => $logDir,
            'files' => array_keys($files),
            'selectedFile' => $selectedFile,
            'lineOptions' => $lineOptions,
            'selectedLines' => $selectedLines,
            'logLines' => $logLines,
            'errorMessage' => $errorMessage,
        ]);
    }

    private function resolveLogDirectory(): string
    {
        $appLogDir = (string) ($_SERVER['APP_LOG_DIR'] ?? $_ENV['APP_LOG_DIR'] ?? '');
        if ($appLogDir !== '' && is_dir($appLogDir)) {
            return $appLogDir;
        }

        return $this->getParameter('kernel.logs_dir');
    }

    /** @return array<string, string> */
    private function discoverLogFiles(string $logDir): array
    {
        $paths = glob(rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.log');
        if ($paths === false) {
            return [];
        }

        sort($paths);

        $files = [];
        foreach ($paths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $basename = basename($path);
            $files[$basename] = $path;
        }

        return $files;
    }

    /** @return list<string> */
    private function tailFileLines(string $path, int $lines): array
    {
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            throw new \RuntimeException('Cannot open log file.');
        }

        try {
            $buffer = '';
            $lineCount = 0;
            $position = -1;

            fseek($fp, 0, SEEK_END);
            $fileSize = ftell($fp);
            if ($fileSize === false || $fileSize === 0) {
                return [];
            }

            while (-$position <= $fileSize && $lineCount <= $lines) {
                fseek($fp, $position, SEEK_END);
                $char = fgetc($fp);

                if ($char === "\n") {
                    ++$lineCount;
                    if ($lineCount > $lines) {
                        break;
                    }
                }

                if ($char !== false) {
                    $buffer = $char . $buffer;
                }

                --$position;
            }

            $rows = preg_split('/\r\n|\r|\n/', $buffer);
            if ($rows === false) {
                return [];
            }

            $rows = array_values(array_filter($rows, static fn (string $row): bool => $row !== ''));

            if (count($rows) > $lines) {
                $rows = array_slice($rows, -$lines);
            }

            return $rows;
        } finally {
            fclose($fp);
        }
    }
}
