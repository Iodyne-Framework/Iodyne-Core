<?php
namespace Iodyne\Framework\Core\Component\Helper\Types\Array\Exceptions;

use Exception;

class KeyDoesntExistsException extends Exception
{
       public function  __construct($class, $parameters)
       {
              parent::__construct($this->message($class,$parameters));
       }
       public function message($class, $parameters)
       {
              return $class." :: The Given Key [{$parameters}] doesnt exists" ;
       }
}