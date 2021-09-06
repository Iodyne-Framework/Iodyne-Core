<?php
namespace Iodyne\Framework\Core\Component\Container\Interfaces;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * The Container Contracts.
 */
interface IocContainerContract extends ContainerInterface
{
       /**
        * Register anything abstract type with their concrete into container.
        * 
        * @param mixed $abstract The abstract type need to register into container.
        * @param \Closure|string|null $concrete The concrete type from given abstract.
        * @param boolean $global Indicate that abstract type with concrete value is available for gobal instaantiation as a single instance.
        * @return void
        */
       public function register(mixed $abstract, Closure|string|null $concrete, $global = false);

       /**
        * Generate given abstract that was regiistering on container to be implemented.
        *
        * @param mixed $abstract
        * @param array $parameters
        * @return object|mixed
        */
       public function generate(mixed $abstract, array $parameters = []);

       /**
        * Undocumented function
        *
        * @param mixed $abstract
        * @return void
        */
       public function instantiate(mixed $abstract);
       
       /** */
       public function singleton(mixed $abstract, Closure|string|null $concrete);
       public function alias($abstract, $alias);
       public function getAlias($abstract);
}