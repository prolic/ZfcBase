<?php

namespace ZfcBase\Mapper;

use ArrayObject;
use DateTime;
use Traversable;
use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use ZfcBase\EventManager\EventProvider;
use ZfcBase\Util\String;
use ZfcUser\Module as ZfcUser;

abstract class AbstractDbMapper extends EventProvider implements DataMapperInterface
{
    /**
     * tableGateway 
     * 
     * @var TableGatewayInterface
     */
    protected $tableGateway;

    /**
     * @var array
     */
    protected $identityMap = array();

    /**
     * Get table name
     *
     * @return string
     */
    abstract public function getTableName();

    /**
     * Get primary key
     *
     * @return string
     */
    abstract public function getPrimaryKey();

    /**
     * Get tableGateway.
     *
     * @return TableGatewayInterface
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }
 
    /**
     * Set tableGateway.
     *
     * @param TableGatewayInterface $tableGateway
     */
    public function setTableGateway(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        return $this;
    }

    /**
     * @param $id
     * @return object
     */
    public function find($id)
    {
        $rowset = $this->getTableGateway()->select(array($this->getPrimaryKey() => $id));
        $row = $rowset->current();
        $model = $this->fromRow($row);
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('model' => $model, 'row' => $row));
        return $model;
    }

    abstract public function fromRow($row);

    /**
     * Persists a mapped object
     *
     * @param object $model
     * @return object
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function persist($model)
    {
        if (!is_object($model) || \get_class($model) !== $this->getClassName()) {
            throw new Exception\InvalidArgumentException('$model must be an instance of ' . $this->getClassName());
        }
        $data = $this->getHydrator()->extract($model);
        $results = $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('data' => $data, 'model' => $model));
        if (!$results->isEmpty()) {
            $result = $results->last();
            $data = $result->getParam('data');
            $model = $result->getParam('model');
        }

        if ($data[$this->getPrimaryKey()] > 0) {
            $this->getTableGateway()->update((array) $data, array($this->getPrimaryKey() => $data[$this->getPrimaryKey()]));
        } else {
            $this->getTableGateway()->insert((array) $data);
            if (!$this->getTableGateway() instanceof AbstractTableGateway) {
                throw new Exception\RuntimeException(
                    get_class($this->getTableGateway()) . ' is not an instance of '
                    . 'Zend\Db\TableGateway\AbstractTableGateway. This is needed, to have access to the db adapter'
                );
            }
            $id = $this->getTableGateway()->getAdapter()->getDriver()->getLastGeneratedValue();
            if ($id) {
                $idSetter = self::fieldToSetterMethod($this->getPrimaryKey());
                $model->$idSetter($id);
            }
        }
        return $model;
    }

    /**
     * add entity to identity map
     *
     * @param object $entity
     * @return bool
     */
    public function addToIdentityMap($entity)
    {
        $className = $this->getClassName();
        $id = serialize($this->getIdentifier($entity));
        if (isset($this->identityMap[$className][$id])) {
            return false;
        }
        $this->identityMap[$className][$id] = $entity;
        return true;
    }

    /**
     * Checks whether an entity is registered in the identity map
     *
     * @param object $entity
     * @return boolean
     */
    public function isInIdentityMap($entity)
    {
        $className = $this->getClassName();
        $id = serialize($this->getIdentifier($entity));
        return isset($this->identityMap[$className][$id]);
    }

    /**
     * remove from identity map
     *
     * @param object $entity
     * @return bool
     */
    public function removeFromIdentityMap($entity)
    {
        $className = $this->getClassName();
        $id = serialize($this->getIdentifier($entity));
        if (isset($this->identityMap[$className][$id])) {
            unset($this->identityMap[$className][$id]);
            return true;
        }
        return false;
    }

    /**
     * look up for an entity by id in identity map
     *
     * @param string $className
     * @param $id
     * @return object|false
     */
    public function lookupIdentityMap($className, $id)
    {
        $id = serialize($id);
        if (isset($this->identityMap[$className][$id])) {
            return $this->identityMap[$className][$id];
        }
        return false;
    }

    /**
     * get the identifier of an entity
     *
     * @param object $entity
     * @return mixed
     * @throws Exception\InvalidArgumentException
     */
    public function getIdentifier($entity)
    {
        $className = $this->getClassName();
        if (!is_object($entity) || get_class($entity) !== $className) {
            throw new Exception\InvalidArgumentException('$entity must be an object of type ' . $className);
        }
        $pk = $this->getPrimaryKey();
        $idGetter = self::fieldToGetterMethod($pk);
        if (method_exists($entity, $idGetter)) {
            $id = $entity->$idGetter();
        } else {
            $property = new \ReflectionProperty($className, $pk);
            $property->setAccessible(true);
            $id = $property->getValue($entity);
        }
        return $id;
    }

    /**
     * Gets the identity map
     *
     * @return array
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * @abstract
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    abstract public function getHydrator();

    public static function fieldToSetterMethod($name)
    {
        return 'set' . String::toCamelCase($name);
    }

    public static function fieldToGetterMethod($name)
    {
        return 'get' . String::toCamelCase($name);
    }
}
