<?php

function get_one_value( $collection, array $params, $field) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $curs = $db_server->selectCollection($collection)->find($params);
    $doc = $curs->getNext();
    $client->close();
    return $doc[$field];
}

function get_one_document($collection, array $params) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $curs = $db_server->selectCollection($collection)->find($params);
    $doc = $curs->getNext();
    $client->close();
    return $doc;
}

function get_all_documents($collection, array $params) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $curs = $db_server->selectCollection($collection)->find($params);
    $docs = array();
    foreach ($curs as $doc) {
        $docs[]=$doc;
    }
    $client->close();
    return $docs;
}

function get_sorted_documents($collection, array $params, array $sort) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $curs = $db_server->selectCollection($collection)->find($params);
	$curs->sort($sort);
    $docs = array();
    foreach ($curs as $doc) {
        $docs[]=$doc;
    }
    $client->close();
    return $docs;
}

function update_one_value( $collection, array $params, $field, $value) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $set = array('$set' => array($field=>$value));
    $status = $db_server->selectCollection($collection)->update($params, $set);
    $client->close();
    if ($status['err']) {
	return false;
    }
    return true;
}

function update_one_document( $collection, array $params, array $updates) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $set = array('$set' => $updates);
    $status = $db_server->selectCollection($collection)->update($params, $set);
    $client->close();
    if ($status['err']) {
	return false;
    }
    return true;
}

function insert_one_document($collection, array $params) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $status = $db_server->selectCollection($collection)->insert($params);
    $client->close();
    if ($status['err']) {
        return false;
    }
    return $params['_id'];
}

function delete_one_document($collection, array $params) {
    $client = new MongoClient(DB_HOST);
    $db_server = $client->selectDB(DB_NAME);
    $db_server->authenticate(DB_USER, DB_PASS);
    $col = $db_server->selectCollection($collection);
    $status = $col->remove($params, array('justOne'=> true));
    $client->close();
    if ($status['err']) {
        return false;
    }
    return true;
}

function prepare_datetime($datetime) {
	return new MongoDate($datetime->format('U'));
}

function prepare_timestamp($timestamp) {
	return new MongoDate($timestamp);
}

?>
