<?php
/*
 * Hitlist Page RSS template
 */

print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>

<feed xmlns="http://www.w3.org/2005/Atom">
<title>Search for <?php print $search_term; ?></title>
  <link rel="self" href="http://<?php print $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?>"/>
  <updated><?php print date('Y-m-d') . 'T00:00:00-05:00'?></updated>
  <author>
    <name><?php print variable_get('site_name', '') ?></name>
    <uri><?php print 'http://' . $_SERVER['SERVER_NAME'] ?></uri>

  </author>
  <id>http://<?php print $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?></id>

<?php print $hitlist_content ?>

</feed>