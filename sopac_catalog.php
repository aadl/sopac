<?php
/**
 * SOPAC is The Social OPAC: a Drupal module that serves as a wholly integrated web OPAC for the Drupal CMS
 * This file contains the Drupal include functions for all the catalog functions within SOPAC
 * This file is called via hook_menu
 *
 * @package SOPAC
 * @version 2.1
 * @author John Blyberg
 */



/**
 * Prepares and returns the HTML for the SOPAC search page/hit list.
 * Uses the following templates: sopac_results.tpl.php, sopac_results_hitlist.tpl.php, sopac_results_nohits.tpl.php
 *
 * @return string SOPAC catalog search HTML
 */
function sopac_catalog_search() {
  global $pager_page_array, $pager_total, $locum_results_all, $locum_cfg;

  // Load Required JS libraries
  drupal_add_js(drupal_get_path('module', 'sopac') .'/js/jquery.treeview.js');
  drupal_add_js(drupal_get_path('module', 'sopac') .'/js/jquery.rating.js');
  drupal_add_js(drupal_get_path('module', 'sopac') .'/js/facet-browser.js');
  require_once('sopac_social.php');
  $getvars = sopac_parse_get_vars();
  $actions = sopac_parse_uri();
  $locum = sopac_get_locum();
  $locum_cfg = $locum->locum_config;
  $no_circ = $locum->csv_parser($locum_cfg['location_limits']['no_request']);
  $valid_search_types = array('title', 'author', 'keyword', 'subject', 'series', 'callnum', 'tags', 'reviews'); // TODO handle this more dynamically

  $sort = $getvars['sort'];
  $format = $getvars['search_format'];
  $location = $getvars['location'];
  $limit_avail = $getvars['limit_avail'];
  $pager_page_array = explode(',', $getvars['page']);
  $search_type = $actions[1];
  $search_term = utf8_urldecode($actions[2]);

  // Begin thinking about RSS
  $hitlist_template = ($getvars['output'] == 'rss') ? 'sopac_results_hitlist_rss' : 'sopac_results_hitlist';

  // If there is a proper search query, we get that data here.
  if (in_array($actions[1], $valid_search_types)) {
    $valid_search = TRUE;

    // Save the search URL in a cookie
    $_SESSION['search_url'] = request_uri();

    if ($getvars['perpage']) {
      $limit = $getvars['perpage'];
    }
    elseif ($user->profile_perpage) {
      $limit = $user->profile_perpage;
    }
    else {
      $limit = variable_get('sopac_results_per_page', 10);
    }

    /* Not implemented yet
    if ($user->uid && $limit != $user->profile_perpage) {
      $field = db_fetch_object(db_query("SELECT * FROM profile_fields WHERE name = 'profile_perpage'"));
      db_query("INSERT INTO profile_values (fid, uid, value) VALUES (%d, %d, '%s') ON DUPLICATE KEY UPDATE value = '%s'", $field->fid, $user->uid, $limit, $limit);
    }
    */

    //if ($addl_search_args['limit']) {
    //  $limit = $addl_search_args['limit'];
    //}
    //else {
    //  $limit = variable_get('sopac_results_per_page', 20);
    //}

    // Initialize the pager if need be
    if ($pager_page_array[0]) {
      $page = $pager_page_array[0] + 1;
    }
    else {
      $page = 1;
    }
    $page_offset = $limit * ($page - 1);

    // Grab the faceted search arguments from the URL
    $facet_args = array();
    if (count($getvars['facet_series'])) {
      $facet_args['facet_series'] = $getvars['facet_series'];
    }
    if (count($getvars['facet_lang'])) {
      $facet_args['facet_lang'] = $getvars['facet_lang'];
    }
    if (count($getvars['facet_year'])) {
      $facet_args['facet_year'] = $getvars['facet_year'];
    }
    if (count($getvars['facet_decade'])) {
      $facet_args['facet_decade'] = $getvars['facet_decade'];
    }
    if (count($getvars['age'])) {
      $facet_args['age'] = $getvars['age'];
    }
    if (count($getvars['facet_subject'])) {
      $facet_args['facet_subject'] = $getvars['facet_subject'];
    }

    // Get the search results from Locum
    $locum_results_all = $locum->search($search_type, $search_term, $limit, $page_offset, $sort, $format, $location, $facet_args, FALSE, $limit_avail);
    $num_results = $locum_results_all['num_hits'];
    $result_info['limit'] = $limit;
    $result_info['num_results'] = $num_results;
    $result_info['hit_lowest'] = $page_offset + 1;
    if (($page_offset + $limit) < $num_results) {
      $result_info['hit_highest'] = $page_offset + $limit;
    }
    else {
      $result_info['hit_highest'] = $num_results;
    }
  }

  // Construct the search form
  $search_form_cfg = variable_get('sopac_search_form_cfg', 'both');
  $search_form = sopac_search_form($search_form_cfg);

  // If we get results back, we begin creating the hitlist
  if ($num_results > 0) {
    // We need to determine how many result pages there are.
    $pager_total[0] = ceil($num_results / $limit);
    $hitlist = '';
    $hitnum = $page_offset + 1;

    // When limiting to available, sometimes the "Last" link takes the user beyond the number of
    // available items and errors out.  This will step them back until they have at least 1 hit.
    if (!count($locum_results_all['results']) && $getvars['limit_avail']) {
      $uri_arr = explode('?', request_uri());
      $uri = $uri_arr[0];
      $getvars_tmp = $getvars;
      if ($getvars_tmp['page']) {
        if ($getvars_tmp['page'] == 1) {
          $getvars_tmp['page'] = '';
        }
        else {
          $getvars_tmp['page']--;
        }
        $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
        $gvar_indicator = $pvars_tmp ? '?' : '';
        $step_link = $uri . $gvar_indicator . $pvars_tmp;
        header('Location: ' . $step_link);
      }
    }

    // Loop through results.
    foreach ($locum_results_all['results'] as $locum_result) {

      // Grab Stdnum
      $stdnum = $locum_result['stdnum'];

      // Grab item status from Locum
      $locum_result['status'] = $locum->get_item_status($locum_result['bnum']);

      // Get the cover image
      $cover_img_url = $locum_result['cover_img'];

      // Grab Syndetics reviews, etc..
      $review_links = $locum->get_syndetics($locum_result['stdnum']);
      if (count($review_links)) {
        $locum_result['review_links'] = $review_links;
      }

      // Send it all off to the template
      $result_body .= theme($hitlist_template, $hitnum, $cover_img_url, $locum_result, $locum_cfg, $no_circ);
      $hitnum++;
    }

    $hitlist_pager = theme('pager', NULL, $limit, 0, NULL, 6);
  }
  elseif ($valid_search) {
    $result_body .= theme('sopac_results_nohits', $locum_results_all, $locum->locum_config);
  }

  // Pull it all together into the search page template
  $result_page = $search_form . theme($output_template, $result_info, $hitlist_pager, $result_body, $locum_results_all, $locum->locum_config);

  // Check to see if we're doing RSS
  if ($getvars['output'] == 'rss') {
    print theme('sopac_results_rss', $result_info, $search_term, $search_type, $result_body, $locum_results_all, $locum->locum_config);
    exit(0);
  }
  else {
    $result_page = $search_form . theme('sopac_results', $result_info, $hitlist_pager, $result_body, $locum_results_all, $locum->locum_config);
  }

  $search_feed_url = sopac_update_url(request_uri(), 'output', 'rss');
  drupal_add_feed($search_feed_url, 'Search for "' . $search_term . '"');

  return '<p>'. t($result_page) .'</p>';

}


