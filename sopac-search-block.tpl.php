<?php
/*
 * Search tracker Block template
 *
 */
$uri_arr = explode('?', $_SERVER['REQUEST_URI']);
$uri = $uri_arr[0];
$getvars = sopac_parse_get_vars();
$sortopts = array(
  '' => 'Relevance',
  'catalog_newest' => 'Newest in Collection',
  'catalog_oldest' => 'Oldest in Collection',
  'newest' => 'Pub date: Newest',
  'oldest' => 'Pub date: Oldest',
  'title' => 'Alphabetically by Title',
  'author' => 'Alphabetically by Author',
  'popular_week' => 'Most Popular this Week',
  'popular_month' => 'Most Popular this Month',
  'popular_year' => 'Most Popular this Year',
  'popular_total' => 'All Time Most Popular',
  'top_rated' => 'Highest Rated',
);
$sorted_by = $sortopts[$search['sortby']] ? $sortopts[$search['sortby']] : 'Relevance';
?>

You Searched For:
<div class="search-block-attr"><?php print $search['term']; ?></div>
<br />
By Search Type:
<div class="search-block-attr"><?php print ucfirst($search['type']); ?></div>
<br />
By Format:
<div class="search-block-attr"><?php 
  $search_format_flipped = is_array($getvars['search_format']) ? array_flip($getvars['search_format']) : array();
  if (count($search['format'])) {
    foreach ($search['format'] as $search_format) {
      $getvars_tmp = $getvars;
      unset($getvars_tmp['search_format'][$search_format_flipped[$search_format]]);
      $getvars_tmp['page'] = '';
      $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
      $gvar_indicator = $pvars_tmp ? '?' : '';
      $rem_link = $uri . $gvar_indicator . $pvars_tmp;
      $search_format_arr[trim($search_format)] = $locum_config['formats'][trim($search_format)] . ' [<a href="' . $rem_link . '">x</a>]';
    }
    print implode('<br />', $search_format_arr);
  } else {
    print 'Everything';
  }
?></div>


<?php
if (is_array($getvars['collection']) && count($getvars['collection'])) {
  print '<br />In Collections:';
  print '<div class="search-block-attr">';
  foreach ($getvars['collection'] as $collection) {
    $getvars_tmp = $getvars;
    unset($getvars_tmp['collection'][$colection]);
    $getvars_tmp['page'] = '';
    $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
    $gvar_indicator = $pvars_tmp ? '?' : '';
    $rem_link = $uri . $gvar_indicator . $pvars_tmp;
    $coll_arr[$collection] = $collection . ' [<a href="' . $rem_link . '">x</a>]';
  }
  print implode('<br />', $coll_arr);
  print '</div>';
}
?>

<?php
if (is_array($getvars['location']) && count($getvars['location'])) {
  print '<br />In Locations:';
  print '<div class="search-block-attr">';
  $location_flipped = array_flip($getvars['location']);
  foreach ($getvars['location'] as $location) {
    $getvars_tmp = $getvars;
    unset($getvars_tmp['location'][$location_flipped[$location]]);
    $getvars_tmp['page'] = '';
    $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
    $gvar_indicator = $pvars_tmp ? '?' : '';
    $rem_link = $uri . $gvar_indicator . $pvars_tmp;
    $location_arr[trim($location)] = $locum_config['locations'][$location] . ' [<a href="' . $rem_link . '">x</a>]';
  }
  print implode('<br />', $location_arr);
  print '</div>';
}
?>

<br />
Sorted by:
<div class="search-block-attr"><?php print $sorted_by; ?></div>

<?php
if (is_array($getvars['facet_series']) && count($getvars['facet_series'])) {
  print '<br />Refined by Series:';
  print '<div class="search-block-attr">';
  $series_flipped = array_flip($getvars['facet_series']);
  foreach ($search['series'] as $series) {
    $getvars_tmp = $getvars;
    unset($getvars_tmp['facet_series'][$series_flipped[$series]]);
    $getvars_tmp['page'] = '';
    $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
    $gvar_indicator = $pvars_tmp ? '?' : '';
    $rem_link = $uri . $gvar_indicator . $pvars_tmp;
    $series_arr[trim($series)] = $series . ' [<a href="' . $rem_link . '">x</a>]';
  }
  print implode('<br />', $series_arr);
  print '</div>';
}
?>

<?php
if (is_array($getvars['facet_lang']) && count($getvars['facet_lang'])) {
  print '<br />Refined by Language:';
  print '<div class="search-block-attr">';
  $lang_flipped = array_flip($getvars['facet_lang']);
  foreach ($search['lang'] as $lang) {
    $getvars_tmp = $getvars;
    unset($getvars_tmp['facet_lang'][$lang_flipped[$lang]]);
    $getvars_tmp['page'] = '';
    $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
    $gvar_indicator = $pvars_tmp ? '?' : '';
    $rem_link = $uri . $gvar_indicator . $pvars_tmp;
    $lang_arr[trim($lang)] = ucfirst($lang) . ' [<a href="' . $rem_link . '">x</a>]';
  }
  print implode('<br />', $lang_arr);
  print '</div>';
}
?>

<?php
if (is_array($getvars['facet_year']) && count($getvars['facet_year'])) {
  print '<br />Refined by Pub. Year:';
  print '<div class="search-block-attr">';
  $year_flipped = array_flip($getvars['facet_year']);
  foreach ($search['year'] as $year) {
    $getvars_tmp = $getvars;
    unset($getvars_tmp['facet_year'][$year_flipped[$year]]);
    $getvars_tmp['page'] = '';
    $pvars_tmp = trim(sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)));
    $gvar_indicator = $pvars_tmp ? '?' : '';
    $rem_link = $uri . $gvar_indicator . $pvars_tmp;
    $year_arr[trim($year)] = $year . ' [<a href="' . $rem_link . '">x</a>]';
  }
  print implode('<br />', $year_arr);
  print '</div>';
}
?>



<br />
<div style="float: right;">» <a href="/research_help">Need help?</a></div>
<?php if ($user->uid) {
  print '<div style="float: right;">» <a href="' . sopac_savesearch_url() . '">Save this search</a></div>';
}
?>
<br />
