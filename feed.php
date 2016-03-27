<?php
# simple feed thing

namespace feeds;
require_once '.config.php';
require_once '.db.php';
require_once 'url.php';
require_once 'simplepie_1.3.1.mini.php';

const TABLE = 'Feeds';
const SUB_TABLE = 'FeedItems';

class Table extends \db\Table
{
    function __construct()
    {
        parent::__construct(TABLE,
            [
                new \db\Field('id'),
                new \db\Field('site'),
                new \db\Field('feed', null, null, true),
                new \db\Field('d_add', null, 'CURRENT_TIMESTAMP'),
                new \db\Field('d_try', null, null, true),
                new \db\Field('d_up', null, null, true),
                new \db\Field('latest', null, null, true),
            ]
            );
        $where_id = [ $this->id->equal() ];
        $this->get = $this->filter($where_id);
        $fs = [ $this->site, $this->feed, $this->d_add ];
        $this->add  = $this->subset($fs)->inserter();
        $this->set  = $this->subset([ $this->feed ])->updater($where_id);
        $all_q = [ $this->d_up->not_equal(null) ];
        $this->all = $this->filter($all_q, $this->d_up);
        $this->find = $this->subset([ $this->id ])->filter($where_id);
        
        $up_try = [ new \db\Field('d_try', null, 'CURRENT_TIMESTAMP') ];
        $this->up_try = $this->subset($up_try)->updater($where_id);
        $up_try[] = $this->feed;
        $up_try[] = $this->d_up;
        $up_try[] = $this->latest;
        $this->up_ok = $this->subset($up_try)->updater($where_id);
        
        $where = [ $this->feed->not_equal(null) ];
        $this->try_up = $this->filter($where, 'd_try ASC', 10);
    }
}

class Q { static $t = null; }
Q::$t = new Table();

function get($id)
{
    Q::$t->get->execute([ $id ]);
    return Q::$t->get->fetch();
}
function add($site, $feed = null)
{
    Q::$t->add->execute([ $site, $feed ]);
    return Q::$t->find->execute([ $site ]);
}

function try_rss($id, $fix = false)
{
    # get feed
    $row = get($id);
    $pie = new \SimplePie();
    if (is_null($row->feed))
        $pie->set_feed_url($row->site);
    else
        $pie->set_feed_url($row->feed);
    $pie->init();
    if (!is_null($pie->error))
    {   # update d_try only
        Q::$t->up_try->execute([ $id ]);
        return false;
    }
    $feed = $pie->subscribe_url();
    # add latest to urls
    foreach ($pie->get_items() as $item)
    {
        $url = $item->get_permalink();
        if ($url == '')
            continue;
        break;
    }
    if (!\urls\compare($url, $row->site))
    {    # resolve those that need to be
        $url = \urls\resolve($url);
        $url = \urls\clean($url);
    }
    # add url with time passed
    $up_date = $item->get_gmdate("Y-m-d H:i:s");
    $url = \urls\add($url, $item->get_title(), $up_date);
    # update d_try, feed, d_up, latest,
    Q::$t->up_ok->execute([ $feed, $up_date, $url ,$id]);
    return true;
}

function all()
{
    Q::$t->all->execute();
    return Q::$t->all->fetchAll();
}

if (__FILE__ != get_included_files()[0])
    return;

if (\config\ROUTE == '/add')
{   # show form
    if (\config\has_P('site'))
    {
        $res = [
            \config\P('site'),
            \config\P('feed', true),
            ];
        if (Q::$t->add->execute($res))
            \config\go('all');
    }
?>
<style>
    form { width: 20%; }
    label, input { display: inline-block; }
    label { width: 20%; text-align: right; }
    input { width: 70%; }
    button { margin-left: 80%; }
</style>
<form method=POST>
    <h1>Add feed</h1>
    <label>Site</label>
    <input name=site type=url required pattern="https?://.+">
    <br>
    <label>Feed</label>
    <input name=feed type=url pattern="https?://.+">
    <br>
    <button>Add</button>
</form>
<?php
    return;
}

