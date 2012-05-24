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
  global $user;
  $account = user_load(array('uid' => $user->uid));
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

  $output = $getvars['output'];
  $sort = $getvars['sort'];
  $format = $getvars['search_format'];
  $location = $getvars['location'];
  $limit_avail = $getvars['limit_avail'];
  $pager_page_array = explode(',', $getvars['page']);
  // temp for advanced page. should this be broken out
  if($actions[1] == 'isn'){
    $actions[1] = 'keyword';
  }
  $search_type = $actions[1];
  if ($actions[3]) {
      $actions[2] = $actions[2] . "/" . $actions[3];
  }
  $search_term = $actions[2];
  // If there is a proper search query, we get that data here.
  if (in_array($actions[1], $valid_search_types)) {
    $valid_search = TRUE;

    // Save the search URL in a cookie
    $_SESSION['search_url'] = request_uri();

    if ($getvars['perpage']) {
      $limit = $getvars['perpage'];
    }
    elseif ($account->profile_perpage) {
      $limit = $account->profile_perpage;
    }
    else {
      $limit = variable_get('sopac_results_per_page', 10);
    }


    if ($user->uid && $limit != $account->profile_perpage) {
      $field = db_fetch_object(db_query("SELECT * FROM profile_fields WHERE name = 'profile_perpage'"));
      db_query("INSERT INTO profile_values (fid, uid, value) VALUES (%d, %d, '%s') ON DUPLICATE KEY UPDATE value = '%s'", $field->fid, $user->uid, $limit, $limit);
    }

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

    // Hide suppressed records unless permission
    $show_inactive = user_access('show suppressed records');

    // Get the search results from Locum
    $locum_results_all = $locum->search($search_type, $search_term, $limit, $page_offset, $sort, $format, $location, $facet_args, FALSE, $limit_avail, $show_inactive);
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
      $stdnum = $locum_result['stdnum'][0];

      // Get the cover image
      $cover_img_url = $locum_result['cover_img'];
      $locum_result['sort'] = $sort;
      // Grab Syndetics reviews, etc..
      $review_links = $locum->get_syndetics($locum_result['stdnum'][0]);
      if (count($review_links)) {
        $locum_result['review_links'] = $review_links;
      }

      // Send it all off to the template
      if ($output == "rss") {
        $result_body .= theme('sopac_results_hitlist_rss', $hitnum, $cover_img_url, $locum_result, $locum_cfg, $no_circ);
      }
      else if ($output == "xml") {
        $result_body .= theme('sopac_results_hitlist_xml', $hitnum, $cover_img_url, $locum_result, $locum_cfg, $no_circ);
      }
      else {
        $result_body .= theme('sopac_results_hitlist', $hitnum, $cover_img_url, $locum_result, $locum_cfg, $no_circ);
      }
      $hitnum++;
    }

    $hitlist_pager = theme('pager', NULL, $limit, 0, NULL, 6);
  }
  else if ($valid_search) {
    $result_body .= theme('sopac_results_nohits', $locum_results_all, $locum->locum_config);
  }

  // Pull it all together into the search page template
  $result_page = $search_form . theme($output_template, $result_info, $hitlist_pager, $result_body, $locum_results_all, $locum->locum_config);

  // Check to see if we're doing RSS
  if ($output == "rss") {
    print theme('sopac_results_rss', $result_info, $search_term, $search_type, $result_body, $locum_results_all, $locum->locum_config);
    exit(0);
  }
  else if ($output == "xml") {
    print theme('sopac_results_xml', $result_info, $hitlist_pager, $result_body, $locum_results_all, $locum->locum_config);
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
  $getvars = sopac_parse_get_vars();
  $output = $getvars['output'];

  // Load social function
  require_once('sopac_social.php');
  drupal_add_js('misc/collapse.js');
  drupal_add_js(drupal_get_path('theme', 'aadl').'/soundmanager2-nodebug-jsmin.js');
  drupal_add_js(drupal_get_path('theme', 'aadl').'/inlineplayer.js');
  $no_circ = $locum->csv_parser($locum->locum_config['location_limits']['no_request']);
  $show_inactive = user_access('show suppressed records');
  $item = $locum->get_bib_item($bnum, $show_inactive);
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
  if (!$insurge->check_reviewed($user->uid, $bnum_arr[0]) && $user->uid) {
      $rev_form = drupal_get_form('sopac_review_form', $bnum_arr[0]);
  }
  else {
      $rev_form = NULL;
  }
  if($machinetags = $insurge->get_machine_tags($bnum)){
    foreach($machinetags as $machinetag){
      $item['machinetags'][$machinetag['namespace']][] = $machinetag;
    }
  }
  if($item['magnatune_url'] || $item['mat_code'] == 'z'){
    $result_page = theme('sopac_record_musicdownload', $item, $locum->locum_config, $rev_arr, $rev_form);
  }
  else if ($item['mat_code']) {
    $item['tracks'] = $locum->get_cd_tracks($bnum);
    $item['trackupc'] = $locum->get_upc($bnum);
    if($item['bnum']) {
      $item_status = $locum->get_item_status($bnum, TRUE);
    }
    // Grab Syndetics reviews, etc..
    $review_links = $locum->get_syndetics($item['stdnum'][0]);
    if (count($review_links)) {
      $item['review_links'] = $review_links;
    }
    $lists = $insurge->get_item_list_ids($item['bnum']);
    if(count($lists)){
      $lists = array_slice($lists, 0, 10);
      $sql = "SELECT * FROM {sopac_lists} WHERE public = 1 and list_id IN (".implode($lists,',').") ORDER BY list_id DESC";
      $res = db_query($sql);
      while ($record = db_fetch_array($res)) {
        $item['lists'][] = $record;
      }
    }
    // Build the page
    $result_page = theme('sopac_record', $item, $item_status, $locum->locum_config, $no_circ, &$locum, $rev_arr, $rev_form);
  }
  else {
    $result_page = t('This record does not exist.');
  }

  if ($output == "rss") {
    $item['status'] = $item_status;
    $item['type'] = 'bib';
    $cover_img_url = $item['cover_img'];
    return theme('sopac_results_hitlist_rss', 1, $cover_img_url, $item, $locum->locum_config, $no_circ);
  } else {
    return '<p>'. t($result_page) .'</p>';
  }
}

