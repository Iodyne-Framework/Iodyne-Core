<?php
namespace Iodyne\Framework\Core\Component\Container;

use Closure;
use Exception;
use TypeError;
use ArrayAccess;
use Iodyne\Framework\Core\Component\Container\Exceptions\BindingResolutionException;
use ReflectionClass;
use Iodyne\Framework\Core\Component\Container\Interfaces\IocContainerContract;
use Iodyne\Framework\Core\Component\Container\Exceptions\EntryNotFoundException;
use Iodyne\Framework\Core\Component\Container\Utils\IocUtility;
use LogicException;
use ReflectionException;
use ReflectionParameter;

/**
 * The Concrete of IoC Container Class.
 */
class IocContainer implements IocContainerContract, ArrayAccess
{
       private static $instance    = null;
       protected $bindings         = [];
       protected $resolved         =  [];
       protected $instances        = [];
       protected $redockingCallbacks = [];
       protected $currentBuildStack = [];
       protected $with = [];
       protected $contextual = [];
       protected $aliases = [];
       protected $abstractAliases = [];
       public function register(mixed $abstract, Closure|string|null $concrete = null, $global = false)
       {
              $this->dropStaleInstances($abstract);
              if (is_null($concrete)) {
                     $concrete = $abstract;
              }
              if(! $concrete instanceof Closure){
                     if (! is_string($concrete)) {
                            throw new TypeError(self::class.'::bind() : Argument #2 [$concrete] must be typeof [Closure|string|null]');
                     }
                     $concrete = $this->getClosureResolver($abstract, $concrete);
              }
              $this->bindings[$abstract] = compact('concrete','global');

              if($this->resolved($abstract)){
                     $this->redocking($abstract);
              }
       }

       public function singleton(mixed $abstract, Closure|string|null $concrete)
       {
              return $this->register($abstract, $concrete, true);
       }
       public function alias($abstract, $alias)
       {
              if($alias === $abstract)
              {
                     throw new LogicException("Logical Error :  Given [{$abstract}] is aliased to itself.");
              }

              $this->aliases[$alias] = $abstract;

              $this->abstractAliases[$abstract] = $alias;

       }
       public function getBindings()
       {
              return $this->bindings;
       }
       public function getAlias($abstract)
       {
              return isset($this->aliases[$abstract]) 
                     ? $this->getAlias($this->aliases[$abstract]) : $abstract;
       }
       /**
        * Generate given abstract that was regiistering on container to be implemented.
        *
        * @param mixed $abstract
        * @param array $parameters
        * @return object|mixed
        */
       public function generate(mixed $abstract, array $parameters = [])
       {
              return $this->resolve($abstract, $parameters);
       }
       public function instantiate(mixed $abstract)
       {
       }
       public function has(string $abstract): bool
       {
              return $this->hasDocked($abstract);
       }
       public function get(string $abstract)
       {
              try {
                     return $this->resolve($abstract);
              } catch (Exception $e) {
                     if ($this->has($abstract)) {
                            throw $e;
                     }
              }
              throw new EntryNotFoundException($abstract, $e->getCode(), $e);
              
       }
       public function hasDocked($abstract)
       {
              return isset($this->bindings[$abstract]);
       }
       public function resolved($abstract)
       {
              if ($this->isAlias($abstract)) {
                     $abstract = $this->getAlias($abstract);
              }
              return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
       }
       public function isAlias($name)
       {
              return isset($this->aliases[$name]);
       }
       public function redocking($abstract)
       {
              $instance = $this->generate($abstract);

              foreach ($this->getRedockingCallbacks($abstract) as $callback) {
                     call_user_func($callback,$this,$instance);
              }
       }
       public function offsetGet($offset)
       {
              
       }
       public function offsetSet($offset, $value)
       {
              
       }
       public function offsetExists($offset)
       {
              
       }
       public function offsetUnset($offset)
       {
              
       }

