<?php
/**
 * SOPAC is The Social OPAC: a Drupal module that serves as a wholly integrated web OPAC for the Drupal CMS
 * This file contains the Drupal include functions for all the SOPAC admin pieces and configuration options
 * This file is called via hook_user
 *
 * @package SOPAC
 * @version 2.1
 * @author John Blyberg
 */

/**
 * This is a sub-function of the hook_user "view" operation.
 */
function sopac_user_view($op, &$edit, &$account, $category = NULL) {
  $locum = sopac_get_locum();
  // SOPAC uses the first 7 characters of the MD5 hash instead of caching the user's password
  // like it used to do.  It's more secure this way, IMHO.
  $account->locum_pass = substr($account->pass, 0, 7);

  // Patron information table (top of the page)
  $patron_details_table = sopac_user_info_table($account, $locum);
  if (variable_get('sopac_summary_enable', 1)) {
    $result['patroninfo']['#title'] = t('Account Summary');
    $result['patroninfo']['#weight'] = 1;
    $result['patroninfo']['#type'] = 'user_profile_category';
    $result['patroninfo']['details']['#value'] = $patron_details_table;
  }

  // Patron checkouts (middle of the page)
  if ($account->valid_card && $account->bcode_verify) {
    $co_table = sopac_user_chkout_table($account, $locum);
    if ($co_table) {
      $result['patronco']['#title'] = t('Checked-out Items');
      $result['patronco']['#weight'] = 2;
      $result['patronco']['#type'] = 'user_profile_category';
      $result['patronco']['details']['#value'] = $co_table;
    }
  }

  // Patron holds (bottom of the page)
  if ($account->valid_card && $account->bcode_verify) {
    $holds_table = drupal_get_form('sopac_user_holds_form');
    if ($holds_table) {
      $result['patronholds']['#title'] = t('Requested Items');
      $result['patronholds']['#weight'] = 3;
      $result['patronholds']['#type'] = 'user_profile_category';
      $result['patronholds']['details']['#value'] = $holds_table;
    }
  }

  // Commit the page content
  $account->content[] = $result;

  // The Summary is not really needed.
  if (variable_get('sopac_history_hide', 1)) {
    unset($account->content['summary']);
  }
  unset($account->content['Preferences']);
}

/**
 * Returns a Drupal themed table of patron information for the "My Account" page.
 *
 * @param object $account Drupal user object for account being viewed
 * @param object $locum Instansiated Locum object
 * @return string Drupal themed table
 */
function sopac_user_info_table(&$account, &$locum) {

  $rows = array();

  // create home branch link if appropriate
  if ($account->profile_pref_home_branch) {
    $home_branch_link = l($account->profile_pref_home_branch, 'user/' . $account->uid . '/edit/Preferences');
  }
  elseif (variable_get('sopac_home_selector_options', FALSE)) {
    $home_branch_link = l(t('Click to select your home branch'), 'user/' . $account->uid . '/edit/Preferences');
  }
  else {
    $home_branch_link = NULL;
  }

  if ($account->profile_pref_cardnum) {
    $cardnum = $account->profile_pref_cardnum;
    $cardnum_link = l($cardnum, 'user/' . $account->uid . '/edit/Preferences');
    $userinfo = $locum->get_patron_info($cardnum);
    $bcode_verify = sopac_bcode_isverified($account);

    if ($bcode_verify) {
      $account->bcode_verify = TRUE;
    }
    else {
      $account->bcode_verify = FALSE;
    }
    if ($userinfo['pnum']) {
      $account->valid_card = TRUE;
    }
    else {
      $account->valid_card = FALSE;
    }

    // Construct the user details table based on what is configured in the admin interface
    if ($account->valid_card && $bcode_verify) {
      if (variable_get('sopac_pname_enable', 1)) {
        $rows[] = array(array('data' => t('Patron Name'), 'class' => 'attr_name'), $userinfo['name']);
      }
      if (variable_get('sopac_lcard_enable', 1)) {
        $rows[] = array(array('data' => t('Library Card Number'), 'class' => 'attr_name'), $cardnum_link);
      }
      // Add row for home branch if appropriate
      if ($home_branch_link) {
        $rows[] = array(array('data' => t('Home Branch'), 'class' => 'attr_name'), $home_branch_link);
      }
      // Checkout history, if it's turned on
      if (variable_get('sopac_checkout_history_enable', 0)) {
        $cohist_enabled = $user->profile_pref_cohist ? 'Enabled' : 'Disabled';
        $last_import = db_result(db_query("SELECT DATESUB(NOW() - last_hist_check) FROM {sopac_last_hist_check} WHERE uid = '" . $user->uid . "'"));
        if ($cohist_enabled = 'Enabled') {
          // TODO Check ILS, enable it if it's not (w/ cache check)
          // Grab + update newest checkouts
        }
        else {
          // TODO Check ILS, disable it is it's not (w/ cache check)
        }
        // Reset cache age
        db_query("UPDATE {sopac_last_hist_check} SET last_hist_check = NOW()");
        $rows[] = array(array('data' => t('Checkout History'), 'class' => 'attr_name'), l($cohist_enabled, 'user/checkout/history'));
      }
      if (variable_get('sopac_numco_enable', 1)) {
        $rows[] = array(array('data' => t('Items Checked Out'), 'class' => 'attr_name'), $userinfo['checkouts']);
      }
      if (variable_get('sopac_fines_display', 1) && variable_get('sopac_fines_enable', 1)) {
        if (variable_get('sopac_fines_warning_amount', 0) > 0 && $userinfo['balance'] > variable_get('sopac_fines_warning_amount', 0)) {
          drupal_set_message('WARNING: Your fine balance of $' .
                             number_format($userinfo['balance'], 2, '.', '') .
                             ' exceeds the limit. You will not be able to request or renew items.');
        }
        $balance = '$' . number_format($userinfo['balance'], 2, '.', '');
        if ($userinfo['balance'] > 0) {
          $balance = l($balance, 'user/fines');
        }
        $rows[] = array(array('data' => t('Fine Balance'), 'class' => 'attr_name'), $balance);
      }
      if (variable_get('sopac_cardexp_enable', 1)) {
        $rows[] = array(array('data' => t('Card Expiration Date'), 'class' => 'attr_name'), date('m-d-Y', $userinfo['expires']));
      }
      if (variable_get('sopac_tel_enable', 1)) {
        $rows[] = array(array('data' => t('Telephone'), 'class' => 'attr_name'), $userinfo['tel1']);
      }
    }
    else {
      $rows[] = array(array('data' => t('Library Card Number'), 'class' => 'attr_name'), $cardnum_link);
    }
  }
  else {
    $cardnum_link = l(t('Click to add your library card'), 'user/' . $account->uid . '/edit/Preferences');
    $rows[] = array(array('data' => t('Library Card Number'), 'class' => 'attr_name'), $cardnum_link);
    // add row for home branch if appropriate
    if ($home_branch_link) {
      $rows[] = array(array('data' => t('Home Branch'), 'class' => 'attr_name'), $home_branch_link);
    }
  }

  if ($account->mail && variable_get('sopac_email_enable', 1)) {
    $rows[] = array(array('data' => t('Email'), 'class' => 'attr_name'), $account->mail);
  }

  // Begin creating the user information display content
  $user_info_disp = theme('table', NULL, $rows, array('id' => 'patroninfo-summary', 'cellspacing' => '0'));

  if ($account->valid_card && !$bcode_verify) {
    $user_info_disp .= '<div class="error">' . variable_get('sopac_uv_cardnum', t('The card number you have provided has not yet been verified by you.  In order to make sure that you are the rightful owner of this library card number, we need to ask you some simple questions.')) . '</div>' . drupal_get_form('sopac_bcode_verify_form', $account->uid, $cardnum);
  }
  elseif ($cardnum && !$account->valid_card) {
    $user_info_disp .= '<div class="error">' . variable_get('sopac_invalid_cardnum', t('It appears that the library card number stored on our website is invalid. If you have received a new card, or feel that this is an error, please click on the card number above to change it to your most recent library card. If you need further help, please contact us.')) . '</div>';
  }

  return $user_info_disp;
}

/**
 * Returns a Drupal-themed table of checked-out items as well as the renewal form functionality.
 *
 * @param object $account Drupal user object for account being viewed
 * @param object $locum Instansiated Locum object
 * @return string Drupal themed table
 */
