<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Każdy błąd pod /api zwracany jest jako zwięzły JSON (RFC 7807) - bez strony HTML i śladu stosu,
 * także dla nieistniejących ścieżek i niedozwolonych metod HTTP.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        $problem = [
            'status' => $status,
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'detail' => $this->detail($exception, $status, $headers),
        ];

        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $problem['violations'] = array_map(static fn (ConstraintViolationInterface $violation) => [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ], iterator_to_array($previous->getViolations()));
        }

        if ($status >= 500) {
            $this->logger->error('Unhandled API exception: '.$exception->getMessage(), ['exception' => $exception]);
        }

        $response = new JsonResponse($problem, $status, $headers + ['Content-Type' => 'application/problem+json']);
        $response->setEncodingOptions(ApiController::JSON_OPTIONS);
        $response->setContent($response->getContent()."\n");

        $event->setResponse($response);
    }

    /**
     * @param array<string, string> $headers
     */
    private function detail(\Throwable $exception, int $status, array $headers): string
    {
        return match (true) {
            $exception instanceof MethodNotAllowedHttpException => \sprintf('Metoda niedozwolona dla tego adresu. Dozwolone: %s.', $headers['Allow'] ?? '-'),
            $exception instanceof NotFoundHttpException && $exception->getPrevious() instanceof ResourceNotFoundException => 'Nie znaleziono takiego endpointu.',
            $exception->getPrevious() instanceof ValidationFailedException => 'Niepoprawne dane wejściowe.',
            $status >= 500 && !$this->debug => 'Wystąpił nieoczekiwany błąd serwera.',
            default => $exception->getMessage(),
        };
    }
}