/**
 * Prepares and returns the HTML for an item record.
 * Uses the following templates: sopac_record.tpl.php
 *
 * @return string Item record HTML
 */
function sopac_bib_record() {
  global $user;

  $locum = sopac_get_locum();
  $insurge = sopac_get_insurge();
  $actions = sopac_parse_uri();
  $bnum = $actions[1];

  // Load social function
  require_once('sopac_social.php');

  $bnum_arr[] = $bnum;
  $reviews = $insurge->get_reviews(NULL, $bnum_arr, NULL);
  $i = 0;
  foreach ($reviews['reviews'] as $insurge_review) {
    $rev_arr[$i]['rev_id'] = $insurge_review['rev_id'];
    $rev_arr[$i]['bnum'] = $insurge_review['bnum'];
    if ($insurge_review['uid']) {
      $rev_arr[$i]['uid'] = $insurge_review['uid'];
    }
    $rev_arr[$i]['timestamp'] = $insurge_review['rev_create_date'];
    $rev_arr[$i]['rev_title'] = $insurge_review['rev_title'];
    $rev_arr[$i]['rev_body'] = $insurge_review['rev_body'];
    $i++;
  }

  $no_circ = $locum->csv_parser($locum->locum_config['location_limits']['no_request']);
  $item = $locum->get_bib_item($bnum, TRUE);
  $item_status = $locum->get_item_status($bnum);
  if ($item['bnum']) {
    // Load javascript collapsible code
    drupal_add_js('misc/collapse.js');

    // Grab Syndetics reviews, etc..
    $review_links = $locum->get_syndetics($item['stdnum']);
    if (count($review_links)) {
      $item['review_links'] = $review_links;
    }

    // Get and patron reviews
    if (!$insurge->check_reviewed($user->uid, $item['bnum']) && $user->uid) {
      $rev_form = drupal_get_form('sopac_review_form', $item['bnum']);
    }
    else {
      $rev_form = NULL;
    }

    // Build the page
    $result_page = theme('sopac_record', $item, $item_status, $locum->locum_config, $no_circ, &$locum, $rev_arr, $rev_form);
  }
  else {
    $result_page = t('This record does not exist.');
  }

  return '<p>'. t($result_page) .'</p>';
}

