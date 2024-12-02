<?php
namespace Awz\Admin\Access\Permission;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\Rule\Factory\RuleControllerFactory;
use Bitrix\Main\Access\Rule\RuleInterface;
use ReflectionClass;

class RuleFactory extends RuleControllerFactory{

    protected const SUFFIX = "Custom\\Rules";
    protected const SUFFIX_SYSTEM = "Permission\\Rules";

    public function createFromAction(string $action, AccessibleController $controller): ?RuleInterface
    {
        $className = $this->getClassName($action, $controller);
        if (!$className  || !class_exists($className ))
        {
            return null;
        }

        $ref = new ReflectionClass($className);
        if ($ref->implementsInterface(RuleInterface::class))
        {
            return $ref->newInstance($controller);
        }

        return null;
    }

    protected function getClassName(string $action, AccessibleController $controller): ?string
    {
        $action = explode('_', $action);
        $action = array_map(fn($el) => ucfirst(mb_strtolower($el)), $action);

        $classCustom = $this->getNamespace($controller). implode($action);
        $classSystem = $this->getNamespaceSystem($controller). implode($action);
        if(class_exists($classCustom))
            return $classCustom;
        return $classSystem;
    }

    protected function getNamespace(AccessibleController $controller): string
    {
        $class = new ReflectionClass($controller);
        $namespace = $class->getNamespaceName();

        return $namespace.'\\'.static::SUFFIX.'\\';
    }

    protected function getNamespaceSystem(AccessibleController $controller): string
    {
        $class = new ReflectionClass($controller);
        $namespace = $class->getNamespaceName();

        return $namespace.'\\'.static::SUFFIX_SYSTEM.'\\';
    }

}