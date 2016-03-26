<?php
namespace urls;
# get Urls from table

include_once ".db.php";

class Q {
    static $get = null;
    static $add = null;
    static $find = null;

    static function init($tbl)
    {
        $get = "SELECT href, title, stamp FROM $tbl WHERE id = ?";
        self::$get = \db\prepare($get);

        $add = "INSERT INTO $tbl (href, title, stamp)".
                "VALUES (?, ?, CURRENT_TIMESTAMP)";
        self::$add = \db\prepare($add);

        $find = "SELECT id FROM $tbl WHERE href LIKE ?";
        self::$find = \db\prepare($find);
    }
}
Q::init('Urls');

function get($id)
{
    Q::$get->execute([ $id ]);
    return Q::$get->fetch();
}

function add($href, $title = null)
{
    Q::$add->execute([ $href, $title ]);
    Q::$find->execute([ $href ]);
    return Q::$find->fetchColumn(0);
}

$find = "SELECT href, title, stamp FROM $tbl WHERE href LIKE ?";
$find = \db\prepare($find);

function show($url, $title = null)
{
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
