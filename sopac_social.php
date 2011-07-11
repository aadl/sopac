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


function sopac_personal_overview_page() {
  global $user;

  $num_reviews = 3;
  $num_ratings = 5; // TODO make these all configurable.

  $insurge = sopac_get_insurge();
  $locum = sopac_get_locum();

  // Pull together the reviews
  $reviews = $insurge->get_reviews($user->uid, NULL, NULL, $num_reviews);
  if ($reviews['total']) {
    $i = 0;
    $rev_bnums = array();
    foreach ($reviews['reviews'] as $insurge_review) {
      $rev_arr[$i]['rev_id'] = $insurge_review['rev_id'];
      $rev_arr[$i]['bnum'] = $insurge_review['bnum'];
      $rev_arr[$i]['uid'] = $user->uid;
      $rev_arr[$i]['timestamp'] = $insurge_review['rev_create_date'];
      $rev_arr[$i]['rev_title'] = $insurge_review['rev_title'];
      $rev_arr[$i]['rev_body'] = $insurge_review['rev_body'];
      if (!in_array($insurge_review['bnum'], $rev_bnums)) {
        $rev_bnums[] = $insurge_review['bnum'];
      }
      $i++;
    }
  }
  $review_norev = '<div class="overview-nodata">'.t('You have not written any reviews yet.').'</div>';
  $rev_bib_info = $locum->get_bib_items_arr($rev_bnums);
  $review_display = theme('sopac_review', $user, NULL, $rev_arr, NULL, NULL, NULL, $review_norev, $rev_bib_info);

  // Pull together the ratings
  $ratings_arr_top = $insurge->get_rating_list($user->uid, NULL, $num_ratings, NULL, 'ORDER BY rating DESC, rate_date DESC');
  $ratings_arr_new = $insurge->get_rating_list($user->uid, NULL, $num_ratings, NULL, 'ORDER BY rate_date DESC');
  $ratings_chunk['nodata'] = '<div class="overview-nodata">' . t('You have not rated any items yet.') . '</div>';
  $ratings_chunk['top']['total'] = $ratings_arr_top['total'];
  $ratings_chunk['top']['ratings'] = $ratings_arr_top['ratings'];
  $ratings_chunk['latest']['total'] = $ratings_arr_new['total'];
  $ratings_chunk['latest']['ratings'] = $ratings_arr_new['ratings'];
  $rate_bnums = array();
  foreach ($ratings_arr_top['ratings'] as $rate_arr) {
    if (!in_array($rate_arr['bnum'], $rate_bnums)) {
      $rate_bnums[] = $rate_arr['bnum'];
    }
  }
  foreach ($ratings_arr_new['ratings'] as $rate_arr) {
    if (!in_array($rate_arr['bnum'], $rate_bnums)) {
      $rate_bnums[] = $rate_arr['bnum'];
    }
  }
  $bibs = $locum->get_bib_items($rate_bnums);
  foreach($bibs as $bib){
    $ratings[$bib['id']] = $bib['doc'];
  }
  $ratings_chunk['bibs'] = $ratings;
  // Pull together the tags
  $tag_arr = $insurge->get_tag_totals($user->uid, NULL, NULL, variable_get('sopac_random_tags', 1), variable_get('sopac_tag_limit', 100), 0, variable_get('sopac_tag_sort', 'ORDER BY count DESC'));
  if (count($tag_arr)) {
    foreach ($tag_arr as $tag_pair) {
      $tags[$tag_pair['tag']] = $tag_pair['count'];
    }
    $tag_cloud = theme('sopac_tag_cloud', $tags, 'personal');
  }
  else {
    $tag_cloud = '<div class="overview-nodata">' . t('You have not tagged any items yet.') . '</div>';
  }

  $result_page = theme('sopac_pat_overview', $review_display, $ratings_chunk, $tag_cloud);
  return $result_page;
}