function sopac_user_chkout_table(&$account, &$locum, $max_disp = NULL) {

  // Process any renew requests that have been submitted
  if ($_POST['sub_type'] == 'Renew Selected') {
    if (count($_POST['inum'])) {
      foreach ($_POST['inum'] as $inum => $varname) {
        $items[$inum] = $varname;
      }
      $renew_status = $locum->renew_items($account->profile_pref_cardnum, $account->locum_pass, $items);
    }
  }
  elseif ($_POST['sub_type'] == 'Renew All') {
    $renew_status = $locum->renew_items($account->profile_pref_cardnum, $account->locum_pass, 'all');
  }

  // Create the check-outs table
  $rows = array();
  if ($account->profile_pref_cardnum) {
    $locum_pass = substr($account->pass, 0, 7);
    $cardnum = $account->profile_pref_cardnum;
    $checkouts = $locum->get_patron_checkouts($cardnum, $locum_pass);

    if (!count($checkouts)) {
      return t('No items checked out.');
    }

    $locum_cfg = $locum->locum_config;
    $header = array('', t('Title'), t('Format'), t('Author'), t('Renews'), t('Due Date'));
    $now = time();
    $ill_bnums = array(1358991, 1356138, 1358990, 1358993, 1358992); // Make config option

    foreach ($checkouts as $co) {
      if ($renew_status[$co['inum']]['error']) {
        $duedate = '<span style="color: red;">' . $renew_status[$co['inum']]['error'] . '</span>';
      }
      else {
        if ($now > $co['duedate']) {
          $duedate = '<span style="color: red;">' . date('m-d-Y', $co['duedate']) . '</span>';
        }
        else {
          $duedate = date('m-d-Y', $co['duedate']);
        }
      }
      $today = strtotime(date('Y-m-d'));
      $moddue = strtotime(date('Y-m-d', $co['duedate']));
      $days = ($moddue - $today) / 86400;
      $dayspan = '';
      if ($days == 0) {
        $dayspan = ' <span style="color: red; font-weight:bold;">(Today)</span>';
      }
      if ($days < 7 && $days > 0) {
        $dayspan = ' <span style="color: red;">(this '.date('l',$co[duedate]).')</span>';
      }

      // Use Author formatting from the catalog
      include_once('sopac_catalog.php');
      $new_author_str = sopac_author_format($co['bib']['author'], $co['bib']['addl_author']);

      // Hover text for the bib
      $hover = $co['title'] . "\n" .
               $new_author_str . "\n" .
               $co['bib']['callnum'] . "\n" .
               $co['bib']['pub_info'] . "\n" .
               $co['bib']['descr'];

      $checkbox = '';
      // CUSTOM ILL DISPLAY
      if (in_array($co['bnum'], $ill_bnums)) {
        // Display call number as the title
        $title = $co['callnum'];
        $author = 'Interlibrary Loan';
      }
      else {
        if ($co['bib']['active']) { // Not suppressed
          $title = l($co['title'], 'catalog/record/' . $co['bnum'], array('attributes' => array('title' => $hover)));
        }
        else {
          $title = $co['title'];
        }
        $author = l($new_author_str, variable_get('sopac_url_prefix', 'cat/seek') . '/search/author/' . urlencode($new_author_str));
        if ($co['avail']['holds'] == 0 &&
            $co['bib']['mat_code'] != 's' &&
            !($co['ill'] == 1 && $co['numrenews'] > 0)) {
          $checkbox = '<input type="checkbox" name="inum[' . $co['inum'] . ']" value="' . $co['varname'] . '">';
        }
      }

      $rows[] = array(
        $checkbox,
        $title,
        $locum_cfg['formats'][$co['bib']['mat_code']],
        $author,
        $co['numrenews'],
        $duedate . $dayspan,
      );
    }
    $submit_buttons = '<input type="submit" name="sub_type" value="' . t('Renew Selected') . '"> <input type="submit" name="sub_type" value="' . t('Renew All') . '">';
    $rows[] = array('data' => array(array('data' => $submit_buttons, 'colspan' => count($rows[0]))), 'class' => 'profile_button' );
  }
  else {
    return FALSE;
  }

  // Wrap it together inside a form
  $content = '<form method="post">' . theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0')) . '</form>';
  return $content;
}

/**
 * Use form API to creat holds table
 *
 * @return string
 */
function sopac_user_holds_form() {
  global $user;
  profile_load_profile(&$user);

  $form = array();
  $cardnum = $user->profile_pref_cardnum;
  $ils_pass = $user->locum_pass;
  $locum = sopac_get_locum();
  $locum_cfg = $locum->locum_config;
  $holds = $locum->get_patron_holds($cardnum, $ils_pass);

  if (!count($holds)) {
    $form['empty'] = array(
      '#type' => 'markup',
      '#value' => t('No items requested.'),
    );
    return $form;
  }

  include_once('sopac_catalog.php');

  $suspend_holds = variable_get('sopac_suspend_holds', FALSE);
  if ($suspend_holds) {
    return _sopac_user_holds_form_multirow($holds);
  }

  $form = array(
    '#theme' => 'form_theme_bridge',
    '#bridge_to_theme' => 'sopac_user_holds_list',
  );

  $sopac_prefix = variable_get('sopac_url_prefix', 'cat/seek') . '/record/';
  $freezes_enabled = variable_get('sopac_hold_freezes_enable', 1);
  $ill_bnums = array(1358991, 1356138, 1358990, 1358993, 1358992); // Make config option

  $form['holds'] = array(
    '#tree' => TRUE,
    '#iterable' => TRUE,
  );
  foreach ($holds as $hold) {
    $bnum = $hold['bnum'];
    $new_author_str = sopac_author_format($hold['bib']['author'], $hold['bib']['addl_author']);

    // CUSTOM ILL DISPLAY
    if (in_array($hold['bnum'], $ill_bnums)) {
      if (preg_match('/canceli([\d]{7})/', $hold['varname'], $matches)) {
        // If item is ready, grab the item record info
        $item_url = 'http://' . $locum_cfg['ils_config']['ils_server'] . '/xrecord=i' . $matches[1];
        $xrecord = simplexml_load_file($item_url);
        foreach ($xrecord->VARFLD as $varfld) {
          if ((string)$varfld->HEADER->TAG == "CALL #") {
            $title = trim((string)$varfld->MARCSUBFLD->SUBFIELDDATA);
            break;
          }
        }
      }
      else {
        // Item isn't in yet, use the bib call number at the title
        $title = $hold['bib']['callnum'];
      }
      $author = 'Interlibrary Loan';
    }
    else {
      if ($hold['bib']['active']) { // Not suppressed
        $title = l($hold['title'], $sopac_prefix . $bnum, array('attributes' => array('title' => $hover)));
      }
      else {
        $title = $hold['title'];
      }
      $author = l($new_author_str, variable_get('sopac_url_prefix', 'cat/seek') . '/search/author/' . urlencode($new_author_str));
    }

    // Hover text for the bib
    $hover = $hold['title'] . "\n" .
             $new_author_str . "\n" .
             $hold['bib']['callnum'] . "\n" .
             $hold['bib']['pub_info'] . "\n" .
             $hold['bib']['descr'];
    $ready = (strpos($hold['status'], 'Ready') !== FALSE || strpos($hold['status'], 'MEL RECEIVED') !== FALSE);

    $hold_to_theme = array();

    $hold_to_theme['cancel'] = array(
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    );
    $hold_to_theme['title_link'] = array(
      '#type' => 'markup',
      '#value' => $title,
    );
    $hold_to_theme['format'] = array(
      '#type' => 'markup',
      '#value' => $locum_cfg['formats'][$hold['bib']['mat_code']],
    );
    $hold_to_theme['author'] = array(
      '#type' => 'markup',
      '#value' => $author,
    );
    $hold_to_theme['ready'] = array(
      '#type' => 'markup',
      '#value' => $ready ? 'request-ready' : 'request-waiting',
    );
    $hold_to_theme['status'] = array(
      '#type' => 'markup',
      '#value' => $hold['status']
    );
    $hold_to_theme['pickup'] = array(
      '#type' => 'markup',
      '#value' => $hold['pickuploc'],
    );
    if ($freezes_enabled) {
      if ($hold['can_freeze']) {
        $hold_to_theme['freeze'] = array(
          '#type' => 'checkbox',
          '#default_value' => $hold['is_frozen'],
        );
      }
      else {
        $hold_to_theme['freeze'] = array(
          '#type' => 'markup',
          '#value' => '&nbsp;'
        );
      }
    }
    $form['holds'][$bnum] = $hold_to_theme;
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => $freezes_enabled ? t('Update Requests') : t('Cancel Selected Requests'),
  );

  return $form;
}

/**
 * Validate request to change holds.
 *
 * @param array $form
 * @param array $form_state
 */
