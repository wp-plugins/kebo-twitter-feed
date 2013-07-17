=== Kebo Twitter Feed ===
Contributors: PeterBooker, lukeketley
Tags: twitter, tweets, feeds, social, api, oauth, widget
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 0.15
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your site to your Twitter account and display your Twitter Feed on your website effortlessly with a custom widget.

== Description ==

Hassle-free and user-friendly way to add a Twitter Feed to your website. We provide a custom Widget to help you display the Twitter Feed on your website, as well as direct access to the data for developers.

We access your Twitter Feed through you giving permission for our Kebo Twitter App to read your Twitter Feed. Your website can then request an updated list of Tweets at any point through our OAuth request server using the credentials stored on your own site, at no point will we store and log any of your information. You can get more information regarding how we treat your data in our [Privacy Policy](http://kebopowered.com/privacy-policy/ "Kebo Privacy Policy").

Features include:

* Hassle-free Twitter API handling. We take care of the OAuth Requests for you.
* Connect your website to Twitter in seconds with no technical knowledge required.
* Caching used to avoid needless requests every page re-fresh.
* Local cache backup, incase the connection to Twitter has problems.

== Installation ==

1. Install using your WordPress Admin or upload /kebo-twitter-feed/ to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the Kebo Twitter menu at the bottom of your WordPress control panel.
4. Click on the large 'Connect to Twitter' button and follow the prompts.
5. Place the 'Kebo Twitter Feed' Widget into a Widget area through the 'Appearance -> Widgets' menu in WordPress.

== Frequently Asked Questions ==

= How do you get my Twitter Feed? =

We provide a connection from your website to your Twitter feed using our Kebo Twitter App, using OAuth2. Once you grant permission for us to read your Twitter Feed we can provide your website with this information.

= What information do you access? =

Your website will hold your OAuth2 credentials and a list of your latest tweets. Your website will occasionally poll Twitter to update your feed, using our OAuth App, but none of the data is ever stored. We only ever request basic profile information and your latest tweets from twitter.

= Do you ever write to my Twitter account? =

The Kebo Twitter App only requests Read access to your account. This prevents us from ever creating tweets or sending direct messages on your behalf.

= Another question? =

Another answer.

== Screenshots ==

1. This is the settings page for the plugin, you will need to connect your website to Twitter by clicking the 'Conntect to Twitter' button.
2. Once you have setup a connection to Twitter, this is what the settings page should look like. You will now have access to our Widget.
3. This is the Widget you can use to display your Twitter Feed.

== Changelog ==

= 0.15 =
* Note: Initial version.

== Developers Notes ==

You can directly access the object containing all the Tweets like this:

`<?php $tweets = kebo_twitter_get_tweets(); ?>`

This function checks the cache and refreshes the data if needed. Then returns the object containing all the Tweets.

`
<?php $i = 0; ?>

<?php foreach ($tweets as $tweet) : ?>

    <?php echo $tweet->text; ?>

    <?php if ( ++$i == $instance['count'] ) break; ?>

<?php endforeach; ?>
`

== Styling the Widget ==

We use the the inbuilt methods to output the Widget and Title containers so that it should fit seamlessly into your website.

If you want to style the inside of the Widget below is the structure of the widget:

`
<ul class="kebo-tweets vertical">

    <li class="tweet">

        <div class="meta">
            <a class="account"></a>
            <a class="date"></a>
        </div>
        
        <p class="text"></p>

        <div class="links">
            <a class="reply"></a>
            <a class="retweet"></a>
            <a class="favourite"></a>
        </div>

    </li>

</ul>
`

The slider has one significant change which is that the <ul> has an ID of 'kebo-tweet-slider'.

