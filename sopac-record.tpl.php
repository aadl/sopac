<?php
/*
 * Item record display template
 */

// Set the page title
global $user;
drupal_set_title(title_case($item['title'] . ' ' .$item['title_medium']));
drupal_set_html_head('<link rel="canonical" href="http://www.aadl.org/catalog/record/'.$item['_id'].'" />');
drupal_set_html_head('<meta property="og:image" content="'.$cover_url.'" />');
drupal_set_html_head('<meta property="og:title" content="'.title_case($item['title'] . ' ' .$item['title_medium']).'" />');
// hard coding url for now. don't want to have tests pick up other installs
drupal_set_html_head('<meta property="og:url" content="http://www.aadl.org/catalog/record/'.$item['_id'].'" />');
$books = $locum->csv_parser($locum_config['format_groups']['books']);
$video = $locum->csv_parser($locum_config['format_groups']['video']);
$music = $locum->csv_parser($locum_config['format_groups']['music']);
if(in_array($item['mat_code'],$books)){
  drupal_set_html_head('<meta property="og:type" content="book" />');
}
else if(in_array($item['mat_code'],$music)){
  drupal_set_html_head('<meta property="og:type" content="album" />');
}
else if(in_array($item['mat_code'],$video)){
  if(stristr('DVD TV',$item['callnum'])) {
    drupal_set_html_head('<meta property="og:type" content="tv_show" />');
  }
  else {
    drupal_set_html_head('<meta property="og:type" content="movie" />');
  }
}
else {
  drupal_set_html_head('<meta property="og:type" content="product" />');
}
$useriplabels = ipmap_labels();
if($item['trailers']){
  if($item['trailers'][0]['type'] == 'trailer'){
    $trailer_url = $item['trailers'][0]['url'];
    if($item['trailers'][0]['image']){
      $trailer_image = $item['trailers'][0]['image'];
    }
    if($item['trailers'][0]['cached'] && in_array('internal',$useriplabels)){
      $trailer_url = 'http://media.aadl.org/trailers/'.$item['bnum'].'.mp4';
    }
  }
}

// Set up some variables.
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
$new_author_str = sopac_author_format($item['author'], $item['addl_author']);
$dl_mat_codes = in_array($item['mat_code'], $locum->csv_parser($locum_config['format_special']['download']));
$no_avail_mat_codes = in_array($item['mat_code'], $locum->csv_parser($locum_config['format_special']['skip_avail']));
$location_label = $item['loc_code'] || ($item['loc_code'] != 'none') ? $locum_config['locations'][$item['loc_code']] : '';
$note_arr = $item['notes'];

// Get Zoom Lends copies
$zooms_avail = $item_status['callnums']['Zoom Lends DVD']['avail'] + $item_status['callnums']['Zoom Lends Book']['avail'];
$avail = $item_status['avail'] - $zooms_avail;

if ($avail > 0) {
  $reqtext = 'There ' . ($avail == 1 ? 'is' : 'are') . " currently $avail available";
}
else {
  $reqtext = 'There are no copies available';
}
if ($zooms_avail > 0) {
  //$zoom_link = l('Zoom Lends', 'catalog/browse/unusual#ZOOM', array('query' => array('lightbox' => 1), 'attributes' => array('rel' => 'lightframe')));
  $zoom_link = 'Zoom Lends';
  $reqtext .= " ($zooms_avail $zoom_link available)";
}
if ($item_status['holds'] > 0) {
  $reqtext .= ' and ' . $item_status['holds'] . ' request' . ($item_status['holds'] == 1 ? '' : 's') . " on " . $item_status['total'] . ' ' . ($item_status['total'] == 1 ? 'copy' : 'copies');
}

// Build the item availability array
if (count($item_status['items'])) {
  foreach ($item_status['items'] as $copy_status) {
    if ($copy_status['avail'] > 0) {
      $status_msg = 'Available';
    }
    else {
      $status_msg = ucwords(strtolower($copy_status['statusmsg']));
    }
    if (variable_get('sopac_multi_branch_enable', 0)) {
      $copy_status_array[] = array($copy_status['location'], $copy_status['callnum'], $locum_config['branches'][$copy_status['branch']], $status_msg);
    }
    else {
      $copy_status_array[] = array($copy_status['location'], $copy_status['callnum'], $status_msg);
    }
  }
}

