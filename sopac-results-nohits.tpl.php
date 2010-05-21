<?php
/*
 *
 * This template is used when locum returns no hits for a particular search.
 *
 */
?>
<div class="hitlist-nohits">
  <?php if ($locum_result['suggestion']) { ?>
  <div class="hitlist-suggestions">
    Did you mean <i><?php print suggestion_link($locum_result); ?></i> ?
  </div>
  <br />
  <?php } ?>
  » Sorry, your search produced no results.
</div>
