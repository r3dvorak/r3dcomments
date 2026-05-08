<?php
/**
 * @package     com_r3dcomments
 * @version     5.3.9
 * @date        2025-11-22
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Joomla\Component\R3dcomments\Administrator\Extension\R3dcommentsComponent;

/**
 * Service provider for com_r3dcomments
 */
return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        // Standard Joomla service providers
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\R3dcomments'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\R3dcomments'));
        $container->registerServiceProvider(new RouterFactory('\\Joomla\\Component\\R3dcomments'));

        // Component Interface binding
        $container->set(
            ComponentInterface::class,
            function (Container $container)
            {
                $component = new R3dcommentsComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );

        // No dynamic administrator menu injection — Joomla does not allow this.
        // Help button is handled in the component toolbar only.
    }
};
