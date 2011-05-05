<?php
// $Id: sopac-patroninfo-profile.tpl.php $
?>
<div class="patroninfo-profile-wrapper"><div class="patroninfo-profile">

<?php if ($title) : ?>
  <h3><?php print $title; ?></h3>
<?php endif; ?>

<dl<?php print $attributes; ?>>
  <?php print $profile_items; ?>
</dl>

</div></div>
