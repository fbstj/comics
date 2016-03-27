<?php
namespace urls;
# get Urls from table

include_once ".db.php";

const TABLE = 'Urls';

class Table extends \db\Table
{
    function __construct()
    {
        parent::__construct(TABLE, [
            new \db\Field('id'),
            new \db\Field('href'),
            new \db\Field('title', null, null, true),
            new \db\Field('stamp', null, 'CURRENT_TIMESTAMP'),
            ]);
        $q = [ $this->href, $this->title, $this->stamp ];
        $where = [ $this->id->equal() ];
        $this->get = $this->subset($q)->filter($where);
        $this->add = $this->subset($q)->inserter($where);
        $q = [ $this->stamp, $this->title ];
        $this->set = $this->subset($q)->updater($where);
        $this->find = $this->subset([ $this->id ])
                            ->filter([ $this->href->like() ]);
    }
}

class Q {
    static $t = null;
}

Q::$t = new Table();

function get($id)
{
    Q::$t->get->execute([ $id ]);
    return Q::$t->get->fetch();
}

function add($href, $title = null, $date = null)
{
    Q::$t->add->execute([ $href, $title ]);
    Q::$t->find->execute([ $href ]);
    $id = Q::$t->find->fetchColumn(0);
    if (!is_null($date))
        Q::$t->set->execute([ $date, $title, $id ]);
    return $id;
}

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
Q::$t->find->execute([ '%'.$_GET['href'].'%' ]);

foreach (Q::$t->find->fetchAll() as $url)
{
?>
    <li><?=show(get($url->id))?></li>
<?php
}
?>
</ul>
<?php
