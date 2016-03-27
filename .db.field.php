<?php
namespace db;
# an sql field

class Field
{
    function __construct($name, $alias = null, $default = null, $nullable = false)
    {
        $this->name = $name;
        if (is_string($alias))
            $this->alias = $alias;
        if (is_string($default))
            $this->default = $default;
        $this->nullable = $nullable;
    }

    function __toString()
    {
        return "`{$this->name}`";
    }

    function select()
    {
        return $this . ($this->alias ? ' AS '. $this->alias : '');
    }

    function cmp($cmp, $value = '?')
    {   # compare op
        return $this .' '. $cmp .' '. $value;
    }

    function equal($value = '?')
    {
        if (is_null($value))
            return $this .' IS NULL';
        return $this->cmp('=', $value);
    }

    function not_equal($value = '?')
    {
        if (is_null($value))
            return $this .' IS NOT NULL';
        return $this->cmp('!=', $value);
    }

    function like($value = '?')
    {
        return $this .' LIKE '. $value;
    }

    function insert()
    {   # return inserter thing (default OR '?)
        if (isset($this->default))
            return $this->default;
        return '?';
    }

    function update($value = '?')
    {   # set value to null to insert default value
        if (isset($this->default))
            return $this->cmp('=', $this->default);
        return $this->cmp('=', $value);
    }
}
