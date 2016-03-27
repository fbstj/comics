<?php
namespace db;
#interface to data backing

$DB = new \PDO("sqlite:./.data.db");
$DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

function prepare($sql)
{
    global $DB;
    if (defined('DEBUG_QUERY'))
        var_dump($sql);
    return $DB->prepare($sql);
}

function gen_where($where = [], $order = null, $limit = null)
{
    if (is_array($where))
        $where = join(' AND ', $where);
    if (strlen(trim($where)) > 0)
        $where = ' WHERE '. $where;
    if (!is_null($order))
        $where .= ' ORDER BY '. $order;
    if (is_numeric($limit))
        $where .= ' LIMIT '. $limit;
    return $where;
}

include_once '.db.field.php';
include_once '.db.table.php';