function sopac_user_holds_form_validate(&$form, &$form_state) {
  global $user;
  // Set defaults to avoid errors when debugging.
  $pickup_changes = $suspend_from_changes = $suspend_to_changes = NULL;

  $update_holds = FALSE;
  $cancel_requested = TRUE;
  $cancellations = array();
  $freeze_changes = array();
  $pickup_changes = array();
  $suspend_from_changes = array();
  $suspend_to_changes = array();

  // Get holds.
  $cardnum = $user->profile_pref_cardnum;
  $password = $user->locum_pass;
  $locum = sopac_get_locum();
  $holds = $locum->get_patron_holds($cardnum, $password);
  // Should be how it comes back from locum
  $holds_by_bnum = array();
  foreach ($holds as $hold) {
    $holds_by_bnum[$hold['bnum']] = $hold;
  }

  $submitted_holds = $form_state['values']['holds'];

  $change_pickup = variable_get('sopac_changeable_pickup_location', FALSE);
  $suspend_holds = variable_get('sopac_suspend_holds', FALSE);
  if ($suspend_holds) {
    // Set up time object for use in validating suspension dates
    $locum = sopac_get_locum();
    $sClosedByTimezone = $locum->locum_config['harvest_config']['timezone'];
    $date_object = new DateTime(now, new DateTimeZone($sClosedByTimezone));
  }

  foreach ($submitted_holds as $bnum => $hold_data) {
    if ($hold_data['cancel']) {
      $cancellations[$bnum] = $cancel_requested;
      $update_holds = TRUE;
      continue;
    }
    $freeze_requested = $hold_data['freeze'];
    if ($freeze_requested != $holds_by_bnum[$bnum]['is_frozen']) {
      $freeze_changes[$bnum] = $freeze_requested;
      $update_holds = TRUE;
    }
    if ($change_pickup) {
      $pickup_location = $hold_data['pickup'];
      if ($pickup_location != $holds_by_bnum[$bnum]['pickuploc']['selected']) {
        $pickup_changes[$bnum] = $pickup_location;
        $update_holds = TRUE;
      }
    }
    if ($suspend_holds) {
      $suspend_from = $hold_data['suspend_from'];
      // Catch unchanged default.
      if ($suspend_from == 'mm/dd/yyyy') {
        $suspend_from = '';
      }
      // Make sure it's a date (allow 2-digit years, but ask for 4).
      elseif (!preg_match('/^([1-9]|1[012])\/([1-9]|[12][0-9]|3[01])\/(20[1-9][0-9]|[1-9][0-9])$/', $suspend_from)) {
        form_set_error("holds[$bnum][suspend_from", t('Please enter suspend dates in the form 4/15/1980 (mm/dd/yyyy).'));
      }
      elseif ($suspend_from != $holds_by_bnum[$bnum]['start_suspend']) {
        $suspend_from_changes[$bnum] = $suspend_from;
        $update_holds = TRUE;
      }

      $suspend_to = $hold_data['suspend_to'];
      // Catch unchanged default.
      if ($suspend_to == 'mm/dd/yyyy') {
        $suspend_to = '';
      }
      // Make sure it's a date (allow 2-digit years, but ask for 4).
      elseif (!preg_match('/^([1-9]|1[012])\/([1-9]|[12][0-9]|3[01])\/(20[1-9][0-9]|[1-9][0-9])$/', $suspend_to)) {
        form_set_error("holds[$bnum][suspend_to", t('Please enter suspend dates in the form 4/15/1980 (mm/dd/yyyy).'));
      }
      elseif ($suspend_to != $holds_by_bnum[$bnum]['end_suspend']) {
        $suspend_to_changes[$bnum] = $suspend_to;
        $update_holds = TRUE;
      }
      if ($suspend_to && !$suspend_from) {
        form_set_error("holds][$bnum][suspend_to", t('You cannot set a suspend to date without a corresponding suspend from date.'));
      }
      elseif ($suspend_to && $suspend_from) {
        $date_parts = explode('/', $suspend_from);
        $date_object->setDate($date_parts[2], $date_parts[0], $date_parts[1]);
        $from_date = $date_object->format('Ymd');
        $date_parts = explode('/', $suspend_to);
        $date_object->setDate($date_parts[2], $date_parts[0], $date_parts[1]);
        $to_date = $date_object->format('Ymd');
        if ($to_date < $from_date) {
          form_set_error("holds[$bnum][suspend_to", t('A suspend to date cannot be before the corresponding suspend from date.'));
        }
      }
    }
  }

  $errors = form_get_errors();
  if (is_array($errors)) {
    // Skip rest of this structure.
  }
  elseif (!$update_holds) {
    form_set_error('', 'Your request to ' . $form['submit']['#value'] . ' did not include any changes.');
  }
  // Store data for use by submit function.
  else {
    $form_state['sopac_user_holds'] = array(
     'cancellations' => $cancellations,
     'freeze_changes' => $freeze_changes,
     'pickup_changes' => $pickup_changes,
     'suspend_from_changes' => $suspend_from_changes,
     'suspend_to_changes' => $suspend_to_changes,
    );
  }
}

/**
 * Pass locum validated request to update holds.
 *
 * @param array $form
 * @param array $form_state
 */
function sopac_user_holds_form_submit(&$form, &$form_state) {
  global $user;
  $cardnum = $user->profile_pref_cardnum;
  $password = $user->locum_pass;
  $cancellations = $form_state['sopac_user_holds']['cancellations'];
  $freeze_changes = $form_state['sopac_user_holds']['freeze_changes'];
  $pickup_changes = $form_state['sopac_user_holds']['pickup_changes'];
  $suspend_changes = array(
    'from' => $form_state['sopac_user_holds']['suspend_from_changes'],
    'to' => $form_state['sopac_user_holds']['suspend_to_changes'],
  );
  $locum = sopac_get_locum();
  $locum->update_holds($cardnum, $password, $cancellations, $freeze_changes, $pickup_changes, $suspend_changes);
}

/**
 * Fork to allow support for changing hold pickup location, and suspend dates. Uses
 * different tpl since extra options require different layout.
 *
 * @param array $holds
 * @return array
 */
function _sopac_user_holds_form_multirow($holds) {
  // <CraftySpace+> TODO: do we need to check for multi-branch, else no pickup location?
  $form = array(
    '#theme' => 'form_theme_bridge',
    '#layout_theme' => 'sopac_user_holds_list_multirow',
  );

  $sopac_prefix = variable_get('sopac_url_prefix', 'cat/seek') . '/record/';
  $form['holds'] = array(
    '#tree' => TRUE,
    '#iterable' => TRUE,
  );
  foreach ($holds as $hold) {
    $bnum = $hold['bnum'];

    $form['holds'][$bnum] = array(
      'cancel' => array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ),
      'title_link' => array(
        '#type' => 'markup',
        '#value' => l(t($hold['title']), $sopac_prefix . $bnum),
      ),
      'status' => array(
        '#type' => 'markup',
        '#value' => $hold['status']
      ),
      'pickup' => array(
        '#type' => 'select',
        '#options' => sopac_get_branch_options(),
        '#default_value' => $hold['pickuploc']['selected'],
      ),
      'freeze' => array(
        '#type' => 'radios',
        '#default_value' => $hold['is_frozen'],
        '#options' => array(0 => t('Active'), 1 => t('Inactive')),
      ),
      'suspend_from' => array(
        '#type' => 'textfield',
        '#title' => 'From',
        '#default_value' => $hold['start_suspend'] ? $hold['start_suspend'] : 'mm/dd/yyyy',
        '#attributes' => array('maxlength' => '10', 'size' => '15'),
      ),
      'suspend_to' => array(
        '#type' => 'textfield',
        '#title' => 'To',
        '#default_value' => $hold['end_suspend'] ? $hold['end_suspend'] : 'mm/dd/yyyy',
        '#attributes' => array('maxlength' => '10', 'size' => '15'),
      ),
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => t('Update Requests'),
  );

  return $form;
}

/**
 * A dedicated check-outs page to list all checkouts.
 */
function sopac_checkouts_page() {
  global $user;

  $account = user_load($user->uid);
  $cardnum = $account->profile_pref_cardnum;
  $locum = sopac_get_locum();
  $userinfo = $locum->get_patron_info($cardnum);
  $bcode_verify = sopac_bcode_isverified($account);
  if ($bcode_verify) {
    $account->bcode_verify = TRUE;
  }
  else {
    $account->bcode_verify = FALSE;
  }
  if ($userinfo['pnum']) {
    $account->valid_card = TRUE;
  }
  else {
    $account->valid_card = FALSE;
  }
  profile_load_profile(&$user);

  if ($account->valid_card && $bcode_verify) {
    $content = sopac_user_chkout_table(&$user, &$locum);
  }
  elseif ($account->valid_card && !$bcode_verify) {
    $content = '<div class="error">' . variable_get('sopac_uv_cardnum', t('The card number you have provided has not yet been verified by you.  In order to make sure that you are the rightful owner of this library card number, we need to ask you some simple questions.')) . '</div>' . drupal_get_form('sopac_bcode_verify_form', $account->uid, $cardnum);
  }
  elseif ($cardnum && !$account->valid_card) {
    $content = '<div class="error">' . variable_get('sopac_invalid_cardnum', t('It appears that the library card number stored on our website is invalid. If you have received a new card, or feel that this is an error, please click on the card number above to change it to your most recent library card. If you need further help, please contact us.')) . '</div>';
  }
  elseif (!$user->uid) {
    $content = '<div class="error">' . t('You must be ') . l(t('logged in'), 'user') . t(' to view this page.') . '</div>';
  }
  elseif (!$cardnum) {
    $content = '<div class="error">' . t('You must register a valid ') . l(t('library card number'), 'user/' . $user->uid . '/edit/Preferences') . t(' to view this page.') . '</div>';
  }

  return $content;
}

/**
 * A dedicated checkout history page.
 */
