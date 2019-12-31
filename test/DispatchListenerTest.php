<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\DispatchListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class DispatchListenerTest extends TestCase
{
    public function createMvcEvent($controllerMatched)
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParam('controller', 'not-found')->willReturn('path');

        $eventManager = new EventManager();

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        return $event;
    }

    public function testControllerManagerUsingAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\ControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertEmpty($log, var_export($log, 1));
        $this->assertSame($event->getResponse(), $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerViaAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertArrayHasKey('error', $log);
        $this->assertSame('error-controller-not-found', $log['error']);
    }
}
