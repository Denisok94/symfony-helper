<?php

namespace Denisok94\SymfonyHelper\Service;

use Denisok94\SymfonyHelper\Service\JsonConverter;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ApiRest
 * @package Denisok94\SymfonyHelper\Service
 */
class ApiRestService implements ServiceSubscriberInterface
{
    /** @var JsonConverter */
    protected $jsonConverter;
    /** @var LoggerInterface|null  */
    protected $logger;
    /** @var ContainerInterface  */
    protected $container;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param JsonConverter $jsonConverter
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonConverter $jsonConverter,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->jsonConverter = $jsonConverter;
        $this->requestStack = $requestStack->getCurrentRequest();
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->requestStack;
    }

    /**
     * @return JsonConverter
     */
    public function getJsonConverter(): JsonConverter
    {
        return $this->jsonConverter;
    }


    /**
     * service_arguments в Symfony6 стал приватным 
     * и Symfony\Component\DependencyInjection\ContainerInterface не доступен
     * @required
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container;
        $this->container = $container;
        return $previous;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the value of eventDispatcher
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return [
            'security.token_storage' => '?' . \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class,
            'security.csrf.token_manager' => '?' . \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class,
        ];
    }
}
