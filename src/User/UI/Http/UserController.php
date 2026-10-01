<?php

declare(strict_types=1);

namespace App\User\UI\Http;

use App\Shared\UI\Http\ApiController;
use App\User\Application\Register\RegisterUserCommand;
use App\User\Application\Register\RegisterUserHandler;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\UI\Http\Request\RegisterUserRequest;
use App\User\UI\Http\Response\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/users', format: 'json')]
final class UserController extends ApiController
{
    #[Route('', name: 'api_users_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterUserRequest $request, RegisterUserHandler $handler): JsonResponse
    {
        try {
            $user = $handler->handle(new RegisterUserCommand($request->email, $request->schoolName, $request->city));
        } catch (EmailAlreadyRegisteredException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return $this->json(UserView::fromUser($user), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_users_show', ['id' => $user->getId()]),
        ]);
    }

    #[Route('/{id}', name: 'api_users_show', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    public function show(int $id, UserRepositoryInterface $users): JsonResponse
    {
        $user = $users->findById($id) ?? throw new NotFoundHttpException(\sprintf('Użytkownik %d nie istnieje.', $id));

        return $this->json(UserView::fromUser($user));
    }
}
