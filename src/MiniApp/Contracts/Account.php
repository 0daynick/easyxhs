<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\MiniApp\Contracts;

interface Account
{
    public function getAppId(): string;

    public function getSecret(): string;
}
