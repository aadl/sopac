<?php print $required_hidden_fields ?>
<table cellspacing="0" class="sticky-enabled sticky-table" id="patroninfo">
  <thead class="tableHeader-processed">
    <tr>
    <?php if ($freezes_enabled) { ?>
      <th>Delete</th>
    <?php } ?>
      <th>Title</th>
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
    <?php if ($freezes_enabled) { ?>
      <td><?php print $hold['cancel'] ?></td>
    <?php } ?>
      <td><?php print $hold['title_link'] ?></td>
      <td><?php print $hold['status'] ?> in queue</td>
      <td><?php print $hold['pickup'] ?></td>
    <?php if ($freezes_enabled) { ?>
      <td><?php print $hold['freeze'] ?></td>
    <?php } ?>
    </tr>
<?php
  }
?>
    <tr class="profile_button <?php print $zebra ?>">
    <?php if ($freezes_enabled) { ?>
      <td colspan="5">
    <?php } else { ?>
      <td colspan="3">
    <?php } ?>
        <?php print $submit ?>
      </td>
    </tr>
  </tbody>
</table>
