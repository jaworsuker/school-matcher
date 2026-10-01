<?php

declare(strict_types=1);

namespace App\School\UI\Http;

use App\School\Domain\Matching\SchoolMatcher;
use App\School\Domain\Repository\SchoolRepositoryInterface;
use App\School\UI\Http\Request\MatchSchoolRequest;
use App\School\UI\Http\Response\MatchResultView;
use App\School\UI\Http\Response\SchoolView;
use App\Shared\UI\Http\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/schools', format: 'json')]
final class SchoolController extends ApiController
{
    #[Route('', name: 'api_schools_list', methods: ['GET'])]
    public function list(SchoolRepositoryInterface $schools): JsonResponse
    {
        return $this->json(array_map(SchoolView::fromSchool(...), $schools->findAll()));
    }

    /**
     * Podgląd dopasowania bez zapisu - np. do podpowiedzi w formularzu rejestracji.
     */
    #[Route('/match', name: 'api_schools_match', methods: ['POST'])]
    public function match(#[MapRequestPayload] MatchSchoolRequest $request, SchoolMatcher $matcher): JsonResponse
    {
        return $this->json(MatchResultView::fromResult($matcher->match($request->name, $request->city)));
    }
}
