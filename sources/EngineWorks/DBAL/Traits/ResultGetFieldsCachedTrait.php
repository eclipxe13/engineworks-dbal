<?php
namespace EngineWorks\DBAL\Traits;

trait ResultGetFieldsCachedTrait
{
    /*
     * getFields implementation using the cached variable
     */
    public function getFields()
    {
        if (null === $this->cacheGetFieldsReturnStorage) {
            $this->cacheGetFieldsReturnStorage = $this->realGetFields();
        }
        return $this->cacheGetFieldsReturnStorage;
    }

    /**
     * Used to set a cache of getFields function
     * @var array
     */
    protected $cacheGetFieldsReturnStorage = null;

    /**
     * This is the implementation of realGetFields since getFields does a cache
     * inside $this->cacheGetFields
     *
     * @see self::getFields
     * @return array|false
     */
    abstract protected function realGetFields();
}
