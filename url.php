<?php
namespace urls;
# get Urls from table

include_once ".db.php";

const TABLE = 'Urls';

class Q {
    static $get = null;
    static $add = null;
    static $find = null;
    static $update = null;
}
$q = \db\gen_select(TABLE, [ 'href', 'title', 'stamp' ], 'id = ?');
Q::$get = \db\prepare($q);

$q = \db\gen_insert(TABLE,
    [ 'href', 'title',             'stamp' ],
    [    '?',     '?', 'CURRENT_TIMESTAMP' ]
    );
Q::$add = \db\prepare($q);

$q = \db\gen_update(TABLE,
    [ 'stamp = ? ', 'title = ?' ],
    'id = ?'
    );
Q::$update = \db\prepare($q);

$q = \db\gen_select(TABLE, 'id', 'href LIKE ?');
Q::$find = \db\prepare($q);

function get($id)
{
    Q::$get->execute([ $id ]);
    return Q::$get->fetch();
}

function add($href, $title = null, $date = null)
{
    Q::$add->execute([ $href, $title ]);
    Q::$find->execute([ $href ]);
    $id = Q::$find->fetchColumn(0);
    if (!is_null($date))
        Q::$update->execute([ $date, $title, $id ]);
    return $id;
}

$find = "SELECT href, title, stamp FROM $tbl WHERE href LIKE ?";
$find = \db\prepare($find);

function show($url, $title = null)
{
    if (is_numeric($url))
        $url = get($url);

    if (is_string($url))
        $href = $url;
    else
        $href = $url->href;
    
    if (!is_null($title))
        $title = $title;
    else if (!is_null($url->title))
        $title = $url->title;
    else
        $tite = $href;
    
    ?><a href="<?=$href?>"><?=$title?></a><?php
}

function resolve($url)
{   # resolve any redirects in $url
	$headers = get_headers($url);
	$headers = array_reverse($headers);
	foreach ($headers as $header) {
		if (strpos($header, 'Location: ') === 0) {
			$url = str_replace('Location: ', '', $header);
			break;
		}
	}
    return $url;
}

function clean($url)
{   # clean up a url
    # remove some parts of the url query
    $q_old = parse_url($url, PHP_URL_QUERY);
    $q_parts = [];
    parse_str($q_old, $q_parts);
    unset(
        $q_parts['utm_source'],
        $q_parts['utm_medium'],
        $q_parts['utm_campaign']
        );
    $q_new = http_build_query($q_parts);
    return str_replace($q_old, $q_new, $url);
}

if (__FILE__ != get_included_files()[0])
    return;

if (isset($_POST['href']))
{
    if (isset($_POST['title']) && $_POST['title'] != '')
        $title = $_POST['title'];
    $id = add($_POST['href'], $title);
    ?><h1>Added</h1><?php
    show(get($id));
}

?>
<form method=POST>
    <h1>Add</h1>
    <label>Link</label>
    <input type='url' required name='href' pattern="https?://.+">
    <br>
    <label>Title</label>
    <input name='title'>
    <br>
    <button>Add</button>
</form>

<form>
    <h1>Find</h1>
    <input type=search name=href required value='<?=$_GET['href']?>'>
    <button>Search</button>
</form>

<?php

?>
<h1>List</h1>
<ul>
<?php
Q::$find->execute([ '%'.$_GET['href'].'%' ]);

foreach (Q::$find->fetchAll() as $url)
{
?>
    <li><?=show(get($url->id))?></li>
<?php
}
?>
</ul>
<?php
