<?php

namespace Denisok94\SymfonyHelper\Service;

use Denisok94\SymfonyHelper\Exception\ConverterException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SymfonySerializerException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class JsonConverter
 * @package Denisok94\SymfonyHelper\Service
 */
class JsonConverter
{
    /** @var Serializer */
    protected $serializer;
    /** @var ValidatorInterface */
    protected $validator;

    /**
     * JsonConverter constructor.
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator, PropertyNamingStrategyInterface $serializerNamingStrategy)
    {
        $this->validator = $validator;
        $serializeBuilder = SerializerBuilder::create();
        $serializeBuilder->setPropertyNamingStrategy($serializerNamingStrategy);
        $this->serializer = $serializeBuilder->build();
    }

    /**
     * @param string $json
     * @param $type
     * @param DeserializationContext|null $context
     * @return mixed
     * @throws ConverterException
     */
    public function fromJson(string $json, $type, DeserializationContext $context = null)
    {
        try {
            $mixed = $this->serializer->deserialize($json, $type, "json");
        }
        catch (UnsupportedFormatException $e) {
            throw new ConverterException($e->getMessage(), $e->getCode(), $e);
        }
        catch (SymfonySerializerException $e) {
            throw new ConverterException($e->getMessage(), $e->getCode(), $e);
        }

        $errors = $this->validator->validate($mixed);
        if ($errors->count() > 0) {
            throw new ConverterException($errors->get(0)->getMessage());
        }

        return $mixed;
    }

    /**
     * @param mixed $object
     * @param array $groups
     * @param bool $serializeNull
     * @return string
     * @throws ConverterException
     */
    public function toJson($object, array $groups=[], bool $serializeNull = true) : string
    {
        $context = new SerializationContext();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }
        $context->setSerializeNull($serializeNull);

        try {
            return $this->serializer->serialize($object, 'json', $context);
        }
        catch (\Exception $e) {
            throw new ConverterException($e->getMessage(), $e->getCode(), $e);
        }
    }
}