function sopac_ratings_page() {
  global $user;

  $insurge = sopac_get_insurge();
  $locum = sopac_get_locum();
  $page_limit = 15; // TODO make this configurable
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  $offset = ($page_limit * $page);

  // Pull together the ratings
  $ratings_arr = $insurge->get_rating_list($user->uid, NULL, $page_limit, $offset, 'ORDER BY rate_date DESC');
  sopac_pager_init($ratings_arr['total'], 0, $page_limit);

  $rate_bnums = array();
  foreach ($ratings_arr['ratings'] as $rate_arr) {
    if (!in_array($rate_arr['bnum'], $rate_bnums)) {
      $rate_bnums[] = $rate_arr['bnum'];
    }
  }
  $bibs = $locum->get_bib_items($rate_bnums);
  foreach($bibs as $bib){
    $ratings[$bib['id']] = $bib['doc'];
  }
  $ratings_arr['bibs'] = $ratings;

  $result_page = theme('sopac_ratings_page', $ratings_arr);
  $result_page .= theme('pager', NULL, $page_limit, 0, NULL, 6);
  return $result_page;
}

function sopac_tags_page_cloud() {
  global $user;

  $insurge = sopac_get_insurge();
  $tag_arr = $insurge->get_tag_totals($user->uid, NULL, NULL, TRUE, NULL);
  foreach ($tag_arr as $tag_pair) {
    $tags[$tag_pair['tag']] = $tag_pair['count'];
  }
  if (count($tags)) {
    $cloud = theme('sopac_tag_cloud', $tags);
  }
  else {
    $cloud = '<div class="overview-nodata">'.t('You have not tagged any items yet.').'</div>';
  }
  return $cloud;
}

function sopac_tags_page_list() {
  global $user;

  $insurge = sopac_get_insurge();
  $tags_res = $insurge->get_tag_totals($user->uid, NULL, NULL, FALSE, NULL, NULL, 'ORDER BY tag ASC');
  foreach ($tags_res as $tag_arr) {
    $tags[$tag_arr['tag'][0]][$tag_arr['tag']] = $tag_arr['count'];
  }

  if (count($tags)) {
    $result_page = theme('sopac_tags_page', $tags);
  }
  else {
    $result_page = '<div class="overview-nodata">'.t('You have not tagged any items yet.').'</div>';
  }
  return $result_page;
}

function theme_sopac_tag_block($block_type) {
  global $user;

  $insurge = sopac_get_insurge();

  switch($block_type) {
    case 'overview':
      $tag_arr = $insurge->get_tag_totals(NULL, NULL, NULL, variable_get('sopac_random_tags', 1), variable_get('sopac_tag_limit', 100), 0, variable_get('sopac_tag_sort', 'ORDER BY count DESC'));
      break;
    case 'record':
      $uri_arr = sopac_parse_uri();
      $bnum = $uri_arr[1];
      $bnum_arr[] = $bnum;
      if ($_GET['deltag'] && $bnum && $user->uid) {
        $insurge->delete_user_tag($user->uid, urldecode($_GET['deltag']), $bnum);
        drupal_set_message('Tag "' . urldecode($_GET['deltag']) . '" Deleted');
        drupal_goto($_GET[q]);
      }
      $tag_arr = $insurge->get_tag_totals(NULL, $bnum_arr);
      $tag_arr_user = $insurge->get_tag_totals($user->uid, $bnum_arr, NULL, FALSE, NULL, NULL, 'ORDER BY tag ASC');
      static $put_tag_form = 1;
      static $put_personal_tag_list = 1;
      break;
    case 'personal':
      $tag_arr = $insurge->get_tag_totals($user->uid, NULL, NULL, variable_get('sopac_random_tags', 1), variable_get('sopac_tag_limit', 100), 0, variable_get('sopac_tag_sort', 'ORDER BY count DESC'));
      break;
  }

  if ($user->uid){
    if ($put_tag_form) {
      $block_suffix = '<br /><br />' . drupal_get_form('sopac_tag_form', $bnum);
    }
  }
  else {
    if ($put_tag_form) {
      $block_suffix = '<br /><br />' . l(t('Login to add tags'), 'user/login', array('query' => drupal_get_destination()));
    }
  }

  if ($user->uid && $put_personal_tag_list && count($tag_arr_user)) {
    $block_suffix .= '<div class="tag-personal-list-head">' . t('Your tags') . '</div>';
    $block_suffix .= '<div class="tag-personal-list-block"><ul class="tag-personal-list">';
    foreach ($tag_arr_user as $tag_user) {
      $block_suffix .= '<li class="tag-personal-list-item">' . $tag_user['tag'] . ' <span class="tag-personal-list-x">[' . l('x', $_GET['q'], array('query' => array('deltag' => urlencode($tag_user['tag'])))) . ']</li>';
    }
    $block_suffix .= '</ul></div>';
  }

  if (count($tag_arr)) {
    foreach ($tag_arr as $tag_pair) {
      $tags[$tag_pair['tag']] = $tag_pair['count'];
    }
    $cloud = theme('sopac_tag_cloud', $tags, $block_type);
  }
  else {
    $cloud = t('No tags, currently.');
  }
  $cloud .= $block_suffix;

  return $cloud;
}

