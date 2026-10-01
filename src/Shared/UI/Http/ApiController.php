<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    /**
     * Polskie znaki w odpowiedziach bez escapowania (ł -> ł).
     *
     * @param array<string, string> $headers
     * @param array<string, mixed>  $context
     */
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return parent::json($data, $status, $headers, $context + [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_UNICODE,
        ]);
    }
}
