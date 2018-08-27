# WPS-PhantomJS-Demo
This is demonstration project powered by [PhantomJS](http://phantomjs.org) and intended for:

* Making full-length screenshots of webpages;
* Interacting with webpage (executing JS inside loaded webpage) for example to close popup ads.

Theoretically it useful for testing how page looks on different devices - smartphones, tablets, or desktops with various resolutions (if you try it with different websites and User Agents, you'll see PhantomJS is not the best choice for making perfect screenshots, in short it's engine is outdated).

The goal of this project - is to show how easily work with DOM and perform general tasks by JavaScript inside PhantomJS.

Unfortunately, [PhantomJS has been deprecated since March 2018](https://github.com/ariya/phantomjs/issues/15344). Partially, due to appearance of competing and more actual/modern products such as "Headless Chrome" and "Headless Firefox".

So, this project will move to closest alternative - [Slimerjs](https://slimerjs.org). Unlike PhantomJS, SlimerJS uses Gecko engine (read Firefox), but JavaScript API is the same. So, virtually, all JS written for PhantomJS are ready to work right now with SlimerJS.

Project features at the time it based on PhantomJS (I hope after move to SlimerJS it'll become more reach of features):
* Validating URL by regular expression on front and back end. I don't use **filter_val** at backend because it works only for latin characters;
* Resolution and User Agent can be choosen from drop-down menu. PhantomJS can work strange with undefault User Agent, for example with some "mobile" user agent it can't correctly make screenshot of facebook or even google;
* Try to close popup ads checkbox - when selected, script will look for keywords in ID || className that usually used for popup banners and ads, and apply **style="display:none"** for those DIV's.
*Plans for future version (after move to SlimerJS) - automatically try to close popup ads by looking for highest Z-index, while left for user manually specify ID or className of DIV he wants to hide.*
* Every image is cached for 5 min, after that it considered outdated and will be re-generated on demand.