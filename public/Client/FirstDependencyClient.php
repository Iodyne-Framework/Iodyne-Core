<?php

namespace Iodyne\Public\Client;

class FirstDependencyClient
{
       public function sendMessage(string $to)
       {
              echo 'Hello :'. $to .' from ' . __METHOD__;
       }
}