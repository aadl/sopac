<?php
/*
 * Theme template for SOPAC hitlist
 *
 */

// Prep some stuff here
$new_author_str = sopac_author_format($locum_result['author'], $locum_result['addl_author']);
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
global $user;
profile_load_profile($user);

if (!module_exists('covercache')) {
  if (strpos($locum_result['cover_img'], 'http://') !== FALSE) {
    $cover_img = $locum_result['cover_img'];
  }
  else {
    $cover_img = base_path() . drupal_get_path('module', 'sopac') . '/images/nocover.png';
  }
  $cover_img = '<img width="100" src="' . $cover_img . '">';
  $cover_img = l($cover_img,
                 variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $locum_result['bnum'],
                 array('html' => TRUE));
}
$list_display = strpos($locum_result['namespace'], 'list') !== FALSE;
?>
  <tr class="hitlist-item <?php if($locum_result['status']['avail']) print "available"; ?>">
    <td class="hitlist-number"><?php print $result_num; ?></td>
    <td class="hitlist-cover">
      <?php print $cover_img; ?>
    </td>
    <td class="hitlist-info">
      <?php
        if ($locum_result['active'] == '0') {
          print '<div class="suppressed">This Record is Suppressed</div>';
        }
      ?>
      <ul>
        <li class="hitlist-title">
          <strong><?php print l(ucwords($locum_result['title']), $url_prefix . '/record/' . $locum_result['bnum']); ?></strong>
          <?php
          if ($locum_result['title_medium']) {
            print "[$locum_result[title_medium]]";
          }
          ?>
        </li>
        <li>
        <?php
          print l($new_author_str, $url_prefix . '/search/author/' . urlencode($new_author_str));
        ?>
        </li>
        <li><?php print $locum_result['pub_info']; ?></li>
        <?php if ($locum_result['callnum']) {
          ?><li><?php print t('Call number: '); ?><strong><?php print $locum_result['callnum']; ?></strong></li><?php
        }
        elseif (count($locum_result['avail_details'])) {
          ?><li><?php print t('Call number: '); ?><strong><?php print key($locum_result['avail_details']); ?></strong></li><?php
        } ?>
        <ul class="hitlist-avail">
          <li class="hitlist-subtitle">
            <?php
            if ($locum_result['status']['avail']) {
              // Build list of locations
              $locations = array();
              foreach ($locum_result['status']['items'] as $item) {
                if ($item['avail']) {
                  $locations[$item['loc_code']] = $item['location'];
                }
              }
              $locations = implode(', ', $locations);

              print $locum_result['status']['avail'] . t(' of ') . $locum_result['status']['total'] . ' ';
              print ($locum_result['status']['total'] == 1 ? t('copy') : t('copies')) . ' ';
              print t('available:') . '</li><li>' . $locations;
            }
            else {
              print t('No copies available');
            }
            ?>
          </li>
        </ul>
        <li class="hitlist-rating">
        <?php
    if (variable_get('sopac_social_enable', 1)) {
      print theme_sopac_get_rating_stars($locum_result['bnum']);
    }
    ?></li>
    <?php
      if ($locum_result['review_links']) {
        print '<li class="button hassub">Reviews &amp; Summaries (' .
              count($locum_result['review_links']) . ')<ul class="submenu" id="rev_' . $locum_result['bnum'] . '">';
        foreach ($locum_result['review_links'] as $rev_title => $rev_link) {
          $rev_link = explode('?', $rev_link);
          print '<li>' . l($rev_title, $rev_link[0], array('query' => $rev_link[1], 'attributes' => array('html' => TRUE, 'target' => "_new"))) . '</li>';
        }
        print '</ul><span></span></li>';
      }
    ?>
    </ul>
    </td>
    <td class="hitlist-actions">
      <ul>
        <?php
          if (!in_array($locum_result['loc_code'], $no_circ)) {
            print sopac_put_request_link($locum_result['bnum'],
                                         $locum_result['status']['avail'],
                                         $locum_result['status']['holds'],
                                         $locum_config['formats'][$locum_result['mat_code']]);
          }
          if ($user->uid) {
            include_once('sopac_user.php');
            print sopac_put_list_links($locum_result['bnum'], $list_display);
          }
          if ($list_display && $locum_result['uid'] == $user->uid) {
            // PART OF A LIST, SHOW ADDITIONAL ACTIONS
            $list_id = intval(str_replace('list', '', $locum_result['namespace']));
            $value = $locum_result['value'];
            $bnum = $locum_result['bnum'];
            print '<li class="button green">' . l('Move to Top of List', "user/listmovetop/$list_id/$value") . '</li>';
            print '<li class="button red">' . l('Remove from List', "user/listdelete/$list_id/$bnum") . '</li>';
          }
        ?>
      </ul>
    </td>
    <td class="hitlist-format-icon">
      <img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/' . $locum_result['mat_code'] . '.png' ?>">
      <br />
      <?php print wordwrap($locum_config['formats'][$locum_result['mat_code']], 8, '<br />'); ?>
    </td>
  </tr>