function sopac_checkout_history_page() {
  global $user;
  profile_load_profile(&$user);
  if ($user->profile_pref_cardnum) {

    // Get the time since the last update
    $last_import = db_result(db_query("SELECT DATESUB(NOW() - last_hist_check) FROM {sopac_last_hist_check} WHERE uid = '" . $user->uid . "'"));

    // Check profile to see if CO hist is enabled
    $user_co_hist_enabled = $user->profile_pref_cohist;
    if (!$user_co_hist_enabled) {
      // CO hist is not enabled, would you like to enable it?
      return $content;
    }
    // CO hist is enabled, would you like to disable it?

    // Set up our data sets
    $url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
    $insurge = sopac_get_insurge();
    $locum = sopac_get_locum();
    $locum_pass = substr($user->pass, 0, 7);
    $cardnum = $user->profile_pref_cardnum;
    $last_checkout_result = $insurge->get_checkout_history($user->uid, 1);
    $last_checkout[(string) $last_checkout_result['bnum']] = $last_checkout_result['codate']; // Like this?

    // If we haven't imported data recently, do it now.
    if ($last_import >= variable_get('sopac_checkout_history_cache_time', 60)) {

      $checkouts = $locum->get_patron_checkout_history($cardnum, $locum_pass, $last_checkout);

      // Check: if profile->co hist is enabled , verify that it's on in the ILS
      // check: "" disables "" off
      if (!is_array($checkouts)) {
        if ($checkouts == 'out') {
          $content = '<div>'. t('This feature is currently turned off.') . '</div>';
          $toggle = l(t('Opt In'), 'user/checkouts/history/opt/in');
        }
        if ($checkouts == 'in') {
          $content = '<div>There are no items in your checkout history.</div>';
          $toggle = l(t('Opt Out'), 'user/checkouts/history/opt/out');
        }
      }
      else {
        foreach ($checkouts as $checkout) {
          $bib_item = $locum->get_bib_item($checkout['bnum']);
          if ($bib_item['bnum']) {
            $insurge->add_checkout_history($user->uid, $checkout['bnum'], $bib_item['title'], $bib_item['author'] . ' ' . $bib_item['addl_author']);
          }
        }
      }
      // Reset cache age
      db_query("UPDATE {sopac_last_hist_check} SET last_hist_check = NOW()");
    }

    // Set up pagination

    // Grab checkout history from Insurge
    $checkout_history = $insurge->get_checkout_history($user->uid);

    if (count ($checkout_history)) {
      // Set up the table
      $header = array('', t('Title'), t('Author'), t('Check-Out Date'));
      $rows = array();
      foreach ($checkout_history as $hist_item) {
        $item = $locum->get_bib_item($hist_item['bnum']);
        $new_author_str = sopac_author_format($item['author'], $item['addl_author']);
        $rows[] = array(
          l(ucwords($item['title']), $url_prefix . '/record/' . $item['bnum']),
          l($new_author_str, $url_prefix . '/search/author/' . urlencode($new_author_str)),
          $hist_item['codate'], // Figure out the best way to format this
        );
        $content = theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0'));
      }
    }
    else {
      // nothing in users co hist
      $content = t('You do not have anything in your checkout history yet.');
    }

  }
  else {
    $content = '<div class="cohist_nocard">' . t('Please register your library card to take advantage of this feature.') . '</div>';
  }
  return $content;
}

/**
 * Handle toggling checkout history on or off.
 */
function sopac_checkout_history_toggle($action) {
  global $user;
  if ($action != 'in' && $action != 'out') { drupal_goto('user/checkouts/history'); }
  $adjective = $action == 'in' ? t('on') : t('off');
  profile_load_profile(&$user);
  if ($user->profile_pref_cardnum) {
    if (!$_GET['confirm']) {
      $confirm_link = l(t('confirm'), $_GET['q'], array('query' => 'confirm=true'));
      $content = "<div>Please $confirm_link that you wish to turn $adjective your checkout history.";
      if ($action == 'out') {
        $content .= ' ' . t('Please note: this will delete your entire checkout history.');
      }
    }
    else {
      $locum = sopac_get_locum();
      $locum_pass = substr($user->pass, 0, 7);
      $cardnum = $user->profile_pref_cardnum;
      $success = $locum->set_patron_checkout_history($cardnum, $locum_pass, $action);
      if ($success === TRUE) {
        $content = "<div>Your checkout history has been turned $adjective.</div>";
      }
      else {
        $content = "<div>An error occurred. Your checkout history has not been turned $adjective. Please try again.</div>";
      }
    }
  }
  else {
    $content = '<div>' . t('Please register your library card to take advantage of this feature.') . '</div>';
  }
  return $content;
}

/**
 * A dedicated holds page to list all holds.
 */
function sopac_holds_page() {
  global $user;

  $account = user_load($user->uid);
  $cardnum = $account->profile_pref_cardnum;
  $locum = sopac_get_locum();
  $userinfo = $locum->get_patron_info($cardnum);
  $bcode_verify = sopac_bcode_isverified($account);
  if ($bcode_verify) {
    $account->bcode_verify = TRUE;
  }
  else {
    $account->bcode_verify = FALSE;
  }
  if ($userinfo['pnum']) {
    $account->valid_card = TRUE;
  }
  else {
    $account->valid_card = FALSE;
  }
  profile_load_profile(&$user);

  if ($account->valid_card && $bcode_verify) {
    $content = drupal_get_form('sopac_user_holds_form');
  }
  elseif ($account->valid_card && !$bcode_verify) {
    $content = '<div class="error">' . variable_get('sopac_uv_cardnum', t('The card number you have provided has not yet been verified by you.  In order to make sure that you are the rightful owner of this library card number, we need to ask you some simple questions.')) . '</div>' . drupal_get_form('sopac_bcode_verify_form', $account->uid, $cardnum);
  }
  elseif ($cardnum && !$account->valid_card) {
    $content = '<div class="error">' . variable_get('sopac_invalid_cardnum', t('It appears that the library card number stored on our website is invalid. If you have received a new card, or feel that this is an error, please click on the card number above to change it to your most recent library card. If you need further help, please contact us.')) . '</div>';
  }
  elseif (!$user->uid) {
    $content = '<div class="error">' . t('You must be ') . l(t('logged in'), 'user') . t(' to view this page.') . '</div>';
  }
  elseif (!$cardnum) {
    $content = '<div class="error">' . t('You must register a valid ') . l(t('library card number'), 'user/' . $user->uid . '/edit/Preferences') . t(' to view this page.') . '</div>';
  }

  return $content;
}

/**
 * A dedicated page for managing fines and payments.
 */
function sopac_fines_page() {
  global $user;

  $locum = sopac_get_locum();
  profile_load_profile(&$user);

  if ($user->profile_pref_cardnum && sopac_bcode_isverified(&$user)) {
    $locum_pass = substr($user->pass, 0, 7);
    $cardnum = $user->profile_pref_cardnum;
    $fines = $locum->get_patron_fines($cardnum, $locum_pass);

    if (!count($fines)) {
      $notice = t('You do not have any fines, currently.');
    }
    else {
      $header = array('', t('Amount'), t('Description'));
      $fine_total = (float) 0;
      foreach ($fines as $fine) {
        $col1 = variable_get('sopac_payments_enable', 1) ? '<input type="checkbox" name="varname[]" value="' . $fine['varname'] . '">' : '';
        $rows[] = array(
          $col1,
          '$' . number_format($fine['amount'], 2),
          $fine['desc'],
        );
        $hidden_vars .= '<input type="hidden" name="fine_summary[' . $fine['varname'] . '][amount]" value="' . addslashes($fine['amount']) . '">';
        $hidden_vars .= '<input type="hidden" name="fine_summary[' . $fine['varname'] . '][desc]" value="' . addslashes($fine['desc']) . '">';
        $fine_total = $fine_total + $fine['amount'];
      }
      $rows[] = array('<strong>Total:</strong>', '$' . number_format($fine_total, 2), '');
      $submit_button = '<input type="submit" value="' . t('Pay Selected Charges') . '">';
      if (variable_get('sopac_payments_enable', 1)) {
        $rows[] = array( 'data' => array(array('data' => $submit_button, 'colspan' => 3)), 'class' => 'profile_button' );
      }
      $fine_table = '<form method="post" action="' . url('user/fines/pay') . '">' . theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0')) . $hidden_vars . '</form>';
      $notice = t('Your current fine balance is $') . number_format($fine_total, 2) . '.';
    }
  }
  else {
    $notice = t('You do not yet have a library card validated with our system.  You can add and validate a card using your ') . l(t('account page'), 'user') . '.';
  }

  $result_page = theme('sopac_fines', $notice, $fine_table, &$user);
  return '<p>'. t($result_page) .'</p>';
}

/**
 * A dedicated page for viewing payment information.
 */
function sopac_finespaid_page() {
  global $user;
  $limit = 20; // TODO Make this configurable

  if (count($_POST['payment_id'])) {
    foreach ($_POST['payment_id'] as $pid) {
      db_query('DELETE FROM {sopac_fines_paid} WHERE payment_id = ' . $pid . ' AND uid = ' . $user->uid);
    }
  }

  if (db_result(db_query('SELECT COUNT(*) FROM {sopac_fines_paid} WHERE uid = ' . $user->uid))) {
    $header = array('', 'Payment Date', 'Payment Description', 'Amount');
    $dbq = pager_query('SELECT payment_id, UNIX_TIMESTAMP(trans_date) as trans_date, fine_desc, amount FROM {sopac_fines_paid} WHERE uid = ' . $user->uid . ' ORDER BY trans_date DESC', $limit);
    while ($payment_arr = db_fetch_array($dbq)) {
      $checkbox = '<input type="checkbox" name="payment_id[]" value="' . $payment_arr['payment_id'] . '">';
      $payment_date = date('m-d-Y, H:i:s', $payment_arr['trans_date']);
      $payment_desc = $payment_arr['fine_desc'];
      $payment_amt = '$' . number_format($payment_arr['amount'], 2);
      $rows[] = array($checkbox, $payment_date, $payment_desc, $payment_amt);
    }
    $submit_button = '<input type="submit" value="' . t('Remove Selected Payment Records') . '">';
    $rows[] = array('data' => array(array('data' => $submit_button, 'colspan' => 4)), 'class' => 'profile_button');
    $page_disp = '<form method="post">' . theme('pager', NULL, $limit, 0, NULL, 6) . theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0')) . '</form>';
  }
  else {
    $page_disp = t('You do not have any payments on record.');
  }

  return $page_disp;
}