/**
 * Formulates and returns the search tracker block HTML.
 * Uses the following templates: sopac_search_block.tpl.php
 *
 * @return string Search tracker block HTML
 */
function sopac_search_block($locum_results_all, $locum_cfg) {
  global $user;

  $getvars = sopac_parse_get_vars();
  $uri = sopac_parse_uri();
  $format = $getvars['search_format'];
  $term_arr = explode('?', trim(preg_replace('/\//', ' ', $uri[2])));

  $search['term'] = trim($term_arr[0]);
  $search['type'] = trim($uri[1]);
  $search['sortby'] = $getvars['sort'] ? $getvars['sort'] : t('Most relevant');
  $search['format'] = count($getvars['search_format']) && ($getvars['search_format'][0] != 'all') ? $getvars['search_format'] : array();
  $search['series'] = count($getvars['facet_series']) ? $getvars['facet_series'] : array();
  $search['lang'] = count($getvars['facet_lang']) ? $getvars['facet_lang'] : array();
  $search['year'] = count($getvars['facet_year']) ? $getvars['facet_year'] : array();
  $search['decade'] = count($getvars['facet_decade']) ? $getvars['facet_decade'] : array();
  $search['age'] = count($getvars['age']) ? $getvars['age'] : array();
  $search['subject'] = count($getvars['facet_subject']) ? $getvars['facet_subject'] : array();

  return theme('sopac_search_block', $search, $locum_results_all, $locum_cfg, $user);

}

/**
 * If nothing is in the author field, we try the addl author field.
 * Oh, and we present first name first.  Like it oughta.
 *
 * @param string $author Author string as presented up from Locum
 * @param string $addl_author_ser Serialized additional author string as presented up from Locum
 * @return string The formatted author string
 */
function sopac_author_format($author, $addl_author_ser) {

  if ($author) {
    $author_arr = explode(',', $author);
    $new_author_str = trim($author_arr[1]) . ' ' . trim($author_arr[0]);
  }
  elseif ($addl_author_ser) {
    $addl_author = unserialize($addl_author_ser);
    if ($addl_author[0]) {
      $author_arr = explode(',', $addl_author[0]);
      $new_author_str = trim($author_arr[1]) . ' ' . trim($author_arr[0]);
    }
  }
  if ($new_author_str) {
    //$new_author_str = ereg_replace("[^A-Za-z\x20-\x7F '.-]", '', $new_author_str );
    $new_author_str = preg_replace('/ - /', ' ', $new_author_str);
  }
  else {
    $new_author_str = '';
  }

  return $new_author_str;
}

/**
 * Create the "Did you mean" link
 *
 * @param array $locum_result Locum result array as passed up from Locum
 * @return string Suggestion link
 */
function suggestion_link($locum_result) {
  $url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
  $sugg_link = l($locum_result['suggestion'], $url_prefix . '/search/' . $locum_result['type'] . '/' . $locum_result['suggestion'],
                 array('query' => sopac_make_pagevars(sopac_parse_get_vars())));
  return $sugg_link;
}

/**
 * This function will return the appropriate request link based on whether the user is logged in, has a verified card, or not
 *
 * @return string HTML string for the request link
 */
