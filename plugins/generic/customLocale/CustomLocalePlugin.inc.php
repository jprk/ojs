<?php

/**
 * @file plugins/generic/customLocale/CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 *
 * @brief This plugin enables customization of locale strings.
 */

define('CUSTOM_LOCALE_DIR', 'customLocale');
import('lib.pkp.classes.plugins.GenericPlugin');

class CustomLocalePlugin extends GenericPlugin {
	/**
	 * Register the plugin.
	 * @param $category string Plugin category
	 * @param $path string Path to plugin
	 * @return boolean Registration success
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Add custom locale data for already registered locale files.
				$locale = AppLocale::getLocale();
				$localeFiles = AppLocale::getLocaleFiles($locale);
				$request =& $this->getRequest();
				$journal = $request->getJournal();
				$journalId = $journal->getId();
				$publicFilesDir = Config::getVar('files', 'public_files_dir');
				$customLocalePathBase = $publicFilesDir . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR;

				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				foreach ($localeFiles as $localeFile) {
					$customLocalePath = $customLocalePathBase . $localeFile->getFilename();
					if ($fileManager->fileExists($customLocalePath)) {
						AppLocale::registerLocaleFile($locale, $customLocalePath, true);
					}
				}

				// Add custom locale data for all locale files registered after this plugin
				HookRegistry::register('PKPLocale::registerLocaleFile', array($this, 'addCustomLocale'));
			}

			return true;
		}
		return false;
	}

	/**
	 * Hook callback to add a custom locale file.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function addCustomLocale($hookName, $args) {
		$locale =& $args[0];
		$localeFilename =& $args[1];

		$request =& $this->getRequest();
		$journal = $request->getJournal();
		$journalId = $journal->getId();
		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		$customLocalePath = $publicFilesDir . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $localeFilename;

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->fileExists($customLocalePath)) {
			AppLocale::registerLocaleFile($locale, $customLocalePath, true);
		}

		return true;
	}

	/**
	 * Get the display name for the plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.customLocale.name');
	}

	/**
	 * Get the description for the plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.customLocale.description');
	}

	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['key'])) {
			$params['path'] = array_merge($params['path'], array($params['key']));
			unset($params['key']);
		}

		if (!empty($params['file'])) {
			$params['path'] = array_merge($params['path'], array($params['file']));
			unset($params['file']);
		}

		return $smarty->smartyUrl($params, $smarty);
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		if (!parent::manage($args, $request)) return false;

		$this->import('CustomLocaleHandler');
		$customLocaleHandler = new CustomLocaleHandler($this->getName());
		switch ($array_shift($args)) {
			case 'edit':
				$customLocaleHandler->edit($args, $request);
				return true;
			case 'saveLocaleChanges':
				$customLocaleHandler->saveLocaleChanges($args, $request);
				return true;
			case 'editLocaleFile':
				$customLocaleHandler->editLocaleFile($args, $request);
				return true;
			case 'saveLocaleFile':
				$customLocaleHandler->saveLocaleFile($args, $request);
				return true;
			default:
				$customLocaleHandler->index($args, $request);
				return true;
		}
	}
}

?>