function sopac_makepayment_page() {
  global $user;

  $locum = sopac_get_locum();
  profile_load_profile(&$user);

  if ($user->profile_pref_cardnum && sopac_bcode_isverified(&$user)) {
    if ($_POST['varname'] && is_array($_POST['varname'])) {
      $varname = $_POST['varname'];
    }
    else {
      $varname = explode('|', $_POST['varname']);
    }
    $locum_pass = substr($user->pass, 0, 7);
    $cardnum = $user->profile_pref_cardnum;
    $fines = $locum->get_patron_fines($cardnum, $locum_pass);
    if (!count($fines) || !count($varname)) {
      $notice = t('You did not select any payable fines.');
    }
    else {
      $header = array('', t('Amount'), t('Description'));
      $fine_total = (float) 0;
      foreach ($fines as $fine) {
        if (in_array($fine['varname'], $varname)) {
          $rows[] = array(
            '',
            '$' . number_format($fine['amount'], 2),
            $fine['desc'],
          );
          $fine_total = $fine_total + $fine['amount'];
          $hidden_vars_arr[$fine['varname']]['amount'] = $_POST['fine_summary'][$fine['varname']]['amount'];
          $hidden_vars_arr[$fine['varname']]['desc'] = $_POST['fine_summary'][$fine['varname']]['desc'];
        }
      }
      $payment_form = drupal_get_form('sopac_fine_payment_form', $varname, (string) $fine_total, $hidden_vars_arr);
      $rows[] = array('<strong>Total:</strong>', '$' . number_format($fine_total, 2), '') ;
      $fine_table = theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0'));
      $notice = t('You have selected to pay the following fines:');
    }
  }
  else {
    $notice = t('You do not yet have a library card validated with our system.  You can add and validate a card using your ') . l(t('account page'), 'user') . '.';
  }

  $result_page = theme('sopac_fines', $notice, $fine_table, &$user, $payment_form);
  return '<p>'. t($result_page) .'</p>';
}

function sopac_fine_payment_form() {
  global $user;

  $args = func_get_args();
  $varname = $args[1];
  $fine_total = $args[2];
  $hidden_vars_arr = $args[3];

  $form['#redirect'] = 'user/fines';
  $form['sopac_payment_billing_info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Billing Information'),
    '#collapsible' => FALSE,
  );

  $form['sopac_payment_billing_info']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name on the credit card'),
    '#size' => 48,
    '#maxlength' => 200,
    '#required' => TRUE,
  );

  $form['sopac_payment_billing_info']['address1'] = array(
    '#type' => 'textfield',
    '#title' => t('Billing Address'),
    '#size' => 48,
    '#maxlength' => 200,
    '#required' => TRUE,
  );

  $form['sopac_payment_billing_info']['city'] = array(
    '#type' => 'textfield',
    '#title' => t('City/Town'),
    '#size' => 32,
    '#maxlength' => 200,
    '#required' => TRUE,
  );

  $form['sopac_payment_billing_info']['state'] = array(
    '#type' => 'textfield',
    '#title' => t('State'),
    '#size' => 3,
    '#maxlength' => 2,
    '#required' => TRUE,
  );

  $form['sopac_payment_billing_info']['zip'] = array(
    '#type' => 'textfield',
    '#title' => t('ZIP Code'),
    '#size' => 7,
    '#maxlength' => 200,
    '#required' => TRUE,
  );

  $form['sopac_payment_billing_info']['email'] = array(
    '#type' => 'textfield',
    '#title' => t('Email Address'),
    '#size' => 48,
    '#maxlength' => 200,
    '#required' => TRUE,
  );

  $form['sopac_payment_cc_info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Credit Card Information'),
    '#collapsible' => FALSE,
  );

  $form['sopac_payment_cc_info']['ccnum'] = array(
    '#type' => 'textfield',
    '#title' => t('Credit Card Number'),
    '#size' => 24,
    '#maxlength' => 20,
    '#required' => TRUE,
  );

  $form['sopac_payment_cc_info']['ccexpmonth'] = array(
    '#type' => 'textfield',
    '#title' => t('Expiration Month'),
    '#size' => 4,
    '#maxlength' => 2,
    '#required' => TRUE,
    '#prefix' => '<table class="fines-form-table"><tr><td>',
    '#suffix' => '</td>',
  );

  $form['sopac_payment_cc_info']['ccexpyear'] = array(
    '#type' => 'textfield',
    '#title' => t('Expiration Year'),
    '#size' => 5,
    '#maxlength' => 4,
    '#required' => TRUE,
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
  );

  $form['sopac_payment_cc_info']['ccseccode'] = array(
    '#type' => 'textfield',
    '#title' => t('Security Code'),
    '#size' => 5,
    '#maxlength' => 5,
    '#required' => TRUE,
  );

  foreach ($hidden_vars_arr as $hkey => $hvar) {
    $form['sopac_payment_form']['fine_summary[' . $hkey . '][amount]'] = array('#type' => 'hidden', '#value' => $hvar['amount']);
    $form['sopac_payment_form']['fine_summary[' . $hkey . '][desc]'] = array('#type' => 'hidden', '#value' => $hvar['desc']);
  }
  $form['sopac_payment_form']['varname'] = array('#type' => 'hidden', '#value' => implode('|', $varname));
  $form['sopac_payment_form']['total'] = array('#type' => 'hidden', '#value' => $fine_total);
  $form['sopac_savesearch_form']['submit'] = array('#type' => 'submit', '#value' => t('Make Payment'));

  return $form;
}

function sopac_fine_payment_form_submit($form, &$form_state) {
  global $user;
  $locum = sopac_get_locum();
  profile_load_profile(&$user);
  $locum_pass = substr($user->pass, 0, 7);

  if ($user->profile_pref_cardnum && sopac_bcode_isverified(&$user)) {
    $fines = $locum->get_patron_fines($cardnum, $locum_pass);
    $payment_details['name'] = $form_state['values']['name'];
    $payment_details['address1'] = $form_state['values']['address1'];
    $payment_details['city'] = $form_state['values']['city'];
    $payment_details['state'] = $form_state['values']['state'];
    $payment_details['zip'] = $form_state['values']['zip'];
    $payment_details['email'] = $form_state['values']['email'];
    $payment_details['ccnum'] = $form_state['values']['ccnum'];
    $payment_details['ccexpmonth'] = $form_state['values']['ccexpmonth'];
    $payment_details['ccexpyear'] = $form_state['values']['ccexpyear'];
    $payment_details['ccseccode'] = $form_state['values']['ccseccode'];
    $payment_details['total'] = $form_state['values']['total'];
    $payment_details['varnames'] = explode('|', $form_state['values']['varname']);
    $payment_result = $locum->pay_patron_fines($user->profile_pref_cardnum, $locum_pass, $payment_details);

    if (!$payment_result['approved']) {
      if ($payment_result['reason']) {
        $error = '<strong>' . t('Your payment was not processed:') . '</strong> ' . $payment_result['reason'];
      }
      else {
        $error = t('We were unable to process your payment.');
      }
      drupal_set_message(t('<span class="fine-notice">' . $error . '</span>'));
      if ($payment_result['error']) {
        drupal_set_message(t('<span class="fine-notice">' . $payment_result['error'] . '</span>'));
      }
    }
    else {
      foreach ($_POST['fine_summary'] as $fine_var => $fine_var_arr) {
        $fine_desc = db_escape_string($fine_var_arr['desc']);
        $sql = 'INSERT INTO {sopac_fines_paid} (payment_id, uid, amount, fine_desc) VALUES (0, ' . $user->uid . ', ' . $fine_var_arr['amount'] . ', "' . $fine_desc . '")';
        db_query($sql);
      }
      $amount = '$' . number_format($form_state['values']['total'], 2);
      drupal_set_message('<span class="fine-notice">' . t('Your payment of ') . $amount . t(' was successful.  Thank-you!') . '</span>');
    }
  }
}


/**
 * A dedicated page for showing and managing saved searches from the catalog.
 */
function sopac_saved_searches_page() {
  global $user;
  $limit = 20; // TODO Make this configurable

  if (count($_POST['search_id'])) {
    foreach ($_POST['search_id'] as $sid) {
      db_query('DELETE FROM {sopac_saved_searches} WHERE search_id = ' . $sid . ' AND uid = ' . $user->uid);
    }
  }

  if (db_result(db_query('SELECT COUNT(*) FROM {sopac_saved_searches} WHERE uid = ' . $user->uid))) {
    $header = array('','Search Description','');
    $dbq = pager_query('SELECT * FROM {sopac_saved_searches} WHERE uid = ' . $user->uid . ' ORDER BY savedate DESC', $limit);
    while ($search_arr = db_fetch_array($dbq)) {
      $checkbox = '<input type="checkbox" name="search_id[]" value="' . $search_arr['search_id'] . '">';
      $parts = explode('?', $search_arr['search_url']);
      $search_desc = l($search_arr['search_desc'], $parts[0], array('query' => $parts[1]));
      // TODO: implement RSS feeds for saved searches.
      $search_feed_url = sopac_update_url($search_arr['search_url'], 'output', 'rss');
      $search_feed = theme_feed_icon($search_feed_url, 'RSS Feed: ' . $search_arr['search_desc']);
      $rows[] = array($checkbox, $search_desc, $search_feed);
    }
    $submit_button = '<input type="submit" value="' . t('Remove Selected Searches') . '">';
    $rows[] = array( 'data' => array(array('data' => $submit_button, 'colspan' => 3)), 'class' => 'profile_button' );
    $page_disp = '<form method="post">' . theme('pager', NULL, $limit, 0, NULL, 6) . theme('table', $header, $rows, array('id' => 'patroninfo', 'cellspacing' => '0')) . '</form>';
  }
  else {
    $page_disp = '<div class="overview-nodata">' . t('You do not currently have any saved searches.') . '</div>';
  }

  return $page_disp;
}


