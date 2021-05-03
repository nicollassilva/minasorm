<?php

namespace MinasORM;

use MinasORM\DatabaseChild;
use MinasORM\Utils\Strings;
use MinasORM\Utils\Properties;
use MinasORM\Builder\QueryBuilder;

/**
 * Database Class for MinasORM package
 * @package MinasORM/MinasORM
 */
class Database {
    use Properties;

    /** @var string $table */
    protected static $tableName = '';

    /** @var string $primary */
    protected static $primaryKey = '';

    /** @var object|null $builderInstance */
    protected static $builderInstance;

    /**
     * Searches for a record in the database based on the primary index
     * @param mixed $id
     * @param null|string|array $columns
     * @return \MinasORM\Builder\QueryBuilder|void|null
     */
    public static function find($id, $columns = ['*'])
    {
        return self::builder()->find($id, $columns);
    }

    /**
     * It works similar to the "find" method, but when it doesn't find the record, 
     * it kills the process.
     * 
     * @param mixed $id
     * @param null|string|array $columns
     * @return \MinasORM\Builder\QueryBuilder|void|null
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        $record = self::builder()->find($id, $columns);

        if(!$record) {
            exit();
        }

        return $record;
    }

    /**
     * Get the model informations
     * @return void
     */
    public static function setChildClass()
    {
        $childClass = new DatabaseChild(
                get_called_class()
            );

        self::$tableName = (new static)->getTable() ?? Strings::lower($childClass->getClassName(true));
        self::$primaryKey = (new static)->getPrimary() ?? 'id';
    }

    /**
     * Instance of the QueryBuilder class
     * Records Model information for executions with the database
     * @return \MinasORM\Builder\QueryBuilder
     */
    protected static function builder()
    {
        //if(self::$builderInstance instanceof QueryBuilder) return self::$builderInstance;

        $builder = new QueryBuilder();

        if(empty(self::$tableName) || empty(self::$primaryKey)) {
            self::setChildClass();
        }

        $builder->setData(
            self::$tableName,
            self::$primaryKey
        );

        //self::$builderInstance = $builder;

        return $builder;
    }

    /**
     * Return all records of the table
     * @return string
     */
    public static function all($columns = ['*'])
    {
        return self::builder()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * Add a basic where clause to the query
     * @param \Closure|string $columns
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param mixed $continuousOperator = 'AND'
     */
    public static function where($columns, $operator = null, $value = null, $continuousOperator = 'AND')
    {
        return self::builder()->where(
                $columns, $operator, $value, $continuousOperator
            );
    }

    /**
     * Get a new instance of QueryBuilder
     * @param string $name
     * @param string $primaryKey
     * @return \MinasORM\Builder\QueryBuilder|void|null
     */
    public static function table(String $name, String $primaryKey = 'id')
    {
        return (new QueryBuilder)
            ->setData($name, $primaryKey);
    }

    public static function firstWhere($columns, $operator = null, $value = null)
    {
        return self::builder()->where(
                $columns, $operator, $value
            )->first();
    }
}