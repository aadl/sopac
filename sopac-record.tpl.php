<?php
/*
 * Item record display template
 */
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
$dl_mat_codes = in_array($item['mat_code'], $locum->csv_parser($locum_config['format_special']['download']));
$no_avail_mat_codes = in_array($item['mat_code'], $locum->csv_parser($locum_config['format_special']['skip_avail']));
$location_label = $item['loc_code'] || ($item['loc_code'] != 'none') ? $locum_config['locations'][$item['loc_code']] : '';
$google_url = "http://books.google.com/books?bibkeys=ISBN:" . trim(preg_replace('/[^\d\s]/', '', $item['stdnum'])) . "&jscmd=viewapi";
?>

<!-- begin item record -->
<table>
  <tr>
  <td width="80%" class="item-info-block">
    <div class="item-title"><strong><?php print ucwords($item['title']); ?></strong></div>
    <?php if (variable_get('sopac_social_enable', 1)) { print '<br />' . theme_sopac_get_rating_stars($item['bnum']); } ?>
    
    <ul class="item-info-list">
    <?php 
    if (!in_array($item['loc_code'], $no_circ)) {
      print '<li class="item-request"><strong>» ' . sopac_put_request_link($item['bnum']) . '</strong></li>';
    }
    ?>
    
    <?php
    if (sopac_prev_search_url(TRUE)){
      print '<li class="item-request"><strong>»</strong> <a href="' . sopac_prev_search_url() . '">' . t('Return to your search') . '</a></li>'; 
    }
    ?>
    <li class="item-details-list">
      <div class="item-detail-block">
      <table>
        <?php 
        if ($item['author']) { print '<tr><th>' . t('Author') . '</th><td><a href="/' . 
          $url_prefix . '/search/author/' . urlencode($item['author']) .
          '">' . $item['author'] . '</a></td></tr>'; } 
        if ($item['addl_author']) {
          $addl_author_arr = unserialize($item['addl_author']);
          print '<tr><th>Additional Authors</th><td>';
          foreach ($addl_author_arr as $addl_author) {
            $addl_author_links[] = '<a href="/' . $url_prefix . '/search/author/' . urlencode($addl_author) . '">' . $addl_author . '</a>';
          }
          print implode('<br />', $addl_author_links);
          print '</td></tr>';
        }
        if ($item['pub_info']) { print '<tr><th>' . t('Publication Info') . '</th><td>' . $item['pub_info'] . '</td></tr>';  }
        if ($item['pub_year']) { print '<tr><th>' . t('Year Published') . '</th><td>' . $item['pub_year'] . '</td></tr>';  }
        if ($item['series']) { print '<tr><th>' . t('Series') . '</th><td><a href="/' . 
          $url_prefix . '/search/series/' . urlencode($item['series']) . '">' . $item['series'] . '</a></td></tr>';  }
        if ($item['edition']) { print '<tr><th>' . t('Edition') . '</th><td>' . $item['edition'] . '</td></tr>';  }
        if ($item['descr']) { print '<tr><th>' . t('Description') . '</th><td>' . nl2br($item['descr']) . '</td></tr>';  }
        if ($item['callnum']) { print '<tr><th>' . t('Call #') . '</th><td>' . $item['callnum'] . '</td></tr>';  }
        if ($item['stdnum']) { print '<tr><th>' . t('ISBN/Standard #') . '</th><td>' . $item['stdnum'] . '</td></tr>';  }
        if ($item['lccn']) { print '<tr><th>' . t('LC #') . '</th><td>' . $item['lccn'] . '</td></tr>';  }
        if ($item['lang']) { print '<tr><th>' . t('Language') . '</th><td>' . $item['lang'] . '</td></tr>';  }
        if ($item['mat_code']) { print '<tr><th>' . t('Material Format') . '</th><td>' . $locum_config['formats'][$item['mat_code']] . '</td></tr>';  }
        if ($location_label) { print '<tr><th>' . t('Location') . '</th><td>' . $location_label . '</td></tr>';  }
        if ($item['notes']) {
          $notes_arr = unserialize($item['notes']);
          print '<tr><th style="padding-top:5px;">' . t('Notes') . '</th><td style="padding-top:5px;">';
          print implode('<br /><br />', $notes_arr);
          print '</td></tr>';
        }
        if ($item['subjects']) {
          $subj_arr = unserialize($item['subjects']);
          if (is_array($subj_arr)) {
            print '<tr><th style="padding-top:5px;">' . t('Subject Headings') . '</th><td style="padding-top:5px;">';
            foreach ($subj_arr as $subj) {
              $subj_links[] = '<a href="/' . $url_prefix . '/search/subject/' . urlencode($subj) . '">' . $subj . '</a>';
            }
            print implode(' | ', $subj_links);
            print '</td></tr>';
          }
        }
        if (!$no_avail_mat_codes) {
          print '<tr><th style="padding-top:5px;">' . t('Copies Available') . '</th><td style="padding-top:5px;">';
          print $item_status['avail'] . t(' of ') . $item_status['total'];
          print '</td></tr>';
        }
        if ($item_status['holds']) { print '<tr><th>' . t('# of Holds') . '</th><td>' . $item_status['holds'] . '</td></tr>'; }
        if ($item_status['on_order']) { print '<tr><th>' . t('On Order') . '</th><td>' . $item_status['on_order'] . '</td></tr>'; }
        ?>
      </table>
      </div>
    </li>
    </ul>
  </td>
  <td width="20%">
    <?php 
      if (module_exists('covercache')) {
        print $cover_img;
      } else {
        $cover_img_url = $item['cover_img'] ? $item['cover_img'] : '/' . drupal_get_path('module', 'sopac') . '/images/nocover.png'; ?>
        <ul class="item-cover-block">
        <li><img width="100" class="item-cover" src="<?php print $cover_img_url; ?>"></li>
        <?php if ($item['title_medium']) { print '<li>' . $item['title_medium'] . '</li>'; } ?>
        </ul>
      <?php } ?>
  </td>

  </tr>
