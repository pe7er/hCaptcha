<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @author      Peter Martin
 * @copyright   Copyright 2016-2017 Peter Martin. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://db8.eu
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  1.0
 */
class PlgCaptchaHcaptchaInstallerScript extends InstallerScript
{
	/**
	 * Extension script constructor.
	 *
	 * @since   1.0.1
	 */
	public function __construct()
	{
		// Define the minumum versions to be supported.
		$this->minimumJoomla = '3.8';
		$this->minimumPhp    = '7.0';
	}
}