/**
 * Returns the form array for saving searches
 *
 * @return array Drupal form array.
 */
function sopac_savesearch_form() {
  global $user;

  $search_path = str_replace('/savesearch/', '/search/', $_GET['q']);
  $search_query = sopac_make_pagevars(sopac_parse_get_vars());
  $uri_arr = sopac_parse_uri();

  $form_desc = 'How would you like to label your ' . $uri_arr[1] . ' search for "' . l($uri_arr[2], $search_path, array('query' => $search_query)) . '" ?';
  $form['#redirect'] = 'user/library/searches';
  $form['sopac_savesearch_form'] = array(
    '#type' => 'fieldset',
    '#title' => t($form_desc),
    '#collapsible' => FALSE,
  );

  $form['sopac_savesearch_form']['searchname'] = array(
    '#type' => 'textfield',
    '#title' => t('Search Label'),
    '#size' => 48,
    '#maxlength' => 128,
    '#required' => TRUE,
    '#default_value' => 'My custom ' . $uri_arr[1] . ' search for "' . $uri_arr[2] . '"',
  );

  $form['sopac_savesearch_form']['uri'] = array('#type' => 'hidden', '#value' => $search_path . '?' . $search_query);
  $form['sopac_savesearch_form']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  return $form;

}


function sopac_savesearch_form_submit($form, &$form_state) {
  global $user;

  $desc = db_escape_string($form_state['values']['searchname']);
  db_query('INSERT INTO {sopac_saved_searches} VALUES (0, ' . $user->uid . ', NOW(), "' . $desc . '", "' . $form_state['values']['uri'] . '")');

  $parts = explode('?', $form_state['values']['uri']);
  $submsg = '<strong>»</strong> ' . t('You have saved this search.') . '<br /><strong>»</strong> ' . l(t('Return to your search'), $parts[0], array('query' => $parts[1])) . '<br /><br />';
  drupal_set_message($submsg);

}


function sopac_update_locum_acct($op, &$edit, &$account) {

  $locum = sopac_get_locum();

  // Make sure we're all legit on this account
  $cardnum = $account->profile_pref_cardnum;
  if (!$cardnum) {
    return 0;
  }
  $userinfo = $locum->get_patron_info($cardnum);
  $bcode_verify = sopac_bcode_isverified($account);
  if ($bcode_verify) {
    $account->bcode_verify = TRUE;
  }
  else {
    $account->bcode_verify = FALSE;
  }
  if ($userinfo['pnum']) {
    $account->valid_card = TRUE;
  }
  else {
    $account->valid_card = FALSE;
  }
  if (!$account->valid_card || !$bcode_verify) {
    return 0;
  }

  if ($edit['mail'] && $pnum) {
    // TODO update email. etc.
  }
}

/**
 * Creates and returns the barcode/patron card number verification form.  It also does the neccesary processing
 * If this function has just successfully processed a form result, then it will instead return a message indicating thus.
 *
 * @param string $cardnum Library patron barcode/card number
 * @return string Either the verification form or a confirmation of success.
 */
function sopac_bcode_verify_form() {

  $args = func_get_args();

  if (variable_get('sopac_require_cfg', 'one') == 'one') {
    $req_flds = FALSE;
    $form_desc = t('Please correctly <strong>answer <u>one</u> of the following questions</strong>:');
  }
  else {
    $req_flds = TRUE;
        $form_desc = t('Please correctly <strong>answer <u>all</u> of the following questions</strong>:');
  }

  $form['sopac_card_verify'] = array(
    '#type' => 'fieldset',
    '#title' => t('Verify your Library Card Number'),
    '#description' => t($form_desc),
    '#collapsible' => FALSE,
    '#validate' => 'sopac_bcode_verify_form_validate',
  );

  if (variable_get('sopac_require_name', 1)) {
    $form['sopac_card_verify']['last_name'] = array(
      '#type' => 'textfield',
      '#title' => t('What is your last name?'),
      '#size' => 32,
      '#maxlength' => 128,
      '#required' => $req_flds,
      '#value' => $_POST['last_name'],
    );
  }

  if (variable_get('sopac_require_streetname', 1)) {
    $form['sopac_card_verify']['streetname'] = array(
      '#type' => 'textfield',
      '#title' => t('What is the name of the street you live on?'),
      '#size' => 24,
      '#maxlength' => 32,
      '#required' => $req_flds,
      '#value' => $_POST['streetname'],
    );
  }

  if (variable_get('sopac_require_tel', 1)) {
    $form['sopac_card_verify']['telephone'] = array(
      '#type' => 'textfield',
      '#title' => t('What is your telephone number?'),
      '#description' => t("Please provide your area code as well as your phone number, eg: 203-555-1234."),
      '#size' => 18,
      '#maxlength' => 24,
      '#required' => $req_flds,
      '#value' => $_POST['telephone'],
    );
  }

  $form['sopac_card_verify']['vfy_post'] = array('#type' => 'hidden', '#value' => '1');
  $form['sopac_card_verify']['uid'] = array('#type' => 'hidden', '#value' => $args[1]);
  $form['sopac_card_verify']['cardnum'] = array('#type' => 'hidden', '#value' => $args[2]);
  $form['sopac_card_verify']['vfy_submit'] = array('#type' => 'submit', '#value' => t('Verify!'));

  return $form;
}

function sopac_bcode_verify_form_validate($form, $form_state) {
  global $account;

  $locum = sopac_get_locum();
  $cardnum = $form_state['values']['cardnum'];
  $uid = $form_state['values']['uid'];
  $userinfo = $locum->get_patron_info($cardnum);
  $numreq = 0;
  $correct = 0;
  $validated = FALSE;

  $req_cfg = variable_get('sopac_require_cfg', 'one');

  // Match the name given
  if (variable_get('sopac_require_name', 1)) {
    if (trim($form_state['values']['last_name'])) {
      $locum_name = ereg_replace("[^A-Za-z0-9 ]", "", trim(strtolower($userinfo['name'])));
      $sub_name = ereg_replace("[^A-Za-z0-9 ]", "", trim(strtolower($form_state['values']['last_name'])));
      if (preg_match('/\b' . $sub_name . '\b/i', $locum_name)) {
        $correct++;
      }
      else {
        $error[] = t('The last name you entered does not appear to match what we have on file.');
      }
    }
    else {
      $error[] = t('You did not provide a last name.');
    }
    $numreq++;
  }

  if (variable_get('sopac_require_streetname', 1)) {
    if (trim($form_state['values']['streetname'])) {
      $locum_addr = ereg_replace("[^A-Za-z ]", "", trim(strtolower($userinfo['address'])));
      $sub_addr = ereg_replace("[^A-Za-z ]", "", trim(strtolower($form_state['values']['streetname'])));
      $sub_addr_arr = explode(' ', $sub_addr);
      if (strlen($sub_addr_arr[0]) == 1 || $sub_addr_arr[0] == 'north' || $sub_addr_arr[0] == 'east' || $sub_addr_arr[0] == 'south' || $sub_addr_arr[0] == 'west') {
        $sub_addr = $sub_addr_arr[1];
      }
      else {
        $sub_addr = $sub_addr_arr[0];
      }
      if (preg_match('/\b' . $sub_addr . '\b/i', $locum_addr)) {
        $correct++;
      }
      else {
        $error[] = t('The street name you entered does not appear to match what we have on file.');
      }
    }
    else {
      $error[] = t('You did not provide a street name.');
    }
    $numreq++;
  }

  if (variable_get('sopac_require_tel', 1)) {
    if (trim($form_state['values']['telephone'])) {
      $locum_tel = ereg_replace("[^A-Za-z0-9 ]", "", trim(strtolower($userinfo['tel1'] . ' ' . $userinfo['tel2'])));
      $sub_tel = ereg_replace("[^A-Za-z0-9 ]", "", trim(strtolower($form_state['values']['telephone'])));
      if (preg_match('/\b' . $sub_tel . '\b/i', $locum_tel)) {
        $correct++;
      }
      else {
        $error[] = t('The telephone number you entered does not appear to match what we have on file.');
      }
    }
    else {
      $error[] = t('You did not provide a telephone number.');
    }
    $numreq++;
  }

  if ($req_cfg == 'one') {
    if ($correct > 0) {
      $validated = TRUE;
    }
  }
  else {
    if ($correct == $numreq) {
      $validated = TRUE;
    }
  }

  if (count($error) && !$validated) {
    foreach ($error as $errkey => $errmsg) {
      form_set_error($errkey, t($errmsg));
    }
  }

  if ($validated) {
    db_query("INSERT INTO {sopac_card_verify} VALUES ($uid, '$cardnum', 1, NOW())");
  }

}