function sopac_user_tag_edit() {
  global $user;

  $pathinfo = explode('/', trim($_GET['q']));
  $tag = $pathinfo[3];
  if ($_GET['ref']) {
    $form['#redirect'] = urldecode($_GET['ref']);
  }
  $form['tagform'] = array(
    '#type' => 'fieldset',
    '#title' => t('Change your tag "') . $tag . t('" to something else'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['tagform']['newtag'] = array(
    '#type' => 'textfield',
    '#title' => t('Enter new tag'),
    '#size' => 42,
    '#maxlength' => 255,
    '#default_value' => $tag,
    '#required' => TRUE,
  );
  $form['tagform']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Edit Tag'),
  );
  $form['tagform']['oldtag'] = array(
    '#type' => 'hidden',
    '#value' => $tag,
  );

  return $form;
}

function sopac_user_tag_edit_submit($form, &$form_state) {
  global $user;
  if ($user->uid) {
    $insurge = sopac_get_insurge();
    $insurge->update_tag($form_state['values']['oldtag'], $form_state['values']['newtag'], $user->uid);
  }
}

function sopac_user_tag_delete() {
  global $user;

  $insurge = sopac_get_insurge();
  $pathinfo = explode('/', trim($_GET['q']));
  $tag = urldecode($pathinfo[3]);
  $tag_total_arr = $insurge->get_tag_totals($user->uid, NULL, $tag);
  $tag_total = $tag_total_arr[0]['count'];
  $tag_total_str = ($tag_total > 1) ? $tag_total . t(' things') : $tag_total . t(' thing');

  drupal_set_message(t('You have tagged ') . $tag_total_str . t(' with "') . $tag . t('".  If you delete this tag, it will be removed completely.'), 'warning');

  if ($_GET['ref']) {
    $form['#redirect'] = urldecode($_GET['ref']);
  }
  $form['tagform'] = array(
    '#type' => 'fieldset',
    '#title' => t('Really delete "') . $tag . t('" from your collection?'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['tagform']['submit_y'] = array(
    '#type' => 'submit',
    '#value' => t('Yes'),
  );
  $form['tagform']['submit_n'] = array(
    '#type' => 'submit',
    '#value' => t('No'),
  );
  $form['tagform']['oldtag'] = array(
    '#type' => 'hidden',
    '#value' => $tag,
  );

  return $form;
}

function sopac_user_tag_delete_submit($form, &$form_state) {
  global $user;

  if (strtolower($form_state['values']['op']) == 'yes') {
    if ($user->uid && $form_state['values']['oldtag']) {
      $insurge = sopac_get_insurge();
      $insurge->delete_user_tag($user->uid, $form_state['values']['oldtag']);
    }
  }
}

function sopac_user_tag_hitlist($tag) {
  global $user;

  require_once('sopac_catalog.php');
  $locum = sopac_get_locum();
  $insurge = sopac_get_insurge();
  $page_limit = variable_get('sopac_results_per_page', 20);
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  $offset = ($page_limit * $page);
  $no_circ = $locum->csv_parser($locum_cfg['location_limits']['no_request']);
  $bnum_arr = $insurge->get_tagged_items($user->uid, $tag, $page_limit, $offset);
  sopac_pager_init($bnum_arr['total'], 0, $page_limit);
  $pager_body = theme('pager', NULL, $page_limit, 0, NULL, 6);
  $hitnum = $page_offset + 1;
  $result_body = '';
  $result_body .= '<table class="hitlist-content">';
  foreach ($bnum_arr['bnums'] as $bnum) {
    $locum_result = $locum->get_bib_item($bnum);

    // Grab Stdnum
    $stdnum = $locum_result['stdnum'][0];
    // Grab item status from Locum
    $locum_result['status'] = $locum->get_item_status($bnum);
    // Get the cover image
    $cover_img_url = $locum_result['cover_img'];
    // Grab Syndetics reviews, etc..
    $review_links = $locum->get_syndetics($locum_result['stdnum'][0]);
    if (count($review_links)) {
      $locum_result['review_links'] = $review_links;
    }

    $result_body .= theme('sopac_results_hitlist', $hitnum, $cover_img_url, $locum_result, $locum->locum_config, $no_circ);
    $hitnum++;
  }
  $result_body .= "</table>";
  $result_body = theme('sopac_user_tag_hitlist', $tag, $pager_body, $result_body);

  return $result_body;
}

/**
 * Returns the tag-addition form for adding tags to the system.
 *
 * @return array Drupal form array.
 */
function sopac_tag_form($form_state, $bnum) {
  $form['tagform'] = array(
    '#type' => 'fieldset',
    '#title' => t('Add tags '),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['tagform']['tags'] = array(
    '#type' => 'textfield',
    '#title' => t('Enter your tags'),
    '#size' => 15,
    '#maxlength' => 255,
  );
  $form['tagform']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add Tags'),
    '#attributes' => array('class' => 'tagsubmit'),
  );
  $form['tagform']['help'] = array(
    '#type' => 'item',
    '#value' => "Seperate tags with a space or use quotes to join multiple words together.",
  );
  $form['tagform']['bnum'] = array(
    '#type' => 'hidden',
    '#default_value' => $bnum,
  );
  return $form;
}

function sopac_tag_form_validate($form, &$form_state) {
  global $user;
  if (!$user->uid) {
    form_set_error('tags', t('Please log in to add tags.'));
    return;
  }
  $bnum = $form_state['values']['bnum'];
  if (!$bnum) {
    form_set_error('tags', t("We're sorry, but we cannot determine which item you're trying to add a tag to. Please try again."));
    return;
  }
  if (!trim($form_state['values']['tags'])) {
    form_set_error('tags', t('Please enter the tag(s) you wish to add to this item.'));
    return;
  }
}

function sopac_tag_form_submit($form, &$form_state) {
  global $user;
  $bnum = $form_state['values']['bnum'];
  $insurge = sopac_get_insurge();
  $tids = $insurge->submit_tags($user->uid, $bnum, trim($form_state['values']['tags']));

  // Summer Game
  if (count($tids) && module_exists('summergame')) {
    if ($player = summergame_player_load(array('uid' => $user->uid))) {
      foreach ($tids as $tid) {
        $tag = $insurge->get_tag($tid);
        $points = summergame_player_points($player['pid'], 10, 'Tagged an Item',
                                           'Added ' . $tag['tag'] . ' bnum:' . $bnum);
        $points_link = l($points . ' Summer Game points', 'summergame/player');
        drupal_set_message("Earned $points_link for tagging an item in the catalog");
      }

    }
  }
}

function theme_sopac_tag_cloud($tags, $cloud_type = 'catalog', $min_size = 10, $max_size = 24, $wraplength = 19) {
  if (!count($tags)) {
    return t('No tags.');
  }

  // largest and smallest array values
  $max_qty = max(array_values($tags));
  $min_qty = min(array_values($tags));

  // find the range of values
  $spread = $max_qty - $min_qty;
  if ($spread == 0) {
    $spread = 1;
  }

  // set the font-size increment
  $step = ($max_size - $min_size) / ($spread);

  // loop through the tag array
  foreach ($tags as $tag => $value) {
    if ($cloud_type == 'personal') {
      $link = 'user/tag/show/' . urlencode($tag);
    }
    else {
      $link = variable_get('sopac_url_prefix', 'cat/seek') . '/search/tags/' . urlencode($tag);
    }
    $size = round($min_size + (($value - $min_qty) * $step));
    if ($spread == 1) {
      $size = $size + 2;
    }
//    $disp_tag = htmlentities(wordwrap($tag, $wraplength, "-<br />-", 1));
    $disp_tag = htmlentities($tag, ENT_NOQUOTES, 'UTF-8');
    $attributes = array('title' => $value . ' things tagged with ' . $tag, 'style' => 'font-size: ' . $size . 'px');
    $cloud .= l($disp_tag, $link, array('attributes' => $attributes)) . ' ';
  }
  return '<div class="tag-cloud">' . $cloud . '</div>';
}

function sopac_review_page($page_type) {
  global $user;

  $locum = sopac_get_locum();
  $insurge = sopac_get_insurge();
  $page_limit = 5; // TODO make this configurable
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  $offset = ($page_limit * $page);

  switch($page_type) {
    case 'catalog':
      $actions = sopac_parse_uri();
      $bnum = $actions[1];
      $bnum_arr[] = $bnum;
      $item = $locum->get_bib_item($bnum);
      $ratings = theme('sopac_get_rating_stars', $bnum);
      $reviews = $insurge->get_reviews(NULL, $bnum_arr, NULL, $page_limit, $offset);
      sopac_pager_init($reviews['total'], 0, $page_limit);
      $title = t('Reviews for ') . ucwords($item['title']);
      $no_rev_msg = t('No reviews have been written yet for ') . '<i>' . ucwords($item['title']) . '</i>';

      $i = 0;
      foreach ($reviews['reviews'] as $insurge_review) {
        $rev_arr[$i]['rev_id'] = $insurge_review['rev_id'];
        $rev_arr[$i]['bnum'] = $insurge_review['bnum'];
        if ($insurge_review['uid']) { $rev_arr[$i]['uid'] = $insurge_review['uid']; }
        $rev_arr[$i]['timestamp'] = $insurge_review['rev_create_date'];
        $rev_arr[$i]['rev_title'] = $insurge_review['rev_title'];
        $rev_arr[$i]['rev_body'] = $insurge_review['rev_body'];
        $i++;
      }

      if ($item['bnum']) {
        if (!$insurge->check_reviewed($user->uid, $item['bnum']) && $user->uid) {
          $rev_form = drupal_get_form('sopac_review_form', $item['bnum']);
        }
        elseif (!$user->uid) {
          $rev_form = '<div class="review-login">' . l(t('Login'), 'user/login', array('query' => drupal_get_destination())) . t(' to write a review') . '</div>';
        }
        $result_page = theme('sopac_review', $user, $title, $rev_arr, $page_type, $rev_form, $ratings, $no_rev_msg);
        $result_page .= theme('pager', NULL, $page_limit, 0, NULL, 6);
      }
      else {
        $result_page = t('This record does not exist.');
      }

      break;
    case 'personal':
      $rev_uid = $user->uid;
      $no_rev_msg = t('You have not submitted any reviews yet.');
      $reviewer_name = $user->name;
    case 'user':
      $actions = sopac_parse_uri(FALSE);
      $rev_uid = $rev_uid ? $rev_uid : $actions[2];
      $rev_user = user_load(array('uid' => $rev_uid));
      $no_rev_msg = $no_rev_msg ? $no_rev_msg : $rev_user->name . t(' has not submitted any reviews yet.');
      $reviewer_name = $reviewer_name ? $reviewer_name : $rev_user->name;
      $reviews = $insurge->get_reviews($rev_uid, NULL, NULL, $page_limit, $offset);
      sopac_pager_init($reviews['total'], 0, $page_limit);
      $title = t('Reviews by ') . $reviewer_name;
      $i = 0;
      $bib_item_arr = array();
      foreach ($reviews['reviews'] as $insurge_review) {
        $locum_result = $locum->get_bib_items_arr(array($insurge_review['bnum']));
        $bib_item_arr[(string) $insurge_review['bnum']] = $locum_result[$insurge_review['bnum']];
        $rev_arr[$i]['rev_id'] = $insurge_review['rev_id'];
        $rev_arr[$i]['bnum'] = $insurge_review['bnum'];
        $rev_arr[$i]['uid'] = $rev_uid;
        $rev_arr[$i]['timestamp'] = $insurge_review['rev_create_date'];
        $rev_arr[$i]['rev_title'] = $insurge_review['rev_title'];
        $rev_arr[$i]['rev_body'] = $insurge_review['rev_body'];
        $i++;
      }
      $result_page .= theme('sopac_review', $user, $title, $rev_arr, $page_type, $rev_form, $ratings, $no_rev_msg, $bib_item_arr);
      $result_page .= theme('pager', NULL, $page_limit, 0, NULL, 6);
      break;
    case 'single':
      $actions = sopac_parse_uri(FALSE);
      $rev_id[] = $actions[2];
      $reviews = $insurge->get_reviews(NULL, NULL, $rev_id);
      sopac_pager_init($reviews['total'], 0, $page_limit);
      $no_rev_msg = t('This review does not exist.');
      $i = 0;
      foreach ($reviews['reviews'] as $insurge_review) {
        $bib_item_arr = $locum->get_bib_items_arr(array($insurge_review['bnum']));
        $rev_arr[$i]['rev_id'] = $insurge_review['rev_id'];
        $rev_arr[$i]['bnum'] = $insurge_review['bnum'];
        $rev_arr[$i]['uid'] = $insurge_review['uid'];
        $rev_arr[$i]['timestamp'] = $insurge_review['rev_create_date'];
        $rev_arr[$i]['rev_title'] = $insurge_review['rev_title'];
        $rev_arr[$i]['rev_body'] = $insurge_review['rev_body'];
        $i++;
      }
      $result_page = theme('sopac_review', $user, $title, $rev_arr, $page_type, $rev_form, $ratings, $no_rev_msg, $bib_item_arr);
      break;
  }

  return '<p>'. t($result_page) .'</p>';

}

function sopac_review_form() {
  global $user;

  $pathinfo = explode('/', trim($_GET['q']));
  if ($pathinfo[1] == 'edit') {
    $title = t('Edit this Review');
    $insurge = sopac_get_insurge();
    $rev_id = $pathinfo[2];
    $insurge_review = $insurge->get_reviews($user->uid, NULL, array($rev_id));
    $review = $insurge_review['reviews'][0];
    $collapsible = FALSE;
    $collapsed = FALSE;
    $form_type = 'edit';
    $bnum = $review['bnum'];
    $form['#redirect'] = urldecode($_GET['ref']);
  }
  else {
    $title = t('Write a Review!');
    $args = func_get_args();
    $collapsible = TRUE;
    $collapsed = TRUE;
    $form_type = 'new';
    $bnum = $args[1];
  }

  $form['revform'] = array(
    '#type' => 'fieldset',
    '#title' => t($title),
    '#collapsible' => $collapsible,
    '#collapsed' => $collapsed,
  );
  $form['revform']['rev_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Enter a title for your review'),
    '#size' => 30,
    '#required' => TRUE,
    '#maxlength' => 254,
    '#default_value' => $review['rev_title'],
  );
  $form['revform']['rev_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Your review'),
    '#default_value' => $review['rev_body'],
    '#required' => TRUE,
    '#rows' => 15,
  );
  $form['revform']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit your Review'),
  );
  $form['revform']['form_type'] = array(
    '#type' => 'hidden',
    '#value' => $form_type,
  );
  $form['revform']['rev_bnum'] = array(
    '#type' => 'hidden',
    '#value' => $bnum,
  );
  if ($rev_id) {
    $form['revform']['rev_id'] = array(
      '#type' => 'hidden',
      '#value' => $rev_id,
    );
  }
  return $form;
}

