# Import data from (py)blosxom into DotClear

Before I started using [DotClear](http://www.dotclear.net/), I was a happy [pyblosxom](http://pyblosxom.sourceforge.net/) user. I moved to DotClear without any real reasons... I didn't want to lose all the posts I made within my previous blog, and therefore I wrote a plugin to import the pyblosxom data. It was successfully tested with data from pyblosxom 1.0, and it should work with the data from all pyblosxom versions, but also with the data from [blosxom](http://www.blosxom.com/).

You only need to know the path of your pyblosxom posts to use this plugin.

This plugin is no longer actively developed for a simple reason: I don't need it anymore. However, all contributions to improve it or to fix a bug are welcome and will be applied.

Features:

  * automatic import of your categories
  * import your posts (including title, publication date and category)
  * import the comments created with the [standard comment plugin](http://pyblosxom.sourceforge.net/blog/registry/input/comments) (including comment author, his/her website, his/her email and publication date)
  * choice of the author who will be used as author for the imported posts
  * option to specify the language used in all posts
  * option to create redirect rules from the old URL to the new URL for an apache web server
  * option to clean up the HTML code (not tested)
  * simple and clean interface
  * available in English and French

Limitations:

  * posts have to be encoded in UTF-8 (if this is not the case, you can use a script to convert the files to UTF-8)
  * the plugin does not update links from a post to another post. If there are such links, you'll have to manually update them.

This plugin is released under the MPL 1.1/GPL 2.0/LGPL 2.1 licenses. 
