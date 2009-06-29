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
$sorted_by = $getvars['sort'];
$uri_arr = explode('?', $_SERVER['REQUEST_URI']);
$uri = $uri_arr[0];
?>

<?php if ($result_info['num_results'] > 0) { ?>
<div class="hitlist-top-bar">

<?php if ($locum_result['suggestion']) { ?>
<div class="hitlist-suggestions">
	Did you mean <i><a href="<?php print suggestion_link($locum_result); ?>"><?php 
		print $locum_result['suggestion']; 
	?></a></i> ?
</div>
<br />
<?php } ?>

	<span class="hitlist-range">
		<strong>»</strong><?php print t(' Showing results ') .$result_info['hit_lowest'] . t(' to ') . $result_info['hit_highest'] . t(' of ') . $result_info['num_results']; ?>
	</span>
	<span class="hitlist-sorter">
		<?php print t('Sort by: '); ?>
		[ <?php print $sorted_by ? '<a href="' . $uri . $sort_rel . '">' . t('Relevance') . '</a>' : '<strong>' . t('Relevance') . '</strong>' ?> ] 
		[ <?php print $sorted_by != 'top_rated' ? '<a href="' . $uri . $sort_toprated . '">' . t('Top Rated') . '</a>' : '<strong>' . t('Top Rated') . '</strong>' ?> ]
		<br />
		[ <?php print $sorted_by != 'catalog_newest' ? '<a href="' . $uri . $sort_justadded . '">' . t('Just Added') . '</a>' : '<strong>' . t('Just Added') . '</strong>' ?> ]
		[ <?php print $sorted_by != 'popular_week' ? '<a href="' . $uri . $sort_popular . '">' . t('Hot this Week') . '</a>' : '<strong>' . t('Hot this Week') . '</strong>' ?> ]
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
