<?php
/*
Plugin Name: reStructuredText
Version: 1.0
Plugin URI: http://xdissent.com
Description: A reStructuredText interface for WordPress.
Author: Greg Thornton
Author URI: http://xdissent.com
*/

error_reporting(E_ALL);

define('SCRIPT_DEBUG', true);

class ReStPlugin 
{
    /**
     * Initializes the plugin, registering it the WordPress.
     *
     * This is the main entry point for most WordPress interactions and is
     * run automatically when this plugin is activated. The net effect is
     * that the Media Library will accept uploaded reSt files, and the editor
     * will contain the reSt interface when creating or updating WordPress
     * Posts or Pages.
     *
     * @return null
     *
     * @todo Add settings to configure where/when reSt is added to the editor.
     */
    public static function init()
    {
        /**
         * Protect from initialization in a non-WordPress environment.
         */
        if (!function_exists('add_action')) {
            return;
        }
        
        /**
         * Add the reSt interface to the "Edit Post" page editor.
         */
        add_action(
            'load-post.php',
            array(__CLASS__, 'hijackEditor')
        );
        
        /**
         * Add the reSt interface to the "New Post" page editor.
         */
        add_action(
            'load-post-new.php',
            array(__CLASS__, 'hijackEditor')
        );
        
        /**
         * Add some custom mime types to the WordPress media upload whitelist.
         */
        add_filter('upload_mimes', array(__CLASS__, 'whitelistMediaTypes'));
    }

    /**
     * Installs various hooks and filters that let us play with the editor.
     *
     * @return null
     */
    public static function hijackEditor()
    {
        /**
         * Install the filter that fixes WordPress's default editor handling.
         */
        add_filter('wp_default_editor', array(__CLASS__, 'catchDefaultEditor'));
        
        /**
         * Install the action to start a output buffer for hacking the editor.
         */
        add_action(
            'post_submitbox_start',
            array(__CLASS__, 'installBuffer')
        );
        
        /**
         * Install the action to hack the form and kill the output buffer.
         */
        add_action(
            'edit_form_advanced',
            array(__CLASS__, 'addRestEditor')
        );
        
        /**
         * Install the required scripts for the "New Post" page.
         */
        add_action(
            'admin_print_scripts-post-new.php', 
            array(__CLASS__, 'installScripts')
        );

        /**
         * Install the required scripts for the "Edit Post" page.
         */
        add_action(
            'admin_print_scripts-post.php', 
            array(__CLASS__, 'installScripts')
        );
        
        /**
         * Install the required styles for the "New Post" page.
         */
        add_action(
            'admin_print_styles-post-new.php', 
            array(__CLASS__, 'installStyles')
        );
        
        /**
         * Install the required styles for the "Edit Post" page.
         */
        add_action(
            'admin_print_styles-post.php', 
            array(__CLASS__, 'installStyles')
        );
        
        /**
         * Register the reSt "the_editor" filter with WordPress.
         */
        add_filter('the_editor', array(__CLASS__, 'filterEditor'));
    }

    /**
     * Returns an accepted media type array, with some new types whitelisted.
     *
     * This filter is called by the "upload_mimes" WordPress filter if this
     * plugin is activated.
     *
     * @param array $mimes The current media type whitelist array.
     *
     * @return array
     */
    public static function whitelistMediaTypes($mimes)
    {
        return array_merge($mimes, array('rst|rest' => 'text/plain'));
    }
    
    /**
     * Enqueues the plugin's scripts for output by WordPress.
     *
     * @return null
     *
     * @todo Determine the script include paths dynamically.
     */
    public static function installScripts()
    {
        wp_enqueue_script(__CLASS__, '/wp-content/plugins/wp-rest/rest.js', array('editor'));
    }
    
    /**
     * Enqueues the plugin's styles for output by WordPress.
     *
     * @return null
     *
     * @todo Determine the style include paths dynamically.
     */
    public static function installStyles()
    {
        wp_enqueue_style(__CLASS__, '/wp-content/plugins/wp-rest/rest.css', null, null, 'all');
    }

    /**
     * Creates an additional output buffer to begin saving output data.
     *
     * <caution>
     * This is a *less than ideal* solution (read: hack) to get around the
     * inability of WordPress plugins to add their own tabs to the editor
     * HTML.
     * </caution>
     *
     * @return null
     *
     * @todo Add multiple buffer protection based on {@link ob_get_level()}.
     */
    public static function installBuffer()
    {
        ob_start();
    }
    
