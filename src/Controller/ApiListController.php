<?php

namespace Denisok94\SymfonyHelper\Controller;

use Denisok94\SymfonyHelper\Model\CollectionModel;
use Denisok94\SymfonyHelper\Exception\ApiRestException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * class ApiListController
 * @package Denisok94\SymfonyHelper\Controller
 */
abstract class ApiListController extends ApiRestController
{
    public $limit = 10;
    public $defaultLimit = 10;

    /**
     * @param Request $request
     * @param string $routeName
     * @return CollectionModel
     * @throws ApiRestException
     * @throws NotFoundHttpException
     */
    protected function getCollection(Request $request, string $routeName): CollectionModel
    {
        $page = $this->getPageNumber($request);
        if ($page == 0 || $page < 0) {
            throw new ApiRestException('page <= 0 or not is int', 400);
        }
        $paginator = $this->getPaginator($page);
        $total = $paginator->count();

        $collection = new CollectionModel();
        $collection
            ->setTotal($total)
            ->setPage($page)
            ->setLimit($this->limit)
            ->setItems(\iterator_to_array($paginator->getIterator(), false))
            ->setPagination($this->getPagination($page, $total, $routeName));
        return $collection;
    }

    /**
     * @param Request $request
     * @return int
     */
    protected function getPageNumber(Request $request): int
    {
        // page=X || /X || 1
        return (int) $request->query->get('page', $request->get('page', 1));
    }

    /**
     * @param int $page
     * @return Paginator
     * @throws NotFoundHttpException
     */
    protected function getPaginator(int $page): Paginator
    {
        $limit = $this->getQuery('limit', $this->limit);
        $offset = ($page - 1) * $limit;

        $query = $this->getQueryBuilder($limit, $offset)->getQuery();
        // $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, \Gedmo\Translatable\Query\TreeWalker\TranslationWalker::class);

        $paginator = new Paginator($query);

        if ($offset > $paginator->count()) {
            throw new NotFoundHttpException('Page not found.');
        }

        return $paginator;
    }

    /**
     * @param int $page
     * @param int $count
     * @return array|null
     */
    protected function getPagination($page, $count, string $routeName)
    {
        $limit = $this->limit;
        $pagesCount = $limit > 0 ? ceil($count / $limit) : 1;
        $pages = $queryList = [];

        $query = $this->requestStack->query->all();
        foreach ($query as $key => $value) {
            if (!in_array($key, ['page', 'limit'])) {
                $queryList[$key] = $value;
            }
        }

        for ($number = 1; $number <= $pagesCount; $number++) {
            $list = ['page' => $number];
            if ($limit != $this->defaultLimit) {
                $list['limit'] = $limit;
            }
            $list = array_merge($list, $queryList);
            $pages[] = [
                'number' => $number,
                'active' => $number == $page,
                'url' => $this->generateUrl($routeName, $list)
            ];
        }

        return $pages;
    }

    /**
     * @return string
     */
    protected function getObjectRequestKey(): string
    {
        return 'id';
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return QueryBuilder
     */
    protected abstract function getQueryBuilder($limit = null, $offset = null);

    /**
     * @return QueryBuilder
     */
    protected function getItemQueryBuilder()
    {
        return $this->getQueryBuilder();
    }

    /**
     * @param Request $request
     * @return Object|null
     * @throws NotFoundHttpException
     * @throws ApiRestException
     */
    protected function getObject(Request $request)
    {
        $key = $this->getObjectRequestKey();
        $value = $request->get($key);

        if ($value === null) {
            throw new NotFoundHttpException('Object not found');
        }

        $queryBuilder = $this->getItemQueryBuilder();
        $alias = current($queryBuilder->getRootAliases());

        $queryBuilder
            ->andWhere($alias . '.' . $key . ' = :value')
            ->setParameter('value', $value);

        try {
            $object = $queryBuilder->getQuery()
                // ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new ApiRestException($e->getMessage(), $e->getCode(), $e);
        }

        if ($object === null) {
            throw new NotFoundHttpException('Object not found');
        }

        return $object;
    }
}
