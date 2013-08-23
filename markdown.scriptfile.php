<?php
/**
 * @package     Markdown.Package
 * @subpackage  pkg_markdown
 * 
 * @copyright   Copyright (C) 2013 piotr_cz. All rights reserved.
 * @licence     GNU General Public Licence version 2 or later; see LICENCE.txt
 */

defined('_JEXEC') or die;

/**
 * Scriptfile for markdown package
 *
 * @package     Markdown.Package
 * @subpackage  pkg_markdown
 */
class Pkg_MarkdownInstallerScript
{
	/**
	 * Method to install the package
	 * 
	 * @param   JInstaller  $parent
	 * 
	 * @return  boolean     True to continue, False to rollback
	 */
	public function install( $parent )
	{
		return true;
	}

	/**
	 * Method to uninstall the package
	 * 
	 * @param   JInstaller  $parent
	 * 
	 * @return  void
	 */
	public function uninstall( $parent )
	{
		return;
	}

	/**
	 * Method to update the package
	 * 
	 * @param   JInstaller  $parent
	 * 
	 * @return  boolean     True to continue, False to rollback
	 */
	public function update( $parent )
	{
		return true;
	}

	/**
	 * Joomla! preflight event
	 * 
	 * @param   string      $type   One of 'install'|'discover_install'|'update'
	 * @param   JInstaller  $parent
	 * 
	 * @return  boolean
	 */
	public function preflight( $type, $parent )
	{
		return true;
	}

	/**
	 * Joomla! postflight event
	 *
	 * @param   string      $type  One of 'install'|'discover_install'|'update'
	 * @param   JInstaller  $parent
	 *
	 * @return  void
	 */
	public function postflight( $type, $parent )
	{
		return;
	}
}
