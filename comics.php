<?php
namespace comics;
# comic follower
include_once ".db.php";
include_once "url.php";

const TABLE = 'Comics';

#define('DEBUG_QUERY',1);

class Table extends \db\Table
{
    function __construct()
    {
        parent::__construct(TABLE,  [
                new \db\Field('name'),
                new \db\Field('site'),
                new \db\Field('feed', null, null, true),
                new \db\Field('stamp', null, 'CURRENT_TIMESTAMP'),
                new \db\Field('first', null, null, true),
                new \db\Field('current', null, null, true),
                new \db\Field('read', null, null, true),
                new \db\Field('latest', null, null, true),
                new \db\Field('resolve', null, null, true),
            ]);

        $where = [ $this->name->equal() ];
        $this->get = $this->filter($where);

        $add_f= [ $this->name, $this->site, $this->stamp ];
        $this->add = $this->subset($add_f)->inserter();

        $this->set_first = $this->subset([ $this->first, $this->stamp ])
                                ->updater($where);
        $set_read = new \db\Field('read', null, 'CURRENT_TIMESTAMP');
        $this->set_current = $this->subset([ $this->current, $set_read, $this->stamp ])
                                ->updater($where);
        $this->set_latest = $this->subset([ $this->latest, $this->stamp ])
                                ->updater($where);
        $this->set_feed = $this->subset([ $this->feed, $this->stamp ])
                                ->updater($where);
    }
}

class Q {
    static $t = null;
}
Q::$t = new Table();

function get($name)
{   # retrieve by name
    Q::$t->get->execute([ $name ]);
    return Q::$t->get->fetch();
}

function add($name, $site)
{   # add comic with name and site
    Q::$t->add->execute([ $name, $site ]);
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
    return set_url(Q::$t->set_first, $name, $href);
}

function set_current($name, $href)
{   # set current
    return set_url(Q::$t->set_current, $name, $href);
}

function set_latest($name, $href)
{   # set latest
    return set_url(Q::$t->set_latest, $name, $href);
}

function mark_as_read($row)
{
    Q::$t->set_current->execute([ $row->latest, $row->name ]);
}

function set_status($row, $status)
{
    Q::$t->set_status->execute([ $status, $row->name ]);
}

function set_feed($name, $href)
{
    Q::$t->set_feed->execute([ $href, $name ]);
    return get($name);
}

function filter($where = [], $order = null, $limit = null)
{   # generate SELECT query
    return Q::$t->filter($where, $order, $limit);
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

function show_list($q, $acts, $q_args = null, $cbs = [])
{
    $q->execute($q_args);
?>
<ul>
<?php
    foreach ($q->fetchAll() as $row)
    {
        ?><div style='text-align: center;'>
        <?=show($row, $acts)?>
<?php
    foreach($cbs as $cb)
    {
        if (!is_callable($cb))
            continue;
        print $cb($row);
    }
?>
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
#    "(status is null or status == 'unread')",
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
