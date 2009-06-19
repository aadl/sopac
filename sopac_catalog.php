<?php
/**
 * SOPAC is The Social OPAC: a Drupal module that serves as a wholly integrated web OPAC for the Drupal CMS
 * This file contains the Drupal include functions for all the catalog functions within SOPAC
 * This file is called via hook_menu
 *
 * @package SOPAC
 * @version 2.0
 * @author John Blyberg
 */



/**
 * Prepares and returns the HTML for the SOPAC search page/hit list.
 * Uses the following templates: sopac_results.tpl.php, sopac_results_hitlist.tpl.php, sopac_results_nohits.tpl.php
 *
 * @return string SOPAC catalog search HTML
 */
function sopac_catalog_search() {
	global $pager_page_array, $pager_total, $locum_results_all, $locum_cfg;
	
	// Load Required JS libraries
	drupal_add_js(drupal_get_path('module', 'sopac') .'/js/jquery.treeview.js');
	drupal_add_js(drupal_get_path('module', 'sopac') .'/js/jquery.rating.js');
	drupal_add_js(drupal_get_path('module', 'sopac') .'/js/facet-browser.js');

	$getvars = sopac_parse_get_vars();
	$locum = new locum_client;
	$locum_cfg = $locum->locum_config;
	$no_circ = $locum->csv_parser($locum_cfg[location_limits][no_request]);
	$valid_search_types = array('title', 'author', 'keyword', 'subject', 'series', 'callnum', 'tags', 'reviews'); // TODO handle this more dynamically
	$actions = sopac_parse_uri();

	$sort = $getvars[sort];
	$format = $getvars[search_format];
	$location = $getvars[location];
	$pager_page_array = explode(',', $getvars[page]);

	// If there is a proper search query, we get that data here.
	if (in_array($actions[1], $valid_search_types)) {
		$valid_search = TRUE;
		$_SESSION[search_url] = $_SERVER[REQUEST_URI];
		if ($addl_search_args[limit]) { 
			$limit = $addl_search_args[limit]; 
		} else { 
			$limit = variable_get('sopac_results_per_page', 20);
		}

		// Initialize the pager if need be
		if ($pager_page_array[0]) { $page = $pager_page_array[0] + 1; } else { $page = 1; }
		$page_offset = $limit * ($page - 1);

		// Grab the faceted search arguments from the URL
		$facet_args = array();
		if (count($getvars[facet_series])) { $facet_args[facet_series] = $getvars[facet_series]; }
		if (count($getvars[facet_lang])) { $facet_args[facet_lang] = $getvars[facet_lang]; }
		if (count($getvars[facet_year])) { $facet_args[facet_year] = $getvars[facet_year]; }

		// Get the search results from Locum
		$locum_results_all = $locum->search($actions[1], utf8_urldecode($actions[2]), $limit, $page_offset, $sort, $format, $location, $facet_args);
		$num_results = $locum_results_all[num_hits];
		$result_info[num_results] = $num_results;
		$result_info[hit_lowest] = $page_offset + 1;
		if (($page_offset + $limit) < $num_results) { 
			$result_info[hit_highest] = $page_offset + $limit; 
		} else { 
			$result_info[hit_highest] = $num_results; 
		}
	}

	// Construct the search form
	$search_form_cfg = variable_get('sopac_search_form_cfg', 'both');
	$search_form = sopac_search_form($search_form_cfg);

	// If we get results back, we begin creating the hitlist
	if ($num_results > 0) {
		// We need to determine how many result pages there are.
		$pager_total[0] = ceil($num_results / $limit);
		$hitlist = '';
		$hitnum = $page_offset + 1;

		foreach ($locum_results_all[results] as $locum_result) {

			// Format stdnum as best we can
			if (preg_match('/ /', $locum_result[stdnum])) {
				$stdnum_arr = explode(' ', $locum_result[stdnum]);
				$stdnum = $stdnum_arr[0];
				$locum_result[stdnum] = $stdnum;
			} else {
				$stdnum = $locum_result[stdnum];
			}

			// Grab item status from Locum
			$item_status = $locum->get_item_status($locum_result[bnum]);
			$locum_result[copies] = $item_status[copies];
			$locum_result[totalcopies] = $item_status[total];
			$locum_result[avail_details] = $item_status[details];

			$cover_img_url = $locum_result[cover_img];
		
			$result_body .= theme('sopac_results_hitlist', $hitnum, $cover_img_url, $locum_result, $locum_cfg, $no_circ);
			$hitnum++;
		}

		$hitlist_pager = theme('pager', NULL, $limit, 0, NULL, 6);
	} else if ($valid_search) {
		$result_body .= theme('sopac_results_nohits', $locum_results_all, $locum->locum_config);
	}

	// Pull it all together into the search page template
	$result_page = $search_form . theme('sopac_results', $result_info, $hitlist_pager, $result_body, $locum_results_all, $locum->locum_config);

	return '<p>'. t($result_page) .'</p>';

}