function sopac_review_form_submit($form, &$form_state) {
  global $user;

  if ($user->uid) {
    $insurge = sopac_get_insurge();
    if ($form_state['values']['form_type'] == 'edit') {
      $insurge->update_review($user->uid, $form_state['values']['rev_id'], $form_state['values']['rev_title'], $form_state['values']['rev_body']);
    }
    elseif ($form_state['values']['form_type'] == 'new') {
      $insurge->submit_review($user->uid, $form_state['values']['rev_bnum'], $form_state['values']['rev_title'], $form_state['values']['rev_body']);
      // Summer Game
      if (module_exists('summergame')) {
        if ($player = summergame_player_load(array('uid' => $user->uid))) {
          $points = summergame_player_points($player['pid'], 100, 'Wrote Review',
                                             $form_state['values']['rev_title'] . ' bnum:' . $form_state['values']['rev_bnum']);
          $points_link = l($points . ' Summer Game points', 'summergame/player');
          drupal_set_message("Earned $points_link for writing a review");
        }
      }
    }
  }
}

function theme_sopac_review_block($block_type) {
  global $user;
  $max_shown = 10; // TODO make this configurable
  $uri = sopac_parse_uri();

  // get_reviews($uid = NULL, $bnum_arr = NULL, $rev_id_arr = NULL, $limit = 10, $offset = 0, $order = 'ORDER BY rev_create_date DESC')
  $insurge = sopac_get_insurge();
  $locum = sopac_get_locum();
  switch ($block_type) {
    case 'personal':
      $reviews = $insurge->get_reviews($user->uid, NULL, NULL, $max_shown);
      $no_rev = t('You have not written any reviews yet.');
      break;
    case 'record':
      $bnum_arr[] = $uri[1];
      $locum_result = $locum->get_bib_item($uri[1]);
      $reviews = $insurge->get_reviews(NULL, $bnum_arr, NULL, $max_shown);
      $no_rev = t('No reviews have been written yet for ') . '<i>' . $locum_result['title'] . '</i>.';
      break;
    case 'overview':
    default:
      $reviews = $insurge->get_reviews(NULL, NULL, NULL, $max_shown);
      $no_rev = t('No reviews have been written yet.');
      break;
  }

  $result_page = '';
  if (count($reviews['reviews'])) {
    foreach ($reviews['reviews'] as $insurge_review) {
      $locum_result = $locum->get_bib_item($insurge_review['bnum']);
      $title_arr = explode(':', htmlentities($locum_result['title'], ENT_NOQUOTES, 'UTF-8'));
      $title = trim($title_arr[0]);
      $review_link = 'review/view/' . $insurge_review['rev_id'];
      $item_link = variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $insurge_review['bnum'];
      $result_page .= '<div class="review-block-list-item">';
      $result_page .= '<div class="review-block-revtitle">' . l(htmlentities($insurge_review['rev_title'], ENT_NOQUOTES, 'UTF-8'), $review_link) . '</div>';
      $result_page .= '<div class="review-block-itemtitle">' . t('A review of ') . '<span class="review-block-itemtitle-title">' . l($title, $item_link) . '</span></div>';
      $result_page .= "</div>\n";
    }
  }
  else {
    $result_page = $no_rev;
  }

  return $result_page;
}

