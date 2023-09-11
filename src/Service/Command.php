<?php

namespace Denisok94\SymfonyHelper\Service;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class Command
 * @package Denisok94\SymfonyHelper\Service
 */
class Command implements ServiceSubscriberInterface
{
    protected ?ContainerInterface $container;
    protected ?LoggerInterface $logger;
    protected ?string $dir = null;
    protected string $php = 'php';
    protected bool $check_versions = false;

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    /**
     * @param ContainerInterface|null $container
     * @required
     */
    public function setContainer(?ContainerInterface $container): ?ContainerInterface
    {
        $this->container = $container;
        return $this->container;
    }

    public function init(): void
    {
        if ($this->container && !$this->dir) {
            if ($this->container->has('parameter_bag')) {
                $this->dir = $this->container->get('parameter_bag')->get('kernel.project_dir');
            } else if ($this->container->has('kernel')) {
                $kernel = $this->container->get('kernel');

                $this->dir = $kernel->getProjectDir() /* ?? $kernel->getRootDir() . '/..' */;
            }
        }
    }

    /**
     * консольная команда
     * @param string $command `app:command`,
     * @param array|string $params параметры `[1,'value']`/ `'1 value'`
     * @param boolean $sync фоновая задача или нет,
     * ```php
     * $command->exec('app:command', '1 value');
     * $command->exec('app:command', [1,'value']);
     * // php bin/console app:command 1 value
     * ```
     * @return array $output
     */
    public function exec(string $command, $params, bool $sync = true): array
    {
        try {
            $this->init();

            if ($this->isCheckVersions()) {
                $v = explode('.', PHP_VERSION);
                $php = 'php' . $v[0] . '.' . $v[1];
            } else {
                $php = $this->php;
            }
            $dir = $this->dir;

            $params = is_array($params) ? implode(' ', $params) : $params;
            // $log = "> $dir/var/log/console.log";
            $syncStr = $sync ? '&' : '';
            // $exec = "$php $dir/bin/console $command $params $log $syncStr";
            $exec = implode(' ',  [
                $php,
                "$dir/bin/console",
                $command,
                $params,
                // $log,
                $syncStr
            ]);
            $this->logger?->debug("exec: $exec", []);

            // $process = new Process(['php', "$dir/bin/console", $command, $params, $log, $syncStr]);
            // try {
            //     // $process->run();
            //     $process->mustRun();
            //     // return $process->getOutput();
            // } catch (ProcessFailedException $exception) {
            //     $this->logger?->error("exec: $command | error:" . $exception->getMessage(), ['params' => $params]);
            // }

            exec($exec, $output, $return_code);
            if ($return_code != 0) {
                $this->logger?->error("$command | code: $return_code", ['params' => $params, 'output' => $output]);
            }
            return $output;
        } catch (Throwable $e) {
            $this->logger?->warning("exec: " . $e->getMessage());
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return [
            'parameter_bag' => '?' . ContainerBagInterface::class,
        ];
    }

    /**
     * @param string $php php7.4 / php8.2
     * @return self
     */
    public function setPhpVersion(string $php): self
    {
        $this->php = $php;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckVersions(): bool
    {
        return $this->check_versions;
    }

    /**
     * @param bool $check_versions 
     * @return self
     */
    public function setCheckVersions(bool $check_versions): self
    {
        $this->check_versions = $check_versions;
        return $this;
    }
}
