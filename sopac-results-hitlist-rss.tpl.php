<?php
/*
 * Theme template for SOPAC RSS hitlist entry
 *
 */

// Prep some stuff here

$new_author_str = sopac_author_format($locum_result['author'], $locum_result['addl_author']);
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
if (module_exists('covercache')) {
  $cover_img_url = covercache_image_url($locum_result['bnum']);
}
if (!$cover_img_url) {
  $cover_img_url = '/' . drupal_get_path('module', 'sopac') . '/images/nocover.png';
}
?>

<entry>
  <title><?php print $locum_result['title'];?></title>
  <id>http://<?php print $_SERVER['SERVER_NAME'] . '/'. $url_prefix . '/record/' . $locum_result['bnum'] ?></id>
  <link rel="alternate" href="http://<?php print $_SERVER['SERVER_NAME'] . $url_prefix . '/record/' . $locum_result['bnum'] ?>"/>
  <author>
    <name><?php print $new_author_str; ?></name>
    <uri>http://<?php print $_SERVER['SERVER_NAME'] . $url_prefix . '/search/author/' . urlencode($new_author_str) ?></uri>
  </author>
  <updated><?php print date('Y-m-d', $locum_result['bib_lastupdate']) . 'T00:00:00-05:00'?></updated>
  <content type="xhtml">
  <div xmlns="http://www.w3.org/1999/xhtml">
    <ul>

    <li><img class="hitlist-cover" src="<?php print $cover_img_url ?>" /></li>
    <li id="publisher">Publisher: New York : Harper, c2010.</li>
    <li id="added">Added on 2010-01-27</li>
    <li>Call number: <strong>324.973 He</strong></li>      <li>
      No copies available. 152 requests on 26 items.      </li>
      <li id="item-request">» <a href="/myaccount?destination=catalog/record/1350470">Please log in to request this item</a></li>    </ul>

            <h4>Reviews and Summaries</h4>
      <ul>
    <li><a href="http://www.syndetics.com/index.aspx?isbn=9780061733635/SUMMARY.html&client=anarp" target="_new">Summary / Annotation</a></li></ul>  </div>
  </content>
</entry>
