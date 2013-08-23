<?php
/**
 * @package     Markdown
 * @subpackage  Plugin.Content.markdown
 *
 * @copyright   Copyright (C) 2013 piotr-cz, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Import dependencies
jimport('joomla.plugin.plugin');
JLoader::import('phpmarkdown.markdown');

/**
 * Markdown Content Plugin.
 *
 * @package     Markdown
 * @subpackage  Plugin.Content.markdown
 * @since       0.1
 * 
 * Possible params: Show help, Show preview
 *
 * Nonsense for: com_redirect, com_templates
 */
class plgContentMarkdown extends JPlugin
{
	/**
	 * Base path for editor files
	 *
	 * @var  string
	 */
	protected $basePath = 'media/editors/markdown/';

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Plugin that converts Markdown text to HTML
	 * 
	 * @param   string  $context  The context of the content being passed to the plugin.
	 * @param   mixed   &$row     An object with a "text" property or the string to be cloaked.
	 * @param   array   &$params  Additional parameters. See {@see plgEmailCloak()}.
	 * @param   int     $page     Optional page number. Unused. Defaults to zero.
	 * 
	 * @return  boolean  True on success.
	 * 
	 * @ref     Decreasing headings using RegEx  http://stackoverflow.com/questions/10993778/decreasing-all-headings-in-classic-asp
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Initialise params
		$processGlobal	= (int) $this->params->get('process', 1);
	//	$contexts		= (array) explode(',', $this->params->get('contexts_custom'));
	//	$contexts_type	= (string) $this->params->get('contexts_type', 'none');
		$cache			= (int)	$this->params->get('cache', 1);
		$cache_time		= (int)	$this->params->get('cache_time', 900);
		$remove_h1		= (int)	$this->params->get('remove_h1', 1);
		$jhttp_adapter	= (string) $this->params->get('jhttp_adapter', 'Curl');
		$md_extensions	= (array) explode(',', strtolower( (string) $this->params->get('md_extensions', ',md,MARKDOWN,txt')));
		// Run this plugin when content is being indexed.

		// Check context
		if (!$this->inContext($context))
		{
			return true;
		}

	/*
		// Read category params
		$categories 		= JCategories::getInstance( substr($component, 4 ) );
		$category			= $categories->get($row->catid);
		$category_params	= new JRegistry($category->params);
	*/

		// Check if MD allowed in article
		$processItem	= (int) (isset($row->params)) ? $row->params->get('md_process', '') : $processGlobal;

		// Process all but not this; or Don't process all but process trhis
		if ($processItem == -1
			|| ($processItem == 0 && $processGlobal == -1))
		{
			return true;
		}

		// Load stylesheet
		if ($this->params->get('style', 0))
		{
			JHtml::_('stylesheet', $this->basePath . 'css/Markdown.preview.css');
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($row->text, '{md ') !== false)
		{
			// Expression to search for (md)
			$regex		= '/{md\s+(.*?)}/i';

			// Find all instances of plugin and put in $matches for loadposition
			// $matches[0] is full pattern match, $matches[1] is the position
			preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

			// No matches, skip this
			if ($matches)
			{
				// Import dependencies
				jimport('joomla.http.http');
				jimport('joomla.http.transport');
				jimport('joomla.filesystem.file');

				// Configure HTTP
				$options 			= new JRegistry;

				if ($jhttp_adapter == 'auto')
				{
					$jhttp_adapter = null;
				}

				// Transports: Curl | Socket | Stream (default)
				// Since Platform 12.1. (and timeout functionality)
				if (class_exists('JHttpFactory'))
				{
					$jhttp				= JHttpFactory::getHttp($options, $jhttp_adapter);
				}
				// Method specified
				elseif ($jhttp_adapter)
				{
					$jhttp_transport 	= 'JHttpTransport' . ucfirst($jhttp_adapter);
					$jhttp 				= new JHttp($options, new $jhttp_transport($options));
				}
				// No method
				else
				{
					$jhttp 				= new JHttp($options, null);
				}

				// Setup cache
				// Note: Cannot use ETag (github responds 404 to Options), Doesn't make sense anyway
				$jcache			= JFactory::getCache('plg_content_markdown', '');
				$jcache->setLifetime($cache_time);
				$jcache->setCaching(true);

				// TODO: file path when referencing local files without a slash /
			//	$file_path 		= JComponentHelper::getParams('com_media')->get('file_path', 'images');

				foreach ($matches as $match)
				{
					$md_url 		= $match[1];
					$output			= false;

					// File on local filesystem
					// && Check extension
					if (stripos($md_url, 'http') !== 0
						&& in_array(strtolower(JFile::getExt($md_url)), $md_extensions)
					)
					{
						// Fix for absolute path. Relative paths apply to JPATH_BASE
						if (strpos($md_url, '/') === 0 || strpos($md_url, '\\') === 0)
						{
							$md_url = $_SERVER['DOCUMENT_ROOT'] . $md_url;
						}

						// Check if file exists
						if (JFile::exists($md_url))
						{
							$output = file_get_contents(JPath::clean($md_url));
						}
					}
					// External file
					else
					{
						// Cache is ON or empty
						if ($cache && !($output = $jcache->get($md_url)))
						{
							// Lock cache
							$jcache->lock($md_url);

							// Get content
							$output	= self::getContent($jhttp, $md_url);

							if ($output !== false)
							{
								// Store in cache
								$jcache->store($output, $md_url);
							}

							$jcache->unlock($md_url);
						}
						// Cache is OFF
						else
						{
							// Get content
							$output = self::getContent($jhttp, $md_url);
						}

						// Keep URL in the content
						if ($output === false)
						{
							$output = $md_url;
						// continue;
						}
					}

					// Remove first header (CR == \r, LF == \n)
					if ($output && $remove_h1)
					{
						$output = preg_replace('~(#.*#?)|(.+\r?\n=+\r?\n)~', '', $output, 1);
					}

					// Replace
					$row->text = str_replace($match[0], $output, $row->text);
				// $row->text = preg_replace("|$match[0]|", $output, $row->text, 1);
				}
			}
		}