if (\config\ROUTE == '/update')
{   # update feeds
    if (\config\has_q('id'))
    {
        try_rss(\config\Q('id'));
        \config\go('latest');
    }
    Q::$t->try_up->execute();
    $rows = Q::$t->try_up->fetchAll();
    foreach ($rows as $row)
    {
        try_rss($row->id);
    }
}

if (\config\ROUTE == '/weird_feeds')
{   # update feeds
    $where = [ "feed NOT LIKE site || '%'" ];
    $q = Q::$t->filter($where, 'd_try ASC', 5);
    $q->execute();
    $rows = $q->fetchAll();
    foreach ($rows as $row)
    {
        try_rss($row->id, true);
    }
}

if (\config\ROUTE == '/set')
{
    if (!\config\has_Q('id'))
    {
        \config\go('latest');
    }
    else if (\config\has_P('feed'))
    {
        $id = \config\Q('id');
        $res = [
            \config\P('feed', true),
            $id,
            ];
        $res = Q::$t->set->execute($res);
        \config\go('update', [ 'id' => $id ]);
    }
    $row = get(\config\Q('id'));
?>
<style>
    form { width: 20%; }
    label, input { display: inline-block; }
    label { width: 20%; text-align: right; }
    input { width: 70%; }
    button { margin-left: 80%; }
</style>
<form method=POST>
    <h1>Set feed</h1>
    <label>Site: </label>
    <a href="<?=$row->site?>"><?=$row->site?></a>
    <br>
    <label>Feed</label>
    <input name=feed type=url value="<?=$row->feed?>" pattern="https?://.+">
    <br>
    <button>Set</button>
</form>
<?php
    return;
}

if (\config\ROUTE == '/untried')
{
    $q = [
        Q::$t->d_up->equal(null),
        Q::$t->feed->not_equal(null),
        ];
    $q = Q::$t->filter($q, Q::$t->d_try . ' ASC');
    $q->execute();
    $rows = $q->fetchAll();
}
else if (\config\ROUTE == '/recent')
{
    $q = Q::$t->filter(null, Q::$t->d_try . ' DESC');
    $q->execute();
    $rows = $q->fetchAll();
}
else if (\config\ROUTE == '/broken')
{
    $q = Q::$t->filter([ Q::$t->feed->equal(null) ], Q::$t->d_try . ' ASC');
    $q->execute();
    $rows = $q->fetchAll();
}
else if (\config\ROUTE == '/latest')
{
    $q = Q::$t->filter([ Q::$t->d_up->not_equal(null) ], Q::$t->d_up . ' DESC, '. Q::$t->d_try . ' DESC');
    $q->execute();
    $rows = $q->fetchAll();
}
?>
<title>Feeds</title>

<style>
table { width: 80%; margin: auto; }
</style>

<table border=1>
<tr>
    <th>Latest</th>
    <th>Link</th>
    <th>Added</th>
    <th>Tried</th>
    <th>Updated</th>
</tr>
<?php

foreach ($rows as $row)
{
    $ln_id = [ 'id' => $row->id ];
?>
<tr>
    <td><?=\urls\show($row->latest)?></td>
    <td>
        <a href="<?=$row->site?>">site</a>
        <br>
<?php
    if (!is_null($row->feed))
    {
?>
<a href="<?=$row->feed?>"><img src="https://www.mozilla.org/media/img/trademarks/feed-icon-14x14.2168a573d0d4.png"></a><?php
    }
?>
        <br>
        <?=\config\ln('set', 'set', $ln_id)?>

    </td>
    <td><?=$row->d_add?></td>
    <td>
        <?=$row->d_try?>
        <br>
        <?=\config\ln('update', 'update', $ln_id)?>
    </td>
    <td><?=$row->d_up?></td>
</tr>
<?php
}

die();