function sopac_lists_page($list_id = 0) {
  global $user;
  require_once('sopac_social.php');
  $insurge = sopac_get_insurge();

  if ($list_id) {
    // display list contents
    $list = db_fetch_array(db_query("SELECT * FROM sopac_lists WHERE list_id = %d LIMIT 1", $list_id));
    if ($list['list_id']) {
      if ($user->uid == $list['uid'] || $list['public'] || user_access('administer sopac')) {
        $sortopts = array(
          'value',
          'title',
          'author',
          'mat_code',
        );
        if (array_search($_GET['sort'], $sortopts)) {
          $list['items']= $insurge->get_list_items($list_id, $_GET['sort'], 'ASC');
        }
        else if ($_GET['sort'] == 'date') {
          $list['items']= $insurge->get_list_items($list_id, 'tag_date', 'ASC');
        }
        else if ($_GET['sort'] == 'date_newest') {
          $list['items']= $insurge->get_list_items($list_id, 'tag_date', 'DESC');
        }
        else {
          $list['items']= $insurge->get_list_items($list_id, 'value', 'ASC');
        }
        $output .= theme('sopac_list', $list, TRUE);
      }
      else {
        $output .= '<p>You do not have permission to view this list.</p>';
        $output .= '<ul><li class="button green">';
        $output .= ($user->uid ? l('View your lists', 'user/lists') : l('Log in to create lists', 'user', array('query' => 'destination=user/lists')));
        $output .= '</li></ul>';
      }
    }
    else {
      drupal_set_message("No list with list id #$list_id exists");
      drupal_goto('user/lists');
    }
  }
  else {
    if ($user->uid) {
      $output = "<h1>My Lists</h1>";
      // display lists
      $res = db_query("SELECT * FROM {sopac_lists} WHERE uid = %d", $user->uid);
      while ($list = db_fetch_array($res)) {
        $list['items'] = $insurge->get_list_items($list['list_id']);
        $output .= theme('sopac_list', $list);
      }
      $output .= '<ul class="list-overview-actions"><li class="button green">' . l('Create New List', 'user/lists/edit') . '</li></ul>';
    }
    else {
      // Anonymous user
      drupal_set_message('You must log in to create and edit your lists');
      drupal_goto('user', drupal_get_destination());
    }
  }

  return $output;
}

function sopac_list_form($form_state, $list, $header) {
  include_once('sopac_catalog.php');
  $locum = sopac_get_locum();
  $formats = $locum->locum_config['formats'];
  $form = array('#list_id' => $list['list_id'], '#header' => $header);
  foreach ($list['items'] as $item) {
    // Cover Image
    if ($item['cover_img'] == "CACHE")
      $item['cover_img'] = "http://media.aadl.org/covers/" . $item['bnum'] . "_50.jpg";
    else if (!$item['cover_img'])
      $item['cover_img'] = base_path() . drupal_get_path('module', 'sopac') . '/images/nocover' . rand(1,4) . '_50.jpg';
    $item['cover_img'] = '<img src="' . $item['cover_img'] . '" />';

    // Material Icon
    $material_icon = '<img src="' . drupal_get_path('module', 'sopac') . '/images/' . $item['mat_code'] . '.png"><br />' .
                     wordwrap($formats[$item['mat_code']], 8, '<br />');

    $data = array(
      'place' => $item['value'],
      'type' => $material_icon,
      'cover' => $item['cover_img'],
      'title' => l($item['title'], variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $item['bnum']),
      'author' => sopac_author_format($item['author'], $item['addl_author']),
      'date' => $item['tag_date'],
      'actions' => sbl('Remove Item', 'user/listdelete/' . $list['list_id'] . '/' . $item['bnum'], array('iconafter' => 'cross')) .
                   '<br />' .
                   sbl('Move to Top', 'user/listmovetop/' . $list['list_id'] . '/' . $item['value'], array('iconafter' => 'arrow_up')) .
                   '<br />' .
                   sbl('Request Item', variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $item['bnum'], array('iconafter' => 'book_go')),
    );

    $form['rows'][$item['bnum']]['data'] = array(
      '#type' => 'value',
      '#value' => $data,
    );
    $form['rows'][$item['bnum']]['places'][$item['bnum']] = array(
      '#type' => 'textfield',
      '#size' => 4,
      '#default_value' => $item['value'],
      '#attributes' => array('class' => 'sopac-list-place'),
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Changes'),
  );

  return $form;
}

function sopac_list_form_submit($form, &$form_state) {
  $new_places = array();
  foreach($form_state['values'] as $current => $new) {
    if (intval($current)) {
      $places[$current] = $new;
    }
  }
  // See if we need to adjust the new values to get down to lowest being 1
  if ($diff = min($places) - 1) {
    foreach($places as &$place) {
      $place = $place - $diff;
    }
  }

  $insurge = sopac_get_insurge();
  $insurge->reorder_list($form['#list_id'], $places);
  drupal_set_message('List order updated');
}

function theme_sopac_list_form($form) {
  // loop through each "row" in the table array
  foreach($form['rows'] as $id => $row) {
    // we are only interested in numeric keys
    if (intval($id)){
      $this_row = $row['data']['#value'];

      //Add the place field to the row
      $this_row[] = drupal_render($form['rows'][$id]['places'][$id]);

      //Add the row to the array of rows
      $table_rows[] = array('data' => $this_row, 'class' => 'draggable');
    }
  }

  //Make sure the header count matches the column count
  $header = $form['#header'];
  $header[] = 'New Place';

  $output .= theme('table', $header, $table_rows, array('id' => 'sopac-list-table'));
  $output .= drupal_render($form);

  // Call add_tabledrag to add and setup the JS for us
  // The key thing here is the first param - the table ID
  // and the 4th param, the class of the form item which holds the weight
  drupal_add_tabledrag('sopac-list-table', 'order', 'sibling', 'sopac-list-place');

  return $output;
}

function sopac_list_edit_form($form_state, $list_id = 0) {
  if ($list_id) {
    $list = db_fetch_array(db_query("SELECT * FROM sopac_lists WHERE list_id = %d LIMIT 1", $list_id));
    $form['list_id'] = array(
      '#type' => 'value',
      '#value' => $list['list_id'],
    );
  }
  else if ($bnum = intval($_GET['bnum'])) {
    $locum = sopac_get_locum();
    $bib = $locum->get_bib_item($bnum);
    // only auto-add an item when creating a new list
    $form['bnum'] = array(
      '#type' => 'value',
      '#value' => $bnum,
    );
    $form['bib_info'] = array(
      '#value' => "<h1>Add " . $bib['title'] . ' to a new list:</h1>',
    );
  }
  if ($list['title'] == 'Wishlist') {
    $form['title'] = array(
      '#type' => 'value',
      '#value' => 'Wishlist',
    );
    $form['title_markup'] = array(
      '#value' => "<h1>Wishlist</h1>",
    );
  }
  else {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('List Title'),
      '#default_value' => $list['title'],
      '#size' => 64,
      '#maxlength' => 128,
      '#description' => t('Name your new list (e.g. My Wishlist, Vacation Reading, Favorites'),
    );
  }
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $list['description'],
    '#size' => 64,
    '#maxlength' => 256,
    '#description' => t('Describe your list (optional)'),
  );
  $form['public'] = array(
    '#type' => 'checkbox',
    '#title' => t('Public'),
    '#description' => t('Allow anyone to see this list?'),
    '#default_value' => $list['public'],
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  return $form;
}

function sopac_list_edit_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  if ($values['list_id']) {
    // Update existing list
    db_query("UPDATE {sopac_lists} SET title = '%s', description = '%s', public = '%d' WHERE list_id = '%d'",
             $values['title'], $values['description'], $values['public'], $values['list_id']);
    drupal_set_message('List "' . $values['title'] . '" updated');
  }
  else {
    // New list
    global $user;
    db_query("INSERT INTO {sopac_lists} (list_id, uid, title, description, public) VALUES (NULL, '%d', '%s', '%s', '%d')",
             $user->uid, $values['title'], $values['description'], $values['public']);
    drupal_set_message('List "' . $values['title'] . '" created');
    if ($values['bnum']) {
      $insurge = sopac_get_insurge();
      $list_id = db_last_insert_id('sopac_lists', 'list_id');
      $insurge->add_list_item($user->uid, $list_id, $values['bnum']);
      drupal_set_message("Item added to your list");
      drupal_goto("user/lists/$list_id");
    }
  }
  drupal_goto('user/lists');
}

function sopac_list_add($bnum, $list_id = 0) {
  global $user;
  $insurge = sopac_get_insurge();
  $locum = sopac_get_locum();
  $bib = $locum->get_bib_item(intval($bnum),1);
  if ($bib['bnum']) {
    // Valid bib record
    if ($list_id == 'wish') {
      // Find the user's wishlist
      $list = db_fetch_object(db_query("SELECT * FROM sopac_lists WHERE title = 'Wishlist' AND uid = %d", $user->uid));
      if ($list->list_id) {
        $list_id = $list->list_id;
      }
      else {
        // Create a new list named "Wishlist" and add the item
        db_query("INSERT INTO {sopac_lists} (list_id, uid, title, description, public) VALUES (NULL, '%d', '%s', '%s', '%d')",
                 $user->uid, 'Wishlist', '', 0);
        drupal_set_message('New Wishlist created');
        $list_id = db_last_insert_id('sopac_lists', 'list_id');
      }
    }
    else {
      $list_id = intval($list_id);
      // Check to see if $user owns the list
      if (user_access('administer sopac')) {
        $list = db_fetch_object(db_query("SELECT * FROM sopac_lists WHERE list_id = '%d'", $list_id));
      }
      else {
        $list = db_fetch_object(db_query("SELECT * FROM sopac_lists WHERE list_id = '%d' AND uid = %d", $list_id, $user->uid));
      }
      if (!$list->list_id) {
        $output .= '<h2>Error: Unable to add item to list, you do not own this list</h2>';
      }
    }
    // add to list and redirect to that list
    if ($insurge->add_list_item($user->uid, $list_id, $bnum)) {
      $output .= '<h2>"' . $bib['title'] . '" has been added to your list</h2>';
    }
    else {
      drupal_set_message('"' . $bib['title'] . '" has not been added, already on list', 'error');
    }
  }
  else {
    $output .= '<h2>' . 'No catalog record found with id #' . intval($bnum) . '</h2>';
  }

  $output .= '<ul>';
  $output .= '<li class="button green"><a href="#" onclick="parent.document.location=(\'' . url('user/lists/' . $list_id) . '\')">Go to List</a></li>';
  $output .= '<li class="button red"><a href="#" onclick="parent.Lightbox.end(\'forceClose\')">Close this window</a></li>';
  $output .= '</ul>';

  return $output;
}

