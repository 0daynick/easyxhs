<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\MiniApp\Contracts;

use OverNick\Easyxhs\Kernel\Contracts\AccessToken;
use OverNick\Easyxhs\Kernel\Contracts\Config;
use OverNick\Easyxhs\Kernel\HttpClient\AccessTokenAwareClient;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface Application
{
    public function getAccount(): Account;

    public function getRequest(): ServerRequestInterface;

    public function getClient(): AccessTokenAwareClient;

    public function getHttpClient(): HttpClientInterface;

    public function getConfig(): Config;

    public function getAccessToken(): AccessToken;

    public function getCache(): CacheInterface;
}
