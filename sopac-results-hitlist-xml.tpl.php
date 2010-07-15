<?php
/*
 * Theme template for SOPAC hitlist
 *
 */

// Prep some stuff here
$locum_result['new_author_str'] = sopac_author_format($locum_result[author], $locum_result[addl_author]);
if(empty($locum_result['new_author_str'])) {
  $locum_result['new_author_str'] = "n/a";
}
if ($cover_img_url == "CACHE")
  $locum_result['cover_img_url'] = "http://media.aadl.org/covers/" . $locum_result['bnum'] . "_200.jpg";
else
  $locum_result['cover_img_url'] = url('', array('absolute' => TRUE)) . drupal_get_path('module', 'sopac') . '/images/nocover' . rand(1,4) . '_200.jpg';
$locum_result['holds'] = intval($locum_result['availability']['holds']);
$locum_result['total'] = count($locum_result['availability']['items']);
unset($locum_result['availability']);
?>
<record>
<?php
echo array2xml($locum_result);
?>
</record>
