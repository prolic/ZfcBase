<?php

namespace ZfcBase\Repository;

use ZfcBase\EventManager\EventProvider;
use ZfcBase\Mapper\AbstractDbMapper;

abstract class AbstractDbRepository extends EventProvider implements RepositoryInterface
{
    /**
     * @var AbstractDbMapper
     */
    protected $mapper;

    /**
     * Set mapper
     *
     * @param AbstractDbMapper $mapper
     */
    public function setMapper(AbstractDbMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Get mapper
     *
     * @return AbstractDbMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getMapper()->getTableName();
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->getMapper()->getPrimaryKey();
    }

    public function find($id)
    {
        $user = $this->getMapper()->find($id);
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('user' => $user));
        return $user;
    }
}