    /**
     * Fixes requests for the default editor when the default is 'rest'.
     *
     * WordPress uses the "wp_default_editor" filter to allow plugins to
     * override the active editor interface if required. The default
     * WordPress logic only considers "html" and "tinymce" as available
     * editors, so custom editor interfaces require this extra processing.
     *
     * <note>
     * It may be assumed that the {@link wp_default_editor()} function will
     * always return 'rest' if this plugin is activated, and the reSt 
     * interface is active, since this filter will be installed automatically.
     * </note>
     *
     * @param string $editor The name of the default editor (from WordPress).
     *
     * @return string
     */
    public static function catchDefaultEditor($editor)
    {
        /**
         * Get the current user (loads values from cookies).
         */
        if ($user = wp_get_current_user()) {
            /**
             * Check to see if the default is in fact 'rest', or if we were
             * passed 'rest' in the first place (miracle).
             */
            $ed = get_user_setting('editor', $editor);
            if ($ed === 'rest') {
                /**
                 * The default editor should be rest.
                 */
                $editor = $ed;
            }
        }

        /**
         * Return the editor for more processing by WordPress.
         */
        return $editor;
    }
    
    /**
     * Adds the reSt editor interface to the WordPress editor HTML.
     *
     * This method retrieves a portion of the WordPress editor form HTML
     * from a previously installed output buffer, then manipulates and outputs
     * the HTML. A tab is added for the reSt editor interface and the output
     * buffer is deleted.
     *
     * @return null
     *
     * @todo Remove the "active" class from other tabs if 'rest' is active.
     */
    public static function addRestEditor()
    {
        /**
         * Get the stored output we buffered.
         */
        $output = ob_get_clean();
        
        /**
         * Determine whether to make the rest editor active.
         */
        if (wp_default_editor() === 'rest') {
            $button = '$0<a id="edButtonREST" class="active hide-if-no-js" onclick="switchEditors.go(\'$1\', \'rest\');">REST</a>';
        } else {
            $button = '$0<a id="edButtonREST" class="hide-if-no-js" onclick="switchEditors.go(\'$1\', \'rest\');">REST</a>';
        }
        
        /**
         * The regex after which the button html will be appended.
         */
        $re = '/switchEditors\.go\(\'(content)\',\ \'tinymce\'\);">\w+<\/a>$/ms';

        /**
         * Wedge the button html into the output.
         */
        $output = preg_replace($re, $button, $output);
        
        /**
         * Output the hacked html.
         */
        echo $output;
    }
    
    /**
     * Filters the WordPress editor widget, adding special reSt handling.
     *
     * WordPress uses a filter to allow plugins to change the HTML of the 
     * editor widget. This is currently the only way to add another editor
     * interface, since we cannot influence the editor's HTML until the
     * "the_editor" filter is run. A reSt-specific toolbar is added to
     * the HTML (regardless of the "active" editor interface) as well as
     * an additional textarea, which is used to track the reSt source used
     * to render a Post/Page's HTML.
     *
     * When this filter is called, it is guaranteed that WordPress has already
     * installed it's own default "the_editor_content" filters. We remove
     * these here, so our own "the_editor_content" filter will be passed the
     * original Post/Page HTML.
     *
     * @param string $editor The HTML for the rendered editor widget.
     *
     * @return string
     *
     * @todo Store and retrieve reSt source for each post.
     */
    public static function filterEditor($editor)
    {   
        global $post;
        trigger_error(print_r($post, true));
        
        if (wp_default_editor() === 'rest') {
        
            /**
             * Strip the class from the editor.
             */
            $editor = str_replace("class='theEditor'", '', $editor);
        
            /**
             * Disable the content transformations added by the other editors.
             *
             * <note>
             * This must be done as late as possible (after "the_editor" filter is
             * called) so the filters won't register themselves after we've
             * removed them.
             * </note>
             */
            remove_filter('the_editor_content', 'wp_htmledit_pre');
    		remove_filter('the_editor_content', 'wp_richedit_pre');
    		
    		/**
    		 * Add the reSt editor content filter.
    		 */
            add_filter('the_editor_content', array(__CLASS__, 'filterEditorContent'));
        }
        
        /**
         * Create a reSt-specific toolbar.
         */
        $prefix = '<div id="resttags">reStructuredText</div>';
        
        /**
         * Get the current reSt source for this Page/Post.
         */
        $rest_src = get_post_meta($post->ID, 'rest_src', true);
        
        /**
         * Create a textarea to hold the reSt source.
         */
        $suffix = sprintf(
            '<div id="restsrc"><textarea cols=40 rows=10>%s</textarea></div>',
            htmlentities($rest_src)
        );
        
        /**
         * Always add the prefix and suffix to the editor we're given.
         */
        $editor = $prefix . $editor . $suffix;
        
        /**
         * Continue processing by other filters.
         */
        return $editor;
    }
    