function sopac_put_request_link($bnum, $avail = 0, $holds = 0, $mattype = 'item') {
  if (variable_get('sopac_catalog_disabled', 0)) {
    $text = 'Requesting Currently Disabled';
  } else {
    global $user;
    $class = 'button green';
    profile_load_profile(&$user);

    if ($user->uid) {
      if (sopac_bcode_isverified(&$user)) {
        // User is logged in and has a verified card number
        $text = "Request this";
        if (variable_get('sopac_multi_branch_enable', 0)) {
          $locum = sopac_get_locum();
          $branches = $locum->locum_config['branches'];

          $class .= ' hassub';

          $text .= "<ul class=\"submenu\"><li>for pickup at</li>";
          $text .= '<li>' .
                   l($user->profile_pref_home_branch,
                     variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $user->profile_pref_home_branch,
                     array('query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe'))) .
                   '</li>';
          $text .= '<li>Other Location...</li>';
          foreach ($branches as $branch) {
            if ($branch != $user->profile_pref_home_branch) {
              $text .= '<li>' .
                       l($branch,
                         variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $branch) .
                       '</li>';
            }
          }
          $text .= "</ul><span></span>";
        }
        else {
          $text = l($text, variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum);
        }
      }
      elseif ($user->profile_pref_cardnum) {
        // User is logged in but does not have a verified card number
        $text = l(t('Verify card to request'), 'user/' . $user->uid);
      }
      else {
        // User is logged in but does not have a card number.
        $text = l(t('Register card to request'), 'user/' . $user->uid);
      }
    }
    else {
      $text = l(t('Log in to request'), 'user/login', array('query' => drupal_get_destination() . '&lightbox=1',
                                                            'attributes' => array('rel' => 'lightframe')));
    }

    return "<li class=\"$class\">$text</li>";
  }
}

/**
 * Build a form to request a hold, including where to pick up item. Uses branches config info from locum.
 *
 * @param array $form_state
 * @param string $bnum
 * @return array
 */