/**
 * Prepares and returns the HTML for an item record.
 * Uses the following templates: sopac_record.tpl.php
 *
 * @return string Item record HTML
 */
function sopac_bib_record() {
	global $user;
	
	$locum = new locum_client;
	$actions = sopac_parse_uri();
	$bnum = $actions[1];
	
	// Load social function
	require_once('sopac_social.php');
	
	$no_circ = $locum->csv_parser($locum->locum_config[location_limits][no_request]);
	$item = $locum->get_bib_item($bnum);
	$item_status = $locum->get_item_status($bnum);
	if ($item[bnum]) {
		$result_page = theme('sopac_record', $item, $item_status, $locum->locum_config, $no_circ, &$locum);
	} else {
		$result_page = 'This record does not exist.';
	}

	return '<p>'. t($result_page) .'</p>';
}

/**
 * Formulates and returns the search tracker block HTML.
 * Uses the following templates: sopac_search_block.tpl.php
 *
 * @return string Search tracker block HTML
 */
function sopac_search_block($locum_results_all, $locum_cfg) {
	global $user;

	$getvars = sopac_parse_get_vars();
	$uri = sopac_parse_uri();
	$format = $getvars[search_format];
	$term_arr = explode('?', trim(preg_replace('/\//', ' ', $uri[2])));

	$search[term] = trim($term_arr[0]);
	$search[type] = trim($uri[1]);
	$search[sortby] = $getvars[sort] ? $getvars[sort] : 'Most relevant';
	$search[format] = count($getvars[search_format]) && ($getvars[search_format][0] != 'all') ? $getvars[search_format] : array();
	$search[series] = count($getvars[facet_series]) ? $getvars[facet_series] : array();
	$search[lang] = count($getvars[facet_lang]) ? $getvars[facet_lang] : array();
	$search[year] = count($getvars[facet_year]) ? $getvars[facet_year] : array();

	return theme('sopac_search_block', $search, $locum_results_all, $locum_cfg, $user);

}

/**
 * If nothing is in the author field, we try the addl author field.
 * Oh, and we present first name first.  Like it oughta.
 *
 * @param string $author Author string as presented up from Locum
 * @param string $addl_author_ser Serialized additional author string as presented up from Locum
 * @return string The formatted author string
 */
function sopac_author_format($author, $addl_author_ser) {

	if ($author) {
		$author_arr = explode(',', $author);
		$new_author_str = trim($author_arr[1]) . ' ' . trim($author_arr[0]);
	} else if ($addl_author_ser) {
		$addl_author = unserialize($addl_author_ser);
		if ($addl_author[0]) {
			$author_arr = explode(',', $addl_author[0]);
			$new_author_str = trim($author_arr[1]) . ' ' . trim($author_arr[0]);
		}
	}
	if ($new_author_str) { 
		$new_author_str = ereg_replace("[^A-Za-z '.-]", '', $new_author_str ); 
		$new_author_str = preg_replace('/ - /', ' ', $new_author_str);	
	} else {
		$new_author_str = '';
	}

	return $new_author_str;
}