		// Convert
		$row->text	= MarkdownExtra::defaultTransform($row->text);

		// Wrap with classname
		// Note: Bootstrapp has styles included, cound drop this.
		if ($this->params->get('style', 0))
		{
			$row->text = '<div class="markdown wmd-preview">' . $row->text . '</div>';
		}

		return true;
	}

	/**
	 * Add field to form
	 * In categories look under tab Options > Markdown Options
	 * 
	 * @param   JForm  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return	boolean
	 */
	public function onContentPrepareForm($form, $data)
	{
		// Check we have a form
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Get parameters
		$processGlobal	= (int) $this->params->get('process', 1);
		$context		= $form->getName();
//print_r($context); print_r($form); die();
		if (!$this->inContext($context))
		{
			return true;
		}

		// Com_content doesn't have params but attribs
		$paramsName = ($context == 'com_content.article') ? 'attribs' : 'params';

		// TOOO: Add H1 stuff
		$form->load(
<<<FORM_MARKDOWN_OPTIONS
<form>
	<fields name="{$paramsName}">
		<fieldset
			name="md"
			label="PLG_CONTENT_MARKDOWN_FIELDSET_LABEL"
			description="PLG_CONTENT_MARKDOWN_FIELDSET_DESC"
		>
			<field
				name="md_process"
				type="radio"
				default=""
				class="btn-group"
				label="PLG_CONTENT_MARKDOWN_FIELD_PROCESS_ITEM_LABEL"
				description="PLG_CONTENT_MARKDOWN_FIELD_PROCESS_ITEM_DESC"
			>
				<option value="">JGLOBAL_USE_GLOBAL</option>
				<option value="-1">JNO</option>
				<option value="1">JYES</option>
			</field>
		</fieldset>
	</fields>
</form>
FORM_MARKDOWN_OPTIONS
);

		return true;
	}

	/**
	 * Get content from URI
	 * 
	 * @param   JHttp   $jhttp  JHttp Object
	 * @param   string  $uri    Uri to get response from
	 * 
	 * @return  string
	 */
	protected static function getContent($jhttp, $uri)
	{
		try
		{
			$response = $jhttp->get($uri);
		}
		// RuntimeException  Cannot use transport
		// UnexpectedValueException  'No HTTP response received.'|'No HTTP response code found.'
		catch (Exception $e)
		{
			return false;
		}
		if ($response->code !== 200
		//	|| !isset($response->body)
			|| strpos($response->headers['Content-Type'], 'text/plain') !== 0 // text/plain; charset=utf-8
		)
		{
			return false;
		}

		return $response->body;
	}

	/**
	 * Check if right context
	 *
	 * @param   string  $context
	 *
	 * @return  boolean
	 */
	protected function inContext($context)
	{
		// Get parameter
		$filtering_custom	= (array) explode(',', $this->params->get('contexts_custom'));

		// Unpack component from context
		list ($component) = explode('.', $context);

		// Check each parameter
		foreach ($filtering_custom as $filtering_context)
		{
			// No dot
			if (strpos($filtering_context, '.') === false)
			{
				if ($filtering_context === $component)
				{
					return true;
				}
			}
			// Dot
			else if ($filtering_context == $context)
			{
				return true;
			}
		}

		return false;
	}
	protected function inContext0($context)
	{
		// Initialise parameters
		$filtering_type		= (string) $this->params->get('contexts_type', 'cwl');
		$filtering_custom	= (array) explode(',', $this->params->get('contexts_custom'));

		if ($filtering_type == 'none')
		{
			return true;
		}

		// 1.level (component.view) or com_categories.categorycom_content
		list ($component) = explode('.', $context);

		// Trim xontexts items
		$contexts = array_map('trim', $filtering_custom);

		// Check
		return (
			($filtering_type == 'cwl' && in_array($component, $contexts))
			|| ($filtering_type == 'cbl' && !in_array($component, $contexts))
		);
	}
}
