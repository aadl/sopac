<?php
/*
 * Hitlist Page template
 */

// Set some stuff up here

$getvars = sopac_parse_get_vars();
$sorted_by = $getvars['sort'];
$uri_arr = explode('?', request_uri());
$uri = $uri_arr[0];
$uri = $_GET['q'];

$perpage = $result_info['limit'];
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
$default_perpage = variable_get('sopac_results_per_page', 10);


// Only show mel link on searches for books
if ($_GET['search_format']) {
  foreach(explode('|', $_GET['search_format']) as $format) {
    if (strpos($locum_config['format_groups']['books'], $format) !== FALSE) {
      $show_mel_link = TRUE;
    }
  }
}
else {
  $show_mel_link = TRUE; // show link if not limited by format
}

?>

<?php if ($result_info['num_results'] > 0) { ?>
<div class="hitlist-top-bar">
<?php echo $rss_link; ?>
<?php if ($locum_result['changed'] == "yes") { ?>
  <p class="resultswitch">No results found for <strong>ALL</strong> your search terms. Found <?php echo $result_info[num_results]; ?> results that match <strong>ANY</strong> terms.</p>
<?php } ?>
<?php if ($locum_result['suggestion']) { ?>
<div class="hitlist-suggestions">
  Did you mean <i><?php print suggestion_link($locum_result); ?></i> ?
</div>
<br />
<?php } ?>

  <div class="hitlist-range">
    <span class="range">Showing results <strong><?php print $result_info['hit_lowest'] . '</strong> to <strong>' . $result_info['hit_highest'] . '</strong> of <strong>' . $result_info['num_results'] .'</strong>'; ?></span>
    <span class="hitlist-sorter">
      <script>
        jQuery(document).ready(function() {$('#sortlist').change(function(){ location.href = $(this).val();});});
      </script>
      Sort by: <select name="sort" id="sortlist">
      <?php
        foreach($sortopts as $key => $value) {
          print '<option value="' . url($_GET['q'], array('query' => sopac_make_pagevars(sopac_parse_get_vars(array('page' => '', 'limit' => '', 'sort' => $key))))) . '" ';
          if ($sorted_by == $key) {
            print 'selected';
          }
          print '>' . $value . '</option>';
        }
      ?>
      </select>
    </span>
    <span class="pagination">Show:
      <?php
        if ($perpage == $default_perpage || !$perpage) {
          print "<strong>" . $default_perpage . "</strong>";
        }
        else {
          $getvars['perpage'] = $default_perpage;
          $getvars['page'] = '';
          print l($default_perpage, $uri, array('query' => sopac_make_pagevars(sopac_parse_get_vars($getvars))));
        }
        print ' | ';
        if ($perpage == ($default_perpage * 3)) {
          print "<strong>" . ($default_perpage * 3) . "</strong>";
        }
        else {
          $getvars['perpage'] = ($default_perpage * 3);
          $getvars['page'] = '';
          print l(($default_perpage * 3), $uri, array('query' => sopac_make_pagevars(sopac_parse_get_vars($getvars))));
        }
        print ' | ';
        if ($perpage == ($default_perpage * 6)) {
          print "<strong>" . ($default_perpage * 6) . "</strong>";
        }
        else {
          $getvars['perpage'] = ($default_perpage * 6);
          $getvars['page'] = '';
          print l(($default_perpage * 6), $uri, array('query' => sopac_make_pagevars(sopac_parse_get_vars($getvars))));
        }
      ?>
    </span>
  </div>

</div>
<br />
<div class="hitlist-pager">
<?php print $hitlist_pager; ?>
</div>
<?php } ?>

<div class="hitlist-content">
<?php print $hitlist_content; ?>
</div>

<div class="hitlist-pager">
<?php print $hitlist_pager; ?>
</div>
<?php if ($show_mel_link) {
  echo '<p class="mel">' , l("Try this search at other Michigan libraries", 'http://elibrary.mel.org/search/X' .  $locum_result['term']) , '</p>';
}
?>
