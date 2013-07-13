<?php

/**
 * 
 * Singleton Database basic functions manager - query, prepared statement & execute
 * 
 * @uses PDO dbBasicManger uses PDO module
 *
 * @version 1.0.0
 * @author LSchab lukasz@schab.czest.pl
 * 
 * You may use DbBasicManager under the terms of the MIT License.
 * 
 */

final class DbBasicManager {

    private static $dbBasicManager = false;

    /**
     * @var PDO
     */
    private $pdoDbConnection;
    private $config;

    /**
     * dbBasicManager constructor
     */
    private function __construct() {
  
    }

    /**
     * 
     * @param array $configDb=array(driver,hostname,database,username,password) DB configuration
     * 
     * driver - Basicly prepared for MySQL and MySQLi (but it would be also useful for PostgreSQL driver)
     */
    public static function setConfiguration($configDb = array()) {
	$this->config = $configDb;
    }

    /**
     * dbBasicManager constructor
     *
     * @return DbBasicManager
     */
    public static function getInstance($configDb = array()) {
	if (self::$dbBasicManager === false) {
	    if (!empty($this->config)) {
		self::$dbBasicManager = new DbBasicManager();
	    } else {
		exit("DbBasicManager need DbBasicManager::setConfiguration() to be run first.");
	    }
	}
	return self::$dbBasicManager;
    }

    /**
     * Set new PDO connection 
     * 
     * @return PDO
     */
    private function setPdoDbConnection() {

	try {
	    $this->pdoDbConnection = new PDO($this->config['driver'] . ':host=' . $this->config['hostname'] . ';port=' . isset($this->config['port']) ? (int) $this->config['port'] : 3306 . '; dbname=' . $this->config['database'], $this->config['username'], $this->config['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	} catch (PDOException $e) {
	    error_log('U_ERROR DbBasicManager => Cannot connect database - dbBasicManager');
	    error_log('PHP   Message: ' . $e->getMessage());
	    error_log('PHP   Trace: ' . $e->getTraceAsString());
	    exit();
	}
	return $this->pdoDbConnection;
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getPdoDbConnection() {
	if (get_class($this->pdoDbConnection) === 'PDO') {
	    return $this->pdoDbConnection;
	} else {
	    return $this->setPdoDbConnection();
	}
    }

    /**
     * Executes a prepared statement PDO
     *
     * @param string $sql
     * @param array $dbParams array of :parameterName => $parameterValue
     * @param string $functionName name for logger
     * @param int $functionLine line for logger
     *
     * @return PDOStatement
     */
    public function pdoExecuteSQL($sql, $dbParams = array()) {
	$dbStm = $this->getPdoDbConnection()->prepare($sql);
	try {
	    $dbStm->execute($dbParams);
	} catch (PDOException $e) {
	    if (strpos($e->getMessage(), '2006 MySQL') !== false) {
		$this->setPdoDbConnection();
		$this->pdoExecuteSQL($sql, $dbParams);
	    } else {
		error_log('U_ERROR DbBasicManager => SQL ERROR');
		error_log('PHP   SQL: ' . $sql);
		error_log('PHP   dbParams: ' . serialize($dbParams));
		error_log('PHP   Message: ' . $e->getMessage());
		error_log('PHP   Trace: ' . $e->getTraceAsString());
		exit();
	    }
	}

	return $dbStm;
    }

    /**
     * Prepares a PDO statement for execution
     *
     * @param string $sql
     *
     * @return PDOStatement for fetching result
     */
    public function pdoPrepareSQL($sql) {
	return $this->getPdoDbConnection()->prepare($sql);
    }

    /**
     * Executes a PDO statement for execution
     *
     * @param PDOStatement $dbStm
     * @param array $dbParams
     * @param string $functionName
     * @param string $functionLine
     *
     * @return PDOStatement for fetching result
     */
    public function pdoExecuteStatement($dbStm, $dbParams = array()) {
	try {
	    if (!empty($dbParams)) {
		$dbStm->execute($dbParams);
	    } else {
		$dbStm->execute();
	    }

	    return $dbStm;
	} catch (PDOException $e) {
	    error_log('U_ERROR DbBasicManager => SQL ERROR');
	    error_log('PHP   SQL: ' . $dbStm->queryString);
	    error_log('PHP   dbParams: ' . serialize($dbParams));
	    error_log('PHP   Message: ' . $e->getMessage());
	    error_log('PHP   Trace: ' . $e->getTraceAsString());
	    exit();
	}
    }

}
