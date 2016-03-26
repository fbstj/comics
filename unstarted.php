<?php
namespace comics;
# comic follower
include_once "comics.php";

if (__FILE__ != get_included_files()[0])
    return;

?>
<title>Unstarted Comics</title>
<style>
    section { width: 50%; margin: auto; border: thin solid; }
    section > :not(h1) { width: 70%; margin: auto; }
    form { display: block; text-align: right; }
    h1 { text-align: center; }
    .add { position: fixed; width: 25%; }
    .edit { position: fixed; right: 0; width: 25%; }
    .add > *, .edit > * { width: 90%; }
</style>

<?php show_nav('unstarted'); ?>

<section>
<h1>Unstarted</h1>
<?php

$q = filter([
    '`current` IS NULL' ,
    '`read` IS NULL'
    ], 'name ASC');

$acts = [
    'read' => 'mark_read',
    ];

show_list($q, $acts);

?>
</section>