function sopac_multibranch_hold_request(&$form_state, $bnum = null) {
  global $user;
  $locum = sopac_get_locum('locum');
  $locum_cfg = $locum->locum_config;
  $options = $locum_cfg['branches'];

  $form = array(
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['hold_location'] = array(
    '#type' => 'select',
    '#title' => '» ' . t('Request this item for pickup at'),
    '#options' => $options,
  );
  if (isset($user->profile_pref_home_branch)) {
    $options = array_flip($options);
    if (array_key_exists($user->profile_pref_home_branch, $options)) {
      $form['hold_location']['#default_value'] = $options[$user->profile_pref_home_branch];
    }
  }
  $form['bnum'] = array(
    '#type' => 'hidden',
    '#default_value' => $bnum,
  );
  $form['op'] = array(
    '#type' => 'submit',
    '#value' => t('Request Hold'),
  );

  return $form;
}

/**
 * Reject hold requests from invalid users
 *
 * @param array $form
 * @param array $form_state
 */
function sopac_multibranch_hold_request_validate($form, &$form_state) {
  global $user;
  profile_load_profile($user);
  profile_load_profile(&$user);

  if ($user->uid) {
    if (sopac_bcode_isverified(&$user)) {
      return;
    }
    else {
      form_set_error(NULL, t('Verify your card to request this item'));
    }
  }
  else {
    form_set_error(NULL, t('Please login to request a hold.'));
  }
  drupal_goto($form_state['values']['destination']);
}

/**
 * Parse submitted form request, convert, and go to url.
 *
 * @param array $form
 * @param array $form_state
 */
function sopac_multibranch_hold_request_submit($form, &$form_state) {
  $location_name = $form['hold_location']['#options'][$form_state['values']['hold_location']];
  $bnum = $form_state['values']['bnum'];
  drupal_goto('catalog/request/' . $bnum . '/' . $form_state['values']['hold_location'] . '/' . $location_name);
}

/**
 * Returns the search URL, only if the user is coming directly from the search page.
 *
 * @return string|bool Search URL or FALSE
 */
function sopac_prev_search_url($override = FALSE) {
  if (!$_SESSION['search_url']) { return FALSE; }
  $referer = substr($_SERVER['HTTP_REFERER'], 7 + strlen($_SERVER['HTTP_HOST']));
  $search = $_SESSION['search_url'];
  if ((($search == $referer) || $override) && $_SESSION['search_url']) {
    return $search;
  }
  else {
    return FALSE;
  }
}

/**
 * Requests a particular item via locum then displays the results of that request
 *
 * @return string Request result
 */
function sopac_request_item() {
  global $user;
  // avoid php errors when debugging
  $varname = $request_result_msg = $request_error_msg = $item_form = $bnum = NULL;

  $button_txt = t('Request Selected Item');
  profile_load_profile(&$user);
  if ($user->uid && sopac_bcode_isverified(&$user)) {
    // support multi-branch & user home branch
    $actions = sopac_parse_uri();
    $bnum = $actions[1];
    $pickup_arg = $actions[2] ? $actions[2] : NULL;
    $pickup_name = $actions[3] ? $actions[3] : NULL;
    $locum = sopac_get_locum();
    $bib_item = $locum->get_bib_item($bnum);
    $hold_result = $locum->place_hold($user->profile_pref_cardnum, $bnum, $varname, $user->locum_pass, $pickup_arg);
    if ($hold_result['success']) {
      // handling multi-branch scenario
      $request_result_msg = t('You have successfully requested a copy of ') . '<span class="req_bib_title"> ' . $bib_item['title'] . '</span>';
      if ($pickup_name) {
        $request_result_msg .= t(' for pickup at ') . $pickup_name;
      }
    }
    else if (count($hold_result['choose_location'])) {
      $request_result_msg = t('Please select a pickup location for your request:');
      $locum = sopac_get_locum();
      foreach ($locum->locum_config['branches'] as $branch) {
        $link = l('<span>' . t('Request this item for pickup at ') . $branch . '</span>',
                  variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $branch,
                  array('html' => TRUE, 'attributes' => array('class' => 'button')));
        $request_result_msg .= '<div class="item-request">' . $link . '</div>';
      }
    }
    else {
      $request_result_msg = t('We were unable to fulfill your request for ') . '<span class="req_bib_title">' . $bib_item['title'] . '</span>';
    }

    if ($hold_result['error']) {
      $request_result_msg = $hold_result['error'];
    }

    if ($hold_result['selection']  && !$hold_result['success']) {
      $requestable = 0;
      $header = array('', t('Location'), t('Call Number'), t('Status'));
      foreach ($hold_result['selection'] as $selection) {
        $status = $selection['status'];
        if ($selection['varname']) {
          $radio = '<input type="radio" name="varname" value="' . $selection['varname'] . '">';
          $non_circ = NULL;
          $requestable++;
        }
        else {
          $radio = '';
          $status = '<span class="non_circ_msg">' . $status . '</span>';
        }
        $rows[] = array(
          $radio,
          $selection['location'],
          $selection['callnum'],
          $status,
        );
      }
      if ($requestable) {
        $submit_button = '<input type="submit" name="sub_type" value="' . $button_txt . '">';
        $request_result_msg = t('Please select the item you would like to request.');
      }
      else {
        $submit_button = NULL;
        $request_result_msg = '';
        $request_error_msg = t('There are no copies of this item available for circulation.');
      }
      if ($submit_button){
        $rows[] = array( 'data' => array(array('data' => $submit_button, 'colspan' => 4)), 'class' => 'req_button' );
      }
      $item_form = '<form method="post">' . theme('table', $header, $rows, array('id' => 'reqlist', 'cellspacing' => '0')) . '</form>';
    }

    // TODO - add a tally for top items data recovery
  }
  else {
    $request_error_msg = t("You must have a valid library card number registered with our system.");
  }
  $result_page = theme('sopac_request', $request_result_msg, $request_error_msg, $item_form, $bnum);
  return '<p>'. t($result_page) .'</p>';
}

/**
 * Returns the save search link.
 *
 * @return string link
 */
function sopac_savesearch_link() {
  $search_link = l(t('Save this search'), str_replace('/search/', '/savesearch/', $_GET['q']),
                   array('query' => sopac_make_pagevars(sopac_parse_get_vars())));
  return $search_link;
}

/**
 * Formulates the basic search form array
 *
 * @return array Drupal search form array
 */
function sopac_search_form_basic() {

  $locum = sopac_get_locum('locum');
  $locum_cfg = $locum->locum_config;
  $getvars = sopac_parse_get_vars();

  $actions = sopac_parse_uri();
  if ($actions[0] == "search") {
    if ($actions[3]) {
      utf8_urldecode($actions[2]);
      $actions[2] = $actions[2] . "/" . $actions[3];
      urlencode($actions[2]);
    }
    $search_query = $actions[2];
    $stype_selected = $actions[1] ? 'cat_' . $actions[1] : 'cat_keyword';
  }
  $sformats = array('' => 'Everything');
  foreach ($locum_cfg[format_groups] as $sfmt => $sfmt_codes) {
    $sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
  }

  $stypes = array(
    'cat_keyword' => t('Keyword'),
    'cat_title' => t('Title'),
    'cat_author' => t('Author'),
    'cat_series' => t('Series'),
    'cat_tags' => t('Tags'),
    'cat_reviews' => t('Reviews'),
    'cat_subject' => t('Subject'),
    'cat_callnum' => t('Call Number'),
  );

  $sortopts = array(
    'relevance' => t('Relevance'),
    'newest' => t('Newest First'),
    'oldest' => t('Oldest First'),
  );

  $sformats = array('' => 'Everything');
  foreach ($locum_cfg['format_groups'] as $sfmt => $sfmt_codes) {
    $sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
  }
  if (is_null($prompt)) {
    $prompt = t('Enter your keywords');
  }

  // Initialize the form
  $form = array(
    '#attributes' => array('class' => 'search-form'),
    '#validate' => array('sopac_search_catalog_validate'),
    '#submit' => array('sopac_search_catalog_submit'),
  );

  // Start creating the basic search form
  $form['basic'] = array('#type' => 'item');
  $form['basic']['inline'] = array('#prefix' => '<div class="container-inline">', '#suffix' => '</div>');
  $form['basic']['inline']['search_query'] = array(
    '#type' => 'textfield',
    '#title' => t('Search '),
    '#default_value' => $search_query,
    '#size' => 25,
    '#maxlength' => 255,
  );
  $form['basic']['inline']['search_type'] = array(
    '#type' => 'select',
    '#title' => t(' by '),
    '#default_value' => $stype_selected,
    '#options' => $stypes,
  );
  $form['basic']['inline']['search_format'] = array(
    '#type' => 'select',
    '#title' => t(' in '),
    '#default_value' => $_GET['search_format'],
    '#selected' => $_GET['search_format'],
    '#options' => $sformats,
  );

  $form['basic']['inline']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Search')
  );

  if (variable_get('sopac_multi_branch_enable', 0)) {
    $form['basic']['limit']['limit'] = array(
      '#prefix' => '<div class="basic-search-inline"><div class="container-inline">',
      '#type' => 'checkbox',
      '#default_value' => $getvars['limit_avail'] ? 1 : 0,
    );

    $form['basic']['limit']['limit_avail'] = array(
      '#type' => 'select',
      '#title' => t('limit to items available at'),
      '#options' => array_merge(array('any' => t('Any Location')), $locum_cfg['branches']),
      '#default_value' => $getvars['limit_avail'],
      '#suffix' => "</div></div>",
    );
  }
  else {
    $form['basic']['limit']['limit'] = array(
      '#prefix' => '<div class="basic-search-inline"><div class="container-inline">',
      '#type' => 'checkbox',
      '#title' => '<strong>' . t('limit to available items') . '</strong>',
      '#default_value' => $getvars['limit_avail'] ? 1 : 0,
      '#suffix' => "</div></div>",
    );
  }

  return $form;

}