/**
 * Create the "Did you mean" link
 *
 * @param array $locum_result Locum result array as passed up from Locum
 * @return string Suggestion link
 */
function suggestion_link($locum_result) {
	$pagevars = sopac_make_pagevars(sopac_parse_get_vars());
	$url_prefix = variable_get('sopac_url_prefix', 'cat/seek'); 
	$sugg_link = '/' . $url_prefix . '/search/' . $locum_result[type] . '/' . $locum_result[suggestion] . '?' . $pagevars;
	return $sugg_link;
}

/**
 * Takes an array and creates page variable.  It's basically the reverse of sopac_parse_get_vars()
 *
 * @param array Array of variables to formulate
 * @return string GET variable string
 */
function sopac_make_pagevars($getvars) {
	$pagevars = '';
	
	if (is_array($getvars) && count($getvars)) {
		foreach ($getvars as $key => $var) {
			if (is_array($var)) { $var = implode('|', $var); }
			$pagevars .= $key . '=' . $var . '&';
		}
		$pagevars = substr($pagevars, 0, -1);
	}
	return $pagevars;

}

/**
 * This function will return the appropriate request link based on whether the user is logged in, has a verified card, or not
 * 
 * @return string HTML string for the request link
 */
function sopac_put_request_link($bnum) {
	global $user;
	profile_load_profile(&$user);

	if ($user->uid) {
		if (sopac_bcode_isverified(&$user)) {
			// User is logged in and has a verified card number
			$link = '/' . variable_get('sopac_url_prefix', 'cat/seek') . '/request/' . $bnum;
			$link_text = 'Request this item';
		} else if ($user->profile_pref_cardnum) {
			// User is logged in but does not have a verified card number
			$link = '/user/' . $user->uid;
			$link_text = 'Verify your card to request this item';
		} else {
			// User is logged in but does not have a card number.
			$link = '/user/' . $user->uid;
			$link_text = 'Register your card to request this item';
		}
	} else {
		$link = '/user/login';
		$link_text = 'Please log in to request this item';
	}

	return '<a href="' . $link . '">' . $link_text . '</a>';
}

/**
 * Returns the search URL, only if the user is coming directly from the search page.
 *
 * @return string|bool Search URL or FALSE
 */
function sopac_prev_search_url($override = FALSE) {
	if (!$_SESSION[search_url]) { return FALSE; }
	$referer = substr($_SERVER[HTTP_REFERER], 7 + strlen($_SERVER[HTTP_HOST]));
	$search = $_SESSION[search_url];
	if ((($search == $referer) || $override) && $_SESSION[search_url]) { return $search; } else { return FALSE; }
}

/**
 * Requests a particular item via locum then displays the results of that request
 *
 * @return string Request result
 */
