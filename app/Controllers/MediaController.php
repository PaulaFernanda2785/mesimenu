<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use RuntimeException;

final class MediaController extends Controller
{
    public function product(Request $request): Response
    {
        $rawPath = trim((string) $request->input('path', ''));
        if ($rawPath === '') {
            return Response::make('Imagem nao informada.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $relativePath = str_replace('\\', '/', ltrim($rawPath, '/'));
        if (str_starts_with($relativePath, 'public/')) {
            $relativePath = ltrim(substr($relativePath, strlen('public/')), '/');
        }

        $isAllowedPath = str_starts_with($relativePath, 'uploads/products/');
        $hasTraversal = str_contains($relativePath, '../') || str_contains($relativePath, '..\\');
        if (!$isAllowedPath || $hasTraversal) {
            return Response::make('Caminho de imagem invalido.', 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $absolutePath = BASE_PATH . '/public/' . $relativePath;
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return Response::make('Imagem nao encontrada.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $mime = @mime_content_type($absolutePath);
        if (!is_string($mime) || $mime === '') {
            $mime = 'application/octet-stream';
        }

        $content = file_get_contents($absolutePath);
        if ($content === false) {
            return Response::make('Falha ao ler imagem.', 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return Response::make($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function tableQr(Request $request): Response
    {
        $rawData = trim((string) $request->input('data', ''));
        if ($rawData === '') {
            return Response::make('Dados do QR nao informados.', 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        if (!str_starts_with($rawData, 'comanda360:')) {
            return Response::make('Formato de payload QR invalido.', 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        if (strlen($rawData) > 600) {
            return Response::make('Dados do QR excedem o limite permitido.', 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $size = (int) $request->input('size', 760);
        if ($size < 200) {
            $size = 200;
        }
        if ($size > 1200) {
            $size = 1200;
        }

        $cacheDir = BASE_PATH . '/storage/cache/table_qr';
        $cacheKey = hash('sha256', $rawData . '|' . $size);
        $cachePath = $cacheDir . '/' . $cacheKey . '.png';

        try {
            if (!is_file($cachePath)) {
                if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                    throw new RuntimeException('Nao foi possivel preparar o diretorio de cache do QR.');
                }

                $this->generateQrWithNode($rawData, $size, $cachePath);
            }

            $content = file_get_contents($cachePath);
            if ($content === false || $content === '') {
                throw new RuntimeException('Falha ao ler a imagem do QR gerada.');
            }
        } catch (RuntimeException $e) {
            return Response::make($e->getMessage(), 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return Response::make($content, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function generateQrWithNode(string $payload, int $size, string $targetPath): void
    {
        $scriptPath = BASE_PATH . '/scripts/generate_table_qr.cjs';
        if (!is_file($scriptPath)) {
            throw new RuntimeException('Script local de geracao de QR nao encontrado.');
        }

        $nodeBinary = $this->resolveNodeBinary();
        $command = escapeshellarg($nodeBinary) . ' ' .
            escapeshellarg($scriptPath) . ' --out ' .
            escapeshellarg($targetPath) . ' --size ' .
            (int) $size . ' --data-base64 ' .
            escapeshellarg(base64_encode($payload));

        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0 || !is_file($targetPath)) {
            $error = trim(implode("\n", $output));
            throw new RuntimeException(
                $error !== '' ? 'Falha ao gerar QR internamente: ' . $error : 'Falha ao gerar QR internamente.'
            );
        }
    }

    private function resolveNodeBinary(): string
    {
        $envNode = trim((string) getenv('NODE_BINARY'));
        if ($envNode !== '') {
            return $envNode;
        }

        $candidates = [
            'node',
            'node.exe',
            'C:\\Program Files\\nodejs\\node.exe',
        ];

        foreach ($candidates as $candidate) {
            $probe = @shell_exec(escapeshellarg($candidate) . ' -v 2>&1');
            if (is_string($probe) && trim($probe) !== '') {
                return $candidate;
            }
        }

        throw new RuntimeException('Node.js nao encontrado no servidor para gerar QR local.');
    }
}
