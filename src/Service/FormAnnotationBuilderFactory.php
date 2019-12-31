<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Form\Annotation\AnnotationBuilder;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FormAnnotationBuilderFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return AnnotationBuilder
     * @throws ServiceNotCreatedException for invalid listener configuration.
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        //setup a form factory which can use custom form elements
        $annotationBuilder = new AnnotationBuilder();
        $eventManager       = $container->build('EventManager');
        $annotationBuilder->setEventManager($ventManager);

        $formElementManager = $container->get('FormElementManager');
        $formElementManager->injectFactory($container, $annotationBuilder);

        $config = $container->get('config');
        if (isset($config['form_annotation_builder'])) {
            $config = $config['form_annotation_builder'];

            if (isset($config['annotations'])) {
                foreach ((array) $config['annotations'] as $fullyQualifiedClassName) {
                    $annotationBuilder->getAnnotationParser()->registerAnnotation($fullyQualifiedClassName);
                }
            }

            if (isset($config['listeners'])) {
                foreach ((array) $config['listeners'] as $listenerName) {
                    $listener = $container->get($listenerName);
                    if (!($listener instanceof ListenerAggregateInterface)) {
                        throw new ServiceNotCreatedException(sprintf('Invalid event listener (%s) provided', $listenerName));
                    }
                    $listener->attach($eventManager);
                }
            }

            if (isset($config['preserve_defined_order'])) {
                $annotationBuilder->setPreserveDefinedOrder($config['preserve_defined_order']);
            }
        }

        return $annotationBuilder;
    }

    /**
     * Create and return AnnotationBuilder instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return AnnotationBuilder
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AnnotationBuilder::class);
    }
}