<?php
/*
Plugin Name: WordPreSt
Version: 1.0
Plugin URI: http://xdissent.com/projects/wordprest/
Description: A reStructuredText editor enhancement for WordPress.
Author: Greg Thornton
Author URI: http://xdissent.com
*/

/**
 * This file is licensed under the GNU Lesser General Public License.
 * See license.txt for details.
 */

/**
 * The reStructuredText Plugin.
 */
class ReStPlugin 
{
    public static $display_name = 'WordPreSt';

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
         * Activate plugin.
         */
        add_action('activate_wordprest/rest.php', array(__CLASS__, 'activatePlugin'));
        
        /**
         * Add the reSt interface to the "Edit Post" page editor.
         */
        add_action('load-post.php', array(__CLASS__, 'hijackEditor'));
        
        /**
         * Add the reSt interface to the "New Post" page editor.
         */
        add_action('load-post-new.php', array(__CLASS__, 'hijackEditor'));
        
        /**
         * Add the reSt interface to the "Edit Post" page editor.
         */
        add_action('load-page.php', array(__CLASS__, 'hijackEditor'));
        
        /**
         * Add the reSt interface to the "New Post" page editor.
         */
        add_action('load-page-new.php', array(__CLASS__, 'hijackEditor'));
        
        /**
         * Add the ajax POST handler for the admin.
         */
        add_action('wp_ajax_rest_update', array(__CLASS__, 'updatePost'));
        
        /**
         * Add some custom mime types to the WordPress media upload whitelist.
         */
        add_filter('upload_mimes', array(__CLASS__, 'whitelistMediaTypes'));
        
        /**
         * Add the options page.
         */
        add_action('admin_menu', array(__CLASS__, 'addSettingsMenu'));
        
        /**
         * Register the plugin options.
         */
        add_action('admin_init', array(__CLASS__, 'registerSettings'));
        
