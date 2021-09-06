<?php
namespace Iodyne\Framework\Core\Component\Container\Interfaces;
interface IocEventContract
{
       public function beforeRegisteringCallbacks();
       public function registeringCallbacks();
       public function afterRegisteringCallbacks();
}