<?php

namespace db;

$DB = new \PDO("sqlite:./.data.db");
$DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

function latest_id()
{
    global $DB;
    return $DB->lastInsertId();
}

function run($sql, $params = null)
{
    global $DB;
    $q = $DB->prepare($sql);
    $q->execute($params);
    return $q;
}

function prepare($sql)
{
    global $DB;
    return $DB->prepare($sql);
}

function gen_select($table, $fields, $where = [], $order = null, $limit = null)
{   # generate a SELECT query
    if (is_array($fields))
        $fields = join(', ', $fields);
    if (is_array($where))
        $where = join(' AND ', $where);
    if (!is_null($order))
        $where .= ' ORDER BY '. $order;
    if (is_numeric($limit))
        $where .= ' LIMIT '. $limit;
    return 'SELECT '. $fields .' FROM '. $table .' WHERE '. $where;
}

function inserter($table, $fields, $values)
{
    global $DB;
    $sql = "INSERT INTO $table ($fields) VALUES ($values)";
    $q = $DB->prepare($sql);
    return function ($params) use ($q, $DB) {
        $q->execute($params);
        return $DB->lastInsertId();
    };
}

function updater($table, $values, $where)
{
    global $DB;
    $sql = "UPDATE $table SET $values WHERE $where LIMIT 1";
    $q = $DB->prepare($sql);
    return function ($params) use ($q) {
        $q->execute($params);
        return $q;
    };
}
