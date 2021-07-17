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

    /** @var null|array $fillables */
    protected $fillables = null;

    /** @var null|array $attributes */
    protected $attributes = null;

    /** @var array $orders */
    private array $orders = [];

    /** @var null|int $limit */
    protected $limit;

    /** @var null|int $offset */
    protected $offset;

    /** @var string|null $model */
    protected $model = null;

    /** @var array|object|null $data */
    protected $data = null;

    /** @var array|object|null $data */
    protected $originalData = null;

    /** @var array|null $data */
    protected $inserts = null;

    /** @var array|null $data */
    protected $updating = null;

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
     * 
     * @param mixed $id
     * @param string|array|null $columns = null
     * 
     * @return \MinasORM\Builder\QueryBuilder
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
     * 
     * @param string $column
     * @param string $order = 'asc'
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function orderBy(String $column, String $order = 'asc')
    {
        $order = Str::lower($order);

        if(!in_array($order, ['asc', 'desc'], true)) {
            return LogErrors::storeLog('Order direction must be "asc" or "desc".');
        }

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
     * 
     * @param \Closure|string|array $columns
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param mixed $condition = null
     * 
     * @return \MinasORM\Builder\QueryBuilder
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
     * 
     * @param array $arrayWheres
     * @param string $continuousOperator
     * @param string $method = 'where'
     * 
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
     * 
     * @param Closure $closure
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function whereCallback(Closure $closure)
    {
        call_user_func($closure, $this);

        return $this;
    }

    /**
     * Prepare the value and operator for a where clause
     * 
     * @param string $value
     * @param string $operator
     * @param bool $useDefault = false
     * 
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
     * 
     * @param string $operator
     * @param mixed $value
     * 
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
               !in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Returns a new instance of QueryBuilder with
     * the same configuration data (table and primary)
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function newQueryWithSetData()
    {
        return (new QueryBuilder)->setData($this->table, $this->primary, $this->model);
    }

    /**
     * Alias of the "where" clause, with continuous operator OR
     * 
     * @param \Closure|string|array $columns
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param mixed $condition = null
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function orWhere($columns, $operator = null, $value = null)
    {
        $this->where($columns, $operator, $value, 'OR');

        return $this;
    }

    /**
     * Sets the columns to be retrieved from the database
     * 
     * @param mixed $columns
     * 
     * @return void
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
     * 
     * @param mixed $operator
     * @param mixed $value
     * @param mixed $condition
     * @param string|null $continuousOperator = 'AND'
     * 
     * @return void
     */
    protected function addWhere($column, $operator, $value, $continuousOperator = 'AND')
    {
        $this->wheres[] = [$column, $operator, $value, $continuousOperator];
    }

    /**
     * Prepare the where array for the next database call
     * 
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
     * 
     * @return string
     */
    protected function prepareOrdersForQuery()
    {
        $orderString = '';

        foreach($this->orders as $key => $order) {
            if(empty($orderString)) {
                $orderString .= " ORDER BY ";
            }

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
     * 
     * @param int $limit
     * 
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
     * 
     * @return \MinasORM\Builder\QueryBuilder|static
     */
    public function take(Int $limit)
    {
        return $this->limit($limit);
    }
    
    /**
     * Execute PDO Query for insert clause
     * 
     * @param string $clause = 'where'
     * 
     * @return \PDO|null|\MinasORM\Builder\QueryBuilder
     */
    protected function executeClause(String $clause = 'where')
    {
        if($clause == 'where' || $clause == 'delete') {
            $this->prepareWhereForQuery();
            
            $query = $this->getFormatedQuery($clause);
        }

        if($clause == 'insert') {
            $query = $this->getStoreQuery();
        }

        if($clause == 'update') {
            $query = $this->getUpdateQuery();
        }

        if(!isset($query)) {
            return LogErrors::storeLog("Type of action not found, the query was not found. [{$clause}]");
        }

        try {
            $stmt = Connect::getInstance()
                ->prepare($query);
        } catch (Exception $e) {
            return LogErrors::storeLog(sprintf(
                    "Error: %s, PDO CODE: %s", $e->getMessage(), $e->getCode()
                ), true);
        }

        $this->bindValues($stmt, $clause);

        try {
            $stmt->execute();
        } catch(PDOException $exception) {
            return LogErrors::storeLog($exception->getMessage(), true);
        }

        return $stmt;
    }

    /**
     * Return a first record or call the callback
     * 
     * @param mixed $columns
     * @param \Closure|null $callback
     * 
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
     * 
     * @param object|null $preparedQuery
     * 
     * @return void
     */
    protected function bindValues($preparedQuery, $action = 'where')
    {
        if($action == 'where' || $action == 'delete') {
            foreach($this->wheres as $index => $where) {
                $preparedQuery->bindParam($index + 1, $where[2], Helpers::getDataType($where[2]));
            }
        } elseif($action === 'insert') {
            foreach($this->inserts as $index => $insert) {
                $preparedQuery->bindValue(":{$index}", $insert);
            }
        } elseif($action === 'update') {
            foreach($this->updating as $index => $updating) {
                $preparedQuery->bindValue(":{$index}", $updating);
            }
        }
    }

    /**
     * Returns the number of records consulted
     * 
     * @return \MinasORM\Builder\QueryBuilder|bool|null
     */
    public function count()
    {
        return $this->queryResults(false, true);
    }

    /**
     * Return formated query as string.
     * 
     * @param string $type
     * 
     * @return string
     */
    public function getFormatedQuery(String $type = 'where')
    {
        if($type === 'where') {
            $columns = implode(', ', $this->columns);
    
            $limit = $this->limit > 0 ? 'LIMIT ' . $this->limit : '';
    
            $offset = $this->offset > 0 ? 'OFFSET ' . $this->offset : '';

            $orders = $this->prepareOrdersForQuery();

            return "SELECT {$columns} FROM {$this->table} {$this->whereString} {$orders} {$limit} {$offset}";
        } elseif ($type === 'delete') {
            return "DELETE FROM {$this->table} {$this->whereString}";
        }
    }

    /**
     * Set tableName and primaryKey of called class
     * 
     * @param string $table
     * @param string $primary
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function setData(String $table, String $primary, String $model)
    {
        $this->table = $table;
        $this->primary = $primary;
        $this->model = $model;

        return $this;
    }

    /**
     * Method responsible for telling which columns
     * in the model table a value can be inserted
     * 
     * @param null|array $fillables
     * 
     * @return void
     */
    public function setFillables(?Array $fillables)
    {
        if(!$fillables) {
            return;
        }

        $this->fillables = $fillables;
    }

    /**
     * Method responsible for setting default
     * values of the columns to be inserted.
     * 
     * @param null|array $attributes
     * 
     * @return void
     */
    public function setAttributes(?Array $attributes)
    {
        if(!$attributes) {
            return;
        }

        $this->attributes = $attributes;
    }

    /**
     * Return query results
     * 
     * @param bool $returnInstance
     * @param bool $returnCount
     * 
     * @return \PDO|void|array|boolean
     */
    protected function queryResults($returnInstance = false, $returnCount = false, $type = 'where')
    {
        $execQuery = $this->executeClause($type);

        if(!$execQuery) return;

        if($returnCount) {
            return $execQuery->rowCount();
        }

        if($execQuery->rowCount() > 1) {
            $this->data = $returnInstance 
                          ? $execQuery->fetchAll(PDO::FETCH_CLASS, $this->model) 
                          : $execQuery->fetchAll(PDO::FETCH_ASSOC);

            $this->setOriginalData();

            return $this->data;
        }

        $this->data = $returnInstance 
                        ? $execQuery->fetchObject($this->model) 
                        : $execQuery->fetch(PDO::FETCH_ASSOC);

        $this->setOriginalData();

        return $this->data;
    }

    /**
     * Clone the query result
     * 
     * @return void
     */
    public function setOriginalData()
    {
        if(!is_object($this->data)) return;
        
        $this->originalData = clone $this->data;
    }

    /**
     * Get the first requested record and return this instance
     * 
     * @param null|string|array $columns
     * 
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
     * 
     * @param int $skip
     * 
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
     * 
     * @param int $skip
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function skip(Int $skip)
    {
        return $this->offset($skip);
    }

    /**
     * Get the first requested record, but when it doesn't find the record, 
     * it kills the process (with 404 error).
     * 
     * @param null|string|array $columns
     * 
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
     * 
     * @param null|string|array $columns
     * 
     * @return \MinasORM\Builder\QueryBuilder|void|array|boolean
     */
    public function get($columns = null)
    {
        if($columns) {
            $this->only($columns);
        }

        return $this->queryResults();
    }
    
    /**
     * This method is invoked when a method 
     * called does not exist in the class
     * 
     * @param mixed $method
     * @param mixed|array $arguments
     * 
     * @return Exception
     */
    public function __call($method, $arguments)
    {
        throw new Exception(
                "Method [{$method}] does not exist on the builder instance.",
            );
    }

    /**
     * This method is invoked when a property 
     * called does not exist in the class
     * 
     * @param $property
     * @return string|null
     */
    public function __get($property): ?String
    {
        throw new Exception(
                "Property [{$property}] does not exist on the builder instance."
            );
    }

    /**
     * Set order by as decrescent
     * 
     * @param null|string $column = null
     * 
     * @return \MinasORM\Builder\QueryBuilder
     */
    public function latest(?String $column = null)
    {
        return $this->orderBy($column ?? $this->primary, 'DESC');
    }

    /**
     * Destroy a record in the table
     * 
     * @param mixed $record = null
     * 
     * @return bool|null
     */
    public function destroy($record = null)
    {
        if(!$record && !$this->data) {
            return LogErrors::storeLog("A primary key value was not found to be deleted.");
        }

        if(!$record) {
            $primary = $this->resolveData();
        }

        if(!$record && !$primary) {
            return LogErrors::storeLog("A primary key value was not found to be deleted. [Step 2]");
        }

        $delete = $this->newQueryWithSetData()
                ->where($this->primary, $record ? $record : $primary);

        return !!$delete->queryResults(false, true, 'delete');
    }

    /**
     * Resolve the model data until
     * find the primary index value
     * 
     * @return string|int|null
     */
    protected function resolveData()
    {
        if (is_object($this->data) && isset($this->data->{$this->primary})) {
            return $this->data->{$this->primary};
        }

        if (is_array($this->data) && isset($this->data[$this->primary])) {
            return $this->data[$this->primary];
        }

        return null;
    }

    /**
     * Prepare and execute insert clause
     * 
     * @param array $data
     * 
     * @return null|void|\MinasORM\Builder\QueryBuilder
     */
    public function prepareStore(Array $data)
    {
        if(!is_array($data)) {
            return LogErrors::storeLog("The data for insertion needs to be passed in the form of an array.");
        }

        if(!$this->fillables) {
            return LogErrors::storeLog("Insert the [fillable] property in the model class, citing the columns to be filled.");
        }

        $this->resolveColumnsToAttach($data);
        
        if(!$this->inserts) {
            return LogErrors::storeLog("There was an error preparing your data for insertion");
        }

        $inserted = $this->queryResults(false, true, 'insert');

        if(!$inserted) return;

        return $this->latest()->first();
    }

    /**
     * Resolve the query string for insert clause
     * 
     * @return array
     */
    public function getBindedQueryInsert()
    {
        $columns = [
            1 => implode(', ', array_keys($this->inserts)),
            array_keys($this->inserts)
        ];

        $countColumns = count($columns[2]);
        $bindedColumns = '';

        for($i = 0; $i < $countColumns; $i++) {
            $bindedColumns .= ":{$columns[2][$i]}, ";
        }

        $clearedColumns = '(' . Str::clearEnd(', ', $columns[1]) . ')';

        $bindedColumns = '(' . Str::clearEnd(', ', $bindedColumns) . ')';

        return [
            $clearedColumns,
            $bindedColumns
        ];
    }

    /**
     * Return the query string for insert clause
     * 
     * @return string
     */
    protected function getStoreQuery()
    {
        [$clearedColumns, $bindedColumns] = $this->getBindedQueryInsert();

        return "INSERT INTO {$this->table} {$clearedColumns} VALUES {$bindedColumns}";
    }

    /**
     * It resolves the columns that must be inserted in the database
     * and removes those that are not in the fillable property
     * 
     * @param array $data
     * 
     * @return void
     */
    protected function resolveColumnsToAttach(Array $data)
    {
        foreach($data as $key => $value) {
            if(!in_array($key, $this->fillables, true)) {
                unset($data[$key]);
            }
        }

        if(!$this->attributes) {
            $this->inserts = $data;
            return;
        }

        foreach($this->attributes as $key => $value) {
            $data[$key] = $value;
        }

        $this->inserts = $data;
    }

    /**
     * Save a model with new settings
     * 
     * @return int|null|void
     */
    public function save()
    {
        if(!$this->data || !$this->data->{$this->primary}) {
            return LogErrors::storeLog("There is no associated model for update or the primary index was not found in the data.");
        }

        $this->prepareUpdate();

        if(empty($this->updating)) return;

        $updating = $this->queryResults(false, true, 'update');

        return $updating;
        
    }

    /**
     * Return query update
     * 
     * @return string
     */
    public function getUpdateQuery()
    {
        if(empty($this->updating)) return;

        $query = $this->getBindedQueryUpdate();

        $primary = $this->originalData->{$this->primary};

        return "UPDATE {$this->table} SET {$query} WHERE {$this->primary} = {$primary}";
    }

    /**
     * Get the part of query update "SET" already
     * 
     * @return string
     */
    public function getBindedQueryUpdate()
    {
        $columns = array_keys($this->updating);
        $bindedColumns = '';

        $countColumns = count($columns);

        for($i = 0; $i < $countColumns; $i++) {
            if($columns[$i] === $this->primary) continue;

            $bindedColumns .= "{$columns[$i]} = :{$columns[$i]}, ";
        }

        $bindedColumns = Str::clearEnd(', ', $bindedColumns);

        return $bindedColumns;
    }

    /**
     * Prepare updates data from query
     * 
     * @return void
     */
    public function prepareUpdate()
    {
        $dataToArray = Arr::toArray($this->data);
        $originalDataToArray = Arr::toArray($this->originalData);

        $dataChanged = array_diff_assoc($dataToArray, $originalDataToArray);

        if(empty($dataChanged)) return;

        $this->updating = $dataChanged;
    }

    public function original($originalData)
    {
        if(is_array($originalData)) {
            $originalData = $originalData[0];
        }

        if(empty($this->originalData)) return;

        if(is_object($this->originalData) && isset($this->originalData->{$originalData})) {
            return $this->originalData->{$originalData};
        }

        if(is_array($this->originalData) && isset($this->originalData[$originalData])) {
            return $this->originalData[$originalData];
        }

        return null;
    }
}