function sopac_delete_review_form() {
  global $user;

  $pathinfo = explode('/', trim($_GET['q']));
  $rev_id = $pathinfo[2];

  $form['#redirect'] = urldecode($_GET['ref']);
  $form['revform'] = array(
    '#type' => 'fieldset',
    '#title' => t('Do you really want to delete this review?'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['revform']['submit_y'] = array(
    '#type' => 'submit',
    '#value' => t('Yes'),
  );
  $form['revform']['submit_n'] = array(
    '#type' => 'submit',
    '#value' => t('No'),
  );
  $form['revform']['rev_id'] = array(
    '#type' => 'hidden',
    '#value' => $rev_id,
  );

  return $form;
}

function sopac_delete_review_form_submit($form, &$form_state) {
  global $user;

  if (strtolower($form_state['values']['op']) == 'yes') {
    $insurge = sopac_get_insurge();

    if (module_exists('summergame')) {
      if ($player = summergame_player_load(array('uid' => $user->uid))) {
        $reviews = $insurge->get_reviews(NULL, NULL, array($form_state['values']['rev_id']));
        $review = $reviews['reviews'][0];

        // Delete the points from the player record if found
        db_query("DELETE FROM sg_ledger WHERE pid = %d AND code_text = 'Wrote Review' " .
                 "AND description LIKE '%%bnum:%d' AND description LIKE '%s%%'",
                 $player['pid'], $review['bnum'], $review['rev_title']);
        if (db_affected_rows()) {
          $player_link = l($points . ' Summer Game score card', 'summergame/player');
          drupal_set_message("Removed points for this review from your $player_link");
        }
      }
    }
    $insurge->delete_review($user->uid, $form_state['values']['rev_id']);
  }
}

/**
 * Returns the HTML and JS code for the ratings widget for $bnum
 *
 * @param object $user Drupal user object
 * @param int $bnum Bib number
 * @param float $rating Rating value override
 * @param boolean $show_label Displays the vote count
 * @param boolean $post_redirect Redirect the page back on itself after form submit.  Useful for lists.
 * @return string HTML/JS widget
 */
function theme_sopac_get_rating_stars($bnum, $rating = NULL, $show_label = TRUE, $post_redirect = FALSE, $id = 'default') {
  global $user;

  // Load Required JS libraries
  drupal_add_js(drupal_get_path('module', 'sopac') . '/js/jquery.rating.js');

  $insurge = sopac_get_insurge();
  $rate_options = array('0.5', '1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0');

  if ($_POST[$id . '_rating_submit_' . $bnum] && $user->uid) {
    $insurge = sopac_get_insurge();
    $insurge->submit_rating($user->uid, $bnum, $_POST[$id . '_bib_rating_' . $bnum]);
    // Summer Game
    if (module_exists('summergame')) {
      if ($player = summergame_player_load(array('uid' => $user->uid))) {
        // Check that player has not already rated this item
        $res = db_query("SELECT lid FROM sg_ledger WHERE pid = %d AND code_text = 'Rated an Item' " .
                        "AND description LIKE '%%bnum:%d' LIMIT 1",
                        $player['pid'], $bnum);
        $rate_count = db_fetch_object($res);
        if (!$rate_count->lid) {
          $points = summergame_player_points($player['pid'], 10, 'Rated an Item',
                                             'Added a Rating to the Catalog bnum:' . $bnum);
          $points_link = l($points . ' Summer Game points', 'summergame/player');
          drupal_set_message("Earned $points_link for rating an item in the catalog");
        }
      }
    }

    if ($post_redirect) {
      header('Location: ' . request_uri());
    }
  }

  if (!$user->uid) {
    $disable_flag = ' disabled="disabled" ';
    $login_string = ' - ' . l(t('Login'), 'user/login', array('query' => drupal_get_destination())) . t(' to add yours');
  }

  $ratings_info_arr = $insurge->get_rating($bnum);
  if ($rating) {
    $ratings_info_arr['value'] = $rating;
  }

  $star_code =
  '
  <script>
  $(function(){
    $(\'.hover-star\').rating({
      callback: function(value, link) {
        this.form.submit();
      },
      required: true,
      half: true
    });
  });
  </script>
  <form method="post" name="form_' . $bnum . '">
  <table><tr><td width="90px">';

  foreach ($rate_options as $val) {
    $checked_flag = '';
    if ((float) $val == (float) $ratings_info_arr['value']) {
      $checked_flag = ' checked="checked"';
    }
    $star_code .= '<input class="hover-star {split:2}" type="radio" name="' . $id . '_bib_rating_' . $bnum . '" value="' . $val . '"' . $disable_flag . $checked_flag . "/>\n";
  }
  $star_code .= '<input type="hidden" name="' . $id . '_rating_submit_' . $bnum . '" value="1"></form></td><td>';

  /*
if ($show_label) {
    if (!$ratings_info_arr['count']) {
      $count_msg = t('No votes yet');
    }
    elseif ($ratings_info_arr['count'] == 1) {
      $count_msg = '1 vote';
    }
    else {
      $count_msg = $ratings_info_arr['count'] . t(' votes');
    }
    $count_msg .= $login_string;
    $star_code .= '<span id="star_vote_count">(' . $count_msg . ')</span>';
  }
*/

  $star_code .= '</td></tr></table>';

  return $star_code;
}