       protected function getClosureResolver ($abstract, $concrete)
       {
              return function ($container, $parameters = []) use ($abstract, $concrete) {
                     if ($abstract == $concrete) {
                            return $container->build($concrete);
                     }
                     return $container->resolve(
                            $concrete,
                            $parameters,
                            false
                     );
              };
       }
       protected function getRedockingCallbacks($abstract)
       {
              return $this->redockingCallbacks[$abstract] ?? [];
       }
       /**
        * Undocumented function
        *
        * @param mixed $abstract
        * @param array $parameters
        * @param boolean $raiseEvents
        * @return object|mixed
        */
       protected function resolve($abstract, $parameters = [], $raiseEvents = true){
              $abstract = $this->getAlias($abstract);
              $concrete = $this->getContextualConcrete($abstract);
              $needsContextualConcrete = !empty($parameters) || ! is_null($concrete);

              if(isset($this->instances[$abstract]) && ! $needsContextualConcrete){
                     return $this->instances[$abstract];
              }

              $this->with[] = $parameters;

              if(is_null($concrete)){
                     $concrete = $this->getConrete($abstract);
              }
       
              if ($this->isBuildable($concrete, $abstract)) {
                     $object = $this->build($concrete);
              } else {
                     $object = $this->generate($abstract,$parameters);
              }

              if ($this->isGlobal($abstract) && !$needsContextualConcrete) {
                     $this->instances[$abstract] = $object;
              }
              
              $this->resolved[$abstract] = true;
              array_pop($this->with);
              return $object;
       }
       protected function isGlobal($abstract)
       {
              return isset($this->instances[$abstract]) || 
                     isset($this->bindings[$abstract]['global']) &&
                     $this->bindings[$abstract]['global'] === true;
       }
       protected function isBuildable($concrete, $abstract)
       {
              return $abstract == $concrete || $concrete instanceof Closure;
       }
       protected function getConrete($abstract)
       {
              if (isset($this->bindings[$abstract])) {
                     return $this->bindings[$abstract]['concrete'];
              }
              return $abstract;
       }
       protected function getContextualConcrete($abstract)
       {
              if(! is_null($binding = $this->findInContextualBindings($abstract))){
                     return $binding;
              }
              if(empty($this->abstractAliases[$abstract])){
                     return;
              }
              foreach ($this->abstractAliases[$abstract] as $alias) {
                     if(! is_null($binding = $this->findInContextualBindings($alias))){
                            return $binding;
                     }
              }
       }
       protected function findInContextualBindings($abstract)
       {
              return $this->contextual[end($this->currentBuildStack)][$abstract] ?? null;
       }
       protected function build($concrete)
       {
              if ($concrete instanceof Closure) {
                     return $concrete($this, $this->getLastParameterOverride());
              }

              try {
                     $reflector = new ReflectionClass($concrete);
              } catch (ReflectionException $e) {
                     throw new BindingResolutionException("Target [{$concrete}] doesn't exist.", 0, $e);
              }

              if(! $reflector->isInstantiable()){
                     return $this->notInstantiable($concrete);
              }

              $this->currentBuildStack[]         = $concrete;
              $constructor                       = $reflector->getConstructor();

              if (is_null($constructor)) {
                     array_pop($this->currentBuildStack);
                     return new $concrete;
              }

              $classDependencies = $constructor->getParameters();

              try {
                     $resolvedConstructorDependencies = $this->resolvedConstructorDependencies($classDependencies);
              } catch (BindingResolutionException $e) {
                     array_pop($this->currentBuildStack);
                     throw $e;
              }
              return $reflector->newInstanceArgs($resolvedConstructorDependencies);
       }
       /**
        * Undocumented function
        *
        * @param \ReflectionParameter[] $classDependencies
        * @return array 
        */
       protected function resolvedConstructorDependencies($classDependencies)
       {
              $results = [];
              foreach ($classDependencies as $dependency) {
                     if($this->hasParameterOverride($dependency)){
                            $results[] = $this->getParameterOverride($dependency);
                            continue;
                     }
                     $result = is_null(IocUtility::getParamaterClassname($dependency)) ? 
                                   $this->resolvePrimitive($dependency) : 
                                   $this->resolveClass($dependency);
                     if($dependency->isVariadic()){
                            $results = array_merge($results, $result);
                     } else {
                            $results[] = $result;
                     }
              }
              return $results;
       }
       protected function notInstantiable($concrete)
       {
              if(! empty($this->currentBuildStack)){
                     $prev = implode(', ', $this->currentBuildStack);

                     $mesage = "Target [{$concrete}] is not instantiable while builing [{$prev}]";
              } else {
                     $mesage =
                     "Target [{$concrete}] is not instantiable";
              }
              throw new BindingResolutionException($mesage);
       }

       protected function hasParameterOverride($dependency)
       {
              return array_key_exists(
                     $dependency->name, $this->getLastParameterOverride()
              );
       }
       protected function getParameterOverride($dependency)
       {
              return $this->getLastParameterOverride()[$dependency->name];
       }
       protected function getLastParameterOverride()
       {
              return count($this->with) ? end($this->with) : [];
       }
       protected function unresolvablePrimitive(ReflectionParameter $parameter)
       {
              $message = "Unresolvable dependency resolving [{$parameter}] in class [{$parameter->getDeclaringClass()->getName()}]";
              throw new BindingResolutionException($message);
       }
       protected function resolvePrimitive(ReflectionParameter $parameter)
       {
              if ($parameter->isDefaultValueAvailable()) {
                     return $parameter->getDefaultValue();
              }
              $this->unresolvablePrimitive($parameter);
       }
       protected function resolveClass(ReflectionParameter $parameter)
       {
              try {
                     return $parameter->isVariadic() ? $this->resolveVariadicClass($parameter) : $this->generate(IocUtility::getParamaterClassname($parameter));
              } catch (BindingResolutionException $e) {
                     if ($parameter->isDefaultValueAvailable()) {
                            array_pop($this->with);
                            return $parameter->getDefaultValue();
                     }
                     if($parameter->isVariadic()){
                            array_pop($this->with);
                            return [];
                     }
                     throw $e;
              }
       }
       protected function resolveVariadicClass(ReflectionParameter $parameter)
       {
              $className = IocUtility::getParamaterClassname($parameter);
              $abstract = $this->getAlias($className);

              if(! is_array($concrete = $this->getContextualConcrete($abstract))){
                     return $this->generate($className);
              }

              return array_map(function($abstract){
                     return $this->resolve($abstract);
              }, $concrete);
       }
       protected function dropStaleInstances($abstract)
       {
              unset($this->instances[$abstract], $this->aliases[$abstract]);
       }
       /**
        * Get the instance of container.
        *
        * @return static
        */
       public static function getIstance():static
       {
              if (is_null(static::$instance)) {
                     static::$instance = new static;
              }
              return static::$instance; 
       }
       /**
        * Set the container instance.
        *
        * @param IocContainerContract|null $container
        * @return IocContainerContract|static
        */
       public function setInstance(IocContainerContract $container = null): IocContainerContract|static
       {
              return static::$instance = $container;
       }
       public function __get($name)
       {
              return $this[$name];
       }
       public function __set($name, $value)
       {
              $this[$name] = $value;
       }
}