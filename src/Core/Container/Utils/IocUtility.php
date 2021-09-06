<?php
namespace Iodyne\Framework\Core\Component\Container\Utils;

use ReflectionNamedType;
use ReflectionParameter;
class IocUtility
{
       public static function getParamaterClassname(ReflectionParameter $parameter)
       {
              $type = $parameter->getType();

              if(! $type instanceof ReflectionNamedType || $type->isBuiltin()){
                     return;
              }

              $name = $type->getName();

              if (! is_null($class = $parameter->getDeclaringClass())) {
                     if($name === 'self'){
                            return $class->getName();
                     }

                     if($name === 'parent' && $parent = $class->getParentClass()){
                            return $parent->getName();
                     }
              }

              return $name;
       }
}