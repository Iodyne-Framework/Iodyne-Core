<?php
namespace Iodyne\Framework\Core\Component\Helper\Types\Array;

use ArrayIterator;
use Generator;
use IteratorAggregate;
use Iodyne\Framework\Core\Component\Helper\Types\Array\Exceptions\KeyDoesntExistsException;

class ArrayType implements IteratorAggregate
{
       protected static $instance = null;
       protected $original;
       public $originalKeyTypeChecking;
       protected $resolved = [];
       protected $iteratorClass;
       protected $generatorClass;
       protected $isOriginal = true;
       protected $wasChanged = false;
       protected $isIndexed;
       protected $isAssociated;
       protected $isMultidimensional;
       protected $key;
       public function __construct(array $data = [])
       {
              $this->setOriginal($data);
              $this->initializer();
       }
       
       public function __set($name, $value)
       {
              
       }
       public function __get($name)
       {
              
       }
       protected function initializer()
       {
              $this->originalKeyTypeChecking     = $this->getOriginalKeyTypeMapping();
              $this->iteratorClass               = $this->getIteratorClass();
              $this->generatorClass              = $this->getGeneratorClass();
       }
       public function isNumericKey($index)
       {
              try {
                     if ( ! array_key_exists($index, $this->getOriginal())) {
                            throw new KeyDoesntExistsException("::isNumericKey(index)", $index);
                     }
                     return call_user_func($this->getOriginalKeyTypeMapping()->is_numeric, $this)[$index];
              } catch (\Throwable $th) {
                     return  $th;
              }
       }
       public function isStringKey($index)
       {
              try {
                     if ( ! array_key_exists($index, $this->getOriginal())) {
                            throw new KeyDoesntExistsException("::isNumericKey(index)", $index);
                     }
                     return call_user_func($this->getOriginalKeyTypeMapping()->is_string, $this)[$index];
              } catch (\Throwable $th) {
                     return  $th;
              }
       }
       protected function getKey()
       {
              return array_keys($this->getOriginal());
       }
       protected function getOriginal()
       {
              return $this->original;
       }
       protected function setOriginal($values)
       {
              $this->original = $values;
       }
       protected function generalCheck()
       {
              if(!is_array($this->getOriginal())){
                     return false;
              }
              if(count($this->getOriginal()) <= 0){
                     return true;
              }
       }
       protected function isAllKeysIsNumeric()
       {
              return array_map("is_int", $this->getKey());
       }
       protected function isAllKeysIsString()
       {
              return array_map("is_string", $this->getKey());
       }
       protected function getOriginalKeyTypeMapping()
       {
              $this->generalCheck();
              return (object)
              [
                     "is_numeric"    =>   function($is_numeric){
                                                 return $this->isAllKeysIsNumeric();
                                          },
                     "is_string"     =>   function ($is_string) {
                                                 return $this->isAllKeysIsString();
                                          },     
              ];
       }
       protected function toUppercaseKey()
       {
              return array_change_key_case($this->original, CASE_UPPER);
       }
       protected function toLowercaseKey()
       {
              return array_change_key_case($this->original, CASE_LOWER);
       }

       public function getIterator(): ArrayIterator
       {
             return $this->getIteratorClass();
       }
       /**
        * Get the value of iteratorClass
        */ 
       protected function getIteratorClass()
       {
              return new ArrayIterator($this->getOriginal());
       }
       public function getGenerator(): Generator
       {
             return $this->getGeneratorClass();
       }
       /**
        * Get the value of iteratorClass
        */ 
       protected function getGeneratorClass()
       {
              foreach ($this->getOriginal() as $item) {
                     yield $item;
              }
       }
}