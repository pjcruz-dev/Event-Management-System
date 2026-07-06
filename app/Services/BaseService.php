<?php

declare(strict_types=1);

namespace App\Services;

abstract class BaseService
{
    abstract public function execute(mixed ...$args): mixed;
}
