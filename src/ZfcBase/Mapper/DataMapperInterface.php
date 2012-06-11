<?php
namespace ZfcBase\Mapper;

interface DataMapperInterface
{
    public function find($id);

    public function persist($model);

    public function remove($model);

    public function getPaginatorAdapter(array $params);

    /**
     * Returns the class name of the object mapped by the data mapper
     *
     * @return string
     */
    public function getClassName();

}