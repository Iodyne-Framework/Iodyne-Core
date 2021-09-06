<?php
namespace Iodyne\Framework\Core\Component\Helper\Types\Array\Contracts;

interface ArrayTypeInterface {
       public function isNumericKey($index): bool;
       public function isStringKey($index): bool;
}