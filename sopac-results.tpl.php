<?php
/*
 * Hitlist Page template
 */

// Set some stuff up here

$getvars = sopac_parse_get_vars();
$sort_oldest = '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => 'oldest')));
$sort_newest = '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => 'newest')));
$sort_toprated = '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => 'top_rated')));
$sort_justadded = '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => 'catalog_newest')));
$sort_popular = '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => 'popular_week')));
$sort_rel =  '?' . sopac_make_pagevars(sopac_parse_get_vars(array('sort' => '')));
$pagevars = sopac_make_pagevars($getvars);
$sorted_by = $getvars[sort];
$uri_arr = explode('?', $_SERVER[REQUEST_URI]);
$uri = $uri_arr[0];
?>

<?php if ($result_info[num_results] > 0) { ?>
<div class="hitlist-top-bar">

<?php if ($locum_result[suggestion]) { ?>
<div class="hitlist-suggestions">
	Did you mean <i><a href="<?php print suggestion_link($locum_result); ?>"><?php 
		print $locum_result[suggestion]; 
	?></a></i> ?
</div>
<br />
<?php } ?>

	<span class="hitlist-range">
		<strong>»</strong> Showing results <?php print $result_info[hit_lowest] . ' to ' . $result_info[hit_highest] . ' of ' . $result_info[num_results]; ?>
	</span>
	<span class="hitlist-sorter">
		Sort by: 
		[ <?php print $sorted_by ? '<a href="' . $uri . $sort_rel . '">Relevance</a>' : '<strong>Relevance</strong>' ?> ] 
		[ <?php print $sorted_by != 'top_rated' ? '<a href="' . $uri . $sort_toprated . '">Top Rated</a>' : '<strong>Top Rated</strong>' ?> ]
		<br />
		[ <?php print $sorted_by != 'catalog_newest' ? '<a href="' . $uri . $sort_justadded . '">Just Added</a>' : '<strong>Just Added</strong>' ?> ]
		[ <?php print $sorted_by != 'popular_week' ? '<a href="' . $uri . $sort_popular . '">Hot this Week</a>' : '<strong>Hot this Week</strong>' ?> ]
	</span>
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
