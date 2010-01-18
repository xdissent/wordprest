=== WordPreSt ===
Donate link: http://pledgie.com/campaigns/7997
Tags: editor, markup, post
Requires at least: 2.7
Tested up to: 2.9
Stable tag: master

A reStructuredText editor enhancement for WordPress.

== Description ==

WordPreSt adds a reStructuredText input mode to the WordPress post/page 
editor. Once installed, reStructuredText may be input directly into the
editor and converted to HTML with the click of a mouse. The reSt source 
is saved with each post/page for future edits. The rendered HTML output 
is stored as the actual post content to allow modification after 
rendering if desired. Plugin settings control the features of the reSt 
convertor and include automatic table of contents links, post title 
generation from reSt, header level customization, and others. A "View 
document source" link is also (optionally) available for each post.

WordPreSt requires no template modifications and every effort has been 
made to ensure a truly seamless integration into WordPress.


== Installation ==

1. Install [Docutils](http://docutils.sourceforge.net/index.html) on your 
   WordPress hosting server.

2. Download the latest stable WordPreSt release from the 
   [GitHub page](http://github.com/xdissent/wordprest/downloads).

3. Unzip the plugin package into the `/wp-content/plugins/` directory. The 
   zip file will automatically extract into a `wordprest` subdirectory.

4. Activate the plugin through the "Plugins" admin menu in WordPress.

5. Configure the WordPreSt settings (in the "Settings" admin menu) and 
   provide an absolute path to the Docutils `rst2html.py` script. 
   WordPreSt does its best to locate the script automatically, but 
   often requires manual configuration.


== Frequently Asked Questions ==

= What is reStructuredText? =

Straight from the horses mouth:

> "reStructuredText is an easy-to-read, what-you-see-is-what-you-get 
plaintext markup syntax and parser system. It is useful for in-line 
program documentation (such as Python docstrings), for quickly 
creating simple web pages, and for standalone documents. reStructuredText 
is designed for extensibility for specific application domains. The 
reStructuredText parser is a component of 
[Docutils](http://docutils.sourceforge.net/index.html). reStructuredText 
is a revision and reinterpretation of the 
[StructuredText](http://dev.zope.org/Members/jim/StructuredTextWiki/FrontPage/) 
and [Setext](http://docutils.sourceforge.net/mirror/setext.html)
lightweight markup systems.

> -- reStructuredText [Home Page](http://docutils.sourceforge.net/rst.html)


= Why Not Just Use Markdown? =

[Markdown](http://daringfireball.net/projects/markdown/) is a great text 
format (and conversion tool) and I'm a huge [Gruber](http://daringfireball.net/) 
fan and all, but it's really quite limited in the grand scheme
of document management. Markdown is not multi-document aware, meaning it
only knows about the one file it is parsing at a time, and lacks the
ability to link between internal references across files. A few of its
features are add-ons requiring plugins, which are not available in every
implementation of Markdown either. It's a generally less-configurable
format as well, where reSt provides extensive optional settings for
various elements.

Perhaps most importantly though, reStructuredText has many output convertors
allowing you to write once, parse a thousand times, into almost any conceivable
format out there. If the output format you desire does not exist, it's 
(fairly) trivial to create your own. Markdown is strictly an (X)HTML 
convertor which severely limits your options if you want to publish your
documents across different media.


= How do I install Docutils? =

If you have shell access to your hosting server, you may be able to simply run
`easy_install docutils`. If `easy_install` is not available, you will need 
to download and install Docutils according to the instructions on the 
[Docutils](http://docutils.sourceforge.net/index.html) home page.


= This is awesome! Can I Give You Some Money? =

Wow, I'm flattered! I'm also very broke, so that works out perfectly! Of course
I'll accept donations, and the guys over at [GitHub](http://github.com) have 
been nice enough to hook me up with a [Pledgie account](http://pledgie.com/campaigns/7997) 
to make it incredibly easy for you to show your gratitude for my hard work. 
Any amount would be greatly appreciated.


== Screenshots ==

1. The WordPreSt editor.


== Changelog ==

= 1.0 =
* Initial release.