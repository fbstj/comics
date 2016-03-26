<?php
# comic updater

namespace comics;

require_once 'comics.php';
require_once 'simplepie_1.3.1.mini.php';

$q = "SELECT CASE WHEN `feed` IS NULL THEN `site` ELSE `feed` END FROM Comics";
if (isset($_GET['new']))
    $q .= " WHERE latest IS NULL";
else if (isset($_GET['resolve']))
    $q .= " WHERE resolve IS NOT NULL";
else if (isset($_GET['unread']))
    $q .= " WHERE latest != current";
# limit to ten rolling things
$q .= " ORDER BY stamp ASC LIMIT 10";
$q = \db\run($q)->fetchAll(\PDO::FETCH_COLUMN, 0);

$pie = new \SimplePie();

$pie->enable_cache(false);

$pie->set_item_limit(1);
if ($_GET['limit'])
    $pie->set_item_limit($_GET['limit']);
    

$pie->set_feed_url($q);

$q = "SELECT name, stamp, latest, resolve FROM Comics WHERE site = ? ";
$q .= "OR ? LIKE '%' || name || '%' OR name LIKE ?";
$q = \db\prepare($q);

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

$pie->init();

foreach ($pie->get_items() as $item)
{
    $feed_url = $item->get_feed()->get_base();
    $feed_title = $item->get_feed()->get_title();
    $feed_match = $feed_title ==  '' or ctype_space($feed_title);
    $feed_match = $feed_match ? "" : "%{$feed_title}%";
    $q->execute([ $feed_url, $feed_title, $feed_match ]);
    $row = $q->fetch();
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

}
