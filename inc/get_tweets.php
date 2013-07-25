<?php
/*
 * Requests Twitter Feed and Updates Transient
 */

/*
 * Requests the Twitter Feed via Kebo Server using OAuth data stored.
 */
function kebo_twitter_get_tweets() {

    // If there is no social connection, we cannot get tweets, so return false
    if ( false === ( $twitter_data = get_transient( 'kebo_twitter_connection_' . get_current_blog_id() ) ) )
        return false;
    
    // Grab the Plugin Options.
    $options = kebo_get_twitter_options();
    
    /*
     * Get transient and check if it has expired.
     */
    if ( false === ( $tweets = get_transient( 'kebo_twitter_feed_' . get_current_blog_id() ) ) ) {
        
        // Make POST request to Kebo OAuth App.
        $request = kebo_twitter_external_request();

        // Response is in JSON format, so decode it.
        $response = json_decode( $request['body'] );
        
        $tweets = $response;
        
        // Check for Error or Success Response.
        if ( isset( $response->errors )) {
            
            // If error, request to Twitter failed.
            return false;
            
        } else {
            
            // Add custom expiry time
            $tweets['expiry'] = time() + ( $options['kebo_twitter_cache_timer'] * MINUTE_IN_SECONDS );
            
            // No error, set transient with latest Tweets
            set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
            
        }
        
    }
    
    /*
     * Check if Twwets have soft expired (user setting), if so run refresh after page load.
     */
    elseif ( $tweets['expiry'] < time() ) {
        
        // Add 10 seconds to soft expire, to stop other threads trying to update it at the same time.
        $tweets['expiry'] = ( $tweets['expiry'] + 10 );
            
        // Update soft expire time.
        set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
        
        // Set silent cache to refresh after page load.
        add_action( 'shutdown', 'kebo_twitter_refresh_cache' );
        
    }
    
    return $tweets;
    
}

/*
 * Alias function for 'kebo_twitter_get_tweets'.
 */
if ( !function_exists( 'get_tweets' ) ) :
    
    function get_tweets() {
        
        kebo_twitter_get_tweets();
    
    }
    
endif;

/*
 * Hooks Output Function to 'wp_footer'.
 */
function kebo_twitter_print_js() {
    
    // Add javascript output script to 'wp_footer' hook with low priority so that jQuery loads before.
    add_action( 'wp_footer', 'kebo_twitter_slider_script', 99 );
    
}

/*
 * Outputs Slider Javascript
 * Shows a single tweet at a time, fading between them.
 */
function kebo_twitter_slider_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {

            jQuery('#kebo-tweet-slider .tweet').eq(0).fadeToggle('1000').delay(10500).fadeToggle('1000');
            var tcount = 1;
            var limit = jQuery("#kebo-tweet-slider .tweet").size();
            var theight = jQuery('#kebo-tweet-slider .tweet').eq(0).outerHeight();
            jQuery('#kebo-tweet-slider').css({ minHeight : theight, })
            var initTweets = setInterval(fadeTweets, 11500);

            function fadeTweets() {
                
                if (tcount == limit) {
                    tcount = 0;
                }
                //theight = jQuery('#kebo-tweet-slider .tweet').eq(tcount).outerHeight();
                //jQuery('#kebo-tweet-slider').height(theight);
                jQuery('#kebo-tweet-slider .tweet').eq(tcount).fadeToggle('1000').delay(10500).fadeToggle('1000');

                ++tcount;

            }

        });
    </script>
    <?php
}

/*
 * Make external request to Kebo auth script
 */
function kebo_twitter_external_request() {
    
    if ( false !== ( $twitter_data = get_transient( 'kebo_twitter_connection_' . get_current_blog_id() ) ) ) {
    
    // URL to Kebo OAuth Request App
    $request_url = 'http://auth.kebopowered.com/request/index.php';
    
    // Setup arguments for OAuth request.
    $data = array(
        'service' => 'twitter',
        'account' => $twitter_data['account'], // Screen Name
        'token' => $twitter_data['token'], // OAuth Token
        'secret' => $twitter_data['secret'], // OAuth Secret
        'userid' => $twitter_data['userid'], // User ID
    );
    
    // Setup arguments for POST request.
    $args = array(
        'method' => 'POST',
        'timeout' => 10,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
            'feed' => 'true',
            'data' => json_encode($data),
        ),
        'cookies' => array(),
        'sslverify' => false,
    );
    
    // Make POST request to Kebo OAuth App.
    $request = wp_remote_post( $request_url, $args );
    
    return $request;
    
    }
    
}

/*
 * Silently refreshes the cache (transient) after page has rendered.
 */
function kebo_twitter_refresh_cache() {
    
    /*
     * If cache has already been updated, no need to refresh
     */
    if ( false !== ( $tweets = get_transient( 'kebo_twitter_feed_' . get_current_blog_id() ) ) )
        if ( $tweets['expiry'] > time() )
            return;
    
        // Make POST request to Kebo OAuth App.
        $request = kebo_twitter_external_request();

        // Response is in JSON format, so decode it.
        $response = json_decode( $request['body'] );

        // Check for Error or Success Response.
        if ( isset( $response->errors )) {

        // If error, request to Twitter failed.
        // Do nothing.
            
    } else {
        
        // We have an object full of Tweets.
        $tweets = $response;
        
        // Add custom expiry time
        $tweets['expiry'] = time() + ( $options['kebo_twitter_cache_timer'] * MINUTE_IN_SECONDS );
        
        // No error, set transient with latest Tweets
        set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
        
    }
    
}