function sopac_request_item() {
	global $user;
	// avoid php errors when debugging
	$varname = $request_result_msg = $request_error_msg = $item_form = $bnum = null;
	
	$button_txt = 'Request Selected Item';
	profile_load_profile(&$user);
	if ($user->uid && sopac_bcode_isverified(&$user)) {
		if ($_POST[sub_type] == $button_txt) {
			if ($_POST[varname]) {
				$varname = $_POST[varname];
			} else {
				$request_error_msg = 'You need to select an item to request.';
			}
		}
		
		// support multi-branch & user home branch
		$actions = sopac_parse_uri();
		$bnum = $actions[1];
		$pickup_arg = $actions[2] ? $actions[2] : null;
		$stored_pickup_options = variable_get('sopac_home_selector_options', array());
		if (!$pickup_arg && count($stored_pickup_options)) {
			$hold_result['choose_location']['options'] = $stored_pickup_options;
		}
		else {
			$pickup_name = $actions[3] ? $actions[3] : null;
			$locum = new locum_client;
			$bib_item = $locum->get_bib_item($bnum);
			$hold_result = $locum->place_hold($user->profile_pref_cardnum, $bnum, $varname, $user->locum_pass, $pickup_arg);
		}
		
		if ($hold_result[success]) {
			// handling multi-branch scenario
			$request_result_msg = 'You have successfully requested a copy of <span class="req_bib_title"> ' . $bib_item[title] . '</span>';
			if ($pickup_name) {
				$request_result_msg .= ' for pickup at ' . $pickup_name;
			}
		}
		// more multibranch
		elseif (is_array($hold_result['choose_location'])) {
			// pickup location
			$form_data = array(
				'options' => $hold_result['choose_location']['options'],
				'bnum' => $bnum,
			);
			$request_result_msg = drupal_build_form('sopac_hold_location_form', $form_data);
		}
		else {
			$request_result_msg = 'We were unable to fulfill your request for <span class="req_bib_title">' . $bib_item[title] . '</span>';
		}
		
		if ($hold_result[error]) {
			$request_result_msg = $hold_result[error];
		}
		
		if ($hold_result[selection]  && !$hold_result[success]) {
			$requestable = 0;
			$header = array('','Location','Call Number', 'Status');
			foreach ($hold_result[selection] as $selection) {
				$status = $selection[status];
				if ($selection[varname]) {
					$radio = '<input type="radio" name="varname" value="' . $selection[varname] . '">';
					$non_circ = NULL;
					$requestable++;
				} else {
					$radio = '';
					$status = '<span class="non_circ_msg">' . $status . '</span>';
				}
				$rows[] = array(
					$radio,
					$selection[location],
					$selection[callnum],
					$status,
				);
			}
			if ($requestable) {
				$submit_button = '<input type="submit" name="sub_type" value="' . $button_txt . '">';
				$request_result_msg = 'Please select the item you would like to request.';
			} else {
				$submit_button = NULL;
				$request_result_msg = '';
				$request_error_msg = 'There are no copies of this item available for circulation.';
			}
			if ($submit_button){
				$rows[] = array( 'data' => array(array('data' => $submit_button, 'colspan' => 4)), 'class' => 'req_button' );
			}
			$item_form = '<form method="post">' . theme('table', $header, $rows, array('id' => 'reqlist', 'cellspacing' => '0')) . '</form>';
		}
		
		// TODO - add a tally for top items data recovery
	} else {
		$request_error_msg = "You must have a valid library card number registered with our system.";
	}
	$result_page = theme('sopac_request', $request_result_msg, $request_error_msg, $item_form, $bnum);
	return '<p>'. t($result_page) .'</p>';
}

// allow user to select branch at which to pickup hold
function sopac_hold_location_page() {
	$output = drupal_get_form('sopac_hold_location_form');
	return $output;
}

function sopac_hold_location_form($form_data = null) {
	global $user;
	if (isset($form_data['bnum'])) {
		$form_data['storage']['options'] = $form_data['options'];
		$form_data['storage']['bnum'] = $form_data['bnum'];
	}
	$options = $form_data['storage']['options'];
	$form = array();
	$form['#action'] = '/hold/location';
	$form['hold_location'] = array(
		'#type' => 'select',
		'#title' => 'Choose a pickup location',
		'#options' => $options,
	);
	if (isset($user->profile_pref_home_branch)) {
		$options = array_flip($options);
		if (array_key_exists($user->profile_pref_home_branch, $options)) {
			$form['hold_location']['#default_value'] = $options[$user->profile_pref_home_branch];
		}
	}
	$form['op'] = array(
		'#type' => 'submit',
		'#value' => 'Submit',
	);
	return $form;
}

