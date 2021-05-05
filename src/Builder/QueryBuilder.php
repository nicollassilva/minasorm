<?php

namespace MinasORM\Builder;

use PDO;
use Closure;
use Exception;
use PDOException;
use MinasORM\Builder\LogErrors;
use MinasORM\Connection\Connect;
use MinasORM\Utils\Arrays as Arr;
use MinasORM\Utils\Strings as Str;
use MinasORM\Builder\Functions\Helpers;

/**
 * QueryBuilder Class for MinasORM
 * @package MinasORM/MinasORM
 */
class QueryBuilder extends Connect {

    /** @var array $wheres */
    protected array $wheres = [];

    /** @var string $whereString */
    protected string $whereString = '';

    /** @var string $table */
    protected string $table = '';

    /** @var string $primary */
    protected string $primary = '';

    /** @var array $columns */
    protected array $columns = ['*'];

    /** @var array $orders */
    private array $orders = [];

    /** @var null|int $limit */
    protected $limit;

    /** @var null|int $offset */
    protected $offset;

    /**
     * Make the first connection to the database. If the connection 
     * has already been made through some previous call, it just verify 
     * the variable with the connection and returns.
     */
    public function __construct()
    {
        Connect::makeConnection();
    }

    /**
     * Search for a record in the database based on the table's primary index
     * @param 
     */
    public function find($id, $columns = null)
    {
        if($columns) {
            $this->only($columns);
        }

        return $this->where($this->primary, $id)->first($columns);
    }

    /**
     * Responsible for order the results of the consultation
     * @param string $column
     * @param string $order = 'asc'
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function orderBy(String $column, String $order = 'asc')
    {
        if(! in_array($order, ['asc', 'desc'], true)) {
            LogErrors::storeLog('Order direction must be "asc" or "desc".');
        }

        $order = Str::lower($order);

        $this->orders[] = [$column, $order];

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query
     *
     * @param  string  $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Search for a record in the database based on the table's primary index
     * @param \Closure|string $columns
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param mixed $condition = null
     */
    public function where($columns, $operator = null, $value = null, $condition = 'AND')
    {
        if(is_array($columns)) {
            return $this->addWhereAsArray($columns, $condition);
        }

        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if($columns instanceof Closure) {
            return $columns($this->newQueryWithSetData());
        }

        if(!$value) {
            $this->addWhere($columns, '=', $operator, $condition);
        } else {
            $this->addWhere($columns, $operator, $value, $condition);
        }
        
        return $this;
    }

    /**
     * Add where clause when it is array incoming
     * @param array $arrayWheres
     * @param string $continuousOperator
     * @param string $method = 'where'
     * @return method whereCallback
     */
    public function addWhereAsArray(Array $arrayWheres, $continuousOperator, $method = 'where')
    {
        return $this->whereCallback(function($query) use ($arrayWheres, $continuousOperator, $method) {
            foreach($arrayWheres as $key => $value) {
                if(is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->$method($key, '=', $value, $continuousOperator);
                }
            }
        });
    }

    /**
     * Execute the closure function to add array of wheres
     * @param closure $closure
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function whereCallback(Closure $closure)
    {
        call_user_func($closure, $this);

        return $this;
    }

    /**
     * Prepare the value and operator for a where clause
     * @param string $value
     * @param string $operator
     * @param bool $useDefault
     * @return array
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            return LogErrors::storeLog("Illegal operator and value combination.", true);
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal
     * Prevents using Null values with invalid operators
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        $operators = [
            '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
            'like', 'like binary', 'not like', 'ilike',
            '&', '|', '^', '<<', '>>',
            'rlike', 'not rlike', 'regexp', 'not regexp',
            '~', '~*', '!~', '!~*', 'similar to',
            'not similar to', 'not ilike', '~~*', '!~~*',
        ];

        return is_null($value) && in_array($operator, $operators) &&
             ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Returns a new instance of QueryBuilder with
     * the same configuration data (table and primary)
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function newQueryWithSetData()
    {
        return (new QueryBuilder)->setData($this->table, $this->primary);
    }

    /**
     * Alias of the "where" clause, with continuous operator OR
     * @param \Closure|string $columns
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param mixed $condition = null
     */
    public function orWhere($columns, $operator = null, $value = null)
    {
        $this->where($columns, $operator, $value, 'OR');

        return $this;
    }

    /**
     * Sets the columns to be retrieved from the database
     * @param mixed $columns
     */
    public function only($columns)
    {
        if($columns instanceof Closure) return;

        if(!is_array($columns) && Str::length($columns) < 1) {
            $this->columns = ['*'];
            return;
        }

        if(gettype($columns) == 'string') {
            $argumentAsArray = explode(',', $columns);

            $this->columns = Arr::clear($argumentAsArray);
            return;
        }

        if(gettype($columns) == 'array') {
            $this->columns = Arr::clear($columns);

            return;
        }

        $this->columns = ['*'];
    }

    /**
     * Add a new where clause for query.
     */
    protected function addWhere($column, $operator, $value, $continuousOperator = 'AND')
    {
        $this->wheres[] = [$column, $operator, $value, $continuousOperator];
    }

    /**
     * Prepare the where array for the next database call
     * @return void
     */
    protected function prepareWhereForQuery()
    {
        foreach($this->wheres as $where) {
            if(mb_strlen($this->whereString) > 0) {
                $this->whereString .= " {$where[3]} {$where[0]} {$where[1]} ?";
            } else {
                $this->whereString = "WHERE {$where[0]} {$where[1]} ?";
            }
        }
    }

