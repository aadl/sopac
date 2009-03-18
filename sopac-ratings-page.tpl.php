<?php
/**
 * SOPAC is The Social OPAC: a Drupal module that serves as a wholly integrated web OPAC for the Drupal CMS
 * This file contains the Drupal include functions for all the SOPAC admin pieces and configuration options
 * This file is called via hook_user
 *
 * @package SOPAC
 * @version 2.0
 * @author John Blyberg
 */
?>

<?php
if (count($ratings_arr[ratings])) {
	print '
		<table class="overview-ratings" width="100%">
		<tr><th></th><th>Title</th><th>Date Rated</th></tr>
		<tr><td colspan="3" style="padding-top:5px;"></td></tr>
	';
	foreach ($ratings_arr[ratings] as $rating) {
		print '<tr><td style="width: 100px;">';
		print theme_sopac_get_rating_stars($rating[bnum], $rating[rating], FALSE, TRUE) . ' ';
		print '</td><td>';
		print '<a href="/' . variable_get('sopac_url_prefix', 'cat/seek') . '/record/' . $rating[bnum] . '">' .
			$ratings_arr[bibs][$rating[bnum]][title] . '</a>';
		print '</td><td>';
		print date("m-d-Y", $rating[rate_date]);
		print '</td></tr>';
	}
	print '</table>';
} else {
	print '<div class="overview-nodata">You have not rated any items yet.</div>';
}
