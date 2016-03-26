<?php
namespace comics;
# comic follower
include_once ".db.php";
include_once "url.php";

$tbl = 'Comics';

class Q {
    static $table = null;

    static $get = null;
    static $add = null;

    static $set_first = null;
    static $set_current = null;
    static $set_latest = null;
    static $set_feed = null;
    static $set_status = null;

    static function init($tbl)
    {
        self::$table = $tbl;

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

        $q = "UPDATE $tbl SET latest = ?, $set_stamp WHERE name = ?";
        self::$set_latest = \db\prepare($q);

        $q = "UPDATE $tbl SET feed = ?, $set_stamp WHERE name = ?";
        self::$set_feed = \db\prepare($q);

        $q = "UPDATE $tbl SET status = ?, $set_stamp WHERE name = ?";
        self::$set_status = \db\prepare($q);
    }
}
Q::init('Comics');

function get($name)
{   # retrieve by name
    Q::$get->execute([ $name ]);
    return Q::$get->fetch();
}

function add($name, $site)
{   # add comic with name and site
    Q::$add->execute([ $name, $site ]);
    return get($name);
}

function set_url($query, $name, $href)
{   # set one of the url parameters
    if (is_numeric($href))
        $url = $href;
    else if (is_string($href))
        $url = \urls\add($href);
    else
        throw new Exception('');
    $query->execute([ $url, $name ]);
    return get($name);
}

function set_first($name, $href)
{   # set first
    return set_url(Q::$set_first, $name, $href);
}

function set_current($name, $href)
{   # set current
    return set_url(Q::$set_current, $name, $href);
}

function set_latest($name, $href)
{   # set latest
    return set_url(Q::$set_latest, $name, $href);
}

function mark_as_read($row)
{
    Q::$set_current->execute([ $row->latest, $row->name ]);
}

function set_status($row, $status)
{
    Q::$set_status->execute([ $status, $row->name ]);
}

function set_feed($name, $href)
{
    Q::$set_feed->execute([ $href, $name ]);
    return get($name);
}

function filter($where = [], $order = null, $limit = null)
{   # generate SELECT query
    $q = \db\gen_select(Q::$table, '*', $where, $order, $limit);
    return \db\prepare($q);
}

function edit_url($id, $field)
{   # show a URL edit box the passed field
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

function show_nav($up = '')
{   # show navigation box at top of pages
?>
<nav>
    <a href=comics.php>Unread</a>
    <a href=new.php>New</a>
    <a href=unstarted.php>Unstarted</a>
    <a href=hidden.php>Hidden</a>
    <a href=finished.php>Finished</a>
    <a href=comics.update.php?<?=$up?>>Update</a>
</nav>
<?php
}

function show_list($q, $acts, $q_args = null)
{
    $q->execute($q_args);
?>
<ul>
<?php
    foreach ($q->fetchAll() as $row)
    {
        ?><div style='text-align: center;'>
        <?=show($row, $acts)?>
        </div><?php
    }
?>
</ul>
<?php
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
        \urls\show('comics.php?name='. urlencode($comic->name) .'&'. $act, "[{$text}]");
        print ' ';
    }
?>
</span>

<span style='float: right;'>
<?php

    \urls\show(
        'comics.php?name='.urlencode($comic->name),
        '[edit]'
        );
?>
</span>
<?php
} # END show($comic, $actions)

# END OF functions
if (__FILE__ != get_included_files()[0])
    return;

if (isset($_GET['name']))
{
    $G_NAME = urldecode($_GET['name']);
}

# -- SAVE actions
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
# -- END SAVE actions

if (isset($G_NAME))
{   # mark as read
    $row = get($G_NAME);
    if (isset($_GET['mark_read']))
    {
        mark_as_read($row);
        header('Location: ?');
    }
    if (isset($_GET['mark_hide']))
    {
        set_status($row, 'hid');
        header('Location: ?');
    }
}
?>
<title>Unread Comics</title>
<style>
    section { width: 50%; margin: auto; border: thin solid; }
    section > :not(h1) { width: 70%; margin: auto; }
    form { display: block; text-align: right; }
    h1 { text-align: center; }
    .add { position: fixed; width: 25%; }
    .edit { position: fixed; right: 0; width: 25%; }
    .add > *, .edit > * { width: 90%; }
</style>

<?php show_nav(); ?>

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
{   # show EDIT box
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
}   # isset($row)
?>

<section>
<h1>Unread</h1>
<div>
<?php
$q = filter([
    '`current` != `latest`',
    '`read` IS NOT NULL',
    "(status is null or status == 'unread')",
    ], 'name ASC');
$q->execute();
$unread_acts = [
    'read' => 'mark_read',
    'hide' => 'mark_hide',
    ];
foreach ($q->fetchAll() as $row)
{
    ?><div style='text-align: center;'>
    <?=show($row, $unread_acts)?>
    </div><?php
}
?>
</ul>
</section>
