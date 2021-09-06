<?php
namespace Iodyne\Framework\Core\Component\Container\Exceptions;
use Exception;
use Psr\Container\ContainerExceptionInterface;

class BindingResolutionException extends Exception implements ContainerExceptionInterface
{
}