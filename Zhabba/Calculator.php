<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhabba
 * Date: 21.03.13
 * Time: 23:50
 * To change this template use File | Settings | File Templates.
 */

namespace Zhabba;

use Zend\Soap\Exception\InvalidArgumentException;
use Zend\Math\BigInteger\Exception\DivisionByZeroException;
use Zend\Db\Adapter\Adapter;

/**
 *  Class implements simplest web-service for Calculator
 */
class Calculator {

    private $db;
    private $fail_wrong_args = "Wrong argument number. Must be exactly two.";
    private $fail_zero_div = "Division by zero exception caught.";
    private $table_name = 'history';

    public function __construct() {
        $this->_setDb($this->_getDbInstance());
    }

    /**
     * Add $arg1 to $arg2
     *
     * @param float $arg1
     * @param float $arg2
     * @return float $result
     * @throws InvalidArgumentException
     */
    public function add($arg1, $arg2) {
        $full_operation = explode("::", __METHOD__);
        $operation = $full_operation[1];
        $e = null;
        if (!$this->_checkArgForExistance($arg1) || !$this->_checkArgForExistance($arg2)) {
            $fail = $this->_getFailWrongArgs();
            $e = new InvalidArgumentException($fail);
        }
        if (!$e instanceof InvalidArgumentException) {
            $result = $arg1 + $arg2;
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'result' => $result)
            );
            return $result;
        } else {
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'fault' => $fail)
            );
            throw $e;
        }
    }

    /**
     * Subtract $arg2 from $arg1
     *
     * @param float $arg1
     * @param float $arg2
     * @return float $result
     * @throws InvalidArgumentException
     */
    public function subtract($arg1, $arg2) {
        $full_operation = explode("::", __METHOD__);
        $operation = $full_operation[1];
        $e = null;
        if (!$this->_checkArgForExistance($arg1) || !$this->_checkArgForExistance($arg2)) {
            $fail = $this->_getFailWrongArgs();
            $e = new InvalidArgumentException($fail);
        }
        if (!$e instanceof InvalidArgumentException) {
            $result = $arg1 - $arg2;
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'result' => $result)
            );
            return $result;
        } else {
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'fault' => $fail)
            );
            throw $e;
        }
    }

    /**
     * Multiply $arg1 by $arg2
     *
     * @param float $arg1
     * @param float $arg2
     * @return float $result
     * @throws InvalidArgumentException
     */
    public function multiply($arg1, $arg2) {
        $full_operation = explode("::", __METHOD__);
        $operation = $full_operation[1];
        $e = null;
        if (!$this->_checkArgForExistance($arg1) || !$this->_checkArgForExistance($arg2)) {
            $fail = $this->_getFailWrongArgs();
            $e = new InvalidArgumentException($fail);
        }
        if (!$e instanceof InvalidArgumentException) {
            $result = $arg1 * $arg2;
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'result' => $result)
            );
            return $result;
        } else {
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'fault' => $fail)
            );
            throw $e;
        }
    }

    /**
     * Divide $arg1 by $arg2
     *
     * @param float $arg1
     * @param float $arg2
     * @return float $result
     * @throws DivisionByZeroException
     * @throws InvalidArgumentException
     */
    public function divide($arg1, $arg2) {
        $full_operation = explode("::", __METHOD__);
        $operation = $full_operation[1];
        $e = null;
        if (!$this->_checkArgForExistance($arg1) || !$this->_checkArgForExistance($arg2)) {
            $fail = $this->_getFailWrongArgs();
            $e = new InvalidArgumentException($fail);
        } else if (!$this->_checkArgForZero($arg2)) {
            $fail = $this->_getFailZeroDiv();
            $e = new DivisionByZeroException($fail);
        }
        if ($e instanceof DivisionByZeroException) {
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'fault' => $fail)
            );
            throw $e;
        } else if ($e instanceof InvalidArgumentException) {
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'fault' => $fail)
            );
            throw $e;
        } else {
            $result = $arg1 / $arg2;
            $this->_inHistory(
                'put',
                array(
                    'operation' => $operation,
                    'arg1' => $arg1,
                    'arg2' => $arg2,
                    'result' => $result)
            );
            return $result;
        }
    }

    /**
     * Retrieves all full operations, operands, results list.
     * @return array @result
     */
    public function getHistory() {
        $data = $this->_inHistory('get');
        $result = array();
        while($data->valid()) {
            $result[] = $data->current();
            $data->next();
        }
        return $result;
    }

    /**
     * @param string $action
     * @param mixed $data
     * @returns mixed $result
     */
    private function _inHistory($action, $data = null) {
        $table = $this->_getTableName();
        $db = $this->_getDb();
        $qi = function($name) use ($db) { return $db->platform->quoteIdentifier($name); };
        $fp = function($name) use ($db) { return $db->driver->formatParameterName($name); };
        switch ($action) {
            case 'get':
                $sql = "SELECT * FROM" . $qi($table);
            break;
            case 'put':
                $sql = "INSERT INTO " .  $qi($table)
                    . "(" . implode(',', array_map($qi, array_keys($data))) . ")"
                    . "VALUES (" . implode(',', array_map($fp, array_keys($data))) . ")";
            break;
        }
        $statement = $db->query($sql);
        $result = $statement->execute($data);
        return $result;
    }

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    private function _getDbInstance() {
        $db = new Adapter(array(
            'driver' => 'Pdo_Sqlite',
            'database' => __DIR__ . "/DB/history.db"
        ));
        return $db;
    }

    /**
     * @param $db
     * @return Calculator
     */
    private function  _setDb($db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return mixed
     */
    private function _getDb()
    {
        return $this->db;
    }

    /**
     * @return string
     */
    private function _getFailWrongArgs()
    {
        return $this->fail_wrong_args;
    }

    /**
     * @return string
     */
    private function _getFailZeroDiv()
    {
        return $this->fail_zero_div;
    }

    /**
     * @return string
     */
    private function _getTableName()
    {
        return $this->table_name;
    }

    /**
     * @param $arg
     * @return bool
     */
    private function _checkArgForExistance($arg)
    {
        $e = is_null($arg) ? false : true;
        return $e;
    }

    /**
     * @param $arg
     * @return bool
     */
    private function _checkArgForZero($arg)
    {
        $e = (0 !== (int) $arg) ? true : false;
        return $e;
    }







}