</table>

<?php if (count($item_status['items']) && !$no_avail_mat_codes) { ?>
<div class="item-avail-disp">
<fieldset class=" collapsible collapsed"><legend><?php print t('Click to view all copies'); ?></legend>
<table cellspacing="0">
  <?php print '<tr class="item-avail-label"><th>' . t('Location') . '</th><th>' . t('Call Number') . '</th><th>' . t('Item Status') . '</th>'; ?>

  <?php
  foreach ($item_status['items'] as $copy_status) {
    if ($copy_status['avail'] > 0) {
      $copy_tag = ($copy_status['avail'] == 1) ? t('copy available') : t('copies available');
      $status_msg = $copy_status['avail'] . ' ' . $copy_tag;
    } else if ($copy_status['due']) {
      $status_msg = t('Next copy due') . ' ' . date('n-j-Y', $copy_status['due']);
    } else {
      $status_msg = $copy_status['statusmsg'];
    }
    print '<tr><td>' . $copy_status['location'] . '</td><td>' . $copy_status['callnum'] . '</td><td>' . $status_msg . '</td></tr>';
  }
  ?>

</table>
</fieldset>
</div>
<?php 
  } else {
    if (!$no_avail_mat_codes) { print t('No copies found.  Please contact a librarian for assistance.'); }
  }
?>
<?php //if (preg_match('%embeddable":true%is', @file_get_contents($google_url))) { ?>
<!-- <br /><span style="padding-left: 10px;"><strong><?php print t('Preview'); ?></strong></span> --!>
<div class="item-google-prev">
  <script type="text/javascript" src="http://books.google.com/books/previewlib.js"></script>
  <script type="text/javascript">
  GBS_insertEmbeddedViewer('ISBN:<?php print trim(preg_replace('/[^\d\s]/', '', $item['stdnum'])); ?>',500,400);
  </script>
</div>
<?php //} ?>
<br />
<!-- end item record -->
