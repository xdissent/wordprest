=== WordPreSt ===
Donate link: http://pledgie.com/campaigns/7997
Tags: editor, markup, post
Requires at least: 2.7
Tested up to: 2.9
Stable tag: master

A reStructuredText editor enhancement for WordPress.

== Description ==

WordPreSt adds a reStructuredText editor mode right along side the current "Visual" and "HTML" modes. Once installed, reStructuredText may be input directly into the editor and converted to HTML with the click of a mouse. The reSt source is saved with each post/page for future edits. The rendered HTML output is stored as the actual post content to allow modification after rendering. Plugin settings control the features of the reSt convertor and include automatic table of contents links, post title generation from reSt, header level customization, and others. A "view reSt source" link is also (optionally) available for each post.

This plugin requires no template modifications and every effort has been made to ensure a truly seamless integration into WordPress.


== Installation ==

1. Download the latest stable release from http://github.com/xdissent/wordprest/downloads

2. Unzip the plugin package into the `/wp-content/plugins/` directory. The zip file will automatically extract into a `wordprest` subdirectory.

3. Activate the plugin through the "Plugins" admin menu in WordPress.

4. Configure the WordPreSt settings (in the "Settings" admin menu) and provide an absolute path to the docutils `rst2html.py` script. WordPreSt does its best to locate the script automatically, but often requires manual configuration.


== Frequently Asked Questions ==

= How do I install Docutils? =

If you have shell access to your hosting server, you may be able to simply `easy_install docutils`. If `easy_install` is not available, you will need to download and install docutils according to the instructions on the [Docutils home page](http://docutils.sourceforge.net "Docutils Python Documentation Utilities").


== Changelog ==

= 1.0 =
* Initial release.