<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class EmailAlreadyRegisteredException extends \DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(\sprintf('Użytkownik z adresem "%s" już istnieje.', $email));
    }
}