/**
 * Formulates the advanced search form array
 *
 * @return array Drupal search form array
 */
function sopac_search_form_adv() {

  $locum = sopac_get_locum('locum');
  $locum_cfg = $locum->locum_config;
  $getvars = sopac_parse_get_vars();

  $actions = sopac_parse_uri();
  $search_args_raw = explode('?', $actions[2]);
  $search_args = trim($search_args_raw[0]);
  $stype_selected = $actions[1] ? 'cat_' . $actions[1] : 'cat_keyword';
  $sformat_selected = $_GET['search_format'] ? $_GET['search_format'] : NULL;
  foreach ($locum_cfg['format_groups'] as $sfmt => $sfmt_codes) {
    $sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
  }

  $stypes = array(
    'cat_keyword' => t('Keyword'),
    'cat_title' => t('Title'),
    'cat_author' => t('Author'),
    'cat_series' => t('Series'),
    'cat_tags' => t('Tags'),
    'cat_reviews' => t('Reviews'),
    'cat_subject' => t('Subject'),
    'cat_callnum' => t('Call Number'),
  );

  $sortopts = array(
    '' => t('Relevance'),
    'atoz' => t('Alphabetical A to Z'),
    'ztoa' => t('Alphabetical Z to A'),
    'catalog_newest' => t('Just Added'),
    'newest' => t('Pub date: Newest'),
    'oldest' => t('Pub date: Oldest'),
    'author' => t('Alphabetically by Author'),
    'top_rated' => t('Top Rated Items'),
    'popular_week' => t('Most Popular this Week'),
    'popular_month' => t('Most Popular this Month'),
    'popular_year' => t('Most Popular this Year'),
    'popular_total' => t('All Time Most Popular'),
  );

  // Initialize the form
  $form = array(
    '#attributes' => array('class' => 'search-form'),
    '#validate' => array('sopac_search_catalog_validate'),
    '#submit' => array('sopac_search_catalog_submit'),
  );

  // Start creating the advanced search form
  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Click for advanced search'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#attributes' => array('class' => 'search-advanced'),
  );
  $form['advanced']['keywords'] = array(
    '#prefix' => '<div class="adv_search_crit">',
    '#suffix' => '</div>',
  );
  $form['advanced']['keywords']['search_query'] = array(
    '#type' => 'textfield',
    '#title' => t('Search term or phrase'),
    '#default_value' => $search_args,
    '#size' => 20,
    '#maxlength' => 255,
  );
  $form['advanced']['keywords']['search_type'] = array(
    '#type' => 'select',
    '#title' => t('Search by'),
    '#default_value' => $stype_selected,
    '#options' => $stypes,
  );
  $form['advanced']['keywords']['sort'] = array(
    '#type' => 'select',
    '#title' => t('Sorted by'),
    '#default_value' => $getvars['sort'],
    '#options' => $sortopts,
  );

  $age_options = array_merge(array('' => 'Any Age Group'), $locum_cfg['ages']);
  unset($age_options['all']);
  $form['advanced']['keywords']['age'] = array(
    '#type' => 'select',
    '#title' => 'in age group',
    '#options' => $age_options,
  );

  $form['advanced']['narrow1'] = array(
    '#prefix' => '<div class="adv_search_crit">',
    '#suffix' => '</div>',
  );

  /* Have not yet implemented collections, and in a number of ways, multi-branch has replaced it
  if (count($locum_cfg['collections'])) {

    foreach ($locum_cfg['collections'] as $loc_collect_key => $loc_collect_var) {
      $loc_collect[$loc_collect_key] = $loc_collect_key;
    }

    $form['advanced']['narrow1']['collection'] = array(
      '#type' => 'select',
      '#title' => t('In these collections'),
      '#size' => 5,
      '#value' => $getvars['collection'],
      '#options' => $loc_collect,
      '#multiple' => TRUE,
    );
  }

  $form['advanced']['narrow1']['location'] = array(
    '#type' => 'select',
    '#title' => t('In these locations'),
    '#size' => 5,
    '#value' => $getvars['location'],
    '#options' => $locum_cfg['locations'],
    '#multiple' => TRUE,
  );

  */

  $form['advanced']['narrow1']['search_format'] = array(
    '#type' => 'select',
    '#title' => t('In these formats'),
    '#size' => 5,
    '#default_value' => $getvars['search_format'],
    '#options' => $locum_cfg['formats'],
    '#multiple' => TRUE,
  );

  if (variable_get('sopac_multi_branch_enable', 0)) {
    $form['advanced']['limit'] = array(
      '#prefix' => '<div class="action"><div class="adv-search-inline"><div class="container-inline">',
      '#type' => 'checkbox',
      '#default_value' => $getvars['limit_avail'] ? 1 : 0,
    );
    $form['advanced']['limit_avail'] = array(
      '#type' => 'select',
      '#title' => 'limit to items available at',
      '#options' => array_merge(array('any' => "Any Location"), $locum_cfg['branches']),
      '#default_value' => $getvars['limit_avail'],
      '#suffix' => "</div></div>",
    );
  }
  else {
    $form['advanced']['limit'] = array(
      '#prefix' => '<div class="action"><div class="adv-search-inline"><div class="container-inline">',
      '#type' => 'checkbox',
      '#title' => '<strong>' . t('limit to available items') . '</strong>',
      '#default_value' => $getvars['limit_avail'] ? 1 : 0,
      '#suffix' => "</div></div>",
    );
  }

  $form['advanced']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Advanced search'),
