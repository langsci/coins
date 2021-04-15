<?php

/**
 * @file CoinsPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class CoinsPlugin
 * @brief COinS plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CoinsPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.coins.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.coins.description');
	}

	/**
	 * Insert COinS tag.
	 * @param $hookName string
	 * @param $params array
	 * @return boolean
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$request = Application::get()->getRequest();

			// Ensure that the callback is being called from a page COinS should be embedded in.
			if (!in_array($request->getRequestedPage() . '/' . $request->getRequestedOp(), array(
				'catalog/book',
			))) return false;

			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager($request);

			// monograph metadata
			$publication = $templateMgr->getTemplateVars('publication');
			$locale = $publication->getData('locale');
			$authors = $publication->getData('authors');
			$firstAuthor = $authors[0];
			$datePublished = $publication->getData('datePublished');
			$publicationTitle = $publication->getData('title');

			// press metadata
			$press = $templateMgr->getTemplateVars('currentPress');
			$publisher = $press->getLocalizedName();
			$place = $press->getSetting('location');

			// series metadata
			$series = $templateMgr->getTemplateVars('series');
			$seriesTitle = $series->getLocalizedFullTitle();
			//$publication ->getSeriesPosition();

			// put values in array
			$vars = array(
				array('ctx_ver', 'Z39.88-2004'),
				array('rft_id', $request->url(null, 'catalog', 'book', $publication->getData('submissionId'))),
				// coins id for book
				array('rft_val_fmt', 'info:ofi/fmt:kev:mtx:book'),
				// genre: book
				array('rft.genre', 'book'),
				// booktitle
				array('rft.btitle', $publicationTitle[$locale]),
				array('rft.aulast', $firstAuthor->getFamilyName($locale)),
				array('rft.aufirst', $firstAuthor->getGivenName($locale)),
				// series
				array('rft.series', $seriesTitle),
				// publisher
				array('rft.publisher', $publisher),
				array('rft.place', $place),
				// published date
				array('rft.date', date('Y-m-d', strtotime($datePublished))),
				// language
				array('rft.language', $locale),
			);
			$title = '';
			foreach ($vars as $entries) {
				list($name, $value) = $entries;
				$title .= $name . '=' . urlencode($value) . '&';
			}
			$title = htmlentities(substr($title, 0, -1));
			$output .= "<span class=\"Z3988\" title=\"$title\"></span>\n";
		}
		return false;
	}
}