function sopac_list_move_top($list_id, $cur_pos) {
  global $user;
  $insurge = sopac_get_insurge();
  $insurge->move_list_item($list_id, $cur_pos, 1);
  drupal_set_message("Item moved to top of list");
  drupal_goto("user/lists/$list_id");
}

function sopac_list_confirm_delete(&$form_state, $list_id) {
  $form = array('#list_id' => $list_id);
  return confirm_form(
    $form,
    t('Delete List'),
    'user/lists',
    t('Are you sure you want to delete this list? The list and all items on it will be removed permanently. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'),
    'sopac_list_confirm_delete');
}

function sopac_list_confirm_delete_submit($form, &$form_state) {
  $insurge = sopac_get_insurge();
  if ($items = $insurge->get_list_items($form['#list_id'])) {
    // Delete all tags with the list id from insurge
    foreach($items as $item) {
      $insurge->delete_user_tag($item['uid'], $item['tag'], $item['bnum']);
    }
  }
  db_query("DELETE FROM sopac_lists WHERE list_id = %d", $form['#list_id']);
  drupal_set_message(t('The list has been deleted.'));
  drupal_goto('user/lists');
}

function sopac_list_confirm_item_delete(&$form_state, $list_id, $bnum) {
  $insurge = sopac_get_insurge();
  $items = $insurge->get_list_items($list_id);
  $item = array();

  foreach($items as $i) {
    if ($i['bnum'] == $bnum) {
      $item = $i;
      break;
    }
  }
  if ($item['bnum']) {
    $form = array('#list_id' => $list_id, '#item' => $item);
    return confirm_form(
      $form,
      t('Delete Item'),
      "user/lists/$list_id",
      t('Are you sure you want to delete this item from the list? This action cannot be undone.'),
      t('Delete'),
      t('Cancel'),
      'sopac_list_confirm_item_delete');
  }
  else {
    drupal_set_message('Cannot find the specified item on this list', 'error');
    drupal_goto("user/lists/$list_id");
  }
}

function sopac_list_confirm_item_delete_submit($form, &$form_state) {
  $insurge = sopac_get_insurge();
  $insurge->delete_list_item($form['#list_id'], $form['#item']['value']);

  drupal_set_message(t('The item has been removed from the list.'));
  drupal_goto('user/lists/' . $form['#list_id']);
}

function theme_sopac_list($list, $expanded = FALSE) {
  global $user;
  $title = ($expanded ? $list['title'] : l($list['title'], 'user/lists/' . $list['list_id']));
  $top .= '<div class="sopac-list">';

  if ($user->uid == $list['uid'] || user_access('administer sopac')) {
    $top .= '<ul class="sopac-list-actions">';
    $top .= '<li class="button green">' . l('Edit List Details', 'user/lists/edit/' . $list['list_id']) . '</li>';
    $top .= '<li class="button red">' . l('Delete List', 'user/lists/delete/' . $list['list_id']) . '</li>';
    $top .= '</ul>';
  }

  $top .= "<h2>$title</h2>";
  $top .= '<p class="sopac-list-details">';
  $top .= '<strong>Description</strong>: ' . $list['description'] . '<br />';
  $top .= ($list['public'] ? 'This list is <strong>publicly viewable</strong>' : 'This list is <strong>private</strong>') . '<br />';

  if ($expanded) {
    $locum = sopac_get_locum();
    $formats = $locum->locum_config['formats'];
    $no_circ = $locum->csv_parser($locum_cfg['location_limits']['no_request']);
    $avail_count = 0;

    if ($list_count = count($list['items'])) {
      include_once('sopac_catalog.php');
      $output .= '<table class="hitlist-content">';
      foreach($list['items'] as $item) {
        // Grab item status
        $item['status'] = $locum->get_item_status($item['bnum']);
        if ($item['status']['avail']) {
          $avail_count++;
        }
        // Grab Syndetics reviews, etc..
        $review_links = $locum->get_syndetics($item['stdnum']);
        if (count($review_links)) {
          $item['review_links'] = $review_links;
        }
        $output .= theme('sopac_results_hitlist', $item['value'], $item['cover_img'], $item, $locum->locum_config, $no_circ);
      }
      $output .= '</table>';

      $sortopts = array(
        'value' => t('Default Order'),
        'title' => t('Title'),
        'author' => t('Author'),
        'date' => t('Date Added - Oldest'),
        'date_newest' => t('Date Added - Newest'),
        'mat_code' => t('Material Type'),
      );
      $top .= '<div class="hitlist-range">';
      $top .= "<span class=\"range\">Showing <strong>$list_count</strong> items ( <strong>$avail_count</strong> currently available" ;
      if ($avail_count > 0) {
        $top .= ' - <span id="showavailable">Show Me</span> ';
      }
      $top .= ' )</span>';
      $top .= '<span class="hitlist-sorter">';
      $top .= '<script>';
      $top .= 'jQuery(document).ready(function() {$(\'#sortlist\').change(function(){ location.href = $(this).val();});});';
      $top .= '</script>';
      $top .= 'Sort by: <select name="sort" id="sortlist">';
      foreach($sortopts as $key => $value) {
        $top .=  '<option value="' . url($_GET['q'], array('query' => array('sort' => $key))) . '" ';
        if ($_GET['sort'] == $key) {
          $top .=  'selected';
        }
        $top .= '>' . $value . '</option>';
      }
      $top .= '</select>';
      $top .= '</span>';
      $top .= '</div>';
    }
    else {
      $output .= '<p>This list is currently empty</p>';
    }
    $output .= '<ul><li class="button green">' . l('back to lists overview', 'user/lists') . '</li></ul>';
  }
  else {
    // overview
    if ($list_count = count($list['items'])) {
      $top .= "<strong>$list_count</strong> items listed</p>";
    }
    else {
      $top .= 'This list is currently <strong>empty</strong></p>';
    }
  }
  $top .= '<div style="clear: both"></div>';
  $output .= '</div><div style="clear:both"></div>';
  return $top . $output;
}

function sopac_put_list_links($bnum, $list_display = FALSE) {
  global $user;
  $insurge = sopac_get_insurge();
  $bnum = intval($bnum);
  $lists = array();
  $action_text = ($list_display ? "Copy to" : "Add to");

  $output .= "<li class=\"button hassub\">$action_text other list";
  $output .= '<span></span>';
  $output .= "<ul class=\"submenu\" id=\"moreact_$bnum\">";
  $output .= '<li>Add to:</li>';

  $res = db_query("SELECT * FROM {sopac_lists} WHERE uid = %d", $user->uid);
  while ($list = db_fetch_array($res)) {
    // Check if item is already in the list
    $in_list = FALSE;
    foreach($insurge->get_list_items($list['list_id']) as $list_item) {
      if ($list_item['bnum'] == $bnum) {
        $in_list = TRUE;
        break;
      }
    }

    if ($list['title'] == 'Wishlist') {
      if ($in_list) {
        $wishlist = '<li class="button">Already on Wishlist</li>';
      }
      else {
        $wishlist = '<li class="button green">' .
                    l($action_text . ' Wishlist', 'user/listadd/' . $bnum . '/' . $list['list_id'],
                      array('query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe'))) .
                    '</li>';
      }
    }
    else {
      $output .= '<li';
      if ($in_list) {
        $output .= ' class="disabled">' . $list['title'];
      }
      else {
        $output .= '>' . l($list['title'], 'user/listadd/' . $bnum . '/' . $list['list_id'],
                         array('query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe')));
      }
      $output .= '</li>';
    }
  }
  if (empty($wishlist)) {
    $wishlist = '<li class="button green">' . l($action_text . ' Wishlist', 'user/listadd/' . $bnum . '/wish') . '</li>';
  }

  $output .= '<li>' . l('» Add to new list...', 'user/lists/edit', array('query' => array('bnum' => $bnum))) . '</li>';
  $output .= '</ul>';
  $output .= '</li>';

  return $wishlist . $output;
}

function sopac_import_cc($list_id, $uid) {
  $insurge = sopac_get_insurge();
  $res = db_query("SELECT DISTINCT bnum FROM sopac_cc_savedcards WHERE uid = '%d' ORDER BY id ASC", $uid);
  while ($item = db_fetch_object($res)) {
    $insurge->add_list_item($uid, $list_id, $item->bnum);
    //drupal_set_message("Added $item->bnum to list $list_id");
  }
}

function sopac_create_pcc_lists() {
	$user_count = 0;
	$res = db_query("SELECT DISTINCT uid FROM sopac_cc_savedcards");
	while ($pcc_user = db_fetch_object($res)) {
		$user_count++;
		// Create a new list for this user
		db_query("INSERT INTO {sopac_lists} (list_id, uid, title, description, public) VALUES (NULL, '%d', '%s', '%s', '%d')",
							$pcc_user->uid, 'Personal Card Catalog List', 'Records imported from the old Personal Card Catalog function', 0);
    $list_id = db_last_insert_id('sopac_lists', 'list_id');
		// import all records for this user into the list
		sopac_import_cc($list_id, $pcc_user->uid);
	}
	drupal_set_message("Created PCC Lists for $user_count users");
}
