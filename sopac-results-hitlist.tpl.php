<?php
/*
 * Theme template for SOPAC hitlist
 *
 */

// Prep some stuff here
$new_author_str = sopac_author_format($locum_result['author'], $locum_result['addl_author']);
if($locum_result['artist']){
  $new_author_str = $locum_result['artist'];
}
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
global $user;
if (!module_exists('covercache') || $locum_result['zipmd5']) {
  if (strpos($locum_result['cover_img'], 'http://') !== FALSE) {
    $cover_img = $locum_result['cover_img'];
  }
  else if($locum_result['zipmd5']) {
    $license = isset($locum_result['licensed_from']) ? $locum_result['licensed_from'] : 'magnatune';
    $cover_img = "http://media.aadl.org/music/$license/".$locum_result['_id']."/data/cover.jpg";
    $locum_result['mat_code'] = 'z';
  }
  else {
    $cover_img = base_path() . drupal_get_path('module', 'sopac') . '/images/nocover.png';
  }
  $cover_img = '<img width="100" src="' . $cover_img . '">';
  $cover_img = l($cover_img,
                 variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['_id'],
                 array('html' => TRUE, 'alias' => TRUE));
}

$list_display = strpos($locum_result['namespace'], 'list') !== FALSE;
if($list_display) {
  $locum_result['_id'] = $locum_result['bnum'];
}
// Get Zoom Lends copies
$zooms_avail = $locum_result['status']['callnums']['Zoom Lends DVD']['avail'] + $locum_result['status']['callnums']['Zoom Lends Book']['avail'];
$avail = $locum_result['status']['avail'] - $zooms_avail;

if ($avail > 0) {
  $availtext = 'There ' . ($avail == 1 ? 'is' : 'are') . " currently $avail available";
}
else {
  $availtext = 'There are no copies currently available,';
}
if ($zooms_avail > 0) {
//  $zoom_text = l('Zoom Lends', 'catalog/browse/unusual', array('alias' => TRUE, 'fragment' => 'ZOOM', 'query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe')));
  $zoom_text = 'Zoom Lends';
  $availtext .= " ($zooms_avail $zoom_text " . ' available)';
}
if($locum_result['mat_code'] == 'z') {
  $availtext = "This item is available for download";
}
else if($locum_result['mat_code'] == 'q') {
  $availtext = "This item is available for online streaming by AADL cardholders";
}
else if($locum_result['db_link']) {
  $availtext = "This item is a database that AADL subscribes to. You can access it online.";
}
else if ($locum_result['status']['avail']) {
  $availtext .= ":";
}
else {
  $reqtext = ($locum_result['status']['holds'] ? $locum_result['status']['holds'] : 'No') .
             ' request' . ($locum_result['status']['holds'] == 1 ? '' : 's') . " on " .
             $locum_result['status']['total'] . ' ' . ($locum_result['status']['total'] == 1 ? 'copy' : 'copies');
}

