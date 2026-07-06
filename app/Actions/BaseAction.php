<?php

declare(strict_types=1);

namespace App\Actions;

abstract class BaseAction
{
    abstract public function handle(mixed ...$args): mixed;
}
