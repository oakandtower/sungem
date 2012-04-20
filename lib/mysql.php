<?php
namespace mysql;

function init() {
	if(!isset($GLOBALS['mysql'])) {
		extract(getConfig('db_mysql'));
		$GLOBALS['mysql'] =  new \PDO($dsn, $username, $password, array(
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
		));
	}

	return $GLOBALS['mysql'];
}

function fetch($query, $params = array()) {
	$st = query($query, $params);
	return $st->fetch();
}

function fetchAll($query, $params = array()) {
	$st = query($query, $params);
	return $st->fetchAll();
}

function insert($query, $items) {
	$st = prepare($query);
	$count = 0;
	foreach($items as $item) {
		$st->execute($item);
		$count += $st->rowCount();
	}
	return $count;
}

function update($query, $params) {
	$st = query($query, $params);
	return $st->rowCount();
}

function delete($query, $params) {
	$st = query($query, $params);
	return $st->rowCount();
}

function prepare($query) {
	$db = init();
	return $db->prepare($query);
}

function query($query, $params = array()) {
	$st = prepare($query);
	$st->execute($params);
	return $st;
}