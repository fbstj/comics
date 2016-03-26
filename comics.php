<?php
namespace comics;
# comic follower
include_once ".db.php";
include_once "url.php";

$tbl = 'Comics';

class Q {
    static $get = null;
    static $add = null;

    static $set_first = null;
    static $set_current = null;
    static $set_latest = null;
    static $set_feed = null;

    static $get_unread = null;
    static $get_unstarted = null;
    static $get_current = null;
    static $get_finished = null;

    static $all = null;
    
    static function init($tbl)
    {
        $q = "SELECT * FROM $tbl WHERE name = ?";
        self::$get = \db\prepare($q);
        
        $q = "INSERT INTO $tbl (name, site, stamp) ".
                "VALUES (?, ?, CURRENT_TIMESTAMP)";
        self::$add = \db\prepare($q);

        $set_stamp = 'stamp = CURRENT_TIMESTAMP';

        $q = "UPDATE $tbl SET first = ?, $set_stamp WHERE name = ?";
        self::$set_first = \db\prepare($q);

        $q = "UPDATE $tbl SET current = ?, read = CURRENT_TIMESTAMP, $set_stamp WHERE name = ?";
        self::$set_current = \db\prepare($q);

        $q = "UPDATE $tbl
    SET latest = ?, $set_stamp
    WHERE name = ?
        AND (latest IS NULL OR latest != ?)";
        self::$set_latest = \db\prepare($q);

        $q = "UPDATE $tbl SET feed = ?, $set_stamp WHERE name = ?";
        self::$set_feed = \db\prepare($q);
        
        
        $get_order = "ORDER BY stamp DESC";
        
        $q = "SELECT * FROM $tbl WHERE `current` != `latest` AND `read` IS NOT NULL $get_order";
        self::$get_unread = \db\prepare($q);

        $q = "SELECT * FROM $tbl WHERE `read` IS NULL ORDER BY name ASC";
        self::$get_unstarted = \db\prepare($q);

        $q = "SELECT * FROM $tbl WHERE `current` = `latest` AND `read` IS NOT NULL ORDER BY name ASC";
        self::$get_current = \db\prepare($q);

        $q = "SELECT * FROM $tbl";
        self::$all = \db\prepare($q);
    }
}
Q::init('Comics');


function get($name)
{
    Q::$get->execute([ $name ]);
    return Q::$get->fetch();
}

function add($name, $site)
{   # add comic with name and site
    Q::$add->execute([ $name, $site ]);
    return get($name);
}

function set_first($name, $href)
{   # set first
    $url = \urls\add($href);
    Q::$set_first->execute([ $url, $name ]);
    return get($name);
}

function set_current($name, $href)
{   # set current
    $url = \urls\add($href);
    Q::$set_current->execute([ $url, $name ]);
    return get($name);
}

function set_latest($name, $href)
{   # set latest
    $url = \urls\add($href);
    Q::$set_latest->execute([ $url, $name, $url ]);
    return get($name);
}

function mark_as_read($row)
{
    Q::$set_current->execute([ $row->latest, $row->name ]);
}

function set_feed($name, $href)
{
    Q::$set_feed->execute([ $href, $name]);
    return get($name);
}

# name stamp first (url) current (url+date:read) latest (url)
# first & latest get their date (aka 'added and 'updated) from their urls


if (__FILE__ != get_included_files()[0])
    return;

function edit_url($id, $field)
{
    if (is_numeric($id))
        $url = \urls\get($id);
?>
<form method=POST>
    <label><?=$field?></label>
    <input name='<?=$field?>' required value="<?=$url->href?>">
    <button>Set</button>
</form>
<?php
}

if (isset($_GET['name']))
{
    $G_NAME = urldecode($_GET['name']);
}

if (isset($_POST['name']))
{
    $row = add($_POST['name'], $_POST['href']);
}

if (isset($_POST['first']))
{
    $row = set_first($G_NAME, $_POST['first']);
}

if (isset($_POST['current']))
{
    $row = set_current($G_NAME, $_POST['current']);
}

if (isset($_POST['latest']))
{
    $row = set_latest($G_NAME, $_POST['latest']);
}

if (isset($_POST['feed']))
{
    if ($_POST['feed'] != '') $feed = $_POST['feed'];
    $row = set_feed($G_NAME, $feed);
}

function show($comic, $actions = [])
{
?>

<a href="<?=$comic->site?>"><?=$comic->name?></a>

<span style='float: left;'><?php

// if (!is_null($comic->first))
// {
//     \urls\show(\urls\get($comic->first), '[first]');
//     print ' ';
// }

    if (!is_null($comic->current))
    {
        \urls\show(\urls\get($comic->current), '[current]');
        print ' ';
    }
    
    if ($comic->current != $comic->latest)
    {
        \urls\show(\urls\get($comic->latest), '[latest]');
        print ' ';
    }
    
    foreach ($actions as $text => $act)
    {
        \urls\show('?name='. urlencode($comic->name) .'&'. $act, "[{$text}]");
        print ' ';
    }
?>
</span>

<span style='float: right;'>
<?php

    \urls\show(
        '?name='.urlencode($comic->name),
        '[edit]'
        );
?>
</span>
<?php
} # END show($comic, $actions)

?>
<title>Comics</title>
<style>
    section { width: 50%; margin: auto; border: thin solid; }
    section > :not(h1) { width: 70%; margin: auto; }
    form { display: block; text-align: right; }
    h1 { text-align: center; }
    .add { position: fixed; width: 25%; }
    .edit { position: fixed; right: 0; width: 25%; }
    .add > *, .edit > * { width: 90%; }
</style>

<section class=add>
<h1>Add</h1>
<form method=POST>
    <label>Name</label>
    <input name='name' required><br>
    <label>Site</label>
    <input name='href' type='url' required><br>
    <button>Add</button>
</form>
</section>

<?php
if (isset($G_NAME))
{
    $row = get($G_NAME);
    if (isset($_GET['mark_read']))
    {
        mark_as_read($row);
        header('Location: ?');
    }
?>
<section class=edit>
<h1>Change</h1>
<form style='text-align: center;'>
    <a href='<?=$row->site?>'><?=$row->name?></a>
    <button style='text-align: right;'>Close</button>
</form>
<form method=POST>
    <label>feed</label>
    <input name='feed' value="<?=$row->feed?>">
    <button>Set</button>
</form>
<?=edit_url($row->first, 'first')?>
<?=edit_url($row->current, 'current')?>
<?=edit_url($row->latest, 'latest')?>
</section>
<?php
}
?>

<section>
<h1>Unread</h1>
<div>
<?php
Q::$get_unread->execute();
$unread_acts = [
    'read' => 'mark_read',
    ];
foreach (Q::$get_unread->fetchAll() as $row)
{
    ?><div style='text-align: center;'><?=show($row, $unread_acts)?></div><?php
}
?>
</ul>
</section>

<section>
<h1>Unstarted</h1>
<ul>
<?php
Q::$get_unstarted->execute();
$unstarted_acts = [
    'read' => 'mark_read',
    ];
foreach (Q::$get_unstarted->fetchAll() as $row)
{
    ?><div style='text-align: center;'>
        <?=show($row, $unstarted_acts)?>
    </div><?php
}
?>
</ul>
</section>

<section>
<h1>Caught up</h1>
<ul>
<?php
Q::$get_current->execute();
foreach (Q::$get_current->fetchAll() as $row)
{
    ?><div style='text-align: center;'><?=show($row)?></div><?php
}
?>
</ul>
</section>
