<?php

namespace Denisok94\SymfonyHelper\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class CollectionModel
 * @package Denisok94\SymfonyHelper\Model
 */
class CollectionModel
{
    /**
     * @var integer
     *
     * @Serializer\Type("int")
     * @Serializer\Groups({"list"})
     */
    private $total;

    /**
     * @var integer
     *
     * @Serializer\Type("int")
     * @Serializer\Groups({"list"})
     */
    private $page;

    /**
     * @var integer
     *
     * @Serializer\Type("int")
     * @Serializer\Groups({"list"})
     */
    private $limit;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     * @Serializer\Groups({"list"})
     */
    private $items;

    /**
     * @var array|null
     *
     * @Serializer\Type("array")
     * @Serializer\Groups({"list"})
     */
    private $pagination;

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     * @return self
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return self
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     * @return self
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getPagination(): ?array
    {
        return $this->pagination;
    }

    /**
     * @param array|null $pagination
     * @return self
     */
    public function setPagination(?array $pagination): self
    {
        $this->pagination = $pagination;
        return $this;
    }
}
