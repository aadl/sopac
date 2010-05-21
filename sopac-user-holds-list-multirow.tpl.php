<?php print $required_hidden_fields ?>
<table cellspacing="0" class="sticky-enabled sticky-table" id="patroninfo">
  <thead class="tableHeader-processed">
    <tr>
      <th>Delete</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
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
      <td colspan="2"><?php print $hold['title_link'] ?></td>
    </tr>
    <tr class="<?php print $zebra ?>">
      <td>&nbsp;</td>
      <td>Availability:</td>
      <td><?php print $hold['status'] ?> in queue</td>
    </tr>
    <tr class="<?php print $zebra ?>">
      <td>&nbsp;</td>
      <td>Pickup at:</td>
      <td><?php print $hold['pickup'] ?></td>
    </tr>
    <tr class="<?php print $zebra ?>">
      <td>&nbsp;</td>
      <td>Status:</td>
      <td><?php print $hold['freeze'] ?></td>
    </tr>
    <tr class="<?php print $zebra ?>">
      <td>&nbsp;</td>
      <td>Suspended:</td>
      <td><?php print $hold['suspend_from'] ?><?php print $hold['suspend_to'] ?></td>
    </tr>
<?php
  }
?>
    <tr class="profile_button <?php print $zebra ?>">
      <td colspan="3"><?php print $submit ?></td>
    </tr>
  </tbody>
</table>
