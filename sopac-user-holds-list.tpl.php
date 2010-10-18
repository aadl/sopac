<?php print $required_hidden_fields ?>
<table cellspacing="0" class="sticky-enabled sticky-table" id="patroninfo">
  <thead class="tableHeader-processed">
    <tr>
      <th>Cancel</th>
      <th>Title</th>
      <th>Format</th>
      <th>Author</th>
      <th>Status</th>
      <th>Pickup Location</th>
    <?php if ($freezes_enabled) { ?>
      <th>Freeze</th>
    <?php } ?>
    </tr>
  </thead>
  <tbody>
<?php
  $zebra = 'even';
  foreach ($holds as $hold) {
    $zebra = $zebra == 'odd' ? 'even' : 'odd';
?>
    <tr class="<?php print $zebra ?>">
      <td><?php print $hold['cancel'] ?></td>
      <td><?php print $hold['title_link'] ?></td>
      <td><?php print $hold['format'] ?></td>
      <td><?php print $hold['author'] ?></td>
      <td class="<?php print $hold['ready'] ?>"><?php print $hold['status'] ?></td>
      <td><?php print $hold['pickup'] ?></td>
    <?php if ($freezes_enabled) { ?>
      <td><?php print $hold['freeze'] ?></td>
    <?php } ?>
    </tr>
<?php
  }
  if ($see_all) {
?>
  <tr class="odd">
    <?php if ($freezes_enabled) { ?>
      <td colspan="7" align="right">
    <?php } else { ?>
      <td colspan="6" align="right">
    <?php } ?>
    <?php print $see_all['#value']; ?>
      </td>
    </tr>
<?php
  }
?>
    <tr class="profile_button <?php print $zebra; ?>">
    <?php if ($freezes_enabled) { ?>
      <td colspan="7">
    <?php } else { ?>
      <td colspan="6">
    <?php } ?>
        <?php print $submit; ?>
      </td>
    </tr>
  </tbody>
</table>
<p><?php print $lockers['#value']; ?></p>