<?php

namespace MinasORM;

use Closure;
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

    /** @var null|string $table */
    protected static $tableName = null;

    /** @var null|string $primary */
    protected static $primaryKey = null;

    /** @var object|null $builderInstance */
    protected static $builderInstance = null;

    /** @var string|null $model */
    protected static $model = null;

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
        $model = self::$model = get_called_class();

        $childClass = new DatabaseChild($model);

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
        $builder = new QueryBuilder();

        self::setChildClass();

        $builder->setData(
            self::$tableName,
            self::$primaryKey,
            self::$model
        );

        $builder->setFillables(
                (new static)->getModelFillables()
            );

        $builder->setAttributes(
                (new static)->getModelAttributes()
            );

        self::$builderInstance = $builder;

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
        return self::builder()
            ->setData($name, $primaryKey, static::class);
    }

    /**
     * Alias of the "find" static method combined with "first" method.
     * @param mixed $id
     * @param null|string $operator = null
     * @param mixed $value = null
     */
    public static function firstWhere($columns, $operator = null, $value = null)
    {
        return self::builder()->where(
                $columns, $operator, $value
            )->first();
    }

    /**
     * Alias of the "find" static method, but when it doesn't find the record, 
     * the callback will be executed.
     */
    public static function findOr($id, $columns, $callback = null)
    {
        if($columns instanceof \Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if($result = self::find($id, $columns)) {
            return $result;
        }

        if($callback instanceof \Closure) {
            return $callback(self::builder());
        }

        return null;
    }

    public static function latest(?String $column = null)
    {
        return self::builder()
            ->orderBy($column ?? self::$primaryKey, 'DESC');
    }

    public static function delete($value)
    {
        return self::builder()
            ->destroy($value);
    }

    /**
     * This method is invoked when a method 
     * called does not exist in the class
     * 
     * @param mixed $method
     * @param mixed|array $arguments
     * 
     * @return \MinasORM\Builder\QueryBuilder|\Exception
     */
    public function __call($method, $arguments)
    {
        return self::$builderInstance->{$method}($arguments);
    }

    public static function create(Array $data)
    {
        return self::builder()
            ->prepareStore($data);
    }
}