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

if ($title) {
  print '<div class="review-item-title">' . $title . '</div>';
}
if ($ratings) {
  print '<br />' . $ratings;
}
if ($rev_form) {
  print '<br />' . $rev_form;
}

if (count($rev_arr)) {
  print '<div class="review-page">';
  foreach ($rev_arr as $rev_item) {
    print '<div class="review-block"><div class="review-header"><span class="review-title">' . l($rev_item['rev_title'], 'review/view/' . $rev_item['rev_id']) . '</span><br />';
    if ($bib_info[$rev_item['bnum']]['title']) {
      print '<span class="item-request"><strong>»</strong></span> ';
      print '<span class="review-byline">' . t('Review for ') . l($bib_info[$rev_item['bnum']]['title'], variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $rev_item['bnum']) . '</span><br />';
    }
    if ($rev_item['uid']) {
      $rev_user = user_load(array('uid' => $rev_item['uid']));
      print '<span class="item-request"><strong>»</strong></span> ';
      print '<span class="review-byline">' . t('submitted by ') . '<span class="review-author">' . l($rev_user->name, 'review/user/' . $rev_item['uid']) . '</span> ';
      print ':: <span class="review-date">' . date("F j, Y, g:i a", $rev_item['timestamp']) . '</span></span>';
      if ($user->uid == $rev_item['uid']) {
        print ' [ ' .
              l(t('delete'), 'review/delete/' . $rev_item['rev_id'], array('attributes' => array('title' => 'Delete this review'), 'query' => array('ref' => $_GET['q']))) .
              ' ] [ ' .
              l(t('edit'), 'review/edit/' . $rev_item['rev_id'], array('attributes' => array('title' => 'Edit this review'), 'query' => array('ref' => $_GET['q']))) .
              ' ]';
      }
    }
    print '</div><div class="review-body">';
    print nl2br($rev_item['rev_body']);
    print '</div></div>';

  }
  print '</div>';
}
else {
  print $no_rev_msg;
}