    /**
     * Filters the WordPress editor content.
     *
     * The returned string should be appropriately encoded for display directly
     * in a "textarea" widget.
     *
     * <note>
     * This filter is only installed if the reSt editor interface is active,
     * so no conditional processing is required.
     * </note>
     *
     * @param string $content The editor content, typically the Post/Page HTML.
     *
     * @return string
     */
    public static function filterEditorContent($content)
    {        
        /**
         * Call the default WordPress HTML editor content filter.
         */
        $content = call_user_func('wp_htmledit_pre', $content);

        return $content;
    }
    
    /**
     * Renders reStructuredText source as HTML.
     *
     * @param array $data A data array containing source, files, and options.
     *
     * @return null
     *
     * @todo Detect and return errors. Convert to JSON response.
     */
    public static function render($data)
    {
        // Set this to the prefix of your docutils installation.
        $prefix = "/Users/xdissent/.virtualenvs/wprst";
        
        // Set this to the path of rst2html.py
        $rst2html = "$prefix/bin/rst2html.py";
        
        /**
         * Find source.
         */
        if (array_key_exists('src', $data)) {
            $source = $data['src'];
        } elseif (array_key_exists('file', $data)) {
            $source = file_get_contents($data['file']);
        } else {
            return;
        }
        
        $rst2html_options = ''
            . '--no-toc-backlinks '
            . '--no-doc-title '
            . '--no-generator '
            . '--no-source-link '
            . '--no-footnote-backlinks '
            . '--initial-header-level=2 ';
            
        $desc = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w')
        );
        
        $exec = $rst2html . ' ' . $rst2html_options;
        
        $proc = proc_open($exec, $desc, $pipes);
        
        if (!is_resource($proc)) {
            throw new Exception('Error opening process.');
        }
        
        $fd = $pipes[0];
        fwrite($fd, $source);
        fflush($fd);
        fclose($fd);
        
        $rest = '';
        while (!feof($pipes[1])) {
            $rest .= fgets($pipes[1]);
        }
        
        fclose($pipes[1]);
        
        proc_close($proc);
        
        $rest = preg_replace('/.*<body>\n+(.*)<\/body>.*/ms', '$1', $rest);
        
        @header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        
        echo $rest;
    }

    /**
     * Processes actions handled by direct script access.
     *
     * @return null
     * @throws Exception
     */    
    public static function main()
    {
        if (!array_key_exists('action', $_GET)) {
            throw new Exception('No action.');
        }
        
        switch ($_GET['action']) {
            case 'render':
                self::render($_POST);
                break;
                
            case 'test':
                self::testForm();
                break;
                
            default:
                throw new Exception('Unknown action.');
        }
    }
    
    /**
     * Outputs an HTML form ready for testing reSt rendering.
     *
     * @return null
     */
    public static function testForm()
    {
        /**
         * Set the header to the appropriate content type and charset.
         */
        @header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        echo '<form action="?action=render" method="post">';
        echo '<textarea name="src"></textarea><input type="submit" />';
        echo '</form>';
    }
}

/**
 * Check to see if the script was directly accessed.
 */
if (!count(debug_backtrace())) {
    /**
     * Load WordPress environment.
     *
     * <caution>This must be done in a global scope.</caution>
     */
    $wp_root = preg_replace('/(.*)wp-content.*/', '$1', $_SERVER['SCRIPT_FILENAME']);
    require_once $wp_root . 'wp-blog-header.php';
    
    /**
     * Initialize the reSt plugin.
     */
    ReStPlugin::init();
    
    /**
     * Process actions.
     */
    ReStPlugin::main();
} else {
    /**
     * Initialize the reSt plugin.
     */
    ReStPlugin::init();
}