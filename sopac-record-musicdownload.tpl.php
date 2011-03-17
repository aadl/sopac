<?php
/*
 * Item record display template
 */

// Set the page title
drupal_set_title(ucwords($item['title']));
global $user;
$verified = FALSE;
if($user->uid && $user->bcode_verified){
  $verified = TRUE;
}
// Set up some variables.
$url_prefix = variable_get('sopac_url_prefix', 'cat/seek');
function sec2hms ($sec, $padHours = false) {
    $hms = "";
    $hours = intval(intval($sec) / 3600); 
    if($hours > 0) {
    $hms .= ($padHours) 
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
          : $hours. ':';
    }
    $minutes = intval(($sec / 60) % 60); 
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
    $seconds = intval($sec % 60); 
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    return $hms;
}
?>

<!-- begin item record -->
<div class="itemrecord">

  <!-- begin left-hand column -->
  <div class="item-left">

    <!-- Cover Image -->
    <?php
      $cover_img = "http://media.aadl.org/magnatune/".$item['_id']."/data/cover.jpg";;
      $cover_img = '<img class="item-cover" width="200" src="' . $cover_img . '">';
    print $cover_img;
    ?>

    <!-- Ratings -->
    <?php
/*
    if (variable_get('sopac_social_enable', 1)) {
      print '<div class="item-rating">';
      print theme_sopac_get_rating_stars($item['bnum']);
      print '</div>';
    }
*/
    ?>

    <!-- Item Details -->
    <ul>
      <?php
      if ($item->release_date) {
        print '<li><b>Magnatune Release:</b> ' . $item['release_date'] . '</li>';
      }
      if ($item->pub_year) {
        print '<li><b>Year Published:</b> ' . $item['pub_year'] . '</li>';
      }
      ?>
      <li><b>Format:</b> 320Kbps MP3</li>
      <li><b>Collection:</b> Magnatune</li>
    </ul>
    <?php if($verified) { ?>
    <h3>All Formats</h3>
    <ul>
    <li><a href="<?php echo '/'.$url_prefix . '/record/'.$item['_id'].'/download?type=album'; ?>">Download MP3 Album</a></li>
    <li><a href="<?php echo '/'.$url_prefix . '/record/'.$item['_id'].'/download?type=flac'; ?>">Download FLAC Album</a></li>
    </ul>
    <!-- Subject Headings -->
    <?php
    }
    if ($item->genres) {
      print '<h3>Genres</h3><ul>';
      if (is_array($item['genres'])) {
        foreach ($item['genres'] as $genre) {
          $subjurl = $url_prefix . '/search/subject/%22' . urlencode($genre) . '%22';
          print '<li>' . l($genre, $subjurl) . '</li>';
        }
      }
      print '</ul>';
    }
    ?>

    <h3>License</h3>
    <ul>
    <li><a href="<?php echo $item['license']; ?>">Creative Commons</a></li>
    <li>This download is available for personal use only.</li>
    </ul>
    <h3>Elsewhere</h3>
    <ul>
    <li><a href="<?php echo $item['magnatune_url']; ?>">Magnatune</a></li>
    </ul>
    
        <!-- Tags -->
    <?php
//    if (variable_get('sopac_social_enable', 1)) {
//      print '<h3>Tags</h3>';
      //$block = module_invoke('sopac','block','view', 4);
      //print $block['content'];
//    }
    ?>
  <!-- end left-hand column -->
  </div>


  <!-- begin right-hand column -->
  <div class="item-right">

    <div class="item-main">
    <!-- Item Format Icon -->
    <ul class="item-format-icon">
      <li><img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/z.png' ?>"></li>
      <li style="margin-top: -2px;"><?php print wordwrap("Music Download", 13, '<br />'); ?></li>
    </ul>

    <!-- Actions -->
    <ul class="item-actions">
      <?php echo sopac_put_request_link($item['_id'],0,0,'magnatune'); ?>
      <?php
        include_once('sopac_user.php');
        //print sopac_put_list_links($item['magnatune_id']);
      ?>
    </ul>

    <!-- Item Title -->
    <h1>
      <?php
      print ucwords($item['title']);
      ?>
    </h1>

    <!-- Item Author -->
    <?php
    if ($item['artist']) {
      $authorurl = $url_prefix . '/search/author/' . $item['artist'];
      print '<h3>by ' . l($item['artist'], $authorurl) . '</h3>';
    }
    ?>
    <p class="info">This download is only available to active library card holders.</p>
    <p class="info"><img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/info.png' ?>" align="center" width="35px" /> <?php echo l('How do I download and use these files?','downloadhelp'); ?></p>
    </div>
<?php if($item['tracks']) { $tracks = $item['tracks']; ksort($tracks); ?>
<div id="item-samples">
<h2>Tracks<?php if($verified) { ?> - Full Length Streaming<?php } ?></h2>
<?php if(!$verified) { ?>
<p><?php echo l(t('Log in to Stream Tracks'), 'user/login', array('query' => drupal_get_destination())); ?></p>
<?php } ?>
<p>Play Album: <img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/prev.png' ?>" align="center" height="20px" id="prev_track" /> <img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/play.png' ?>" height="30px" align="center" id="play_pause" /> <img src="<?php print base_path() . drupal_get_path('module', 'sopac') . '/images/next.png' ?>" height="20px" align="center" id="next_track" /></p>
<ul class="samples">
<?php foreach($tracks as $track => $info) { ?>
<?php if($verified) { ?>
<li><a href="<?php echo '/'.$url_prefix . '/record/'.$item['_id'].'/download?type=play&tracknum='.$track; ?>" class="mp3player"><?php echo $track; ?>. <?php echo $info['title']; ?> (<?php echo sec2hms($info['length']); ?>)</a><span class="right">(<?php echo round(($info['size'] / 1048576), 2); ?>MB) <a href="<?php echo '/'.$url_prefix . '/record/'.$item['_id'].'/download?type=track&tracknum='.$track; ?>">Download Track</a></span></li>
<?php } else { ?>
<li><?php echo $track; ?>. <?php echo $info['title']; ?></li>
<?php } } ?>
</ul>
</div>
<?php } ?>
    <!-- Community / SOPAC Reviews -->
<!--
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
-->

  <!-- end right-hand column -->
  </div>

<!-- end item record -->
</div>
