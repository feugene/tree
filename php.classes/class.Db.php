<?php

define('_DB_SERVER_', 'localhost');
define('_DB_NAME_', '____');
define('_DB_USER_', '___');
define('_DB_PASSWD_', '____');


class Db
{

	protected $server;
	protected $user;
	protected $password;
	protected $database;
	protected $link;
	protected $result;
	protected static $instance = array();

	protected static $_servers = array(
		array('server' => _DB_SERVER_, 'user' => _DB_USER_, 'password' => _DB_PASSWD_, 'database' => _DB_NAME_)
	);
	
	public static function getInstance()
	{
		if (!isset(self::$instance[0]))
		{
			self::$instance[0] = new Db(
				self::$_servers[0]['server'],
				self::$_servers[0]['user'],
				self::$_servers[0]['password'],
				self::$_servers[0]['database']
			);
		}
		return self::$instance[0];
	}
	
	public function __construct($server, $user, $password, $database, $connect = true)
	{
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;

		if ($connect)
			$this->connect();
	}
	
	
	public function	connect()
	{
		try {
			$this->link = $this->_getPDO($this->server, $this->user, $this->password, $this->database, 5);
		} catch (PDOException $e) {
			throw new Exception(sprintf('Соединение с БД не возможно: %s'), $e->getMessage());
		}
		$this->link->exec('SET NAMES \'utf8\'');
		
		return $this->link;
	}
	
	protected static function _getPDO($host, $user, $password, $dbname, $timeout = 5)
	{
		$dsn = 'mysql:';
		if ($dbname)
			$dsn .= 'dbname='.$dbname.';';
		if (preg_match('/^(.*):([0-9]+)$/', $host, $matches))
			$dsn .= 'host='.$matches[1].';port='.$matches[2];
		elseif (preg_match('#^.*:(/.*)$#', $host, $matches))
			$dsn .= 'unix_socket='.$matches[1];
		else
			$dsn .= 'host='.$host;

		return new PDO($dsn, $user, $password, array(PDO::ATTR_TIMEOUT => $timeout, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
	}
	
	public function __destruct()
	{
		if ($this->link)
			$this->disconnect();
	}
	
	public function	disconnect()
	{
		unset($this->link);
	}

	public function execute($sql)
	{	
		$this->result = $this->query($sql);
		return (bool)$this->result;
	}
	
	public function query($sql)
	{
		$this->result = $this->link->query($sql);
		return $this->result;
	}
	
	public function executeS($sql)
	{		
		$this->result = $this->query($sql);
		if (!$this->result)
			return false;

		$result_array = array();
		while ($row = $this->nextRow($this->result))
			$result_array[] = $row;
		
		return $result_array;
	}
	
	public function nextRow($result = false)
	{
		if (!$result)
			$result = $this->result;
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getRow($sql)
	{
		$sql .= ' LIMIT 1';
		$this->result = false;
		
		$this->result = $this->query($sql);
		if (!$this->result)
			return false;

		$result = $this->nextRow($this->result);
		return $result;
	}
	
	public function	Insert_ID()
	{
		return $this->link->lastInsertId();
	}

}