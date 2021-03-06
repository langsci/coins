<?php

/**
 * @file plugins/generic/coins/CoinsPlugin.inc.php
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CoinsPlugin
 * @ingroup plugins_generic_coins
 *
 * @brief COinS plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('classes.monograph.PublishedMonographDAO');

class CoinsPlugin extends GenericPlugin {
	/**
	 * @see Plugin::register
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			HookRegistry::register ('TemplateManager::display', array($this, 'handleTemplateDisplay'));
		}
		return $success;
	}

	/**
	 * @see Plugin::getDisplayName
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.coins.displayName');
	}

	/**
	 * @see Plugin::getDescription
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.coins.description');
	}

	/**
	 * @see Plugin::getInstallSitePluginSettingsFile
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Intercept the googlescholar template to add the COinS tag
	 * @param $hookName string Hook name
	 * @param $args array Array of hook parameters
	 * @return boolean false to continue processing subsequent hooks
	 */
	function handleTemplateInclude($hookName, $args) {
		$templateMgr =& $args[0];
		$smarty =& $args[1];
		if (!isset($smarty['smarty_include_tpl_file'])) return false;
		switch ($smarty['smarty_include_tpl_file']) {
			case 'frontend/objects/monograph_dublinCore.tpl':
				
				// get variables
				$request = $this->getRequest();

				// monograph metadata
				$publishedMonograph = $templateMgr->get_template_vars('publishedMonograph');
				$authors = $publishedMonograph->getAuthors();
				$firstAuthor = $authors[0];
				$datePublished = $publishedMonograph->getDatePublished();
				$language = $publishedMonograph->getLocale();
				
				// press metadata
				$currentPress = $templateMgr->get_template_vars('currentPress');
				$publisher = $currentPress->getLocalizedName();
				$place = $currentPress->getSetting('location');
				
				// series metadata
				$seriesId = $publishedMonograph ->getSeriesId();
				$seriesDAO = new SeriesDAO;
				$series = $seriesDAO -> getById($seriesId,1);
				$seriesTitle = $series->getLocalizedFullTitle();
				$seriesPosition = $publishedMonograph ->getSeriesPosition();
				
				// put values in array 
				$vars = array(
					array('ctx_ver', 'Z39.88-2004'),
					array('rft_id', $request->url(null, 'catalog', 'book', $publishedMonograph->getId())),
					// coins id for book
					array('rft_val_fmt', 'info:ofi/fmt:kev:mtx:book'),
					// genre: book
					array('rft.genre', 'book'),
					// booktitle
					array('rft.btitle', $publishedMonograph->getLocalizedFullTitle()),
					
					array('rft.aulast', $firstAuthor->getLastName()),
					array('rft.aufirst', $firstAuthor->getFirstName()),
					array('rft.auinit', $firstAuthor->getMiddleName()),
				
					// series
					array('rft.series', $seriesTitle),
					
					// publisher
					array('rft.publisher', $publisher), 
					array('rft.place', $place), 
					
					// published date
					array('rft.date', date('Y-m-d', strtotime($datePublished))),
					
					// language
					array('rft.language', substr($language, 0, 3)),
					
				);

				$title = '';
				foreach ($vars as $entries) {
					list($name, $value) = $entries;
					$title .= $name . '=' . urlencode($value) . '&';
				}
				$title = htmlentities(substr($title, 0, -1));

				$templateMgr->assign('title', $title);
				$templateMgr->display($this->getTemplatePath() . 'coinsTag.tpl', 'text/html', 'CoinsPlugin::addCoinsTag');
				break;
		}
		return false;
	}

	/**
	 * Hook callback: Handle requests.
	 * @param $hookName string Hook name
	 * @param $args array Array of hook parameters
	 * @return boolean false to continue processing subsequent hooks
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];
		switch ($template) {
			case 'frontend/pages/book.tpl':
				HookRegistry::register ('TemplateManager::include', array($this, 'handleTemplateInclude'));
				break;
		}
		return false;
	}

}
?>
