<?php
/**
 * SOPAC is The Social OPAC: a Drupal module that serves as a wholly integrated web OPAC for the Drupal CMS
 * This file contains the Drupal include functions for all the SOPAC admin pieces and configuration options
 * This file is called via hook_menu
 *
 * @package SOPAC
 * @version 2.0
 * @author John Blyberg
 */


/**
 * Sets up administrative options for SOPAC and returns a systems settings form
 *
 * @return string Settings form HTML
 */
function sopac_admin() {

	$form['sopac_general'] = array(
		'#type' => 'fieldset',
		'#title' => t('General Settings'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);
	
	$form['sopac_general']['sopac_lib_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Your Institution\'s name'),
		'#default_value' => variable_get('sopac_lib_name', 'Anytown Public Library'),
		'#size' => 32,
		'#maxlength' => 254,
		'#description' => t("The name of your library or institution."),
		'#required' => TRUE,
	);

	$locum_path = trim(variable_get('sopac_locum_path', '/usr/local/include/locum'));
	if ($locum_path[0] != '/') { $locum_path = '/' . $locum_path; }
	if (substr($locum_path, -1) != '/') { $locum_path .= '/'; }
	if (!file_exists($locum_path . 'locum-client.php')) {
		$locum_error = '<br /><span style="color: red;">NOTE: ' . $locum_path . 'locum-client.php does not seem to exist!</span>' ;
	}
	$form['sopac_general']['sopac_locum_path'] = array(
		'#type' => 'textfield',
		'#title' => t('Path to the Locum Client Library'),
		'#default_value' => variable_get('sopac_locum_path', '/usr/local/include/locum'),
		'#size' => 32,
		'#maxlength' => 128,
		'#description' => t("The path to where you have installed Locum." . $locum_error),
		'#required' => TRUE,
	);
	
	$insurge_path = trim(variable_get('sopac_insurge_path', '/usr/local/include/insurge'));
	if ($insurge_path[0] != '/') { $insurge_path = '/' . $insurge_path; }
	if (substr($insurge_path, -1) != '/') { $insurge_path .= '/'; }
	if (!file_exists($insurge_path . 'insurge-client.php')) {
		$insurge_error = '<br /><span style="color: red;">NOTE: ' . $insurge_path . 'insurge-client.php does not seem to exist!</span>' ;
	}
	$form['sopac_general']['sopac_insurge_path'] = array(
		'#type' => 'textfield',
		'#title' => t('Path to the Insurge Client Library'),
		'#default_value' => variable_get('sopac_insurge_path', '/usr/local/include/insurge'),
		'#size' => 32,
		'#maxlength' => 128,
		'#description' => t("The path to where you have installed Insurge." . $insurge_error),
		'#required' => TRUE,
	);
	
	$form['sopac_general']['sopac_url_prefix'] = array(
		'#type' => 'textfield',
		'#title' => t('SOPAC URL prefix'),
		'#default_value' => variable_get('sopac_url_prefix', 'cat/seek'),
		'#size' => 24,
		'#maxlength' => 72,
		'#description' => t("This is the URL prefix you wish SOPAC to use within the site, for example, to use www.yoursite.com/cat/seek as the base URL for SOPAC, enter 'cat/seek' without the leading or trailing slash.  If you change this, you will likely need to clear your site cache in your <a href=\"/admin/settings/performance\">performance settings</a>."),
		'#required' => TRUE,
	);

	$form['sopac_general']['sopac_results_per_page'] = array(
		'#type' => 'textfield',
		'#title' => t('Max # of results per page'),
		'#default_value' => variable_get('sopac_results_per_page', 20),
		'#size' => 10,
		'#maxlength' => 4,
		'#description' => t("This is the maxumum number of results that will be displayed per page in the catalog hit-list."),
		'#required' => TRUE,
	);

	$form['sopac_general']['sopac_search_form_cfg'] = array(
		'#type' => 'select',
		'#title' => t('Default Search Form Display'),
		'#default_value' => variable_get('sopac_search_form_cfg', 'both'),
		'#description' => t("This option allows you to configure how you want the search form to be displayed from within the SOPAC context.  You can Display Just the basic search box, or the basic search box and the advanced search form drop-down option."),
		'#options' => array('basic' => t('Display just the basic form'), 'both' => t('Display both basic and advanced forms'))
	);
	
	// the next two settings to support giving users ability to select a home branch
	
	$description = 	t('Check this box if your library has multiple branches, and you would like each user to be able to select a home branch.');
	$description .= t(' NOTE: If you check this box, you must also enter a valid library card number in the next field.');
	$description .= t(' NOTE: the user option will be set up during the first cron job after at least 1 bib record has been harvested.');
	$form['sopac_general']['sopac_home_branch_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Allow Users to Select a Home Branch'),
		'#default_value' => variable_get('sopac_home_branch_enable', 0),
		'#description' => $description,
	);
	
	$form['sopac_general']['sopac_admin_card'] = array(
		'#type' => 'textfield',
		'#title' => t('Library Card Number For Use in Setup'),
		'#default_value' => variable_get('sopac_admin_card', ''),
		'#description' => t("Enter a valid library card number for use in setting up user's and build you to select a home branch."),
	);
	
	$form['sopac_general']['sopac_ssl_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Enable SSL for Account Management'),
		'#default_value' => variable_get('sopac_ssl_enable', 0),
		'#description' => t("Selecting this option will cause SOPAC to redirect browsers to an encrypted page if the user is accesing their personal information.  You will need to have SSL enabled on your web server.  This is HIGHLY RECCOMENDED.  If you enable this option, you must put the following setting in your Apache vhost configuration for this site's <VirtualHost *:443> section: SetEnv HTTPS TRUE"),
	);
	
	$form['sopac_general']['sopac_ssl_port'] = array(
		'#type' => 'textfield',
		'#title' => t('SSL Connection Port'),
		'#default_value' => variable_get('sopac_ssl_port', 443),
		'#size' => 6,
		'#maxlength' => 4,
		'#description' => t("This is the port that your Apache SSL process is listening on."),
		'#required' => TRUE,
	);
	
	$form['sopac_fines'] = array(
		'#type' => 'fieldset',
		'#title' => t('Fines Settings'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);
	
	$form['sopac_fines']['sopac_fines_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Enable Fines Management'),
		'#default_value' => variable_get('sopac_fines_enable', 1),
		'#description' => t("Check this box to allow users to access their fines through SOPAC."),
	);

	$form['sopac_fines']['sopac_payments_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Enable Payments Management'),
		'#default_value' => variable_get('sopac_payments_enable', 1),
		'#description' => t("Check this box to allow users to pay their fines through SOPAC."),
	);
	
	$form['sopac_social_features'] = array(
		'#type' => 'fieldset',
		'#title' => t('Social Feature Settings'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);
	
	// This really isn't implemented yet and is here are a reminder
	$form['sopac_social_features']['sopac_social_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Enable the Social Catalog Components'),
		'#default_value' => variable_get('sopac_social_enable', 1),
		'#description' => t("Check this box if you would like to enable community-driven reviews, ratings, comments, and tagging in the catalog."),
	);
	
	$form['sopac_social_features']['sopac_random_tags'] = array(
		'#type' => 'checkbox',
		'#title' => t('Random Tags'),
		'#default_value' => variable_get('sopac_random_tags', 1),
		'#description' => t("Check this box if you would like to display tags in random order."),
	);
	
	$form['sopac_social_features']['sopac_tag_limit'] = array(
		'#type' => 'textfield',
		'#title' => t('Tag Limit'),
		'#default_value' => variable_get('sopac_tag_limit', 100),
		'#size' => 6,
		'#maxlength' => 5,
		'#description' => t("This is the maximum number of tags to display in the tag cloud."),
	);

	$form['sopac_social_features']['sopac_tag_sort'] = array(
		'#type' => 'select',
		'#title' => t('Tag Cloud Sorting'),
		'#default_value' => variable_get('sopac_tag_sort', 'ORDER BY count DESC'),
		'#description' => t("How to sort tags in tag cloud if Random Tags is not checked."),
		'#options' => array(
			'ORDER BY count ASC' => t('By count, ascending'),
			'ORDER BY count DESC' => t('By count, descending'),
			'ORDER BY tag ASC' => t('Alphabeticaly, ascending'),
			'ORDER BY tag DESC' => t('Alphabeticaly, descending'),
		),
	);

	$form['sopac_account'] = array(
		'#type' => 'fieldset',
		'#title' => t('Account Page Settings'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);
	
	$form['sopac_account']['sopac_history_hide'] = array(
		'#type' => 'checkbox',
		'#title' => t('Hide the Account History on the Account page'),
		'#default_value' => variable_get('sopac_history_hide', 1),
		'#description' => t("Check this box if you would like to hide the Account History on the Account page."),
	);

	$form['sopac_account']['sopac_summary_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Enable the Account Summary on the Account page'),
		'#default_value' => variable_get('sopac_summary_enable', 1),
		'#description' => t("Check this box if you would like to enable the Account Summary on the Account page.  You probably want this."),
	);

	$form['sopac_account']['sopac_pname_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display Patron Name in the Account Summary'),
		'#default_value' => variable_get('sopac_pname_enable', 1),
		'#description' => t("Check this box if you would like to have patron names appear in the Account Summary."),
	);
	
	$form['sopac_account']['sopac_lcard_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display Library Card Number in the Account Summary'),
		'#default_value' => variable_get('sopac_lcard_enable', 1),
		'#description' => t("Check this box if you would like to have library card numbers appear in the Account Summary."),
	);
	
	$form['sopac_account']['sopac_numco_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display # of checkouts in the Account Summary'),
		'#default_value' => variable_get('sopac_numco_enable', 1),
		'#description' => t("Check this box if you would like # of checkouts appear in the Account Summary."),
	);
	
	if (variable_get('sopac_fines_enable', 1)) {
		$form['sopac_account']['sopac_fines_display'] = array(
			'#type' => 'checkbox',
			'#title' => t('Display Fine Amounts in the Account Summary'),
			'#default_value' => variable_get('sopac_fines_display', 1),
			'#description' => t("Check this box if you would like fine amounts to appear in the Account Summary."),
		);
	}
	
	$form['sopac_account']['sopac_cardexp_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display Library Card Expiration Date in the Account Summary'),
		'#default_value' => variable_get('sopac_cardexp_enable', 1),
		'#description' => t("Check this box if you would like to have library card expiration dates appear in the Account Summary."),
	);
	
	$form['sopac_account']['sopac_tel_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display telephone # in the Account Summary'),
		'#default_value' => variable_get('sopac_tel_enable', 1),
		'#description' => t("Check this box if you would like the patron telephone # to appear in the Account Summary."),
	);
	
	$form['sopac_account']['sopac_email_enable'] = array(
		'#type' => 'checkbox',
		'#title' => t('Display Email Address in the Account Summary'),
		'#default_value' => variable_get('sopac_email_enable', 1),
		'#description' => t("Check this box if you would like the patron email address to appear in the Account Summary."),
	);

	$form['sopac_account']['sopac_invalid_cardnum'] = array(
		'#type' => 'textarea',
		'#title' => t('Invalid Library Card Message'),
		'#default_value' => variable_get('sopac_invalid_cardnum', 'It appears that your card number is invalid.  If you feel that this is in error, please contact us.'),
		'#description' => t("This is the message that is displayed to users if they have entered an invalid library card number.  HTML is OK."),
		'#required' => TRUE,
	);
	
	$form['sopac_cardnum_verify'] = array(
		'#type' => 'fieldset',
		'#title' => t('Card Number Verification Requirements'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);
	
	$form['sopac_cardnum_verify']['sopac_require_cfg'] = array(
		'#type' => 'select',
		'#title' => t('Require Patrons to Meet All or One of the Requirements'),
		'#default_value' => variable_get('sopac_require_cfg', 'one'),
		'#description' => t("This option allows you to tell SOPAC if you would like to require users to fulfill all of the following enabled requirements, or just one.  For example, if this is set to just 'One', users would have to enter one correct value instead of all corect values in order to have their library card verified."),
		'#options' => array('one' => t('Just One'), 'all' => t('All Requirements'))
	);
	
	$form['sopac_cardnum_verify']['sopac_require_name'] = array(
		'#type' => 'checkbox',
		'#title' => t('Require Patron name for Verification'),
		'#default_value' => variable_get('sopac_require_name', 1),
		'#description' => t("Check this box if you would like to require the patron to type in their name in order to verify their library card number."),
	);
	
	$form['sopac_cardnum_verify']['sopac_require_tel'] = array(
		'#type' => 'checkbox',
		'#title' => t('Require Telephone Number for Verification'),
		'#default_value' => variable_get('sopac_require_tel', 1),
		'#description' => t("Check this box if you would like to require the patron to type in their telephone number in order to verify their library card number."),
	);
	
	$form['sopac_cardnum_verify']['sopac_require_streetname'] = array(
		'#type' => 'checkbox',
		'#title' => t('Use Address Street Name for Verification'),
		'#default_value' => variable_get('sopac_require_streetname', 1),
		'#description' => t("Check this box if you would like to use the street name of the patron's address to verify their library card number."),
	);
	
	$form['sopac_cardnum_verify']['sopac_uv_cardnum'] = array(
		'#type' => 'textarea',
		'#title' => t('Unvalidated Library Card Message'),
		'#default_value' => variable_get('sopac_uv_cardnum', 'The card number you have provided has not yet been verified by you.  In order to make sure that you are the rightful owner of this library card number, we need to ask you some simple questions.'),
		'#description' => t("This is the message that is displayed to users on their account page if they have not yet verified their library card number.  HTML is OK."),
		'#required' => TRUE,
	);
	
	// This will call sopac_admin_submit() to clear menu cache
	$form = system_settings_form($form);
	$form['#submit'][] = 'sopac_admin_submit';
	
	// Return the SOPAC configuration form
	return $form;
	
}