function sopac_hold_location_form_submit($form, &$form_state) {
	$location_name = $form['hold_location']['#options'][$form_state['values']['hold_location']];
	//drupal_set_message(t('You chose ' . $location_name));
	$bnum = $form_state['storage']['bnum'];
	unset($form_state['storage']);
	drupal_goto('catalog/request/' . $bnum . '/' . $form_state['values']['hold_location'] . '/' . $location_name);
}

/**
 * Returns the url string to use in the save search link.
 *
 * @return string URL
 */
function sopac_savesearch_url() {
	$search_url = '/' . variable_get('sopac_url_prefix', 'cat/seek') . '/savesearch' . substr($_SERVER[REQUEST_URI], 8 + strlen(variable_get('sopac_url_prefix', 'cat/seek')));
	return $search_url;
}

/**
 * Formulates the basic search form array
 *
 * @return array Drupal search form array
 */
function sopac_search_form_basic() {

	$locum = new locum();
	$locum_cfg = $locum->locum_config;
	$getvars = sopac_parse_get_vars();

	$actions = sopac_parse_uri();
	$search_args_raw = explode('?', $actions[2]);
	$search_args = trim($search_args_raw[0]);
	$stype_selected = $actions[1] ? 'cat_' . $actions[1] : 'cat_keyword';
	$sformat_selected = $_GET[search_format] ? $_GET[search_format] : 'all';

	$stypes = array(
		'cat_keyword' => 'Keyword',
		'cat_title' => 'Title',
		'cat_author' => 'Author',
		'cat_series' => 'Series',
		'cat_tags' => 'Tags',
		'cat_reviews' => 'Reviews',
		'cat_subject' => 'Subject',
		'cat_callnum' => 'Call Number',
	);

	$sortopts = array(
		'relevance' => 'Relevance',
		'newest' => 'Newest First',
		'oldest' => 'Oldest First',
	);

	foreach ($locum_cfg[format_groups] as $sfmt => $sfmt_codes) {
		$sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
	}
	if (is_null($prompt)) {
		$prompt = t('Enter your keywords');
	}

	// Initialize the form
	$form = array(
		'#action' => '/search_handler',
		'#attributes' => array('class' => 'search-form'),
	);

	// Start creating the basic search form
	$form['basic'] = array('#type' => 'item');
	$form['basic']['inline'] = array('#prefix' => '<div class="container-inline">', '#suffix' => '</div>');
	$form['basic']['inline']['search_query'] = array(
		'#type' => 'textfield',
		'#title' => 'Search ',
		'#default_value' => $search_args,
		'#size' => 25,
		'#maxlength' => 255,
		'#value' => $search_args,
	);
	$form['basic']['inline']['search_type'] = array(
		'#type' => 'select',
		'#title' => t(' by '),
		'#default_value' => $stype_selected,
		'#value' => $stype_selected,
		'#options' => $stypes,
	);
	$form['basic']['inline']['search_format'] = array(
		'#type' => 'select',
		'#title' => t(' in '),
		'#default_value' => $sformat_selected,
		'#selected' => $sformat_selected,
		'#options' => $sformats,
	);

	$form['basic']['inline']['submit'] = array('#type' => 'submit', '#value' => t('Search'));

	return $form;

}

/**
 * Formulates the advanced search form array
 *
 * @return array Drupal search form array
 */
