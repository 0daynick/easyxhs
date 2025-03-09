<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\Kernel\Contracts;

interface Jsonable
{
    public function toJson(): string|false;
}
