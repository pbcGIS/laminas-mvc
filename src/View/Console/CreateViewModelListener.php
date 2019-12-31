<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\View\Console;

use Laminas\EventManager\EventManagerInterface as Events;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Model\ConsoleModel;

class CreateViewModelListener implements ListenerAggregateInterface
{
    /**
     * Listeners we've registered
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Attach listeners
     *
     * @param  Events $events
     * @return void
     */
    public function attach(Events $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'createViewModelFromString'), -80);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'createViewModelFromArray'),  -80);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'createViewModelFromNull'),   -80);
    }

    /**
     * Detach listeners
     *
     * @param  Events $events
     * @return void
     */
    public function detach(Events $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }


    /**
     * Inspect the result, and cast it to a ViewModel if a string is detected
     *
     * @param MvcEvent $e
     * @return void
    */
    public function createViewModelFromString(MvcEvent $e)
    {
        $result = $e->getResult();
        if (!is_string($result)) {
            return;
        }

        // create Console model
        $model = new ConsoleModel;

        // store the result in a model variable
        $model->setVariable(ConsoleModel::RESULT, $result);
        $e->setResult($model);
    }

    /**
     * Inspect the result, and cast it to a ViewModel if an assoc array is detected
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function createViewModelFromArray(MvcEvent $e)
    {
        $result = $e->getResult();
        if (!ArrayUtils::hasStringKeys($result, true)) {
            return;
        }

        $model = new ConsoleModel($result);
        $e->setResult($model);
    }

    /**
     * Inspect the result, and cast it to a ViewModel if null is detected
     *
     * @param MvcEvent $e
     * @return void
    */
    public function createViewModelFromNull(MvcEvent $e)
    {
        $result = $e->getResult();
        if (null !== $result) {
            return;
        }

        $model = new ConsoleModel;
        $e->setResult($model);
    }
}
