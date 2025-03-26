<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\MiniApp;

use OverNick\Easyxhs\Kernel\Contracts\AccessToken as AccessTokenInterface;
use OverNick\Easyxhs\Kernel\HttpClient\AccessTokenAwareClient;
use OverNick\Easyxhs\Kernel\HttpClient\AccessTokenExpiredRetryStrategy;
use OverNick\Easyxhs\Kernel\HttpClient\RequestUtil;
use OverNick\Easyxhs\Kernel\HttpClient\Response;
use OverNick\Easyxhs\Kernel\Traits\InteractWithCache;
use OverNick\Easyxhs\Kernel\Traits\InteractWithClient;
use OverNick\Easyxhs\Kernel\Traits\InteractWithConfig;
use OverNick\Easyxhs\Kernel\Traits\InteractWithHttpClient;
use OverNick\Easyxhs\Kernel\Traits\InteractWithServerRequest;
use OverNick\Easyxhs\MiniApp\Contracts\Account as AccountInterface;
use OverNick\Easyxhs\MiniApp\Contracts\Application as ApplicationInterface;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\RetryableHttpClient;

use function array_merge;
use function is_null;
use function str_contains;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Application implements ApplicationInterface
{
    use InteractWithCache;
    use InteractWithClient;
    use InteractWithConfig;
    use InteractWithHttpClient;
    use InteractWithServerRequest;
    use LoggerAwareTrait;

    protected ?AccountInterface $account = null;

    protected ?AccessTokenInterface $accessToken = null;

    public function getAccount(): AccountInterface
    {
        if (! $this->account) {
            $this->account = new Account(
                appId: (string) $this->config->get('app_id'), /** @phpstan-ignore-line */
                secret: (string) $this->config->get('secret'), /** @phpstan-ignore-line */
            );
        }

        return $this->account;
    }

    public function setAccount(AccountInterface $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getAccessToken(): AccessTokenInterface
    {
        if (! $this->accessToken) {
            $this->accessToken = new AccessToken(
                appId: $this->getAccount()->getAppId(),
                secret: $this->getAccount()->getSecret(),
                cache: $this->getCache(),
                httpClient: $this->getHttpClient(),
                stable: $this->config->get('use_stable_access_token', false)
            );
        }

        return $this->accessToken;
    }

    public function setAccessToken(AccessTokenInterface $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    #[Pure]
    public function getUtils(): Utils
    {
        return new Utils($this);
    }

    public function createClient(): AccessTokenAwareClient
    {
        $httpClient = $this->getHttpClient();

        if ($this->config->get('http.retry', false)) {
            $httpClient = new RetryableHttpClient(
                $httpClient,
                $this->getRetryStrategy(),
                (int) $this->config->get('http.max_retries', 2) // @phpstan-ignore-line
            );
        }

        return (new AccessTokenAwareClient(
            client: $httpClient,
            accessToken: $this->getAccessToken(),
            failureJudge: fn (
                Response $response
            ) => ($response->toArray()['errcode'] ?? 0) || ! is_null($response->toArray()['error'] ?? null),
            throw: (bool) $this->config->get('http.throw', true),
        ))->setPresets($this->config->all());
    }

    public function getRetryStrategy(): AccessTokenExpiredRetryStrategy
    {
        $retryConfig = RequestUtil::mergeDefaultRetryOptions((array) $this->config->get('http.retry', []));

        return (new AccessTokenExpiredRetryStrategy($retryConfig))
            ->decideUsing(function (AsyncContext $context, ?string $responseContent): bool {
                return ! empty($responseContent)
                    && str_contains($responseContent, '410101')
                    && str_contains($responseContent, 'access_token expired');
            });
    }

    /**
     * @return array<string,mixed>
     */
    protected function getHttpClientDefaultOptions(): array
    {
        return array_merge(
            ['base_uri' => 'https://miniapp.xiaohongshu.com/'],
            (array) $this->config->get('http', [])
        );
    }
}
