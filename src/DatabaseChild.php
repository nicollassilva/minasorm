<?php

namespace MinasORM;

class DatabaseChild
{
    public $childName;
    public $childObject;

    public function __construct(String $child)
    {
        $childClass = $child;
        $getClassName = explode("\\", $childClass);

        $this->setClassName(end($getClassName))
             ->setChildObject($childClass);
    }

    /**
     * Set name of the child class
     * @param string $name
     * @return \MinasORM\DatabaseChild
     */
    private function setClassName(String $name)
    {
        $this->childName = $name;

        return $this;
    }

    /**
     * Defines the name::class of the child class in the properties of the class
     * @param string $childObject
     * @return void
     */
    private function setChildObject(String $childObject)
    {
        $this->childObject = $childObject;
    }

    /**
     * Return the child class name
     * @param bool $tableMode
     * @return string
     */
    public function getClassName(Bool $tableMode = false)
    {
        if(!$tableMode) {
            return $this->childName;
        }

        return $this->childName . 's';
    }
    
    /**
     * Return new instance of class
     * @return \MinasORM\DatabaseChild
     */
    public function instance()
    {
        return new $this->childObject;
    }
}