?>
    <?php if($minimal) { ?>
    <tr class="hitlist-item"><td><?php print $result_num; ?></td>
    <td><strong><?php print l(mb_convert_case($locum_result['title'],MB_CASE_TITLE, "UTF-8"), $url_prefix . '/record/' . $locum_result['_id'],array('alias' => TRUE)); if($locum_result[title_medium]){ print ' ['.$locum_result[title_medium].']'; } ?></strong></td><td><?php if($new_author_str) { print l($new_author_str, $url_prefix . '/search/author/' . urlencode($new_author_str),array('alias' => TRUE)); } ?></td><td><?php if($locum_result['callnum']) {echo $locum_result['callnum'];} ?></td><td><?php if ($list_display) { echo str_replace(', 12:00 am', '', date("F j, Y, g:i a", strtotime($locum_result['tag_date']))); } ?></td></tr>
    <?php
    }
    else {
    ?>
    <div class="hitlist-item <?php if($locum_result['status']['avail']) print "available"; ?>">
    <div class="hitlist-number"><?php print $result_num; ?></div>
    <div class="hitlist-cover">
      <?php print $cover_img; ?>
    </div>
    <div class="hitlist-format">
      <img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/' . $locum_result['mat_code'] . '.png' ?>">
      <br />
      <?php print wordwrap($locum_config['formats'][$locum_result['mat_code']], 8, '<br />'); ?>
    </div>
    <div class="hitlist-info">
      <?php
        if ($locum_result['active'] == '0') {
          print '<div class="suppressed">AADL currently owns no copies of this item</div>';
        }
      ?>
      <ul>
        <li class="hitlist-title">
          <?php
          if ($locum_result['title_medium']) {
            $locum_result['title'] = $locum_result['title'] . ' ' .$locum_result['title_medium'];
          }
          ?>
          <strong><?php print l(title_case($locum_result['title']), $url_prefix . '/record/' . $locum_result['_id'],array('alias' => TRUE,'html' => TRUE)); ?></strong>
          <?php if($locum_result['non_romanized_title']){ echo " (". $locum_result['non_romanized_title'] .")";} if($locum_result['pub_year']){ ?>
          (<?php echo $locum_result['pub_year']; ?>)<?php } ?>
        </li>
        <li>
        <?php
          print l($new_author_str, $url_prefix . '/search/author/' . urlencode($new_author_str),array('alias' => TRUE));
        ?><?php if($locum_result['non_romanized_author']){ echo " (". $locum_result['non_romanized_author'] .")";}?>
        </li>
        <?php if($locum_result['Director']) { ?>
          <li>Director: <?php echo l($locum_result['Director'][0], $url_prefix . '/search/author/' . urlencode($locum_result['Director'][0]),array('alias' => TRUE)); ?></li>
        <?php } ?>
              <?php
      if ($locum_result['status']['callnums']) {
        if (count($locum_result['status']['callnums']) > 5) {
          print '<li>Call number: <strong>' . l($locum_result['callnum'], $url_prefix . '/search/callnum/"' . urlencode($locum_result['callnum']) .'"',array('alias' => TRUE)) . '</strong></li>';
        } else {
          print '<li>Call number: <strong>' . implode(", ", array_map('sopac_linkfromcallnum', array_keys($locum_result['status']['callnums']))) . '</strong></li>';
        }
      }
        elseif (count($locum_result['avail_details'])) {
          ?><li><?php print t('Call number: '); ?><strong><?php print key($locum_result['avail_details']); ?></strong></li><?php
        } ?>
        <?php if ($locum_result['genres']) { ?>
        <li>Genres: <?php echo implode(', ',$locum_result['genres']); ?></li>
        <?php } ?>
        <?php if ($locum_result['mpaa_rating']) { ?>
        <li>Rated: <span class="mpaa_rating"><?php echo $locum_result['mpaa_rating']; ?></span></li>
        <?php } ?>
        <?php if ($locum_result['sort'] == 'catalog_newest') { ?>
        <li><strong>Added on <?php echo date('m-d-Y', strtotime($locum_result['bib_created'])); ?></strong></li>
        <?php } ?>
        <?php if ($list_display) { ?>
        <li><strong>Added to list</strong> on
        <?php
          // Don't display timestamp if it's exactly midnight (Checkout History)
          echo str_replace(', 12:00 am', '', date("F j, Y, g:i a", strtotime($locum_result['tag_date'])));
        ?>
        </li>
        <?php } ?>
        <ul class="hitlist-avail">
          <li class="hitlist-subtitle"><?php print $availtext; ?></li>
          <?php
            if ($locum_result['status']['avail']) {
              // Build list of locations
              $locations = array();
              foreach ($locum_result['status']['items'] as $item) {
                if ($item['avail']) {
                  $locations[$item['loc_code']] = $item['location'];
                }
              }
              print '<li>' . implode(', ', $locations) . '</li>';
            }
            if ($reqtext) {
              print '<li class="hitlist-subtitle">' . $reqtext . '</li>';
            }
          ?>
        </ul>
    <?php
      if ($locum_result['review_links']) {
        print '<li class="button hassub">Reviews &amp; Summaries (' .
              count($locum_result['review_links']) . ')<ul class="submenu" id="rev_' . $locum_result['_id'] . '">';
        foreach ($locum_result['review_links'] as $rev_title => $rev_link) {
          $rev_link = explode('?', $rev_link);
          print '<li>' . l($rev_title, $rev_link[0], array('query' => $rev_link[1], 'attributes' => array('html' => TRUE, 'target' => "_new", 'alias' => TRUE))) . '</li>';
        }
        print '</ul><span></span></li>';
      }
      if($locum_result['trailers']) { ?>
        <li class="button"><?php print l("Watch Trailer / Previews", $url_prefix . '/record/' . $locum_result['_id']); ?></li>
    <?php  } ?>
    </ul>
    </div>
    <div class="hitlist-actions">
      <ul>
        <?php
          if ($locum_result['stream_filetype'] == 'pdf') { ?>
            <li class="button green"><?php echo l("Download Available", variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['_id'], array('alias' => TRUE)); ?></li>
          <?php }
          else if($locum_result['mat_code'] == 'z') { ?>
            <li class="button green"><?php echo l("View Album", variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['_id'], array('alias' => TRUE)); ?></li>
       <?php  } elseif ($locum_result['mat_code'] == 'q') { ?>
            <li class="button green"><?php echo l("Watch Online", variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['_id'], array('alias' => TRUE)); ?></li>
       <?php }  else if ($locum_result['status']['libuse'] > 0 && $locum_result['status']['libuse'] == $locum_result['status']['total']) { ?>
            <li class="button">Library Use Only</li>
        <?php } else if ($locum_result['disable_requests'] || in_array($locum_result['loc_code'], $no_circ) || in_array($locum_result['mat_code'], $no_circ)) { ?>
            <li class="button red">Not Requestable</li>
        <?php }
            else if ($locum_result['db_link']){ ?>
            <li class="button"><a href="<?php print $locum_result['db_link']; ?>">Visit Website</a></li>
        <?php }
          else {
            print sopac_put_request_link($locum_result['_id'],
                                         $locum_result['status']['avail'],
                                         $locum_result['status']['holds'],
                                         $locum_config['formats'][$locum_result['mat_code']]);
            if(user_access('staff request')){
              print sopac_put_staff_request_link($locum_result['_id']);
            }
          }
          if ($user->uid && $locum_result['mat_code'] != 'z' && $locum_result['mat_code'] != 'q') {
            include_once('sopac_user.php');
            print sopac_put_list_links($locum_result['_id'], $list_display);
          }
          if ($list_display) {
            // PART OF A LIST, SHOW ADDITIONAL ACTIONS
            $list_id = intval(str_replace('list', '', $locum_result['namespace']));
            if (sopac_lists_access($list_id)) {
              $value = $locum_result['value'];
              $bnum = $locum_result['_id'];
              if (!$locum_result['freeze']) {
                print '<li class="button green">' . l('Move to Top of List', "user/listmovetop/$list_id/$value", array('alias' => TRUE)) . '</li>';
              }
              print '<li class="button red">' . l('Remove from List', "user/listdelete/$list_id/$bnum", array('alias' => TRUE)) . '</li>';
            }
          }
        ?>
      </ul>
    </div>
  <?php } ?>
  </div>
