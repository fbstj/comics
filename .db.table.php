<?php
namespace db;
# an sql field

class Table
{
    function __construct($name, $fields = [])
    {
        $this->name = $name;
        $this->fields = $fields;
        foreach ($fields as $f)
        {
            if (is_string($f))
                continue;
            $this->{$f->name} = $f;
        }
    }
    
    function subset($fields)
    {
        return new Table($this->name, $fields);
    }
    
    function filter($where = [], $order = null, $limit = null)
    {
        $fields = [];
        foreach ($this->fields as $f)
        {
            if (is_string($f))
                $fields[] = $f;
            else if ($f instanceof Field)
                $fields[] = $f->select();
        }
        if (is_array($fields))
            $fields = join(', ', $fields);
        $where = gen_where($where, $order, $limit);
        $q = "SELECT $fields FROM {$this->name} $where";
        return prepare($q);
    }
    
    function inserter()
    {
        $fields = []; $values = [];
        foreach ($this->fields as $f)
        {
            if (is_string($f))
            {
                $fields[] = $f;
                $values[] = '?';
            }
            else if ($f instanceof Field)
            {
                $fields[] = $f->select();
                $values[] = $f->insert();
            }
        }
        $fields = join(', ', $fields);
        $values = join(', ', $values);
        $q = "INSERT INTO {$this->name} ($fields) VALUES ($values)";
        return prepare($q);
    }
    
    function updater($where = [], $order = null, $limit = null)
    {
        $values = [];
        foreach ($this->fields as $f)
        {
            if (is_string($f))
            {
                $values[] = $f .' = ?';
            }
            else if ($f instanceof Field)
            {
                $values[] = $f->update();
            }
        }
        $values = join(', ', $values);
        $where = gen_where($where, $order, $limit);
        $q = "UPDATE {$this->name} SET $values $where";
        return prepare($q);
    }
}
