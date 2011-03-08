<?php
if ($request_result_msg) {
  print '<div class="req_result_msg">' . $request_result_msg . '</div>';
}

if ($request_error_msg) {
  print '<div class="req_error_msg">' . $request_error_msg . '</div>';
}

if ($item_form) {
  print $item_form;
}

else {
  if ($_GET['lightbox']) {
?>
<ul>
<li class="button green"><a href="#" onclick="parent.document.location=('<?php print url('user') ?>')">Go to My Account</a></li>
<li class="button red"><a href="#" onclick="parent.Lightbox.end('forceClose')">Close this window</a></li>
</ul>
<?php
  }
  else {
    print '<ul>';
    print '<li class="button green">' . l('Go to My Account', 'user') . '</li>';
    print '<li class="button green">' . l('Return to catalog record', variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum) . '</li>';
    print '</ul>';
  }
}
?>