<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    public const JSON_OPTIONS = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT;

    /**
     * Odpowiedzi czytelne bez dodatkowych narzędzi (np. przy testowaniu curlem):
     * wcięcia, polskie znaki bez escapowania i znak nowej linii na końcu.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed>  $context
     */
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $response = parent::json($data, $status, $headers, $context + [
            'json_encode_options' => self::JSON_OPTIONS,
        ]);

        return $response->setContent($response->getContent()."\n");
    }
}
