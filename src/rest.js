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
        var url = '/xdissent/wprst/wp-content/plugins/rest/rest.php?action=render';
        var src = $(this).val();
        $.post(url, { src: src }, function(data) {
            html_editor.val(data);
            /**
             * Update meta.
             */
            $('#the-list input[value=rest_src]').each(function() {
                meta = $(this).attr('id').replace('[key]', '');
                $(this).closest('tr').find('textarea').val(src);
                $(this).next('div.submit').find('input[class^=add]').click();
            });
        }, 'html');
    });
});