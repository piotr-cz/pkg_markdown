Markdown Package
================

- **Editor Plugin**
  Features: Pagedown editor with Live preview, Save as HTML

- **Content Plugin**
  Features: Use remote files (own cache), Apply to all items or specified ones

- **PHP Markdown library**
  Features: Wrapper for [PHP Markdown library](https://github.com/michelf/php-markdown/)

[![PayPal - The safer, easier way to pay online!](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=7HC53K3SM4JS8&lc=CZ&item_name=extensions%20development&item_number=development_donations&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted)


Requirements
------------

* Joomla 2.5+
* PHP 5.3+


Installation
------------

Install pkg_markdown via the Joomla extension manager.


Authors
-------
* [Markdown](http://daringfireball.net/projects/markdown/) by John Gruber
* [PHP Markdown library](http://michelf.ca/projects/php-markdown/) by Michel Fortin
* [PageDown](code.google.com/p/pagedown/) by John Fraser and Stackexchange
* package all together [piotr-cz](http://www.piotr.cz)


Licence
-------

GNU General Public Licence version 2 or later


Bugs/Requests
-------------

[Report a bug or request a feature here](https://github.com/piotr-cz/pkg_markdown/issues)


TODO
----

Evaluate use of CodeMirror for hightliging -> Mardown syntax

- Would need to install assets into /media/editors/codemiror
- [CodeMirror: Markdown mode](http://codemirror.net/mode/markdown/index.html)
- [CodeMirror: GFM mode](http://codemirror.net/mode/gfm/index.html)


Server-side preview using phpmarkdown

- Would need to decouple pagedown previewing
- [MarItUp! example](http://markitup.jaysalvat.com/examples/serverside/)
