<?php
namespace comics;
# comic follower
include_once "comics.php";

if (__FILE__ != get_included_files()[0])
    return;

$q = Q::$all->queryString ." WHERE latest IS NULL";
$q = \db\prepare($q);

?>
<title>New Comics</title>
<style>
    section { width: 50%; margin: auto; border: thin solid; }
    section > :not(h1) { width: 70%; margin: auto; }
    form { display: block; text-align: right; }
    h1 { text-align: center; }
    .add { position: fixed; width: 25%; }
    .edit { position: fixed; right: 0; width: 25%; }
    .add > *, .edit > * { width: 90%; }
</style>

<nav>
    <a href=comics.php>All</a>
    <a href=comics.update.php?new>Update</a>
</nav>

<section>
<h1>New</h1>
<div>
<?php
$q->execute();
foreach ($q->fetchAll() as $row)
{
    ?><div style='text-align: center;'><?=show($row)?></div><?php
}
?>
</ul>
</section>
