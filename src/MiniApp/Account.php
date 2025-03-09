<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\MiniApp;

use OverNick\Easyxhs\MiniApp\Contracts\Account as AccountInterface;
use RuntimeException;

class Account implements AccountInterface
{
    public function __construct(
        protected string $appId,
        protected ?string $secret
    ) {
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getSecret(): string
    {
        if ($this->secret === null) {
            throw new RuntimeException('No secret configured.');
        }

        return $this->secret;
    }
}
