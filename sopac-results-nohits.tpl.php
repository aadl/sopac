<?php
/*
 *
 * This template is used when locum returns no hits for a particular search.
 *
 */

// Only show mel link on searches for books
if ($_GET['search_format']) {
  foreach(explode('|', $_GET['search_format']) as $format) {
    if (strpos($locum_config['format_groups']['books'], $format) !== FALSE) {
      $show_mel_link = TRUE;
    }
  }
}
else {
  $show_mel_link = TRUE; // show link if not limited by format
}
?>
<div class="hitlist-nohits">
  <?php if ($locum_result['suggestion']) { ?>
  <div class="hitlist-suggestions">
    Did you mean <i><?php print suggestion_link($locum_result); ?></i> ?
  </div>
  <br />
  <?php } ?>
  » Sorry, your search produced no results.
  <?php if ($show_mel_link) print ' ' . l("Try this search at other Michigan libraries", 'http://elibrary.mel.org'); ?>
</div>
