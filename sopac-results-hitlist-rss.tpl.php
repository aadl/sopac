<?php
/*
 * Theme template for SOPAC RSS hitlist entry
 *
 */

// Prep some stuff here
$new_author_str = sopac_author_format($locum_result['author'], $locum_result['addl_author']);
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');

if (!module_exists('covercache')) {
  if (strpos($locum_result['cover_img'], 'http://') !== FALSE) {
    $cover_img = $locum_result['cover_img'];
  }
  else {
    $cover_img = base_path() . drupal_get_path('module', 'sopac') . '/images/nocover.png';
  }
  $cover_img = '<img class="hitlist-cover" width="100" src="' . $cover_img . '">';
  $cover_img = l($cover_img,
                 variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['bnum'],
                 array('html' => TRUE));
}
if ($locum_result['type'] == 'bib') { 
?>
<title><?php print $locum_result['title'];?></title>
<updated><?php print $locum_result['bib_lastupdate']; ?>T00:00:00-05:00</updated>
<id><?php print url($url_prefix . '/record/' . $locum_result['bnum'], array('absolute' => TRUE)); ?></id>
<link rel="alternate" type="text/html" hreflang="en" href="<?php print url($url_prefix . '/record/' . $locum_result['bnum'], array('absolute' => TRUE)); ?>"/>
<?php } ?>

    <entry>
      <title><?php print $locum_result['title'];?></title>
      <id><?php print url($url_prefix . '/record/' . $locum_result['bnum'], array('absolute' => TRUE)); ?></id>
      <link rel="alternate" href="<?php print url($url_prefix . '/record/' . $locum_result['bnum'], array('absolute' => TRUE)); ?>"/>
      <updated><?php print date('Y-m-d'); ?>T00:00:00-05:00</updated>
      <published><?php print $locum_result['bib_created']; ?>T00:00:00-05:00</published>
      <author>
        <name><?php print $new_author_str; ?></name>
        <uri>http://<?php print $_SERVER['SERVER_NAME'] . $url_prefix . '/search/author/' . urlencode($new_author_str) ?></uri>
      </author>
      <content type="xhtml" xml:lang="en" xml:base="http://<?php print $_SERVER['SERVER_NAME']; ?>/">
        <div xmlns="http://www.w3.org/1999/xhtml">
        <table>
          <tr>
            <td>
              <p><?php print $cover_img; ?></p>
            </td>
            <td style="padding-left: 30px;">
              <ul>
                <li id="publisher">Publisher: <?php print $locum_result['pub_info'] . ', ' . $locum_result['pub_year']; ?></li>
                <li id="added">Added on <?php print $locum_result['bib_created']; ?></li>
                <?php if ($locum_result['callnum']) { ?><li>Call number: <strong><?php print $locum_result['callnum']; ?></strong></li> <?php } ?>
                <li>
                  <?php
                  print $locum_result['status']['avail'] . t(' of ') . $locum_result['status']['total'] . ' ';
                  print ($locum_result['status']['total'] == 1) ? t('copy available') : t('copies available');
                  ?>
                </li>
                <li id="item-request">» <?php print l(t('Request this item'), $url_prefix . '/record/' . $locum_result['bnum'], array('absolute' => TRUE)); ?></li>
              </ul>
            </td>
          </tr>
        </table>
        </div>
      </content>
    </entry>
