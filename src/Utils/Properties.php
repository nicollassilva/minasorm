<?php

namespace MinasORM\Utils;

trait Properties {

    /**
     * Get the table name of model
     * @return string|null
     */
    public function getTable(): ?String
    {
        if(isset($this->table) && gettype($this->table) == 'string') {
            return $this->table;
        }

        return null;
    }

    /**
     * Get primary key of model
     * @return string|null
     */
    public function getPrimary(): ?String
    {
        if(isset($this->primary) && gettype($this->primary) == 'string') {
            return $this->primary;
        }
        
        return null;
    }

    /**
     * Get the fillable columns from the model table
     * @return array|null
     */
    public function getModelFillables(): ?Array
    {
        if(isset($this->fillables) && gettype($this->fillables) == 'array') {
            return $this->fillables;
        }
        
        return null;
    }

    /**
     * Get the default attributes of the empty columns values
     * @return array|null
     */
    public function getModelAttributes(): ?Array
    {
        if(isset($this->attributes) && gettype($this->attributes) == 'array') {
            return $this->attributes;
        }
        
        return null;
    }
}