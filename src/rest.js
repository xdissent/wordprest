jQuery(document).ready(function($){

    var rest_tab = $('#edButtonREST');
    var rest_tags = $('#resttags');
    var rest_src = $('#restsrc textarea');
    var other_tabs = $('#edButtonHTML, #edButtonPreview');
    var html_tab = $('#edButtonHTML');
    var tinymce_tab = $('#edButtonPreview');
    var all_tabs = $('#edButtonREST, #edButtonHTML, #edButtonPreview');
    var quick_tags = $('#quicktags');
    var html_editor = $('#content');
    var editor_container = $('#editorcontainer');
    var update_rest;
    
    /**
     * Store the original editor switching method.
     */
    switchEditors._orig_go = switchEditors.go;

    /**
     * Override the editor switching method.
     */
    switchEditors.go = function(id, mode) {
        /**
         * Get some reasonable defaults.
         */
        id = id || 'content';
        mode = mode || this.mode || 'rest';
        var self = this;
        
        if (!self.mode) {
            all_tabs.filter('.active').each(function() {
                if ($(this).attr('id') == 'edButtonHTML') {
                    self.mode = 'html';
                } else if ($(this).attr('id') == 'edButtonPreview') {
                    self.mode = 'tinymce';
                } else {
                    self.mode = 'rest';
                }
            });
        }

        /**
         * Bail if we're already in the requested mode.
         */
        if (self.mode == mode) {
           return false;
        }

        /**
         * Try to find the editor.
         */
        var ed;
        try {
            ed = tinyMCE.get(id);
        } catch(e) {
            ed = false;
        }
    
        all_tabs.removeClass('active');
        
        /**
         * Handle the different editor types.
         *
         * WordPress makes this *very* difficult. Plus, TinyMCE is involved,
         * so you already knew it was going to be a headache. Ultimately we're
         * going to have to take on the responsibility of switching every 
         * individual editor element on and off, depending on the mode. There 
         * are so many edge cases that you should avoid messing with the
         * following code unless you are sure you know what you're doing.
         */
        if (mode == 'rest') {
        
            rest_tab.addClass('active');
            
            if (ed) {
                ed.hide();
            }
            
            editor_container.hide();
            html_editor.hide();
            quick_tags.hide();

            rest_src.parent().show();
            rest_tags.show();
            
        } else if (mode == 'html') {
        
            html_tab.addClass('active');
            
            rest_src.parent().hide();
            rest_tags.hide();

            if (ed) {
                ed.hide();
            }
            
            html_editor.show();
            editor_container.show();
            quick_tags.show();
            
        } else if (mode == 'tinymce') {
        
            tinymce_tab.addClass('active');
            
            html_editor.hide();
            quick_tags.hide();

            rest_src.parent().hide();
            rest_tags.hide();
            
            edCloseAllTags();
            
            html_editor.val(this.wpautop(html_editor.val()));
            
            editor_container.show();
            
            if (ed) {
                ed.show();
            } else {
                html_editor.show();
                tinyMCE.execCommand('mceAddControl', false, id);
            }
        }
        
        
        /**
         * Save our preferences with the WordPress system.
         */
        setUserSetting('editor', mode);
        self.mode = mode;

        return false;
    }
    
    
    /**
     * Saves reSt source to database for post. Generates new HTML
     * from reSt and updates the HTML editor. Then it autosaves.
     */
    update_rest = function() {
        /**
         * @todo Calculate this path correctly.
         */
        var src = rest_src.val();
        var post_id = $('#post_ID').val();

        autosave_disable_buttons();
        
        $.post(
            ajaxurl, 
            { 
                action: 'rest_update',
                post_id: post_id, 
                src: src 
            }, 
            function(data) {
                html_editor.val(data);
                autosave_enable_buttons();
                delayed_autosave();
            }
        );
    }    


    /**
     * Remove active tag from other tabs, which is hard to do in PHP.
     */
    if (rest_tab.hasClass('active')) {
        other_tabs.removeClass('active');
        html_editor.hide();
        editor_container.hide();
    } else {
        rest_tags.hide();
        rest_src.parent().hide();
    }
    
    rest_src.change(function() {
    
        // Force WordPress to autosave if this post has no id.
        if ($('#post_ID').val() < 0) {
        
            // Fake out autosave to think we've edited.
            html_editor.val(html_editor.val() + ' ');
            
            // Intercept the call to autosave_update_post_ID on success.
            var old_autosave_update_post_ID = autosave_update_post_ID;
            autosave_update_post_ID = function(post_ID) {
                old_autosave_update_post_ID(post_ID);
                update_rest();
                autosave_update_post_ID = old_autosave_update_post_ID;
            }
            
            // Call a delayed autosave.
            delayed_autosave();         
            return;
        }
        
        update_rest();
    });
    
    var rest_tools = $('<div />').attr({ id: 'rest_tools' }).css({ float: 'left' }).appendTo(rest_tags);
    
    var tool_factory = function(name, container, click) {
        $('<input />').attr({
            id: 'rest_' + name,
            type: 'button'
        }).val(name).click(click).appendTo(container);
    }
    
    tool_factory('emphasis', rest_tools, function() {});
    tool_factory('strong', rest_tools, function() {});
    tool_factory('literal', rest_tools, function() {});
    tool_factory('link', rest_tools, function() {});
    tool_factory('image', rest_tools, function() {});
    tool_factory('more', rest_tools, function() {});
        
    var rest_controls = $('<div />').attr({ id: 'rest_tools' }).css({ float: 'right' }).appendTo(rest_tags);

    tool_factory('load', rest_controls, function() {});    
    var rest_auto_update = $('<input type="checkbox" /><label>auto-update</label>').appendTo(rest_controls);
    
    rest_tags.append($('<div />').css({ clear: 'both' }));
});