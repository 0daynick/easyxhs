<?php

declare(strict_types=1);

namespace OverNick\Easyxhs\MiniApp;

use OverNick\Easyxhs\Kernel\Contracts\RefreshableAccessToken as RefreshableAccessTokenInterface;
use OverNick\Easyxhs\Kernel\Exceptions\HttpException;
use JetBrains\PhpStorm\ArrayShape;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function intval;
use function is_string;
use function json_encode;
use function sprintf;

class AccessToken implements RefreshableAccessTokenInterface
{
    protected HttpClientInterface $httpClient;

    protected CacheInterface $cache;

    const CACHE_KEY_PREFIX = 'xhs_mini';

    public function __construct(
        protected string $appId,
        protected string $secret,
        protected ?string $key = null,
        ?CacheInterface $cache = null,
        ?HttpClientInterface $httpClient = null,
        protected ?bool $stable = false
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create(['base_uri' => 'https://miniapp.xiaohongshu.com/']);
        $this->cache = $cache ?? new Psr16Cache(new FilesystemAdapter(namespace: 'OverNick\Easyxhs', defaultLifetime: 1500));
    }

    public function getKey(): string
    {
        return $this->key ?? $this->key = sprintf('%s.access_token.%s.%s.%s', static::CACHE_KEY_PREFIX, $this->appId, $this->secret, (int) $this->stable);
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @throws HttpException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getToken(): string
    {
        $token = $this->cache->get($this->getKey());

        if ($token && is_string($token)) {
            return $token;
        }

        return $this->refresh();
    }

    /**
     * @return array{access_token:string}
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[ArrayShape(['access_token' => 'string', 'app_id' => 'string'])]
    public function toQuery(): array
    {
        return [
            'app_id' => $this->appId,
            'access_token' => $this->getToken()
        ];
    }

    /**
     * @throws \OverNick\Easyxhs\Kernel\Exceptions\HttpException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function refresh(): string
    {
        return $this->getAccessToken();
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \OverNick\Easyxhs\Kernel\Exceptions\HttpException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getAccessToken(): string
    {
        $response = $this->httpClient->request(
            'POST',
            'api/rmp/token',
            [
                'json' => [
                    'appid' => $this->appId,
                    'secret' => $this->secret,
                ],
            ]
        )->toArray(false);

        if ($response['code'] != 0) {
            throw new HttpException('Failed to get access_token: '. $response['msg']);
        }

        $this->cache->set($this->getKey(), $response['data']['access_token'], intval($response['data']['expire_in']) - 10);

        return $response['data']['access_token'];
    }
}
