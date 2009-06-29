<?php

$uri_arr = explode('?', $_SERVER['REQUEST_URI']);
$uri = $uri_arr[0];
$getvars = sopac_parse_get_vars();

?>
<div id="container">
	<div id="content">
	<div id="sidetreecontrol"><a href="?#">Collapse All</a> | <a href="?#">Expand All</a></div>
	<br />
		<ul id="facet" class="treeview">
<?php

$mat_count = count($locum_result['facets']['mat']);
$search_formats = is_array($getvars['search_format']) ? $getvars['search_format'] : array();
if ($mat_count) {
	if (!is_array($getvars['search_format']) || strtolower($_GET['search_format']) == 'all') { 
		$li_prop = ' class="closed"'; 
	} else { 
		$li_prop = NULL; 
	}
	print "<li$li_prop><span class=\"folder\">by Format</span> <small>($mat_count)</small><ul>\n";
	foreach ($locum_result['facets']['mat'] as $mat_code => $mat_code_count) {
		if (in_array($mat_code, $search_formats)) {
			print '<li id="tree-kid" class="facet-item-selected"><strong>» ' . $locum_config['formats'][$mat_code] . "</strong></li>\n";
		} else {
			$getvars_tmp = $getvars;
			if ($getvars_tmp['search_format'][0] == 'all') { unset($getvars_tmp['search_format'][0]); }
			$getvars_tmp['search_format'][] = $mat_code;
			if (isset($getvars_tmp['page'])) { $getvars_tmp['page'] = ''; }
			$link_addr = $uri . '?' . sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp)); 
			print '<li id="tree-kid">» <a href="' . $link_addr . '">' . $locum_config['formats'][$mat_code] . '</a> <small>(' . $mat_code_count . ")</small></li>\n";
			unset($getvars_tmp);
		}
	}
	print "</ul></li>\n";
}

$facet_loc = is_array($getvars['location']) ? $getvars['location'] : array();
$loc_count = count($locum_result['facets']['loc']);
if ($loc_count) {
	if (!is_array($getvars['location'])) { $li_prop = ' class="closed"'; } else { $li_prop = NULL; }
	print "<li$li_prop><span class=\"folder\">by Location</span> <small>($loc_count)</small><ul>\n";
	foreach ($locum_result['facets']['loc'] as $loc => $loc_count_indv) {
		$loc_name = $locum_config['locations'][$loc] ? $locum_config['locations'][$loc] : $loc;
		if (in_array($loc, $facet_loc)) {
			print '<li id="tree-kid" class="facet-item-selected"><strong>» ' . $loc_name . "</strong></li>\n";
		} else {
			$getvars_tmp = $getvars;
			$getvars_tmp['location'][] = urlencode($loc);
			if (isset($getvars_tmp['page'])) { $getvars_tmp['page'] = ''; }
			$link_addr = $uri . '?' . sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp));
			print '<li id="tree-kid">» <a href="' . $link_addr . '">' . $loc_name . '</a> <small>(' . $loc_count_indv . ")</small></li>\n";
			unset($getvars_tmp);
		}
	}
	print "</ul></li>\n";

}


$facet_series = is_array($getvars['facet_series']) ? $getvars['facet_series'] : array();
if (count($locum_result['facets']['series'])) {
	foreach ($locum_result['facets']['series'] as $series => $series_count) {
		$ser_arr = explode(';', $series);
		$ser_clean = trim($ser_arr[0]);
		$series_result_unweeded[$ser_clean]++;
	}
	foreach ($series_result_unweeded as $series => $series_count) {
		if ($series_count > 1) { $series_result[$series] = $series_count; }
	}
	$series_count = count($series_result);
	if ($series_count) {
		if (!is_array($getvars['facet_series'])) { $li_prop = ' class="closed"'; } else { $li_prop = NULL; }
		print "<li$li_prop><span class=\"folder\">by Series</span> <small>($series_count)</small><ul>\n";
		foreach ($series_result as $series => $series_name_count) {
			if (in_array($series, $facet_series)) {
				print '<li id="tree-kid" class="facet-item-selected"><strong>» ' . $series . "</strong></li>\n";
			} else {
				$getvars_tmp = $getvars;
				$getvars_tmp['facet_series'][] = urlencode($series);
				if (isset($getvars_tmp['page'])) { $getvars_tmp['page'] = ''; }
				$link_addr = $uri . '?' . sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp));
				print '<li id="tree-kid">» <a href="' . $link_addr . '">' . $series . '</a> <small>(' . $series_name_count . ")</small></li>\n";
				unset($getvars_tmp);
			}
		}
		print "</ul></li>\n";
	}
}

$facet_lang = is_array($getvars['facet_lang']) ? $getvars['facet_lang'] : array();
$lang_count = count($locum_result['facets']['lang']);
if ($lang_count) {
	if (!is_array($getvars['facet_lang'])) { $li_prop = ' class="closed"'; } else { $li_prop = NULL; }
	print "<li$li_prop><span class=\"folder\">by Language</span> <small>($lang_count)</small><ul>\n";
	foreach ($locum_result['facets']['lang'] as $lang => $lang_code_count) {
		if (in_array($lang, $facet_lang)) {
			print '<li id="tree-kid" class="facet-item-selected"><strong>» ' . ucfirst($lang) . "</strong></li>\n";
		} else {
			$getvars_tmp = $getvars;
			$getvars_tmp['facet_lang'][] = urlencode($lang);
			if (isset($getvars_tmp['page'])) { $getvars_tmp['page'] = ''; }
			$link_addr = $uri . '?' . sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp));
			print '<li id="tree-kid">» <a href="' . $link_addr . '">' . ucfirst($lang) . '</a> <small>(' . $lang_code_count . ")</small></li>\n";
			unset($getvars_tmp);
		}
	}
	print "</ul></li>\n";

}

$facet_year = is_array($getvars['facet_year']) ? $getvars['facet_year'] : array();
$year_count = count($locum_result['facets']['pub_year']);
if ($year_count) {
	if (!is_array($getvars['facet_year'])) { $li_prop = ' class="closed"'; } else { $li_prop = NULL; }
	print "<li$li_prop><span class=\"folder\">by Pub. Year</span> <small>($year_count)</small><ul>\n";
	foreach ($locum_result['facets']['pub_year'] as $year => $pub_year_count) {
		if (in_array($year, $facet_year)) {
			print '<li id="tree-kid" class="facet-item-selected"><strong>» ' . $year . "</strong></li>\n";
		} else if ($year <= date('Y')) { // 'cuz catalogers are so infallable.. *cough*
			$getvars_tmp = $getvars;
			$getvars_tmp['facet_year'][] = urlencode($year);
			if (isset($getvars_tmp['page'])) { $getvars_tmp['page'] = ''; }
			$link_addr = $uri . '?' . sopac_make_pagevars(sopac_parse_get_vars($getvars_tmp));
			print '<li id="tree-kid">» <a href="' . $link_addr . '">' . $year . '</a> <small>(' . $pub_year_count . ")</small></li>\n";
			unset($getvars_tmp);
		}
	}
	print "</ul></li>\n";

}

?>				
		</ul>
	</div>
</div>