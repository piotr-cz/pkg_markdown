<?php
/**
 * @package     Markdown
 * @subpackage  Plugin.Editors.markdown
 *
 * @copyright   Copyright (C) 2013 piotr-cz, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Quick check
if (!isset($_POST['client']))
{
	die;
}

// We are a valid Joomla entry point.
define('_JEXEC', 1);

// Determine if we are in Site or Administration
$client 	= ($_POST['client']);

// Get Base
$base		= dirname(dirname(dirname(__FILE__)));

// Alter base for administrator
if ($client == 'administrator')
{
	$base .= '/administrator';
}
// Or reset client to site
else
{
	$client = 'site';
}

// Setup the base path related constant.
define('JPATH_BASE', $base);

require_once ( JPATH_BASE . '/includes/defines.php' );
require_once ( JPATH_BASE . '/includes/framework.php' );


// Import dependencies
JLoader::import('phpmarkdown.markdown');


// Get Application object
$app		= JFactory::getApplication($client);
$input		= $app->input;

// Check for request forgeries.
JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

// Get data
$data		= $input->get->post('data', null, 'html');

// Convert MD to HTML
$html		= MarkdownExtra::defaultTransform($data);

// Prepare headers
header("Content-Type", "text/html; charset=utf-8");
//header("Content-Type", "x-text/html-fragment; charset=utf-8");

// Output HTML
echo $html;


// Exit
jexit();
