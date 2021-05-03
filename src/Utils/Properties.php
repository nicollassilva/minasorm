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
    public function getFillables(): ?Array
    {
        if(isset($this->fillables) && gettype($this->fillables) == 'array') {
            return $this->fillables;
        }
        
        return null;
    }
}