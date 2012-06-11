<?php

namespace ZfcBase\Service;

use Traversable;
use Zend\ServiceManager\ServiceLocatorAwareInterface,
    Zend\ServiceManager\ServiceLocatorInterface,
    Zend\EventManager\EventManagerAwareInterface,
    Zend\EventManager\EventManagerInterface;

class AbstractService implements ServiceLocatorAwareInterface, EventManagerAwareInterface {
    /**
     * @var EventManagerInterface
     */
    protected $events;
    
    /**
     * @var ServiceLocatorInterface
     */
    protected $locator;

    /**
     * set service locator
     *
     * @param ServiceLocatorInterface $locator
     */
    public function setServiceLocator(ServiceLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Set the event manager instance used by this context
     *
     * @param  EventManagerInterface $events
     * @return mixed
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(__CLASS__, get_called_class()));
        $this->events = $events;
        $this->attachDefaultListeners();
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function events()
    {
        if (null === $this->events) {
            $this->setEventManager($this->getServiceLocator()->get('EventManager'));
        }
        return $this->events;
    }

    /**
     * attach default listeners
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
        
    }
    
}
