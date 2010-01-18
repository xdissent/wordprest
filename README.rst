===============================================
WordPreSt reStructuredText Plugin For WordPress
===============================================

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

.. more

.. contents::


Installation
------------

1. Install `Docutils`_ on your WordPress hosting server.

2. Download the latest stable WordPreSt release from the `GitHub page`_.

3. Unzip the plugin package into the ``/wp-content/plugins/`` directory. The 
   zip file will automatically extract into a ``wordprest`` subdirectory.

4. Activate the plugin through the "Plugins" admin menu in WordPress.

5. Configure the WordPreSt settings (in the "Settings" admin menu) and 
   provide an absolute path to the Docutils ``rst2html.py`` script. 
   WordPreSt does its best to locate the script automatically, but 
   often requires manual configuration.

.. _Docutils: http://docutils.sourceforge.net/index.html
.. _GitHub page: http://github.com/xdissent/wordprest/downloads


Frequently Asked Questions
--------------------------

What is reStructuredText?
~~~~~~~~~~~~~~~~~~~~~~~~~

Straight from the horse's mouth:

    "reStructuredText is an easy-to-read, what-you-see-is-what-you-get 
    plaintext markup syntax and parser system. It is useful for in-line 
    program documentation (such as Python docstrings), for quickly 
    creating simple web pages, and for standalone documents. reStructuredText 
    is designed for extensibility for specific application domains. The 
    reStructuredText parser is a component of `Docutils`_. reStructuredText 
    is a revision and reinterpretation of the `StructuredText`_ and `Setext`_
    lightweight markup systems."
    
    -- reStructuredText `Home Page`_
    
.. _Home Page: http://docutils.sourceforge.net/rst.html
.. _StructuredText: http://dev.zope.org/Members/jim/StructuredTextWiki/FrontPage/
.. _Setext: http://docutils.sourceforge.net/mirror/setext.html


Why Not Just Use Markdown?
~~~~~~~~~~~~~~~~~~~~~~~~~~

`Markdown`_ is a great text format (and conversion tool) and I'm a huge
`Gruber`_ fan and all, but it's really quite limited in the grand scheme
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

.. _Markdown: http://daringfireball.net/projects/markdown/
.. _Gruber: http://daringfireball.net/


How do I install Docutils?
~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have shell access to your hosting server, you may be able to simply run
``easy_install docutils``. If ``easy_install`` is not available, you will need 
to download and install Docutils according to the instructions on the 
`Docutils`_ home page.


This is *awesome*! Can I Give You Some Money?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Wow, I'm flattered! I'm also very broke, so that works out perfectly! Of course
I'll accept donations, and the guys over at `GitHub`_ have been nice enough to
hook me up with a `Pledgie account`_ to make it incredibly easy for you to 
show your gratitude for my hard work. Any amount would be greatly appreciated.

.. _GitHub: http://github.com
.. _Pledgie account: http://pledgie.com/campaigns/7997


Screenshots
-----------

1. The WordPreSt editor.

   .. image:: http://xdissent.com/wp-content/uploads/2010/01/screenshot-1-300x228.png
      :alt: WordPreSt screenshot
      :target: http://xdissent.com/wp-content/uploads/2010/01/screenshot-1.png
   

The Future
----------

Here are a few upcoming features for WordPreSt:

* Without HTML auto-update enabled for a post, there is a very slim chance 
  that changes to the reSt source will be lost if you navigate away from 
  the editor. One solution would be to hook into WordPress's own auto-save
  hook, which saves a draft version of your post. There will be some crazy
  post ID handling that will need to be done, but it appears that meta data
  can be saved to drafts as well, so it's feasible that we could save the
  reSt with each auto-saved draft.
  
* A "load reSt from file" button would be super useful for folks like me who
  prefer composing posts in an offline editor. The file's contents would have
  to be sent to the server and then passed back to the reSt editor and 
  (optionally) rendered into the HTML editor.
  
* HTML conversion options should be configurable on a per-post basis, 
  overriding the global options. The post specific options could be stored
  in an additional meta field.

* The reSt toolbar could use a few more tools to make it easier to insert
  some of the more configurable reSt directives, like images. Directives 
  that require options or that have multiple presentation modes (links for
  example) will probably be handled through a pop up / modal box interface.
  
* WordPress has a few other places where posts can be inserted, like 
  QuickPress, that should be optionally reStructuredText enabled.
  
* Pygments can be used for syntax highlighting if available, but the setup
  might be a little too complicated for beginners. Even though the irony
  there is really funny, it would be a good idea to add a couple of settings
  to handle which Pygments stylesheet to use, possibly on a per-post basis
  as well. A "custom" option would allow the user to provide his own Pygments
  CSS. Of course, WordPreSt should automatically determine whether or not
  to include the Pygments styles when displaying a post/page.
  
* Getting the absolute path of ``rst2html.py`` is definitely the main 
  stumbling block for new installs. The settings screen should have a button
  to determine (via AJAX) whether or not the current setting is correct. 
  WordPreSt automatically tries to locate the script upon activation, but
  the auto-locate feature could be useful from within the settings page
  as well. Docutils could probably be detected more accurately through a
  Python script, and if not found, an option to install through the settings
  page would be very helpful (if possible). Eventually it may even be possible
  to package a standalone Docutils package, requiring *only* the Python
  interpretor to work.
  
* Sometimes you just have to look up the syntax for a more rarely used reSt
  directive. A toolbar link to the `Quick reStructuredText`_ reference page
  would come in handy from time to time.
  
.. _Quick reStructuredText: http://docutils.sourceforge.net/docs/user/rst/quickref.html