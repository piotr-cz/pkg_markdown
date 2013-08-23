<?php
/**
 * @package     Markdown
 * @subpackage  Library.phpmarkdown
 *
 * @copyright   PHP Markdown & Extra Copyright (c) 2004-2013 Michel Fortin; Original Markdown Copyright (c) 2004-2006 John Gruber
 * @license     GNU General Public License version 2 or later; see License.md
 */

// no direct access
defined('JPATH_PLATFORM') or die;

// Require Markdown classes without autoloaded
require_once dirname(__FILE__) . '/php-markdown-lib/Michelf/Markdown.php';
require_once dirname(__FILE__) . '/php-markdown-lib/Michelf/MarkdownExtra.php';

# Get Markdown class
use \Michelf\Markdown,
	\Michelf\MarkdownExtra
;

// Alias classes to global namespace
class_alias('\Michelf\Markdown', 'Markdown'); // Should be `PhpmarkdownMichelfMarkdown`
class_alias('\Michelf\MarkdownExtra', 'MarkdownExtra');