// Part of supporting user home branch in multibranch situation
function sopac_setup_user_home_selector() {
	$admin_card = variable_get('sopac_admin_card', '');
	if (!variable_get('sopac_home_branch_enable', 0) || !$admin_card || variable_get('sopac_home_selector_options', false)) {
		return false;
	}
	$locum = new locum_client;
	$bib_numbers = $locum->get_bib_numbers();
	if (!count($bib_numbers)) {
		return false;
	}
	foreach ($bib_numbers as $bnum) {
		$hold_result = $locum->place_hold($admin_card, $bnum);
		if (is_array($hold_result['choose_location'])) {
			$options = $hold_result['choose_location']['options'];
			variable_set('sopac_home_selector_options', $options);
			$options = join("\n\r", $options);
			$description = t('Choose a branch as your home. This will be the default pickup location when you place holds.');
			db_query("
				INSERT INTO {profile_fields} (title, name, explanation, category, type, weight, required, register, visibility, autocomplete, options, page) 
				VALUES ('%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, %d, '%s', '%s')", 
				'Home Branch', 'profile_pref_home_branch', $description, 'Preferences', 'selection', 1, 0, 1, 1, 0, $options, ''
			);
			return true;
		}
	}
	return false;
}

// Rebuild menu cache
function sopac_admin_submit($form, &$form_state) {
    menu_rebuild();
}

