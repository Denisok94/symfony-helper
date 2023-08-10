<?php

namespace Denisok94\SymfonyHelper\Manager;

use Exception, Throwable;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Psr\Log\LoggerInterface;

/**
 * Class BaseManager
 * @package XDContents\EventModuleBundle\Manager
 */
class BaseManager
{
    /** @var EntityManager */
    protected $entityManager;
    /** @var string */
    protected $class; // Класс сущности
    /** @var LoggerInterface|null */
    protected $logger;

    /**
     * EventManagerManager constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return self
     */
    public function setClass($class): self
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param int $id
     * @return object|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getById(int $id): ?object
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->andWhere('e.id = :id')->setParameter('id', $id);
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $criteria
     * @return object|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    //----------------------------------

    /**
     * @return integer
     */
    public function getLastId(): int
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select("MAX(e.id)");
        $a = $queryBuilder->getQuery()->getOneOrNullResult();
        if ($a) {
            $a = array_shift($a);
            $b = (int) $a + 1;
            return $b;
        } else {
            return 1;
        }
    }

    /**
     * @param object $entity
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function delete($entity)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    /**
     * @param object $entity
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);
    }

    //----------------------------------

    /**
     * Поиск данных
     * @param array $criteria
     * @param string $baseAlias
     * @return object[]|null
     * ```php
     * $criteria = [name => value];
     * $criteria = ['enabled' => true];
     * 
     * $criteria = [name => [operator, value]];
     * $criteria = ['roles' => ['like', '%ROLE_PARTICIPANT%'];
     * 
     * $criteria = [[operator, name, value]];
     * $criteria = ['!=', 'id', 999];
     * 
     * $criteria = [[where, [parameter => value]]];
     * $criteria = [["u.firstName like :name", ['name' => '%Ivanov%']];
     * 
     * $this->manager->search($criteria, 'u');
     * ```
     * @throws Exception
     * @throws \Doctrine\ORM\NoResultException        If the query returned no result.
     * @throws \Doctrine\ORM\NonUniqueResultException If the query result is not unique.
     */
    public function search(array $criteria, string $baseAlias = 'e')
    {
        $p = 0; // prefix, чтоб избежать перезапись двух условий для одного поля
        $queryBuilder = $this->createQueryBuilder($baseAlias);
        $this->preSearch($queryBuilder, $baseAlias, $criteria);
        foreach ($criteria as $name => $value) {
            $p++;
            // ~ ['title' => $title]
            if (is_string($name) && !is_array($value)) {
                $queryBuilder->andWhere("$baseAlias.$name = :$name$p")->setParameter($name . $p, $value);
                continue;
            }
            // ~ ['title' => ['like', "%$title%"]] / ['id' => ['in', [1, 2], 'ppt']]
            if (is_string($name) && is_array($value) && (count($value) == 2 || count($value) == 3)) {
                $name = trim($name);
                $operator = trim($value[0]);
                $where = $value[1];
                $qp = $value[2] ?? $baseAlias;
                switch ($operator) {
                    case 'like':
                        $queryBuilder->andWhere("lower($qp.$name) like lower(:$name$p)");
                        break;
                    case 'orLike':
                        $queryBuilder->orWhere("lower($qp.$name) like lower(:$name$p)");
                        break;
                    case 'in':
                        $queryBuilder->andWhere("$qp.$name in (:$name$p)");
                        break;
                    case 'not in':
                        $queryBuilder->andWhere("$qp.$name not in (:$name$p)");
                        break;
                    default:
                        $queryBuilder->andWhere("$qp.$name $operator :$name$p");
                        break;
                }
                $queryBuilder->setParameter($name . $p, $where);
                continue;
            }
            // ~ ['!=', 'id', $user->getId()] 
            // todo: ['select', 'u.id', 'pp'] ['join', 'profileParticipant', 'p']
            if (is_numeric($name) && is_array($value) && count($value) == 3) {
                $operator = trim($value[0]);  // =, >, >=, <, <=, <>/!=
                $name = trim($value[1]);
                $where = $value[2];
                switch ($operator) {
                        // case 'select':
                        //     $queryBuilder->select("$name, $where");
                        //     break;
                        // case 'join':
                        //     $queryBuilder->join("u.$name", "$where");
                        //     break;
                    default:
                        $queryBuilder->andWhere("$baseAlias.$name $operator :$name$p")->setParameter($name . $p, $where);
                        break;
                }
                continue;
            }
            // ~ ["u.lastName like :fio OR u.firstName like :fio", ['fio' => "%$term%"]]
            if (is_numeric($name) && is_array($value) && count($value) == 2) {
                $where = trim($value[0]);
                $queryBuilder->andWhere("$where");
                foreach ($value[1] as $parameter => $parameterValue) {
                    $queryBuilder->setParameter("$parameter", $parameterValue);
                }
            }
        }
        $this->postSearch($queryBuilder, $baseAlias, $criteria);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $baseAlias
     * @param array $criteria
     */
    public function preSearch($queryBuilder, string $baseAlias, array &$criteria)
    {
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $baseAlias
     * @param array $criteria
     */
    public function postSearch($queryBuilder, string $baseAlias, array $criteria)
    {
    }

    //----------------------------------

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     * @throws Exception
     */
    public function getRepository()
    {
        if ($this->class) {
            return $this->entityManager->getRepository($this->class);
        } else {
            throw new Exception('set entity class in setClass(Entity::class)');
        }
    }

    /**
     * @param string $alias
     * @param string $indexBy The index for the from.
     * @return QueryBuilder
     * @throws Exception
     */
    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->getRepository()->createQueryBuilder($alias, $indexBy);
    }

    //----------------------------------

    /**
     * Запись в лог
     * @param string $message
     * @param mixed $context
     */
    public function info(string $message, $context = null): void
    {
        if ($this->logger) {
            $this->logger->info($this->textLogger($message), $this->paramLogger($context));
        }
    }

    /**
     * Запись в лог
     * @param string $message
     * @param mixed $context
     */
    public function error(string $message, $context = null): void
    {
        if ($this->logger) {
            $this->logger->error($this->textLogger($message), $this->paramLogger($context));
        }
    }

    /**
     * @param string $message
     * @return string
     */
    public function textLogger(string $message): string
    {
        return $message;
    }

    /**
     * @return array
     * @param mixed $context
     */
    public function paramLogger($context = null): array
    {
        return $context ? (is_array($context) ? $context : [$context]) : ([]);
    }

    /**
     * Запись в лог
     * @param Throwable $e
     */
    public function warning(Throwable $e): void
    {
        if ($this->logger) {
            $this->logger->warning(
                sprintf("%s(%s:%s)", $e->getMessage(), $e->getFile(), $e->getLine()),
                $this->paramLogger($e->getTrace())
            );
        }
    }

    /**
     * Запись в лог
     * @param Throwable $e
     */
    public function critical(Throwable $e): void
    {
        if ($this->logger) {
            $this->logger->critical(
                sprintf("%s(%s:%s)", $e->getMessage(), $e->getFile(), $e->getLine()),
                $this->paramLogger($e->getTrace())
            );
        }
    }

    /**
     * Запись в лог
     * @param string $level
     * @param string $message
     * @param mixed $context
     */
    public function log(string $level, string $message, $context = null): void
    {
        if ($this->logger) {
            $this->logger->log(
                $level,
                $message,
                $context ? (is_array($context) ? $context : [$context]) : ([])
            );
        }
    }
}