/**
 * Reharvest Bib record from ILS and redirect to the record page.
 *
 * @access public
 * @return void
 */
function sopac_bib_record_reharvest($bnum = NULL) {
  error_reporting(E_ALL ^ E_NOTICE);
  ini_set('display_errors', 1);
  require_once('/usr/local/lib/locum/locum-server.php');
  $locum = new locum_server;
  $actions = sopac_parse_uri();
  if(!$bnum){
    $actions = sopac_parse_uri();
    $bnum = $actions[1];
  }
  $reharvest = $locum->import_bibs($bnum,$bnum);
  $path = variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum;
  drupal_goto($path);
}

/**
 * Download album or tracks
 *
 * @access public
 * @return void
 */
function sopac_bib_record_download($bnum = NULL) {
  $locum = sopac_get_locum();
  $actions = sopac_parse_uri();
  if(!$bnum){
    $actions = sopac_parse_uri();
    $bnum = $actions[1];
  }
  $bib = $locum->get_bib_item($bnum);
  $type = $_GET['type'];
  global $user;
  if ($user->uid && $user->bcode_verified && $type && $bib['zipmd5']) {
    switch($type){
      case 'album':
        $locum->count_download($bnum,"album");
        $path = "http://media.aadl.org/magnatune/$bnum/derivatives/".$bib['zipmd5'].".zip?name=$bnum.zip";
        header("Location: $path");
        break;
      case 'flac':
        $locum->count_download($bnum,"flac");
        $path = "http://media.aadl.org/magnatune/$bnum/derivatives/".$bib['zipmd5']."-flac.zip?name=$bnum-flac.zip";
        header("Location: $path");
        break;
      case 'track':
        $tracknum = $_GET['tracknum'];
        $locum->count_download($bnum,"track",$tracknum);
        if(!$tracknum) {
          $path = variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum;
          drupal_set_message(t("There appears to be a problem downloading the file. Please make sure you have an active library card associated with this account"),"error");
          drupal_goto($path);
        }
        $paddedtrack = str_pad($tracknum, 2, "0", STR_PAD_LEFT);
        $trackname = $bib['tracks'][$tracknum]['title'] . "-" . $bib['artist'];
        if($bib['tracks'][$tracknum]['filename']) {
          $filename = $bib['tracks'][$tracknum]['filename'];
        }
        else {
          $filename = $paddedtrack."-".str_replace(array(' ','(',')'),'-', $trackname).".mp3";
        }
        $path = "http://media.aadl.org/magnatune/$bnum/derivatives/".str_replace(array(' ','(',')'),'-', $bib['title'])."/".urlencode($filename)."?name=".urlencode($filename);
        //header('Content-Disposition: attachment; filename="'.$path.'"');
        //readfile($path);
        header("Location: $path");
        break;
      case 'play':
        $tracknum = $_GET['tracknum'];
        $locum->count_download($bnum,"play",$tracknum);
        $paddedtrack = str_pad($tracknum, 2, "0", STR_PAD_LEFT);
        $trackname = $bib['tracks'][$tracknum]['title'] . "-" . $bib['artist'];
        if($bib['tracks'][$tracknum]['filename']) {
          $filename = $bib['tracks'][$tracknum]['filename'];
        }
        else {
          $filename = $paddedtrack."-".str_replace(array(' ','(',')'),'-', $trackname).".mp3";
        }
        $path = "http://media.aadl.org/magnatune/$bnum/derivatives/streaming/".rawurlencode($filename);
        //header('Content-Disposition: attachment; filename="'.$path.'"');
        header('Content-Type: audio/mpeg');
        readfile($path);
        //header("Location: $path");
        break;
    }

  }
  else {
    $path = variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum;
    drupal_set_message(t("There appears to be a problem downloading the file. Please make sure you have an active library card associated with this account"),"error");
    drupal_goto($path);
  }
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
/*
  if ($author) {
    $author_arr = explode(',', trim($author, '.'));
    $new_author_str = trim($author_arr[1]) . ' ' . trim($author_arr[0]);
  }

  elseif ($addl_author_ser) {
    $addl_author = unserialize($addl_author_ser);
    if ($addl_author[0]) {
      $author_arr = explode(',', trim($addl_author[0], '.'));
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
*/
  return $author;
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
    $class = 'button red';
    $text = 'Requesting Disabled';
    if ($message = variable_get('sopac_catalog_disabled_message', FALSE)) {
      $text = "<a title=\"$message\">$text</a>";
    }
  }
  else {
    global $user;
    $class = 'button green';

    if ($user->uid) {
      if ($user->bcode_verified) {
        // User is logged in and has a verified card number
        if ($mattype == 'Magazine') {
          $text = 'Request an Issue';
          $options = array('alias' => TRUE); // no lightbox on links
        }
        else if ($mattype == 'Music Download') {
          $locum = sopac_get_locum();
          $bib = $locum->get_bib_item($bnum);
          $size = round(($bib['zipsize'] / 1048576), 2);
          $text = 'Download MP3 Album (' . $size . 'MB)';
        }
        else {
          $text = 'Request this';
          $options = array('query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe'), 'alias' => TRUE);
        }

        if (variable_get('sopac_multi_branch_enable', 0) && $mattype != 'Music Download' && $mattype != 'Stream') {
          $locum = sopac_get_locum();
          $branches = $locum->locum_config['branches'];
          $class .= ' hassub';

          $text .= "<ul class=\"submenu\"><li>for pickup at</li>";
          if ($user->profile_pref_home_branch) {
            $home_branch_code = array_search($user->profile_pref_home_branch, $branches);
            $text .= '<li>' .
                     l($user->profile_pref_home_branch,
                       variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $home_branch_code,
                       $options) .
                     '</li>';
            $text .= '<li>Other Location...</li>';
          }
          foreach ($branches as $branch_code => $branch_name) {
            if ($branch_name != $user->profile_pref_home_branch) {
              $text .= '<li>' .
                       l($branch_name,
                         variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $branch_code,
                         $options) .
                       '</li>';
            }
          }
          $text .= "</ul><span></span>";
        }
        else if($mattype == 'Music Download') {
          $text = '<a href="/'.variable_get('sopac_url_prefix', 'cat/seek').'/record/'.$bnum.'/download?type=album">'.$text.'</a>';
        }
        else if ($mattype == 'Stream')
          $text = 'Watch Online Below';
        else {
          $text = l($text, variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum, array('alias' => TRUE));
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
    else if($mattype == 'Music Download'){
      $text = l(t('Log in to Download'), 'user/login', array('query' => drupal_get_destination()));
    }
    else if ($mattype == 'Stream')
          $text = l(t('Login to Watch'), 'user/login', array('query' => drupal_get_destination()));
    else {
      $text = l(t('Log in to request'), 'user/login', array('query' => drupal_get_destination()));
    }
  }
  return "<li class=\"$class\">$text</li>";
}

function sopac_put_staff_request_link($bnum) {
  if (variable_get('sopac_catalog_disabled', 0)) {
    $class = 'button red';
    $text = 'Staff Requests Disabled';
    if ($message = variable_get('sopac_catalog_disabled_message', FALSE)) {
      $text = "<a title=\"$message\">$text</a>";
    }
  }
  else {
    global $user;
    $class = 'button green';
    $text = 'Request For Patron';
    $options = array('query' => array('lightbox' => 1,'staff' => 1), 'attributes' => array('rel' => 'lightframe'), 'alias' => TRUE);
    if (variable_get('sopac_multi_branch_enable', 0)) {
      $locum = sopac_get_locum();
      $branches = $locum->locum_config['branches'];
      $class .= ' hassub';

      $text .= "<ul class=\"submenu\"><li>for pickup at</li>";
      if ($user->profile_pref_home_branch) {
        $home_branch_code = array_search($user->profile_pref_home_branch, $branches);
        $text .= '<li>' .
                 l($user->profile_pref_home_branch,
                   variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $home_branch_code,
                   $options) .
                 '</li>';
        $text .= '<li>Other Location...</li>';
      }
      foreach ($branches as $branch_code => $branch_name) {
        if ($branch_name != $user->profile_pref_home_branch) {
          $text .= '<li>' .
                   l($branch_name,
                     variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $branch_code,
                     $options) .
                   '</li>';
        }
      }
      $text .= "</ul><span></span>";
    }
    else {
      $text = l($text, variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum, array('alias' => TRUE));
    }
  }

  return "<li class=\"$class\">$text</li>";
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
  //profile_load_profile($user);
  //profile_load_profile(&$user);

  if ($user->uid) {
    if ($user->bcode_verified) {
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
  if (variable_get('sopac_catalog_disabled', FALSE)) {
    drupal_set_message(variable_get('sopac_catalog_disabled_message', 'Requesting is disabled'), 'error');
    drupal_goto(variable_get('sopac_url_prefix', 'cat/seek'));
  }

  global $user;
  // avoid php errors when debugging
  $varname = $request_result_msg = $request_error_msg = $item_form = $bnum = NULL;
  $staff_request = $_GET['staff'];
  $patron_bcode = $_POST['patron_barcode'];
  if($_GET['patron_barcode']) {
    $patron_bcode = $_GET['patron_barcode'];
  }

  $button_txt = t('Request Selected Item');
  //profile_load_profile(&$user);
  if (($user->uid && $user->bcode_verified) || $staff_request == 1) {
    // support multi-branch & user home branch
    $locum = sopac_get_locum();
    $actions = sopac_parse_uri();

    $bnum = $actions[1];
    $pickup_arg = $actions[2] ? $actions[2] : NULL;
    $pickup_name = $locum->locum_config['branches'][$pickup_arg];
    $varname = $actions[3] ? $actions[3] : NULL;
    $bib_item = $locum->get_bib_item($bnum, TRUE);
    $barcode = $user->profile_pref_cardnum;
    if($staff_request && $patron_bcode && user_access('staff request')) {
      $barcode = $patron_bcode;
      $patron_info = $locum->get_patron_info($barcode);
    }

    if($staff_request && !$patron_bcode){
      $item_form = drupal_get_form('sopac_staff_request_form');
      $result_page = theme('sopac_request', $request_result_msg, $request_error_msg, $item_form, $bnum);
      return '<p>'. t($result_page) .'</p>';
    }
    else {
      $hold_result = $locum->place_hold($barcode, $bnum, $varname, $user->locum_pass, $pickup_arg);
    }
    // Set home branch if none set
    if ($pickup_name && !$user->profile_pref_home_branch) {
      user_save($user, array('profile_pref_home_branch' => $pickup_name));
      drupal_set_message("Your home branch has been set to $pickup_name.<br />" . l('Adjust your home branch preference', "user/$user->uid/edit/Preferences"));
    }

    if ($hold_result['success']) {
      // handling multi-branch scenario
      $request_result_msg = t('You have successfully requested a copy of ') . '<span class="req_bib_title"> ' . $bib_item['title'] . ' ' .$bib_item['title_medium'] . '</span>';
      if ($pickup_name) {
        $request_result_msg .= t(' for pickup at ') . $pickup_name;
      }
      if($staff_request){
        $request_result_msg .= '<br />for patron '.$patron_info['name'].' ( '.$barcode.' )';
      }
      else {
        $request_result_msg .= '<br />(Please allow a few moments for the request to appear on your My Account list)';
      }
/*
      // probably abstract this out to a locum->sendalert or something?
      $item_status = $locum->get_item_status($bnum);
      $zooms_avail = $item_status['callnums']['Zoom Lends DVD']['avail'] + $item_status['callnums']['Zoom Lends Book']['avail'];
      $avail = $item_status['avail'] - $zooms_avail;
      if ($avail > 0) {
        require_once('/usr/local/lib/libphp-aadl/contrib/redisent/redisent.php');
        $redisjob = array();
        $redisjob['command'] = 'holdrequest';
        $redisjob['title'] = $bib_item['title'];
        $redisjob['bnum'] = $bib_item['bnum'];
        // Build list of locations
        $locations = array();
        foreach ($item_status['items'] as $itemstat) {
          if ($itemstat['avail']) {
            $locations[$itemstat['loc_code']] = $itemstat['location'] . " (".$itemstat['callnum'].")";
          }
        }
        $locations = implode(', ', $locations);
        $redisjob['locations'] = $locations;
        $redisjob['pickup_loc'] = $pickup_name;
        $redis = new redisent('multivac');
        $redis->publish('redisbot', json_encode($redisjob));
      }
*/

    }
    else if (count($hold_result['choose_location'])) {
      $locum = sopac_get_locum();
      $request_result_msg = '<h2 class="title">' . t('Please select a pickup location for your request:') . '</h2><div class="item-request"><ul>';

      foreach ($locum->locum_config['branches'] as $branch_code => $branch_name) {
        $link = l($branch_name, variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum . '/' . $branch_code);
        $request_result_msg .= '<li class="button green">' . $link . '</li>';
      }
      $request_result_msg .= '</ul></div>';
    }
    else if ($hold_result['selection']) {
      $request_result_msg = '<h2 class="title">' . t('Please select an item for your request:') . '</h2>';

      $issues = array();
      $link_options = array();

      if ($_GET['lightbox']) {
        $link_options['query']['lightbox'] = 1;
      }
      if($staff_request) {
        $link_options['query']['staff'] = 1;
      }
      if($patron_bcode) {
        $link_options['query']['patron_barcode'] = $patron_bcode;
      }

      // Group items by callnumber
      foreach ($hold_result['selection'] as $selection) {
        $selection['branch_code'] = strtolower($selection['location'][0]);
        // Get issue number info
        preg_match('/v\.([\d]+)[^\d]/', $selection['callnum'], $vol_match);
        preg_match('/no\.([\d]+)[^\d]/', $selection['callnum'], $no_match);
        if ($vol_match[1] || $no_match[1]) {
          // volume and/or number found
          $issue_id = intval($vol_match[1] . str_pad($no_match[1], 6, '0', STR_PAD_LEFT));
        }
        else {
          // search for year
          if (preg_match('/([\d]{4})/', $selection['callnum'], $year_match)) {
            $issue_id = $year_match[1];
            // translate seasons/months into corresponding issue values
            if (stripos($selection['callnum'], 'jan') !== FALSE) {
              $issue_id .= '01';
            }
            else if (stripos($selection['callnum'], 'feb') !== FALSE) {
              $issue_id .= '02';
            }
            else if (stripos($selection['callnum'], 'mar') !== FALSE) {
              $issue_id .= '03';
            }
            else if (stripos($selection['callnum'], 'apr') !== FALSE) {
              $issue_id .= '04';
            }
            else if (stripos($selection['callnum'], 'may') !== FALSE) {
              $issue_id .= '05';
            }
            else if (stripos($selection['callnum'], 'jun') !== FALSE) {
              $issue_id .= '06';
            }
            else if (stripos($selection['callnum'], 'jul') !== FALSE) {
              $issue_id .= '07';
            }
            else if (stripos($selection['callnum'], 'aug') !== FALSE) {
              $issue_id .= '08';
            }
            else if (stripos($selection['callnum'], 'sep') !== FALSE) {
              $issue_id .= '09';
            }
            else if (stripos($selection['callnum'], 'oct') !== FALSE) {
              $issue_id .= '10';
            }
            else if (stripos($selection['callnum'], 'nov') !== FALSE) {
              $issue_id .= '11';
            }
            else if (stripos($selection['callnum'], 'dec') !== FALSE) {
              $issue_id .= '12';
            }
            else if (stripos($selection['callnum'], 'spr') !== FALSE) {
              $issue_id .= '01';
            }
            else if (stripos($selection['callnum'], 'sum') !== FALSE) {
              $issue_id .= '02';
            }
            else if (stripos($selection['callnum'], 'fal') !== FALSE) {
              $issue_id .= '03';
            }
            else if (stripos($selection['callnum'], 'win') !== FALSE) {
              $issue_id .= '04';
            }

            // check for date
            if (preg_match('/[A-Za-z]{3} ([\d]{1,2}) /', $selection['callnum'], $date_match)) {
              $issue_id .= str_pad($date_match[1], 2, '0', STR_PAD_LEFT);
            }
          }
          else {
            // no year found, just use the call number to sort A-Z
            $issue_id = $selection['callnum'];
          }
        }
        $issues[$issue_id][] = $selection;
      }
      // reverse to show latest issues first
      krsort($issues);

      foreach ($issues as $issue) {
        $selection = array();

        if (!$first_issue_found) {
          // First issue isn't requestable
          $selection = $issue[0];
          $selection['location'] = 'N/A';
          $selection['varname'] = '';
          $selection['status'] = 'Latest copy is unrequestable';
          $first_issue_found = TRUE;
        }
        else {
          // select the best availble item to be the link for this callnum
          shuffle($issue);
          foreach($issue as $issue_item) {
            if ($issue_item['status'] == 'AVAILABLE') {
              $selection = $issue_item;
              if ($issue_item['branch_code'] == $pickup_arg) {
                // Availble at the itended branch, use it
                break;
              }
            }
            else {
              if ($issue_item['varname']) {
                $selection = $issue_item;
              }
            }
          }
          // if nothing was requestable, just use the first item
          if (empty($selection)) {
            $selection = $issue[0];
          }
        }

        $status = $selection['status'];
        if ($selection['varname']) {
          $request = l('Request this item',
                       variable_get('sopac_url_prefix', 'cat/seek') . "/request/$bnum/$pickup_arg/" . $selection['varname'],
                       $link_options);
          $requestable++;
        }
        else {
          $request = '';
          $status = '<span class="non_circ_msg">' . $status . '</span>';
        }
        $rows[] = array(
          $selection['location'],
          $selection['callnum'],
          $status,
          $request,
        );
      }

      if (count($rows)) {
        $header = array(t('Location'), t('Call Number'), t('Status'), t('Request'));
        $item_form = theme('table', $header, $rows);
      }
      else {
        $request_result_msg = '';
        $item_form = t('There are no copies of this item available for circulation.');
      }
      $item_form .= '<ul><li class="button red">' .
                    l('Return to ' . $bib_item['title'], variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $bnum) .
                    '</li></ul>';
    }
    else {
      drupal_set_message(t('We were unable to fulfill your request for ') . '<span class="req_bib_title">' . $bib_item['title'] . '</span>', 'error');
    }

    if ($hold_result['error']) {
      drupal_set_message($hold_result['error'], 'error');
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

function sopac_staff_request_form(&$form_state, $patron_barcode = NULL) {
  $form['inline'] = array(
    '#prefix' => '<div>',
    '#suffix' => '</div>',
  );
  $form['inline']['patron_barcode'] = array(
    '#type' => 'textfield',
    '#title' => 'Patron Barcode',
    '#default_value' => $patron_barcode,
    '#size' => 25,
    '#maxlength' => 255,
  );
  $form['inline']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Request'),
  );
  $form['#name'] = 'patron-barcode';
  return $form;
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
  $form['inline'] = array(
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>'
  );
  $form['inline']['search_query'] = array(
    '#type' => 'textfield',
    '#default_value' => $search_query,
    '#size' => 25,
    '#maxlength' => 255,
    '#attributes' => array('x-webkit-speech' => 'true'),
  );
  $form['inline']['search_type'] = array(
    '#type' => 'select',
    '#title' => t(' by '),
    '#default_value' => $stype_selected,
    '#options' => $stypes,
  );
  $form['inline']['search_format'] = array(
    '#type' => 'select',
    '#title' => t(' in '),
    '#default_value' => $_GET['search_format'],
    '#selected' => $_GET['search_format'],
    '#options' => $sformats,
  );
  $form['inline']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Search'),
  );
  $form['inline']['advanced_link'] = array(
    '#prefix' => '<div class="searchtips">',
    '#value' => l(" Advanced Search", variable_get('sopac_url_prefix', 'cat/seek') . '/advanced'),
  );
  $form['inline']['search_tips'] = array(
    '#value' => l("Search Tips", variable_get('sopac_url_prefix', 'cat/seek') . '/tips'),
    '#suffix' => '</div>',
  );
  $form['inline2'] = array(
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['inline2']['limit'] = array(
    '#type' => 'checkbox',
    '#default_value' => $getvars['limit'],
  );
  $form['inline2']['limit_avail'] = array(
    '#type' => 'select',
    '#title' => 'limit to items available at',
    '#options' => array_merge(array('any' => "Any Location"), $locum_cfg['branches']),
    '#default_value' => $getvars['limit_avail'],
  );
  return $form;
}

/**
 * Formulates the advanced search form array
 *
 * @return array Drupal search form array
 */
function sopac_search_form_adv() {
  $locum = sopac_get_locum();
  $locum_cfg = $locum->locum_config;
  $getvars = sopac_parse_get_vars();

  $actions = sopac_parse_uri();
  if ($actions[0] == "search") {
    if($actions[3]) { $actions[2] = $actions[2] . "/" . $actions[3]; urlencode($actions[2]); }
    $search_query = $actions[2];
    $stype_selected = $actions[1] ? 'cat_' . $actions[1] : 'cat_keyword';
  }
  $sformats = array('' => 'Everything');
  foreach ($locum_cfg[format_groups] as $sfmt => $sfmt_codes) {
    $sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
  }

  $stypes = array(
    'cat_keyword' => 'Keyword',
    'cat_title' => 'Title',
    'cat_author' => 'Author',
    'cat_series' => 'Series',
    'cat_tags' => 'Tags',
    'cat_reviews' => 'Reviews',
    'cat_subject' => 'Subject',
    'cat_callnum' => 'Call Number',
    'cat_isn' => 'ISBN or ISSN',
  );

  $sortopts = array(
    '' => 'Relevance',
    'atoz' => 'Alphabetical A to Z',
    'ztoa' => 'Alphabetical Z to A',
    'catalog_newest' => 'Just Added',
    'newest' => 'Pub date: Newest',
    'oldest' => 'Pub date: Oldest',
    'author' => 'Alphabetically by Author',
    'top_rated' => 'Top Rated Items',
    'popular_week' => 'Most Popular this Week',
    'popular_month' => 'Most Popular this Month',
    'popular_year' => 'Most Popular this Year',
    'popular_total' => 'All Time Most Popular',
  );

  // Initialize the form
  $form = array(
    '#attributes' => array('class' => 'search-form'),
    '#validate' => array('sopac_search_catalog_validate'),
    '#submit' => array('sopac_search_catalog_submit'),
  );

  $form['search_query'] = array(
    '#type' => 'textfield',
    '#title' => t('Search term or phrase'),
    '#default_value' => $search_query,
    '#size' => 50,
    '#maxlength' => 255,
  );

  $form['search_type'] = array(
    '#type' => 'select',
    '#title' => t('Search by'),
    '#default_value' => $stype_selected,
    '#options' => $stypes,
  );
  $form['sort'] = array(
    '#type' => 'select',
    '#title' => t('Sorted by'),
    '#default_value' => '',
    '#options' => $sortopts,
  );
  $form['age_group'] = array(
    '#type' => 'select',
    '#title' => 'in age group',
    '#options' => array('' => "Any Age Group", 'adult' => "Adult", 'teen' => "Teen", 'youth' => "Youth"),
  );
  $form['limit'] = array(
    '#prefix' => '<div class="container-inline">',
    '#type' => 'checkbox',
    '#default_value' => $getvars['limit'],
  );
  $form['limit_avail'] = array(
    '#type' => 'select',
    '#title' => 'limit to items available at',
    '#options' => array_merge(array('any' => "Any Location"), $locum_cfg['branches']),
    '#default_value' => $getvars['limit_avail'],
    '#suffix' => "</div>",
  );
  if (count($locum_cfg[collections])) {
    foreach ($locum_cfg[collections] as $loc_collect_key => $loc_collect_var) {
      $loc_collect[$loc_collect_key] = $loc_collect_key;
    }

    $form['collection'] = array(
      '#type' => 'select',
      '#title' => t('In these collections'),
      '#size' => 5,
      '#default_value' => $getvars[collection],
      '#options' => $loc_collect,
      '#multiple' => TRUE,
    );
  }

  asort($locum_cfg[formats]);
  $form['search_format'] = array(
    '#type' => 'select',
    '#title' => t('In these formats'),
    '#size' => 5,
    '#default_value' => $getvars[search_format],
    '#options' => $locum_cfg[formats],
    '#multiple' => TRUE,
  );
  $form['publisher'] = array(
    '#type' => 'textfield',
    '#title' => t('Publisher'),
    '#size' => 20,
    '#maxlength' => 255,
  );
  $form['pub_year_start'] = array(
    '#type' => 'textfield',
    '#title' => t('Published year between'),
    '#size' => 20,
    '#maxlength' => 255,
  );
  $form['pub_year_end'] = array(
    '#type' => 'textfield',
    '#title' => t('and'),
    '#size' => 20,
    '#maxlength' => 255,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Search'),
  );
  $form['clear'] = array(
    '#name' => 'clear',
    '#type' => 'button',
    '#value' => t('Reset'),
    '#attributes' => array('onclick' => 'this.form.reset(); return false;'),
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
  if(strstr($search_query,'+')){
    $search_query = urlencode($search_query);
  }
  $search_type = $form_state['values']['search_type'];
  $search_type_arr = explode('_', $search_type);
  if ($search_type_arr[0] == 'cat') {
    $search_type = $search_type_arr[1];
    $search_fmt = $search_type_arr[2];

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
    if ($form_state['values']['age_group']) {
      $uris['age'] = $form_state['values']['age_group'];
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
      //$uris['pub'] = trim($form_state['values']['publisher']);
      $search_query .= " @publisher ".trim($form_state['values']['publisher']);
    }

    // Publication date ranges
    if ($form_state['values']['pub_year_start'] || $form_state['values']['pub_year_end']) {
      $uris['facet_year'] = trim($form_state['values']['pub_year_start']) . '-' .
                            trim($form_state['values']['pub_year_end']);
    }
    $search_url = variable_get('sopac_url_prefix', 'cat/seek') . '/search/' . $search_type . '/' . $search_query;
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

function array2xml($array) {
  $xml="";
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $xml .= "<$key>" . array2xml($value) . "</$key>\n";
    } else {
      $xml .= "<$key>" . htmlspecialchars($value) . "</$key>\n";
    }
  }
  return $xml;
}

function sopac_linkfromcallnum($callnum)
{
    $url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
    return l($callnum, $url_prefix . '/search/callnum/"' . urlencode($callnum) .'"',array('alias' => TRUE));
}
