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

function gen_where($where = [], $order = null, $limit = null)
{
    if (is_array($where))
        $where = join(' AND ', $where);
    if (!is_null($order))
        $where .= ' ORDER BY '. $order;
    if (is_numeric($limit))
        $where .= ' LIMIT '. $limit;
    return $where;
}

function gen_select($table, $fields, $where = [], $order = null, $limit = null)
{   # generate a SELECT query
    if (is_array($fields))
        $fields = join(', ', $fields);
    $where = gen_where($where, $order, $limit);
    return 'SELECT '. $fields .' FROM '. $table .' WHERE '. $where;
}

function gen_insert($table, $fields, $values)
{
   if (is_array($fields))
        $fields = join(', ', $fields);
    if (is_array($values))
        $values = join(', ', $values);
     return "INSERT INTO $table ($fields) VALUES ($values)";
}

function gen_update($table, $values, $where, $limit = 1)
{
    $where = gen_where($where, $order, $limit);
    return "UPDATE $table SET $values WHERE $where";
}
