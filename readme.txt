=== Bamboo Tweets ===
Contributors: bamboosolutions
Donate link: https://www.bamboomanchester.uk
Tags: enquiries, contact form, shortcodes
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bamboo Tweets provides a widget and a shortcode to display the latest tweets from your Twitter account in a clean and simple list.

== Description ==

Everybody wants their tweets incorporating into their website, so Noodle Tweets makes it as simple as possible by providing a WordPress widget that works in any widget area. By using the latest Twitter standards you can rest assured that this plug-in will work both now and in the future.

The widget displays the latest tweets from your Twitter account in a clean and simple list. When adding the widget to a widget area you can set the username of the required Twitter account and the number of tweets to display.

Usage
Twitter now relies on OAuth security to control all third party access. For this widget/shortcode to access your tweets, you will need to register it as an application with Twitter.

Fortunately this isn’t difficult to do, just follow the steps below to get the settings you will need:

Goto https://dev.twitter.com/user/login?destination=home
Sign in with your Twitter sign in details
Click your avatar in the top right of the page to view the menu, then click My Applications
Click ‘Create a new application’ and fill in the application details: (the name has to be unique so you may want to use your website address for this)
Click the ‘Create my access token’ button (There is a slight delay at this point, so you may have to refresh the page before your access token appears)
You should now have your OAuth settings and access token, you will need to copy the following information:
Consumer Key
Consumer Secret
Access token
Access token secret
NOTE: These steps are also listed in a handy ‘help’ link when setting up the widget.

If you wish to use the widget simply enter the details above in to the widget settings along with your Twitter username and you are good to go.

If you prefer to use the shortcode you need to enter your shortcode as follows (filling in details from above, count sets the number of tweets to display):

[bamboo-tweets username="xxxxx" count="x" consumer_key="xxxxx" consumer_secret="xxxxx" token="xxxxx" token_secret="xxxxx"]

== Changelog ==

= 1.2.1 =
* Testing in WP4.7 and rebranded.

= 1.2 =
* Added shortcode functionality
