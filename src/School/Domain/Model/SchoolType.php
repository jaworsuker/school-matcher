<?php

declare(strict_types=1);

namespace App\School\Domain\Model;

enum SchoolType: string
{
    case Liceum = 'liceum';
    case Technikum = 'technikum';
}