?>

<!-- begin item record -->
<div class="itemrecord">

  <!-- begin left-hand column -->
  <div class="item-left">

    <!-- Cover Image -->
    <?php
    if (!module_exists('covercache')) {
      if (strpos($item['cover_img'], 'http://') !== FALSE) {
        $cover_img = $item['cover_img'];
      }
      else {
        $cover_img = base_path() . drupal_get_path('module', 'sopac') . '/images/nocover.png';
      }
      $cover_img = '<img class="item-cover" width="200" src="' . $cover_img . '">';
    }
    print $cover_img;
    ?>

    <!-- Ratings -->
    <?php
    if (variable_get('sopac_social_enable', 1)) {
      print '<div class="item-rating">';
      print theme_sopac_get_rating_stars($item['bnum']);
      print '</div>';
    }
    ?>

    <!-- Item Details -->
    <ul>
      <?php
      if ($item['pub_info']) {
        print '<li><b>Published:</b> ' . $item['pub_info'] . '</li>';
      }
      if ($item['pub_year']) {
        print '<li><b>Year Published:</b> ' . $item['pub_year'] . '</li>';
      }
      if ($item['edition']) {
        print '<li><b>Edition:</b> ' . $item['edition'] . '</li>';
      }
      if ($item['descr']) {
        print '<li><b>Description:</b> ' . nl2br($item['descr']) . '</li>';
      }
      if ($item['stdnum'] && !is_array($item['stdnum'])) {
        print '<li><b>ISBN/Standard #:</b>' . $item['stdnum'] . '</li>';
      }
      if ($item['lang']) {
        print '<li><b>Language:</b> ' . $locum_config['languages'][$item['lang']] . '</li>';
      }
      if ($item['mat_code']) {
        print '<li><b>Format:</b> ' . $locum_config['formats'][$item['mat_code']] . '</li>';
      }
      if ($item['mpaa_rating']) { ?>
        <li><strong>Rated:</strong> <span class="mpaa_rating"><?php echo $item['mpaa_rating']; ?></span></li>
      <?php } ?>
    </ul>
    <?php if (($item['stdnum'] && is_array($item['stdnum'])) || ($item['upc'] && is_array($item['upc']) )) { ?>
    <h3>ISBN/Standard Number</h3>
    <ul>
    <?php
      if (count($item['stdnum'])) {
        foreach($item['stdnum'] as $stdnum) {
          print '<li>' . $stdnum . '</li>';
        }
      }
      if (count($item['upc'])) {
        foreach($item['upc'] as $upc) {
          print '<li>' . $upc . '</li>';
        }
      }
      ?>

    </ul>
    <?php } ?>
    <?php if ($item['genres']) { ?>
    <h3>Genres</h3>
    <ul>
     <?php
        foreach($item['genres'] as $genre) {
          print '<li>' . l($genre, $url_prefix . '/search/callnum/"' . urlencode(str_replace('/',' ',$genre)).'"',array('query' => array('mat_type' => 'j'))) . '</li>';
        }
      ?>
    </ul>
    <?php } ?>
     <?php if ($item['series']) { ?>
     <h3>Series</h3>
     <ul>
     <?php
        foreach($item['series'] as $series) {
          print '<li>' . l($series, $url_prefix . '/search/series/"' . urlencode($series).'"') . '</li>';
        }
      ?>
      </ul>
      <?php } ?>

    <!-- Additional Credits -->
    <?php
    if ($item['addl_author']) {
      print '<h3>Additional Credits</h3><ul>';
      $addl_author_arr = $item['addl_author'];
      foreach ($addl_author_arr as $addl_author) {
        $addl_author_link = $url_prefix . '/search/author/%22' . urlencode($addl_author) .'%22';
        print '<li>' . l($addl_author, $addl_author_link) . '</li>';
      }
      print '</ul>';
    }
    ?>

    <!-- Subject Headings -->
    <?php
    if ($item['subjects']) {
      print '<h3>Subjects</h3><ul>';
      $subj_arr = $item['subjects'];
      if (is_array($subj_arr)) {
        foreach ($subj_arr as $subj) {
          $subjurl = $url_prefix . '/search/subject/%22' . urlencode($subj) . '%22';
          print '<li>' . l($subj, $subjurl) . '</li>';
        }
      }
      print '</ul>';
    }
    ?>
    <!-- Lists -->
    <?php if ($item['lists']){ ?>
    <h3>Recently Listed On</h3>
    <ul>
    <?php foreach($item['lists'] as $list) { ?>
      <li><?php echo l($list['title'],'user/lists/'.$list['list_id']); ?></li>
    <?php } ?>
    </ul>
    <?php } ?>
    <!-- Tags -->
    <?php
    if (variable_get('sopac_social_enable', 1)) {
      $block = module_invoke('sopac','block','view', 4);
      print $block['content'];
    }
  ?>
    <h3>Share This</h3>
    <script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
    <script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
    <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
    <ul>
    <li><fb:like href="http://www.aadl.org/catalog/record/<?php echo $item['_id']; ?>" layout="button_count" show_faces="false" width="450" font=""></fb:like></li>
    <li><a href="http://twitter.com/share" class="twitter-share-button" data-url="http://www.aadl.org/catalog/record/<?php echo $item['_id']; ?>" data-text="Enjoying <?php echo title_case($item['title'] . ' ' .$item['title_medium']); ?>" data-count="none" data-via="aadl">Tweet</a></li>
    <li><g:plusone size="small"></g:plusone></li>
    </ul>

  <!-- end left-hand column -->
  </div>


  <!-- begin right-hand column -->
  <div class="item-right">

    <!-- Supressed record notification -->
    <?php
      if ($item['active'] == '0') {
        print '<div class="suppressed">This Record is Suppressed</div>';
      }
    ?>

    <div class="item-main">
    <!-- Item Format Icon -->
    <ul class="item-format-icon">
      <li><img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/' . $item['mat_code'] . '.png' ?>"></li>
      <li style="margin-top: -2px;"><?php print wordwrap($locum_config['formats'][$item['mat_code']], 8, '<br />'); ?></li>
    </ul>

    <!-- Actions -->
    <ul class="item-actions">
      <?php
      if ($item_status['libuse'] > 0 && $item_status['libuse'] == $item_status['total']) { ?>
        <li class="button">Library Use Only</li>
      <?php } else if (in_array($item['loc_code'], $no_circ) || in_array($item['mat_code'], $no_circ)) { ?>
            <li class="button red">Not Requestable</li>
      <?php }
      else {
        print sopac_put_request_link($item['bnum'], 1, 0, $locum_config['formats'][$item['mat_code']]);
        if(user_access('staff request')){
              print sopac_put_staff_request_link($item['bnum']);
        }
      }
      if ($user->uid) {
        include_once('sopac_user.php');
        print sopac_put_list_links($item['bnum']);
      }
      // Summer Game links
      if (module_exists('summergame')) {
        if (variable_get('summergame_points_enabled', 0)) {
          if ($player = summergame_player_load(array('uid' => $user->uid))) {
            print '<li class="button">' .
                  l('I Finished This', 'http://play.aadl.org/summergame/player/consume/' . $player['pid'] . '/' . $item['bnum']) .
                  '</li>';
          }
        }
      }
      ?>
    </ul>

    <!-- Item Title -->
    <h1>
      <?php
      print title_case($item['title']);
      if ($item['title_medium']) {
        print ' '.title_case($item[title_medium]);
      }
      ?>
    </h1>
    <?php if($item['non_romanized_title']) { ?>
        <h1><?php echo $item['non_romanized_title']; ?></h1>
    <?php } ?>
    <!-- Item Author -->
    <?php
    if ($item['author']) {
      $authorurl = $url_prefix . '/search/author/' . $new_author_str;
    ?>
      <h3>by <?php echo l($new_author_str, $authorurl); ?><?php if($item['non_romanized_author']){ echo " (". $item['non_romanized_author'] .")";}?></h3>
    <?php }
    $avail_class = ($item_status['avail'] ? "request-avail" : "request-unavail");
    print '<p class="item-request ' . $avail_class . '">' . $reqtext . '</p>';
    ?>
    </div>

    <!-- Where to find it -->
    <div class="item-avail-disp">
      <h2>Where To Find It</h2>
      <?php
      if ($item_status['callnums']) {
        if (count($item_status['callnums']) > 10) {
          print '<p>Call number: <strong>' . l($item['callnum'], $url_prefix . '/search/callnum/"' . urlencode($item['callnum']) .'"',array('alias' => TRUE)) . '</strong> (see all copies below for individual call numbers)</p>';
        } else {
          print '<p>Call number: <strong>' . implode(", ", array_map('sopac_linkfromcallnum', array_keys($item_status['callnums']))) . '</strong></p>';
        }
      }

      if (count($item_status['items'])) {
        if ($item_status['avail']) {
          // Build list of locations
          $locations = array();
          foreach ($item_status['items'] as $itemstat) {
            if ($itemstat['avail']) {
              $locations[$itemstat['loc_code']] = $itemstat['location'];
            }
          }
          $locations = implode(', ', $locations);

          print "<p>Available Copies: <strong>$locations</strong></p>";
        }

        print '<div><fieldset class="collapsible collapsed"><legend>Show All Copies (' . count($item_status['items']) . ')</legend><div>';
        if (variable_get('sopac_multi_branch_enable', 0)) {
          print theme('table', array("Location", "Call Number", "Branch", "Item Status"), $copy_status_array);
        }
        else {
          print theme('table', array("Location", "Call Number", "Item Status"), $copy_status_array);
        }
        print '</div></fieldset></div>';
      }
      elseif ($item['download_link']) {
        print '<div class="item-request">';
        print '<p>' . l(t('Download this Title'), $item['download_link'], array('attributes' => array('target' => '_new'))) . '</p>';
        print '</div>';
      }
      else {
        if (!$no_avail_mat_codes) {
          print '<p>No copies found.</p>';
        }
      }
      if (count($item_status['orders'])) {
        print '<p>' . implode("</p><p>", $item_status['orders']) . '</p>';
      }
      ?>
    </div>
    <?php if($item['machinetags']['bctg']) { ?>
    <div id="item-trailer">
      <h2>Additional Content</h2>
      <p>Each Book Club to Go kit contains a guide to facilitate group discussion and understanding of the book that includes summary information and reviews of the title, an author biography, a list of suggested discussion questions and read-alikes, and tips for book groups.  This guide is available for download:</p>
      <?php foreach($item['machinetags']['bctg'] as $machinetag) { ?>
        <p><a href="<?php print $machinetag['value']; ?>">Guide<?php print ($machinetag['predicate'] == 'large') ? ' (large print)' : ''; print ($machinetag['predicate'] == 'text') ? ' (text file)' : ''; ?>: <?php print title_case($item['title']);?></a></p>
      <?php } ?>
    </div>
    <?php } if($item['tq_item']){ ?>    
    <div id="item-trailer">
    <h2><?php echo $item['tq_item']; ?></h2>
    <p><?php echo $item['tq_text']; ?></p>
    </div>
    <?php } ?>
    <?php if($trailer_url){ ?>
    <div id="item-trailer">
      <h2>Trailer / Previews</h2>
      <p>
      <object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='480' height='308' id='single1' name='single1'>
      <param name='movie' value='http://media.aadl.org/jw52/player.swf'>
      <param name='allowfullscreen' value='true'>
      <param name='allowscriptaccess' value='always'>
      <param name='wmode' value='transparent'>
      <param name='flashvars' value='provider=video&file=<?php echo rawurlencode($trailer_url); if($trailer_image) { echo '&image='.$trailer_image; } ?>'>
      <embed
      type='application/x-shockwave-flash'
      id='single2'
      name='single2'
      src='http://media.aadl.org/jw52/player.swf'
      width='480'
      height='308'
      bgcolor='undefined'
      allowscriptaccess='always'
      allowfullscreen='true'
      wmode='transparent'
      flashvars='provider=video&file=<?php echo rawurlencode($trailer_url); if($trailer_image) { echo '&image='.$trailer_image; } ?>' />
      </object>
      </p>
      <p>Trailers Powered by Internet Video Archive</p>
    </div>
    <?php } ?>
    <?php if($item['tracks']) { ?>
    <div id="item-samples">
      <h2>Tracks</h2>
      <ul class="samples">
      <?php foreach($item['tracks'] as $track => $info) { ?>
        <li><a href="http://media.aadl.org/cdsamples/<?php echo $item['trackupc']['upc']; ?>/<?php echo $item['trackupc']['upc']; ?>.<?php echo ltrim($info['track'],"0"); ?>.mp3"><?php echo $info['track']; ?>. <?php echo $info['name']; ?></a></li>
      <?php } ?>
      </ul>
    </div>
    <?php } ?>
    <!-- Notes / Additional Details -->
    <?php
    if (is_array($note_arr)) {
      print '<div id="item-notes">';
      print '<h2>Additional Details</h2>';
      foreach($note_arr as $note) {
        print '<p>' . $note . '</p>';
      }
      print '</div>';
    }
    ?>

    <!-- Syndetics / Review Links -->
    <?php
    if ($item['review_links']) {
      print '<div id="item-syndetics">';
      print '<h2>Reviews &amp; Summaries</h2>';
      print '<ul>';
      foreach ($item['review_links'] as $rev_title => $rev_link) {
        $rev_link = explode('?', $rev_link);
        print '<li>' . l($rev_title, $rev_link[0], array('query' => $rev_link[1], 'attributes' => array('target' => '_new'))) . '</li>';
      }
      print '</ul></div>';
    }
    ?>

    <!-- Community / SOPAC Reviews -->
    <div id="item-reviews">
      <h2>Community Reviews</h2>
      <?php
      if (count($rev_arr)) {
        foreach ($rev_arr as $rev_item) {
          print '<div class="hreview">';
          print '<h3 class="summary">' . l($rev_item['rev_title'], 'review/view/' . $rev_item['rev_id'], array('attributes' => array('class' => 'fn url'))) . '</h3>';
          if ($rev_item['uid']) {
            $rev_user = user_load(array('uid' => $rev_item['uid']));
            print '<p class="review-byline">submitted by <span class="review-author">' . l($rev_user->name, 'review/user/' . $rev_item['uid']) . ' on <abbr class="dtreviewed" title="' . date("c", $rev_item['timestamp']) . '">' . date("F j, Y, g:i a", $rev_item['timestamp']) . '</abbr></span>';
            if ($user->uid == $rev_item['uid']) {
              print ' &nbsp; [ ' .
                    l(t('delete'), 'review/delete/' . $rev_item['rev_id'], array('attributes' => array('title' => 'Delete this review'), 'query' => array('ref' => $_GET['q']))) .
                    ' ] [ ' .
                    l(t('edit'), 'review/edit/' . $rev_item['rev_id'], array('attributes' => array('title' => 'Edit this review'), 'query' => array('ref' => $_GET['q']))) .
                    ' ]';
            }
            print '</p>';
          }
          print '<div class="review-body description">' . nl2br($rev_item['rev_body']) . '</div></div>';
        }
      }
      else {
        print '<p>No reviews have been written yet.  You could be the first!</p>';
      }
      print $rev_form ? $rev_form : '<p>' . l(t('Login'), 'user/login', array('query' => array('destination' => $_GET['q']))) . ' to write a review of your own.</p>';
      ?>
    </div>

    <!-- Google Books Preview -->
    <?php
    foreach($item['stdnum'] as $stdnum) {
      $isbnarr[] = 'ISBN:'.preg_replace("/[^0-9X]/","", $stdnum);
    }
    ?>
    <div id="item-google-books">
      <div class="item-google-prev">
        <script type="text/javascript" src="http://books.google.com/books/previewlib.js"></script>
          <script type="text/javascript">
            var w=document.getElementById("item-google-books").offsetWidth;
            var h=(w*1.3);
            GBS_insertEmbeddedViewer(['<?php print implode("','",$isbnarr); ?>'],w,h);
          </script>
      </div>
    </div>

  <!-- end right-hand column -->
  </div>

<!-- end item record -->
</div>
