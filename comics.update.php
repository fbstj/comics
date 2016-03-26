<?php
# comic updater

namespace comics;

require_once 'comics.php';
require_once 'simplepie_1.3.1.mini.php';

$q = "SELECT CASE WHEN `feed` IS NULL THEN `site` ELSE `feed` END FROM Comics";
if (isset($_GET['name']))
    $q .= ' WHERE name LIKE "%'.$_GET['name'].'%"';
else if (isset($_GET['new']))
    $q .= " WHERE latest IS NULL";
else if (isset($_GET['resolve']))
    $q .= " WHERE resolve IS NOT NULL";
else if (isset($_GET['unread']))
    $q .= " WHERE latest != current";
else if (isset($_GET['finished']))
    $q .= " WHERE latest = current";
#$q .= " ORDER BY stamp ASC LIMIT 10";
$q = \db\run($q)->fetchAll(\PDO::FETCH_COLUMN, 0);

# limit to ten rolling things
$qs = array_chunk($q, 10);

$pie = new \SimplePie();

#$pie->enable_cache(false);

$pie->set_item_limit(1);
if ($_GET['limit'])
    $pie->set_item_limit($_GET['limit']);

?>
<title>Update Comics</title>

<table border=1>
<tr>
    <th>Comic</th>
    <th>URL</th>
    <th>Updated</th>
    <th>Feed URL</th>
    <th>Feed title</th>
</tr>
<?php

flush();

$q1 = filter('feed = ? OR site = ?');

$q2 = filter(
    "(site LIKE ?) OR (? LIKE '%' || site || '%')".
    " OR (? LIKE '%' || name || '%') OR (name LIKE ?)"
    );

foreach ($qs as $qp)
{
$pie->set_feed_url($qp);

$pie->init();

foreach ($pie->get_items() as $item)
{
    $feed_url = $item->get_feed()->feed_url;
    $feed_title = $item->get_feed()->get_title();
    $q1->execute([ $feed_url, $feed_url ]);
    $row = $q1->fetchAll();
    if (count($row) == 0)
    {
        $feed_match = $feed_title ==  '' or ctype_space($feed_title);
        $feed_match = $feed_match ? "" : "%{$feed_title}%";
        $q2->execute([ $feed_url, $feed_title, $feed_match ]);
        $row = $q2->fetch();
    }
    else $row = $row[0];
    $url = $item->get_permalink();
    if ($url == '')
        continue;
    # resolve those that need to be
    if (!is_null($row->resolve))
        $url = \urls\resolve($url);
    $url = \urls\clean($url);
    # add url with time passed
    $up_date = $item->get_gmdate("Y-m-d H:i:s");
    $url = \urls\add($url, $item->get_title(), $up_date);
    $comic = set_latest($row->name, $url);

?>
<tr>
    <td><?=$comic->name?></td>
    <td><?=$url?></td>
    <td><?=$up_date?></td>
    <td><?=$feed_url?></td>
    <td><?=$feed_title?><br><?=$item->get_title()?></td>
</tr>
<?php

}   # for item in pie
    flush();
}   # for feeds in
