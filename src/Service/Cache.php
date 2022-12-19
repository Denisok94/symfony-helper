<?php

namespace Denisok94\SymfonyHelper\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class Cache
 *
 * - `useFilesystem`/`useRedis` - обратиться к группе, где хранится кеш
 * - `hasItem(key)` - наличие в кеше
 * - `getItem(key)`/ `getItems([key1, key2, ...])` - получить объект CacheItem. Если нет значения, будет пустой CacheItem, не null
 * - `CacheItem->get()` - взять сохранённое значение
 * - `CacheItem->set($value)` - записать значение
 * - `save(CacheItem)` - сохранить в кеше. true/false
 * - `deleteItem(key)`/`deleteItems([key1, key2, ...])` - удалить из кеша. true/false
 * - `clear()` - очистить группу. true/false
 * - `prune()` - удалить весь устаревший кеш для Filesystem
 * 
 * ```php
 * $cache = $this->cache->useFilesystem('cache.user');
 * $user_id = $request->query->get('id');
 * if ($cache->hasItem('user_' . $user_id)) {
 *  $userJson = $cache->getItem('user_' . $user_id)->get();
 * } else {
 *  $user = $this->userManager->findUserBy(['id' => $user_id]);
 *  $userJson = $this->jsonConverter->toJson($user, ['user-info']);
 *  $userCache = $cache->getItem('user_' . $user_id)->set($userJson);
 *  $isSaved = $cache->save($userCache); 
 * }
 * return JsonResponse::fromJsonString($userJson);
 * ```
 * @link https://symfony.com/doc/current/components/cache.html
 * @link https://symfony.com/doc/current/components/cache/cache_pools.html
 * @package Denisok94\SymfonyHelper\Component
 */
class Cache
{
    /** @var AbstractAdapter */
    public $cache;
    /** @var string */
    private $directory;
    /** @var LoggerInterface|null */
    private $logger;

    /**
     * class Cache
     * @param string $directory 
     */
    public function __construct(
        string $directory,
        ?LoggerInterface $logger = null
    ) {
        $this->directory = $directory;
        $this->logger = $logger;
    }

    /**
     * @param string $namespace пространство/группа, по умолчанию @
     * @param integer $defaultLifetime время жизни в секундах, 0 - всегда, пока сами не удалим
     * @param string|null $directory папка, где хранить кэш
     * @link https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html
     * @return AbstractAdapter
     */
    public function useFilesystem(string $namespace = '', int $defaultLifetime = 0, ?string $directory = null): AbstractAdapter
    {
        $directory = $directory ?? $this->directory;
        $this->cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);
        if ($this->logger) {
            $this->cache->setLogger($this->logger);
        }
        if ($defaultLifetime > 0) {
            $this->cache->prune(); // очистить от устаревшего кеша
        }
        return $this->cache;
    }

    /**
     * @param string $namespace пространство/группа
     * @param integer $defaultLifetime время жизни в секундах, 0 - всегда, пока сами не удалим
     * @param string|null $redis dns адрес 'redis://localhost'
     * ```yaml
     * //~services.yaml
     * parameters.redis_dsn: "%env(REDIS_URL)%/3"
     * ```
     * @param array $options Configure the Options
     * @link https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html
     * @return AbstractAdapter
     */
    public function useRedis(string $namespace = '', int $defaultLifetime = 0, ?string $redis = null, array $options = []): AbstractAdapter
    {
        $redisConnection = $redis ?? $this->container->get('redis_dsn');
        if ($redisConnection) {
            $client = RedisAdapter::createConnection($redisConnection, $options);
            $this->cache = new RedisAdapter($client, $namespace, $defaultLifetime);
            if ($this->logger) {
                $this->cache->setLogger($this->logger);
            }
            return $this->cache;
        }
    }
}
