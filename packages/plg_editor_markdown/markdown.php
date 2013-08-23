<?php
/**
 * @package     Markdown
 * @subpackage  Plugin.Editors.markdown
 *
 * @copyright   Copyright (C) 2013 piotr-cz, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;


// Import dependencies
JLoader::register('plgEditorNone', JPATH_PLUGINS . '/editors/none/none.php');

/**
 * Markdown Editor Plugin.
 *
 * @package     Markdown
 * @subpackage  Plugin.Editors.markdown
 * @since       0.1
 * 
 * Possible params: Show help, Show preview
 * Extending JEditorNone for onInit
 */
class plgEditorMarkdown extends plgEditorNone // JPlugin
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
	 * Initialises the Editor.
	 *
	 * @return	 string  JavaScript Initialization string.
	 */
	public function onInit()
	{
	// JHtml::_('core'); or JHtml::_('behavior.framework');

		JHtml::_('script', $this->basePath . 'js/Markdown.Converter.js', false, false, false, false);
		JHtml::_('script', $this->basePath . 'js/Markdown.Sanitizer.js');
		JHtml::_('script', $this->basePath . 'js/Markdown.Editor.js');

		JHtml::_('stylesheet', $this->basePath . 'css/Markdown.css');
		JHtml::_('stylesheet', $this->basePath . 'css/Markdown.preview.css');

		// Add Preview rendering function
		// Get plgEditorNone->insertAtCursor
		return parent::onInit();
	}

	/**
	 * Copy editor content to form field.
	 * 
	 * @param   string  $id  The id of the editor field.
	 *
	 * @return  string  Javascript
	 * 
	 * @note    called by
	 * 	Joomla.submibutton
	 * 	JFormFieldEditor->save() [/administrator/components/com_content/views/article/tmpl/edit.php:51]
	 * 
	 * Markdown.converter ignores images with paths to relative files.
	 */
	public function onSave($id)
	{
		// Plain MD
		if ($this->params->get('output_format', 'md') != 'html')
		{
			return;
		}

		$root			= JUri::root();

		// Convert to HTML
		return <<<SCRIPT

			var element			= document.getElementById( 'wmd-input_{$id}' )
			  , converter		= Joomla.editors.instances[ '{$id}' ].getConverter()
			  , html			= converter.makeHtml( element.value )
			;

			// Remove root from anchors and images
			element.value 	= html
				.replace( '<img src="{$root}', '<img src="' )
				.replace( '<a href="{$root}', '<a href="' )
			;


SCRIPT;
	}

	/**
	 * Get the editor content.
	 * 
	 * @param   string  $id  The id of the editor field.
	 *
	 * @return  string  Javascript
	 */
	public function onGetContent($id)
	{
		return "document.getElementById( 'wmd-input_{$id}' ).value; \n";
	}

	/**
	 * Set the editor content.
	 * 
	 * @param   string  $id       The id of the editor field.
	 * @param   string  $content  The content to set.
	 *
	 * @return  string  Javascript
	 */
	public function onSetContent($id, $content)
	{
		return <<<SCRIPT

	document.getElementById( 'wdm-input_{$id} ).value = {$content};
	Joomla.editors.instances[ '{$id}' ].refreshPreview();
	return;

SCRIPT;
	}

	/**
	 * Adds the editor specific insert method.
	 * 
	 * @return  boolean
	 * 
	 * @note  When code is in javascript file, this should be much easier.
	 * @note  to add title to image use (?: title=("[^"]*"))? -> (.. "Optional title")
	 */
	public function onGetInsertMethod()
	{
		static $done = false;

		// Do this only once.
		if ($done)
		{
			return true;
		}

		$done = true;
		JFactory::getDocument()->addScriptDeclaration(
<<<SCRIPT
	function jInsertEditorText( text, editor )
	{
		// Parse Image HTML > MD
		if ( !text.indexOf('<img '))
		{
			text = text.replace( /<img src="([^"]*)" alt="([^"]*)"[^\/]*\/>/gmi, "![$2]($1)" );
		}
		// Parse Anchor HTML > MD
		else if ( !text.indexOf('<a '))
		{
			text = text.replace( /<a href="([^"]*)"[^>]*>([^<]*)<\/a>/gmi, "[$2]($1)" );
		}

		insertAtCursor( document.getElementById( 'wmd-input_' + editor ), text );
		Joomla.editors.instances[ editor ].refreshPreview();
	}
SCRIPT
);

		return true;
	}

	/**
	 * Display the editor area.
	 * 
	 * @param   string   $name     The control name.
	 * @param   string   $content  The contents of the text area.
	 * @param   string   $width    The width of the text area (px or %). 100%
	 * @param   string   $height   The height of the text area (px or %). 250
	 * @param   int      $col      The number of columns for the textarea.
	 * @param   int      $row      The number of rows for the textarea.
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $id       An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param   string   $asset    Asset ID
	 * @param   object   $author   Author ID
	 * @param   array    $params   Associative array of editor parameters.
	 * 
	 * @return  string  HTML
	 */
	public function onDisplay($name, $content, $width, $height, $col, $row,
		$buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		// Initialise variables.
		$root			= JUri::root();

		// HTML markups
		$html_preview  	= '';
		$html_buttons	= '';

		// JSON strings
		$json_help		= '{}';
		$json_strings	= self::translateEditorUI();

		// Get parameters
		$show_preview	= $this->params->get('show_preview', 1);
		$show_help		= $this->params->get('show_help', 1);

		if (empty($id))
		{
			$id = $name;
		}

		// Only add "px" to width and height if they are not given as a percentage
		if (is_numeric($width))
		{
			$width .= 'px';
		}

		if (is_numeric($height))
		{
			$height .= 'px';
		}

		// Must pass the field id to the buttons in this editor.
		$html_buttons 	.= $this->_displayButtons($id, $buttons, $asset, $author);

		// Get preview area
		if ($show_preview)
		{
			$html_buttons	.= $this->_toogleButton($id);
			$html_preview	.= '<div id="wmd-preview_' . $id . '" class="wmd-panel wmd-preview" style="max-height:' . $height . '; width:' . $width . '"></div>';
		}

		// Build panel markup
		$html = <<<HTML
	<div class="wmd-panel">
		<div id="wmd-button-bar_{$id}"></div>
		<textarea name="{$name}" class="wmd-input" id="wmd-input_{$id}" cols="{$col}" rows="{$row}" style="width: {$width}; height: {$height};">{$content}</textarea>
	</div>
	{$html_preview}
	{$html_buttons}
HTML;

		// Show Help?
		if ($show_help)
		{
			// Note: Cannot use modal for cross-origin
			// @ref  MDC: window.open  https://developer.mozilla.org/en-US/docs/DOM/window.open
			$json_help	= "{
				'title'		: '" . JText::_('PLG_EDITORS_MARKDOWN_LOCALE_HELPTITLE', 'Markdown Editing Help') . "',
				'handler'	: function(){ window.open( 'http://stackoverflow.com/editing-help', 'help', 'height=500,width=750,scrollbars=yes,scrollbars=yes' ); }
}";
		}

		// Add script declaration (after elements or on DOMready
	// JFactory::getDocument()->addScriptDeclaration(<<<SCRIPT
		$html .= (<<<SCRIPT
	<script type="text/javascript">
	

	(function(){

		// Prepare converter
		var converter 	= Markdown.getSanitizingConverter(); // OR new Markdown.Converter();


		// RegExp
		var rxMdRelative 	= /(\!?\[.*\])\(((?!(https?:)?\/\/).*)\)/gmi
		  , rxHtmlRelative	= /(<img src|<a href)="((?!(https?:)?\/\/).+?)"/gmi
		;

		// Prepend root for preview relative paths (Images and Anchors) MD and HTML.
		converter.hooks.chain( 'preConversion', function( text )
		{
			return text
				.replace( rxMdRelative, '$1({$root}$2)' )
				.replace( rxHtmlRelative, '$1="{$root}$2"' )
			;
		});

		// Prepare options
		var options =
		{
			// Help button functionality
			helpButton	: {$json_help},
	
			// Local strings
			strings		: {$json_strings}
		};


		// Run editor
		var editor		= new Markdown.Editor( converter, '_{$id}', options );
		editor.run();

		// Create pointer to editor instance
		Joomla.editors.instances['{$id}'] = editor;
	})();

</script>
SCRIPT
);

		return $html;
	}

	/**
	 * Adds preview button
	 *
	 * @param   string  $id  The id of the editor field.
	 * 
	 * @return  string
	 *
	 * @todo    add PLG_EDITORS_MARKDOWN_BUTTON_PREVIEW for ajax previews
	 * @note    Could use 'active' class on button
	 */
	private function _toogleButton($id)
	{
		$title 		= JText::_('PLG_EDITORS_MARKDOWN_BUTTON_TOGGLE_EDITOR');

		$onclick	= "var previewStyle = document.getElementById( 'wmd-preview_{$id}' ).style; previewStyle.display = (previewStyle.display == 'none') ? '' : 'none'; return false";

		if (version_compare(JVERSION, '3', 'ge'))
		{
			$html = <<<TOGGLE_BTN
	<div class="toggle-editor btn-toolbar pull-right">
		<div class="btn-group">
			<a class="btn" href="#" onclick="{$onclick}" title="{$title}">
				<i class="icon-eye"></i>
				{$title}
			</a>
		</div>
	</div>
	<div class="clearfix"></div>
TOGGLE_BTN;
		}
		else
		{
			$html = <<<TOGGLE_BTN

	<div class="toggle-editor">
		<div class="button2-left">
			<div class="blank">
				<a onclick="{$onclick}" title="{$title}">
					{$title}
				</a>
			</div>
		</div>
	</div>
TOGGLE_BTN;
		}

		return $html;
	}

	/**
	 * Translate Toolbar UI
	 * 
	 * @return  string
	 */
	private static function translateEditorUI()
	{
		// Initialise variables.
		$lang			= JFactory::getLanguage();
		$strings_json	= '{}';

		// Skip for English
		if ($lang->get('tag') == 'en-GB')
		{
			return $strings_json;
		}

		// Translation strings
		$string = array_flip(
			array(
				'bold', 'boldexample',
				'italic', 'italicexample',
				'link', 'linkdescription', 'linkdialog',
				'quote', 'quoteexample',
				'code', 'codeexample',
				'image', 'imagedescription', 'imagedialog',
				'olist', 'ulist', 'litem',
				'heading', 'headingexample',
				'hr',
				'undo', 'redo', 'redomac',
				'help'
			)
		);

		// Translate each key
		foreach ($strings as $key => &$translated)
		{
			$key = 'PLG_EDITOR_MARKDOWN_LOCALE_' . strtoupper($key);

			if ($lang->hasKey($key))
			{
				$translated = JText::_($key);
			}
		}

		// Encode
		$strings_json = json_encode($strings);

		return $strings_json;
	}

	/**
	 * Render editor-xtd buttons
	 *
	 * @param   string   $name  The control name.
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $asset    Asset ID
	 * @param   object   $author   Author ID
	 * 
	 * @return  string  HTML
	 */
	public function _displayButtons($name, $buttons, $asset, $author)
	{
	//	return parent::_displayButtons($name, $buttons, $asset, $author);

		// Load modal popup behavior
		JHtml::_('behavior.modal', 'a.modal-button');

		$args		= array('name' => $name, 'event' => 'onGetInsertMethod');

		$html 	= '';
		$results[] 	= $this->update($args);

		foreach ($results as $result)
		{
			if (is_string($result) && trim($result))
			{
				$html .= $result;
			}
		}

		if (is_array($buttons) || (is_bool($buttons) && $buttons))
		{
			$results 		= $this->_subject->getButtons($name, $buttons, $asset, $author);
			$buttons_html 	= '';

			foreach ($results as $button)
			{
				// Results should be an object
				if ( !$button->get('name'))
				{
					continue;
				}

				$modal 		= ($button->get('modal')) ? 'class="modal-button btn"' : null;
				$href		= ($button->get('link')) ? 'class="btn" href="' . JUri::base() . $button->get('link') . '"' : null;
				$onclick	= ($button->get('onclick')) ? 'onclick="' . $button->get('onclick') . '"' : null;
				$title		= 'title="' . ( $button->get( $button->get('title') ? 'title' : 'text') )  . '"';
				$rel		= 'rel="' . $button->get('options') . '"';

				$b_name		= $button->get('name');
				$b_text		= $button->get('text');

				if (version_compare(JVERSION, '3.0', 'ge'))
				{
					$buttons_html .= <<<BUTTONS_HTML_3X
							<a {$modal} {$title} {$href} {$onclick} {$rel}><i class="icon-{$b_name}"></i>{$b_text}</a>
BUTTONS_HTML_3X;
				}
				else
				{
					$buttons_html .= <<<BUTTONS_HTML_25
						<div class="button2-left">
							<div class="{$b_name}">
								<a {$modal} {$title} {$href} {$onclick} {$rel}>{$b_text}</a>
							</div>
						</div>
BUTTONS_HTML_25;
				}
			}

			// This will allow plugins to attach buttons or change the behavior on the fly using AJAX
			if (version_compare(JVERSION, '3.0', 'ge'))
			{
				$html .= <<<TOOLBAR_HTML_3X
					<div id="editor-xtd-buttons" class="btn-toolbar pull-left">
						<div class="btn-toolbar">
							{$buttons_html}
						</div>
					</div>
			<!--	<div class="clearfix"></div> -->
TOOLBAR_HTML_3X;
			}
			else
			{
				$html .= <<<TOOLBAR_HTML_25
				<div id="editor-xtd-buttons">
					{$buttons_html}
				</div>
TOOLBAR_HTML_25;
			}
		}

		return $html;
	}
}
