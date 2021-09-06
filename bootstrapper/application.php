<?php

require_once  __DIR__ . '../../vendor/autoload.php';

use Iodyne\Framework\Core\Component\Helper\Types\Array\ArrayType;

$array = new ArrayType([
       "users" => ([
              "username"    => "hamid",
              "email"       => "hamid@google.com",
       ]),
       "suppliers" => ([
              "username_s"    => "hamid",
              "email_s"       => "hamid@google.com",
       ])
]);
dd($array);