    /**
     * Prepare the orders array for the next database call
     * @return void
     */
    protected function prepareOrdersForQuery()
    {
        $orderString = " ORDER BY ";

        foreach($this->orders as $key => $order) {
            if(isset($this->orders[$key + 1])) {
                $nextOrder = $this->orders[$key + 1];
            }

            if(isset($nextOrder) && $nextOrder[1] === $order[1]) {
                $orderString .= "{$order[0]}, ";
            } else {
                $orderString .= "{$order[0]} {$order[1]}, ";
            }

            $nextOrder = null;
        }

        return Str::clearEnd(', ', $orderString);
    }

    /**
     * Limits the amount of results to be returned by the query
     * @param int $limit
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function limit(Int $limit)
    {
        if($limit >= 0) {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query
     *
     * @param int $limit
     * @return \MinasORM\Builder\QueryBuilder|static
     */
    public function take(Int $limit)
    {
        return $this->limit($limit);
    }

    /**
     * Execute PDO Query
     * @return object|bool|null 
     */
    protected function executeQuery()
    {
        $this->prepareWhereForQuery();

        $query = Connect::getInstance()
            ->prepare($this->getFormatedQuery());
        
        $this->bindValues($query);

        try {
            $query->execute();
        } catch(PDOException $exception) {
            return LogErrors::storeLog($exception->getMessage(), true);
        }

        return $query;
    }

    /**
     * Return a first record or call the callback
     * @param mixed $columns
     * @param \Closure|null $callback
     * @return \MinasORM\Builder\QueryBuilder|mixed|static
     */
    public function firstOr($columns = ['*'], Closure $callback = null)
    {
        if($columns instanceof Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if($result = $this->first($columns)) {
            return $result;
        }

        return $callback();
    }

    /**
     * Bind values to execute query
     * @param object|null $preparedQuery
     * @return void
     */
    protected function bindValues($preparedQuery)
    {
        foreach($this->wheres as $index => $where) {
            $preparedQuery->bindParam($index + 1, $where[2], Helpers::getDataType($where[2]));
        }
    }

    /**
     * Returns the number of records consulted
     * @return \MinasORM\Builder\QueryBuilder|bool|null
     */
    public function count()
    {
        return $this->queryResults(false, true);
    }

    /**
     * Return formated query as string.
     * @param string $type
     * @return string
     */
    public function getFormatedQuery(String $type = 'where')
    {
        $columns = implode(', ', $this->columns);

        $limit = $this->limit > 0 ? 'LIMIT ' . $this->limit : '';

        $orders = $this->prepareOrdersForQuery();

        $offset = $this->offset > 0 ? 'OFFSET ' . $this->offset : '';

        if($type === 'where') {
            return "SELECT {$columns} FROM {$this->table} {$this->whereString} {$orders} {$limit} {$offset}";
        }
    }

    /**
     * Set tableName and primaryKey of called class
     * @param string $table
     * @param string $primary
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function setData(String $table, String $primary)
    {
        $this->table = $table;
        $this->primary = $primary;

        return $this;
    }

    /**
     * Return query results
     * @param bool $returnInstance
     * @param bool $returnCount
     * @return \PDO|void|array|boolean
     */
    protected function queryResults($returnInstance = false, $returnCount = false)
    {
        $execQuery = $this->executeQuery();

        if(!$execQuery) return;

        if($returnCount) {
            return $execQuery->rowCount();
        }

        if($execQuery->rowCount() > 1) {
            return $returnInstance ? $execQuery->fetchAll(PDO::FETCH_CLASS, static::class)
                   : $execQuery->fetchAll(PDO::FETCH_ASSOC);
        }

        return $returnInstance ? $execQuery->fetchObject(static::class)
               : $execQuery->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the first requested record and return this instance
     * @param null|string|array $columns
     * @return \MinasORM\Builder\QueryBuilder|void|array|boolean
     */
    public function first($columns = null)
    {
        if($columns) {
            $this->only($columns);
        }

        $this->limit(1);

        return $this->queryResults(true);

    }

    /**
     * Skips the table records according to the (offset * limit);
     * @param int $skip
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function offset(Int $offset)
    {
        if($offset >= 0) {
            $this->offset = $offset;
        }

        return $this;
    }

    /**
     * Alias of the "offset" method;
     * @param int $skip
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function skip(Int $skip)
    {
        return $this->offset($skip);
    }

    /**
     * Get the first requested record, but when it doesn't find the record, 
     * it kills the process (with 404 error).
     * @param null|string|array $columns
     * @return \MinasORM\Builder\QueryBuilder|void|array|boolean
     */
    public function firstOrFail($columns = null)
    {
        if($columns) {
            $this->only($columns);
        }

        $this->limit(1);

        if($result = $this->queryResults(true)) {
            return $result;
        }

        exit();
    }

    /**
     * Get the first requested record and return in array(s)
     * @param null|string|array $columns
     * @return \MinasORM\Builder\QueryBuilder|void|array|boolean
     */
    public function get($columns = null)
    {
        if($columns) {
            $this->only($columns);
        }

        return $this->queryResults();

    }
    
    public function __call($method, $arguments)
    {
        throw new Exception(
                "Method [{$method}] does not exist on the builder instance.",
            );
    }

    /**
     * @param $property
     * @return string|null
     */
    public function __get($property): ?String
    {
        throw new Exception(
                "Property [{$property}] does not exist on the builder instance."
            );
    }

    public function latest(?String $column = null)
    {

        return $this->orderBy($column ?? $this->primary, 'DESC');
    }
}