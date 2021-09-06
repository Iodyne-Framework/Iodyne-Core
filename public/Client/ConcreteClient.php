<?php
namespace Iodyne\Public\Client;

class ConcreteClient
{
       protected $dependency ;
       public function __construct(FirstDependencyClient $client)
       {
              $this->dependency = $client ;
       }
       public function greet($to = null)
       {
              if(! is_null($to)){
                     return $this->dependency->sendMessage($to);
              }
              return $this->dependency->sendMessage("Jancok");
       }
}

