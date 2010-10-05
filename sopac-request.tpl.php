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

$myaccount_link = "https://www.aadl.org/myaccount";
/*
print '<div class="req_return_link"><strong>»</strong> ' .
      l(t('Return to the record display'), variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum) .
      '</div>';

if (sopac_prev_search_url(TRUE)){
  print '<div class="req_return_link"><strong>»</strong> ' .
        l(t('Return to your search'), sopac_prev_search_url(TRUE)) .
        '</div>';
}

print '<br />';
*/
?>
<ul>
<li class="button"><a href="#" onclick="parent.document.location=('<?php print $myaccount_link; ?>')">Go to My Account</a></li>
<li class="button"><a href="#" onclick="parent.Lightbox.end('forceClose')">Close this window</a></li>
</ul>
