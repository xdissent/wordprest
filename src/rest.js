jQuery.fn.extend({
    insertAtCaret: function(insert) {
        if (document.selection) {
            alert('IE not supported yet.');
            return;
        }
        
        this.focus();
        var orig = this.val();
        var start = this[0].selectionStart;
        this.val(orig.substring(0, start) + insert + orig.substring(start));
        this[0].selectionStart = this[0].selectionEnd = start;
    },
    
    wrapSelection: function(before, after) {
        if (document.selection) {
            alert('IE not supported yet.');
            return;
        }
        
        this.focus();
        
        if (this[0].selectionStart == undefined) {
            return;
        }
        
        if (after == undefined) {
            after = before;
        }
        
        var orig_start = this[0].selectionStart;
        var orig_end = this[0].selectionEnd;
        var orig = this.val();
        var start = orig_start;
        var end = orig_end;
        
        if (start == end) {
        
            // Grow selection to encapsulate entire word.
            while (start > 0 && orig[start - 1].match(/\w/)) {
                start--;
            }
            while (end < orig.length && orig[end].match(/\w/)) {
                end++;
            }
        }
            
        this.val(orig.substring(0, start) + before + orig.substring(start, end) + after + orig.substring(end));
        this[0].selectionStart = orig_start + before.length;
        this[0].selectionEnd = orig_end + before.length;
    }
});

jQuery(document).ready(function($){

    var rest_tab = $('#edButtonREST');
    var rest_toolbar = $('#rest-toolbar');
    var rest_container = $('#rest-container');
    var rest_src = $('#rest-container textarea');
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

            rest_container.show();
            rest_toolbar.show();
            
        } else if (mode == 'html') {
        
            html_tab.addClass('active');
            
            rest_container.hide();
            rest_toolbar.hide();

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

            rest_container.hide();
            rest_toolbar.hide();
            
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

        var src = rest_src.val();
        var post_id = $('#post_ID').val();

        $('#rest-tool-update-HTML').attr({ disabled: 'disabled' });
        autosave_disable_buttons();
        
        $.post(
            ajaxurl, 
            { 
                action: 'rest_update',
                post_id: post_id, 
                src: src 
            }, 
            function(data) {
                var tmp = $('<div>' + data + '</div>');
                var title = $('.title', tmp);
                if (title.length) {
                    title.remove();
                    $('#title').val(title.html());
                    data = tmp.html();
                }
                html_editor.val(data);
                autosave_enable_buttons();
                $('#rest-tool-update-HTML').removeAttr('disabled');
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
        rest_toolbar.show();
        rest_container.show();
    } else {
        rest_toolbar.hide();
        rest_container.hide();
    }
    
    /**
     * Set up the reSt toolbar.
     */
    var rest_tools = $('<div />').attr({ id: 'rest-tools' }).appendTo(rest_toolbar);
    
    var tool_factory = function(name, container, click) {
        var id = name.replace(/\s+/g, '-');
        $('<input />').attr({
            id: 'rest-tool-' + id,
            type: 'button'
        }).val(name).click(click).appendTo(container);
    }
    
    
    tool_factory('emphasis', rest_tools, function() {
        rest_src.wrapSelection('*');
    });
    
    tool_factory('strong', rest_tools, function() {
        rest_src.wrapSelection('**');
    });
    
    tool_factory('literal', rest_tools, function() {
        rest_src.wrapSelection('``');
    });
    
    // tool_factory('link', rest_tools, function() {});
    
    // tool_factory('image', rest_tools, function() {});
    
    tool_factory('more', rest_tools, function() {
        rest_src.insertAtCaret("\n.. more\n");
    });
    

        
    var rest_controls = $('<div />').attr({ id: 'rest-controls' }).appendTo(rest_tools);

    tool_factory('update HTML', rest_controls, function() {
        $(this).attr({ disabled: 'disabled'});
        update_rest();
    });
    
    var rest_auto_update = $('<input type="checkbox" /><label>auto-update</label>').appendTo(rest_controls);

    
    /**
     * Enable reSt parsing.
     */
    if (rest_src.length) {
        rest_src.change(function () {
            if (rest_auto_update.attr('checked')) {
                update_rest();
            }
        });
    } else {
        $('input', rest_toolbar).attr({ disabled: 'disabled' });
    }

});