function sopac_search_form_adv() {
	
	$locum = new locum();
	$locum_cfg = $locum->locum_config;
	$getvars = sopac_parse_get_vars();

	$actions = sopac_parse_uri();
	$search_args_raw = explode('?', $actions[2]);
	$search_args = trim($search_args_raw[0]);
	$stype_selected = $actions[1] ? 'cat_' . $actions[1] : 'cat_keyword';
	$sformat_selected = $_GET[search_format] ? $_GET[search_format] : 'all';
	foreach ($locum_cfg[format_groups] as $sfmt => $sfmt_codes) {
		$sformats[preg_replace('/,[ ]*/', '|', trim($sfmt_codes))] = ucfirst($sfmt);
	}

	$stypes = array(
		'cat_keyword' => 'Keyword',
		'cat_title' => 'Title',
		'cat_author' => 'Author',
		'cat_series' => 'Series',
		'cat_tags' => 'Tags',
		'cat_reviews' => 'Reviews',
		'cat_subject' => 'Subject',
		'cat_callnum' => 'Call Number',
	);

	$sortopts = array(
		'' => 'Relevance',
		'catalog_newest' => 'Newest in Collection',
		'catalog_oldest' => 'Oldest in Collection',
		'newest' => 'Pub date: Newest',
		'oldest' => 'Pub date: Oldest',
		'title' => 'Alphabetically by Title',
		'author' => 'Alphabetically by Author',
		'top_rated' => 'Top Rated Items',
		'popular_week' => 'Most Popular this Week',
		'popular_month' => 'Most Popular this Month',
		'popular_year' => 'Most Popular this Year',
		'popular_total' => 'All Time Most Popular',
	);

	// Initialize the form
	$form = array(
		'#action' => '/search_handler',
		'#attributes' => array('class' => 'search-form'),
	);

	// Start creating the advanced search form
	$form['advanced'] = array(
		'#type' => 'fieldset',
		'#title' => t('Click for advanced search'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
		'#attributes' => array('class' => 'search-advanced'),
	);	 
	$form['advanced']['keywords'] = array(
		'#prefix' => '<div class="adv_search_crit">',
		'#suffix' => '</div>',
	);	 
	$form['advanced']['keywords']['search_query'] = array(
		'#type' => 'textfield',
		'#title' => t('Search term or phrase'),
		'#default_value' => $search_args,
		'#size' => 20,
		'#maxlength' => 255,
		'#value' => $search_args,
	);
	$form['advanced']['keywords']['search_type'] = array(
		'#type' => 'select',
		'#title' => t('Search by'),
		'#default_value' => $stype_selected,
		'#value' => $stype_selected,
		'#options' => $stypes,
	);
	$form['advanced']['keywords']['sort'] = array(
		'#type' => 'select',
		'#title' => t('Sorted by'),
		'#default_value' => '',
		'#value' => $getvars[sort],
		'#options' => $sortopts,
	);

	$form['advanced']['narrow1'] = array(
		'#prefix' => '<div class="adv_search_crit">',
		'#suffix' => '</div>',
	);

	if (count($locum_cfg[collections])) {
		
		foreach ($locum_cfg[collections] as $loc_collect_key => $loc_collect_var) {
			$loc_collect[$loc_collect_key] = $loc_collect_key;
		}
		
		$form['advanced']['narrow1']['collection'] = array(
			'#type' => 'select',
			'#title' => t('In these collections'),
			'#size' => 5,
			'#value' => $getvars[collection],
			'#options' => $loc_collect,
			'#multiple' => TRUE,
		);
	}
	
	$form['advanced']['narrow1']['location'] = array(
		'#type' => 'select',
		'#title' => t('In these locations'),
		'#size' => 5,
		'#value' => $getvars[location],
		'#options' => $locum_cfg[locations],
		'#multiple' => TRUE,
	);
	
	$form['advanced']['narrow1']['search_format'] = array(
		'#type' => 'select',
		'#title' => t('In these formats'),
		'#size' => 5,
		'#value' => $getvars[search_format],
		'#options' => $locum_cfg[formats],
		'#multiple' => TRUE,
	);
	
	$form['advanced']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Advanced search'),
		'#prefix' => '<div class="action">',
//		'#suffix' => '</div>',
	);
	
	$form['advanced']['clear'] = array(
		'#name' => 'clear',
		'#type' => 'button',
		'#value' => t('Reset'),
		'#attributes' => array('onclick' => 'this.form.reset(); return false;'),
	//	'#prefix' => '<div class="action">',
		'#suffix' => '</div>',
	);
	
	return $form;
}