//    '#prefix' => '<div class="action">',
//    '#suffix' => '</div>',
  );

  $form['advanced']['clear'] = array(
    '#name' => 'clear',
    '#type' => 'button',
    '#value' => t('Reset'),
    '#attributes' => array('onclick' => 'this.form.reset(); return false;'),
  //  '#prefix' => '<div class="action">',
    '#suffix' => '</div>',
  );

  return $form;
}

/**
 * validate on form submission
 */
function sopac_search_catalog_validate($form, &$form_state) {
  if (trim($form_state['values']['search_query']) == '') {
    form_set_error('search_query', t('Please enter a search term to start your search'));
  }
}

/**
 * build a search url based on form submission, handles both basic and advanced search forms
 */
function sopac_search_catalog_submit($form, &$form_state) {
  $locum = sopac_get_locum('locum');
  $locum_cfg = $locum->locum_config;

  $search_query = trim($form_state['values']['search_query']);
  if (!$search_query) {
    $search_query = '*';
  }
  $search_type = $form_state['values']['search_type'];
  $search_type_arr = explode('_', $search_type);
  if ($search_type_arr[0] == 'cat') {
    $search_type = $search_type_arr[1];
    $search_fmt = $search_type_arr[2];
    $search_url = variable_get('sopac_url_prefix', 'cat/seek') . '/search/' . $search_type . '/' . $search_query;

    // Material / Format types
    if ($search_fmt) {
      if ($search_fmt != 'all') {
        $uris['search_format'] = $locum->csv_parser($locum_cfg['format_groups'][$search_fmt], '|');
      }
    }
    elseif ($form_state['values']['search_format']) {
      if (is_array($form_state['values']['search_format'])) {
        $uris['search_format'] = trim(implode('|', $form_state['values']['search_format']));
      }
      else {
        $uris['search_format'] = $form_state['values']['search_format'];
      }
    }

    // Location selections overrule collection selections and act as
    // a filter if they are in a selection colection.
    if ($form_state['values']['collection']) {
      $locations = array();
      $uris['collection'] = trim(implode('|', $form_state['values']['collection']));
      foreach ($form_state['values']['collection'] as $collection) {
        $collection_arr = $locum->csv_parser($locum_cfg['collections'][$collection]);
        if ($form_state['values']['location']) {
          $valid_locs = array_intersect($form_state['values']['location'], $collection_arr);
          if (count($valid_locs)) {
            $locations = array_merge($locations, $valid_locs);
          }
          else {
            $locations = array_merge($locations, $collection_arr);
          }
        }
        else {
          $locations = array_merge($locations, $collection_arr);
        }
      }
      if ($form_state['values']['location']) {
        $locations = array_merge($locations, array_diff($form_state['values']['location'], $locations));
      }
    }
    elseif ($form_state['values']['location']) {
      $locations = $form_state['values']['location'];
    }
    if (count($locations)) {
      $uris['location'] = trim(implode('|', $locations));
    }

    // Sort variable
    if ($form_state['values']['sort']) {
      $uris['sort'] = $form_state['values']['sort'];
    }

    // Age Group variable
    if ($form_state['values']['age']) {
      $uris['age'] = $form_state['values']['age'];
    }

    // Limit to Available
    if ($form_state['values']['limit_avail'] || $form_state['values']['limit']) {
      if (variable_get('sopac_multi_branch_enable', 0)) {
        if ($form_state['values']['limit_avail'] && $form_state['values']['limit']) {
          $uris['limit_avail'] = $form_state['values']['limit_avail'];
        }
      }
      else {
        $uris['limit_avail'] = 'any';
      }
    }

    // Publisher Search
    if ($form_state['values']['publisher']) {
      $uris['pub'] = trim($form_state['values']['publisher']);
    }

    // Publication date ranges
    if ($form_state['values']['pub_year_start'] || $form_state['values']['pub_year_end']) {
      $uris['pub_year'] = trim($form_state['values']['pub_year_start']) . '|' .
                          trim($form_state['values']['pub_year_end']);
    }
  }
  elseif ($search_type_arr[0] == 'web') {
    switch ($search_type_arr[1]) {
      case 'local':
        $search_url = 'search/node/' . utf8_urldecode($search_query);
        break;
      case 'google':
        $search_url = 'http://www.google.com/search?hl=en&q=' . utf8_urldecode($search_query);
        break;
    }
  }

  drupal_goto($search_url, $uris);
}