        /**
         * Intercept requests for reSt source.
         */
        add_action('template_redirect', array(__CLASS__, 'viewSource'));
    }
    
    /**
     * Creates default options when plugin is activated.
     *
     * @return null
     */
    public static function activatePlugin()
    {
        add_option('rst2html_options', array(
            'initial-header-level' => 2,
            'toc-backlinks' => 'entry',
            'doc-title' => 'disable',
            'generator' => 'disable',
            'source-link' => 'disable',
            'footnote-backlinks' => 'disable'
        ));
        
        add_option('rst2html_path', self::findConvertor());
    }
    
    /**
     * Auto locates the rst2html.py script.
     *
     * Returns the path of the located convertor script or false.
     *
     * @return mixed
     */
    public static function findConvertor()
    {
        $search = array(
            '/bin',
            '/usr/bin',
            '/usr/local/bin',
            dirname(__FILE__)
        );
        
        $found = false;
        foreach ($search as $path) {
            $file = $path . '/rst2html.py';
            if (file_exists($file) && is_executable($file)) {
                $found = $file;
                break;
            }
        }
        
        return $found;
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
        add_action('post_submitbox_start', array(__CLASS__, 'installBuffer'));
        
        /**
         * Install the action to hack the form and kill the output buffer.
         */
        add_action('edit_form_advanced', array(__CLASS__, 'addRestEditor'));
        add_action('edit_page_form', array(__CLASS__, 'addRestEditor'));
        
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
         * Install the required scripts for the "New Page" page.
         */
        add_action(
            'admin_print_scripts-page-new.php', 
            array(__CLASS__, 'installScripts')
        );
        
        /**
         * Install the required scripts for the "Edit Page" page.
         */
        add_action(
            'admin_print_scripts-page.php', 
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
         * Install the required styles for the "New Page" page.
         */
        add_action(
            'admin_print_styles-page-new.php', 
            array(__CLASS__, 'installStyles')
        );
        
        /**
         * Install the required styles for the "Edit Page" page.
         */
        add_action(
            'admin_print_styles-page.php', 
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
        wp_enqueue_script(
            __CLASS__, 
            '/wp-content/plugins/wordprest/rest.js', 
            array('editor')
        );
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
        wp_enqueue_style(
            __CLASS__,
            '/wp-content/plugins/wordprest/rest.css',
            null, 
            null, 
            'all'
        );
        
        $user = wp_get_current_user();
        $color = get_user_option('admin_color', $user->ID);
        if (!$color) {
            $color = 'fresh';
        }
        
        wp_enqueue_style(
            __CLASS__ . '-' . $color,
            '/wp-content/plugins/wordprest/rest-' . $color . '.css',
            null, 
            null, 
            'all'
        );
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
            $button = '$0<a id="edButtonREST" class="active hide-if-no-js" onclick="switchEditors.go(\'$1\', \'rest\');">reSt</a>';
        } else {
            $button = '$0<a id="edButtonREST" class="hide-if-no-js" onclick="switchEditors.go(\'$1\', \'rest\');">reSt</a>';
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

        if (wp_default_editor() === 'rest') {
        
            /**
             * Strip the class from the editor to prevent auto-TinyMCE
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
            add_filter(
                'the_editor_content',
                array(__CLASS__, 
                'filterEditorContent')
            );
        }
        
        /**
         * Create a reSt-specific toolbar.
         */
        $prefix = '<div id="rest-toolbar"></div>';

        /**
         * Either output a warning or the textarea for reSt source.
         */
        if (!get_option('rst2html_path')) {
        
            /**
             * Display a warning if rst2html.py is not set up.
             */
            $contents = '<p>No HTML convertor found! Please edit your <a href="';
            $contents .= admin_url('options-general.php?page=rest-plugin-settings');
            $contents .= '">' . self::$display_name . ' Settings</a>.</p>';
            
        } else {
            /**
             * Get the current reSt source for this Page/Post.
             */
            $rest_src = '';
            if ($post->ID) {
                $rest_src = get_post_meta($post->ID, 'rest_src', true);
            }
            
            /**
             * Escape printf chars.
             */
            $rest_src = str_replace('%', '%%', $rest_src);
            
            /**
             * Create a textarea to hold the reSt source.
             */
            $contents = sprintf(
                '<textarea cols=40 rows=10>%s</textarea>',
                htmlentities($rest_src)
            );
        }
        
        /**
         * Wrap the textarea or error message in a div.
         */
        $suffix = '<div id="rest-container">' . $contents . '</div>';
        
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
        
        $content = str_replace('%', '%%', $content);

        return $content;
    }
    
    /**
     * Saves the reSt source for a post, and outputs the rendered HTML.
     *
     * @return null
     */
    public static function updatePost()
    {
        $data = $_POST;

        if (array_key_exists('src', $data)) {
            $source = $data['src'];
        } else {
            return;
        }
        
        if (array_key_exists('post_id', $data)) {
            $post_id = $data['post_id'];
        } else {
            return;
        }
        
        /**
         * Set the current reSt source for this Page/Post.
         */
        update_post_meta($post_id, 'rest_src', $source);
        
        echo self::render($data);
        exit;
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
        // Get the rst2html path.
        $rst2html = get_option('rst2html_path', false);
        
        // Bail if we can't find the convertor.
        if (!get_option('rst2html_path')) {
            return;
        }
        
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
        
        /**
         * Strip slashes if appropriate.
         */
        if (get_magic_quotes_gpc()) {
            $source = stripslashes($source);
        }
        
        /**
         * Get the convertor options.
         */
        $rst2html_options = get_option('rst2html_options');
        
        /**
         * Don't embed the stylesheet to save some processing time.
         */
        $rst2html .= ' --link-stylesheet';
        
        /**
         * Special handling for toc-backlinks option.
         */
        if (array_key_exists('toc-backlinks', $rst2html_options)) {
            $toc_links = $rst2html_options['toc-backlinks'];
            unset($rst2html_options['toc-backlinks']);
            
            if ($toc_links == 'entry') {
                $rst2html .= ' --toc-entry-backlinks';
            } elseif ($toc_links == 'top') {
                $rst2html .= ' --toc-top-backlinks';
            } elseif ($toc_links == 'disable') {
                $rst2html .= ' --no-toc-backlinks';
            }
        }
        
        /**
         * Special handling for doc-title option.
         */
        if (array_key_exists('doc-title', $rst2html_options)) {
            if ($rst2html_options['doc-title'] == 'enable') {
                unset($rst2html_options['doc-title']);
            }
        }
        
        /**
         * Special handling for source-link option.
         */
        if (array_key_exists('source-link', $rst2html_options)) {
            if ($rst2html_options['source-link'] == 'enable') {
                $rst2html .= ' --source-url=\?source\=rest';
            }
        }
        
        /**
         * Handle all normal options.
         */
        foreach ($rst2html_options as $opt => $val) {
            if ($val == 'enable') {
                $rst2html .= ' --' . $opt;
            } elseif ($val == 'disable') {
                $rst2html .= ' --no-' . $opt;
            } elseif ($val) {
                $rst2html .= ' --' . $opt . '=' . $val;
            }
        }

        $desc = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w')
        );
        
        $proc = proc_open($rst2html, $desc, $pipes);
        
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
        
        $rest = preg_replace('/(.*)<\/body>.*/ms', '$1', $rest);
        $rest = preg_replace('/.*<body>[\n\s]+(.*)/ms', '$1', $rest);
        $rest = str_replace('<!-- more -->', '<!--more-->', $rest);
        
        return $rest;
    }
    
    
    /**
     * Adds the reSt settings page to the settings admin menu.
     *
     * @return null
     */
    public static function addSettingsMenu()
    {
        add_options_page(
            self::$display_name . ' Settings',
            self::$display_name . ' Settings',
            'administrator',
            'rest-plugin-settings',
            array(__CLASS__, 'settingsPage')
        );
    }

    
    /**
     * Renders the reSt options page.
     *
     * @return null
     */
    public static function settingsPage()
    {
        $available_rst2html_options = array(
            'doc-title' => 'Promote the main section title to document title (will replace current title).',
            'generator' => 'Add "Generated by Docutils" text and link.',
            'source-link' => 'Add "View document source" link.',
            'footnote-backlinks' => 'Generate links from footnotes back to the reference.'
        );
        
        $rst2html_header_levels = array(1, 2, 3, 4);
        
        $rst2html_options = get_option('rst2html_options', array());
    
        ?>
        
        <div class="wrap">
            <h2><?php echo self::$display_name ?> Settings</h2>
            
            <form method="post" action="options.php">
            <?php settings_fields('rest-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">HTML Convertor Path</th>
                    <td>
                        <input type="text" name="rst2html_path" value="<?php echo get_option('rst2html_path'); ?>" />
                        <span class="description">The absolute path to the docutils rst2html.py script.</span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">HTML Convertor Options</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php echo self::$display_name ?> settings</span>
                            </legend>

                            <?php foreach ($available_rst2html_options as $opt => $label) { ?>
                                <label for="<?php echo $opt ?>">
                                    <input type="hidden" name="rst2html_options[<?php echo $opt ?>]" value="disable" />
                                    <input type="checkbox" name="rst2html_options[<?php echo $opt ?>]"
                                        id="<?php echo $opt ?>" value="enable" 
                                        <?php if (array_key_exists($opt, $rst2html_options)) {
                                            if ($rst2html_options[$opt] === 'enable') {
                                                echo 'checked="checked"';
                                            }
                                        } ?> />
                                    <?php echo $label ?>
                                </label><br />
                            <?php } ?>
                            
                            <label for="initial-header-level">
                                Top level section headers should be converted to 
                                <select id="initial-header-level" name="rst2html_options[initial-header-level]">
                                    <?php foreach ($rst2html_header_levels as $level) { ?>
                                        <option value="<?php echo $level ?>"
                                            <?php if (array_key_exists('initial-header-level', $rst2html_options)) {
                                                if ($rst2html_options['initial-header-level'] == $level) {
                                                    echo 'selected="selected"';
                                                }
                                            } ?>>H<?php echo $level ?></option>
                                    <?php } ?>
                                </select>
                                HTML tags.<br />
                            </label><br />
                            
                            <label for="toc-backlinks">
                            
                                <input type="radio" name="rst2html_options[toc-backlinks]" value="entry" 
                                    <?php if ($rst2html_options['toc-backlinks'] == 'entry') {
                                        echo 'checked="checked"';
                                    } ?>
                                />
                                Link from section headers to their table of contents entries.<br />
                                
                                <input type="radio" name="rst2html_options[toc-backlinks]" value="top" 
                                    <?php if ($rst2html_options['toc-backlinks'] == 'top') {
                                        echo 'checked="checked"';
                                    } ?>
                                />
                                Link from section headers to the top of the table of contents.<br />
                                
                                <input type="radio" name="rst2html_options[toc-backlinks]" value="disable" 
                                    <?php if ($rst2html_options['toc-backlinks'] == 'disable') {
                                        echo 'checked="checked"';
                                    } ?>
                                />
                                Disable section header links.<br />
                            </label>
                            
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
            <input type="submit" class="button-primary" value="Save Changes" />
            </p>
            
            </form>
        </div>

        <?php
    }
    
    /**
     * Registers reSt settings with WordPress.
     *
     * @return null
     */
    public static function registerSettings()
    {
        register_setting('rest-settings-group', 'rst2html_path');
        register_setting('rest-settings-group', 'rst2html_options');
    }
    
    /**
     * Outputs a post's reSt source if requested or 404 if it doesn't exist.
     *
     * @return null
     */
    public static function viewSource()
    {
        if (array_key_exists('source', $_GET) && $_GET['source'] == 'rest') {
            global $wp_query;
            
            if ($wp_query->post_count != 1) {
                include(get_404_template());
                exit;
            }
            
            $rest_src = get_post_meta($wp_query->post->ID, 'rest_src', true);
            
            if (!$rest_src) {
                include(get_404_template());
                exit;
            }
            
            header('Content-type: text/plain');
            echo $rest_src;
            exit;
        }
    }
}

/**
 * Perform class initialization.
 */
ReStPlugin::init();