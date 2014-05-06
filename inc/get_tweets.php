<?php
/*
 * Requests Twitter Feed and Updates Transient
 */

function kebo_twitter_get_tweets() {

    // If there is no social connection, we cannot get tweets, so return false
    $twitter_data = get_option( 'kebo_twitter_connection' );
    if ( empty ( $twitter_data ) ) {
        return false;
    }

    // Grab the Plugin Options.
    $options = kebo_get_twitter_options();

    /*
     * Get transient and check if it has expired.
     */
    if ( false === ( $tweets = get_transient( 'kebo_twitter_feed_' . get_current_blog_id() ) ) ) {

        // Make POST request to Kebo OAuth App.
        $response = kebo_twitter_external_request();
        
        // If not WP Error response is in body
        if ( ! is_wp_error( $response ) ) {
            
            // Response is in JSON format, so decode it.
            $response = json_decode( $response['body'] );
            
        }
        
        // Check for Error or Success Response.
        if ( isset( $response->errors ) ) {
            
            // If error, add to error log.
            kebo_twitter_add_error( $response );
            
        } else {
            
            // We have Tweets, linkify the text
            $tweets = kebo_twitter_linkify( $response );

            // Add custom expiry time
            $tweets['expiry'] = time() + ( $options['kebo_twitter_cache_timer'] * MINUTE_IN_SECONDS );

            // JSON encode Tweet data
            $tweets = json_encode( $tweets );
            
            // No error, set transient with latest Tweets
            set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
            
            // Decode for use
            $tweets = json_decode( $tweets, false );
            
        }
        
    }
    
    /*
     * If we have serialized data from the Transient we must decode it.
     */
    if ( is_string( $tweets ) && ( null != json_decode( $tweets ) ) ) {
		
        $tweets = json_decode( $tweets, false );
		
    }

    /*
     * Check if Tweets have soft expired (user setting), if so run refresh after page load.
     */
    if ( ! empty( $tweets->expiry ) && $tweets->expiry < time() ) {

        // Add 30 seconds to soft expire, to stop other threads trying to update it at the same time.
        $tweets->expiry = ( time() + 30 );

        // JSON encode Tweet data
        $tweets = json_encode( $tweets );
        
        // Update soft expire time.
        set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
        
        // Decode for use
        $tweets = json_decode( $tweets, false );

        // Set silent cache to refresh after page load.
        add_action( 'shutdown', 'kebo_twitter_refresh_cache' );
        
    }
    
    return $tweets;
    
}

/*
 * Alias function for 'kebo_twitter_get_tweets'.
 */
if ( ! function_exists( 'get_tweets' ) ) {

    function get_tweets() {

        $tweets = kebo_twitter_get_tweets();
        
        return $tweets;
        
    }

}

/*
 * Hooks Output Function to 'wp_footer'.
 */

function kebo_twitter_print_js() {

    // Add javascript output script to 'wp_footer' hook with low priority so that jQuery loads before.
    add_action( 'wp_footer', 'kebo_twitter_slider_script', 99 );
    
}

/*
 * Make external request to Kebo auth script
 */

function kebo_twitter_external_request() {

    if ( false !== ( $twitter_data = get_option( 'kebo_twitter_connection' ) ) ) {

        // URL to Kebo OAuth Request App
        $request_url = 'http://auth.kebopowered.com/request/index.php';

        // Setup arguments for OAuth request.
        $data = array(
            'service' => 'twitterfull',
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
                'data' => json_encode( $data ),
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
    if ( false !== ( $tweets = get_transient( 'kebo_twitter_feed_' . get_current_blog_id() ) ) ) {

        /*
         * Check if we are already updating.
         */
        if ( get_transient( 'kebo_cron_is_running' ) ) {
            die();
        }

        /*
         * Create hash of the current time (nothing else should occupy the same microtime).
         */
        $hash = hash( 'sha1', microtime() );

        /*
         * Set transient to show we are updating and set the hash for this specific thread.
         */
        set_transient( 'kebo_cron_is_running', $hash, 5 );
        
        /*
         * Sleep so that other threads at the same point can set the hash
         */
        usleep( 250000 ); // Sleep for 1/4th of a second
        
        /*
         * Only one thread will have the same hash as is stored in the transient now, all others can die.
         */
        if ( get_transient( 'kebo_cron_is_running' ) && ( get_transient( 'kebo_cron_is_running' ) != $hash ) ) {
            die();
        }
        
        // Make POST request to Kebo OAuth App.
        $response = kebo_twitter_external_request();
        
        // If not WP Error response is in body
        if ( ! is_wp_error( $response ) ) {
            
            // Response is in JSON format, so decode it.
            $response = json_decode( $response['body'] );
            
        }
        
        // Grab the Plugin Options.
        $options = kebo_get_twitter_options();

        // Check for Error or Success Response.
        if ( isset( $response->errors ) ) {
            
            // If error, add to error log.
            kebo_twitter_add_error( $response );
            
        } else {
            
            // We have Tweets, linkify the text
            $tweets = kebo_twitter_linkify( $response );

            // Add custom expiry time
            $tweets['expiry'] = time() + ( $options['kebo_twitter_cache_timer'] * MINUTE_IN_SECONDS );
            
            // JSON encode Tweet data
            $tweets = json_encode( $tweets );

            // No error, set transient with latest Tweets
            set_transient( 'kebo_twitter_feed_' . get_current_blog_id(), $tweets, 24 * HOUR_IN_SECONDS );
            
        }
        
        /*
         * Remove transient once updating is done.
         */
        delete_transient( 'kebo_cron_is_running' );
        
    }
    
}

/*
 * Converts Tweet text urls, account names and hashtags into HTML links.
 */
function kebo_twitter_linkify( $tweets ) {
    
    $options = kebo_get_twitter_options();
    
    foreach ( $tweets as $tweet ) {
        
        /*
         * Extra Link Processing ( rel attribute and target attribute )
         */
        if ( ! empty( $tweet->retweeted_status ) ) {
           
            /*
             * Check mb_ function compatibility and fallback to regex
             */
            if ( function_exists( 'mb_strlen' ) ) {
                
                /*
                 * Convert Entities into HTML Links
                 */
                $tweet->retweeted_status->text = kebo_twitter_linkify_entities( $tweet->retweeted_status->text, $tweet->retweeted_status->entities );
                
            } else {
           
                /*
                 * Turn Hasntags into HTML Links
                 */
                $tweet->retweeted_status->text = preg_replace( '/#([A-Za-z0-9\/\.]*)/', '<a href="http://twitter.com/search?q=$1">#$1</a>', $tweet->retweeted_status->text );

                /*
     -           * Turn Mentions into HTML Links
                 */
                $tweet->retweeted_status->text = preg_replace( '/@([A-Za-z0-9_\/\.]*)/', '<a href="http://www.twitter.com/$1">@$1</a>', $tweet->retweeted_status->text );
            
            }
            
            /*
             * Decode HTML Chars like &#039; to '
             */
            $tweet->retweeted_status->text = htmlspecialchars_decode( $tweet->retweeted_status->text, ENT_QUOTES );

            /*
             * Convert any leftover text links (e.g. when images are uploaded and Twitter adds a URL but no entity)
             */
            $tweet->retweeted_status->text = make_clickable( $tweet->retweeted_status->text );

            /*
             * NoFollow URLs
             */
            $tweet->retweeted_status->text = ( 'nofollow' == $options['kebo_twitter_nofollow_links'] ) ? stripslashes( wp_rel_nofollow( $tweet->retweeted_status->text ) ) : $tweet->retweeted_status->text;

            /*
             * Add target="_blank" to all links
             */
            $tweet->retweeted_status->text = links_add_target( $tweet->retweeted_status->text, '_blank', array( 'a' ) );
            
        } elseif ( ! empty( $tweet->text ) ) {
            
            /*
             * Check mb_ function compatibility and fallback to regex
             */
            if ( function_exists( 'mb_strlen' ) ) {
                
                /*
                 * Convert Entities into HTML Links
                 */
                $tweet->text = kebo_twitter_linkify_entities( $tweet->text, $tweet->entities );
                
            } else {
           
                /*
                 * Turn Hasntags into HTML Links
                 */
                $tweet->text = preg_replace( '/#([A-Za-z0-9\/\.]*)/', '<a href="http://twitter.com/search?q=$1">#$1</a>', $tweet->text );

                /*
     -           * Turn Mentions into HTML Links
                 */
                $tweet->text = preg_replace( '/@([A-Za-z0-9_\/\.]*)/', '<a href="http://www.twitter.com/$1">@$1</a>', $tweet->text );
            
            }
            
            /*
             * Decode HTML Chars like &#039; to '
             */
            $tweet->text = htmlspecialchars_decode( $tweet->text, ENT_QUOTES );

            /*
             * Convert any leftover text links (e.g. when images are uploaded and Twitter adds a URL but no entity)
             */
            $tweet->text = make_clickable( $tweet->text );

            /*
             * NoFollow URLs
             */
            $tweet->text = ( 'nofollow' == $options['kebo_twitter_nofollow_links'] ) ? stripslashes( wp_rel_nofollow( $tweet->text ) ) : $tweet->text;

            /*
             * Add target="_blank" to all links
             */
            $tweet->text = links_add_target( $tweet->text, '_blank', array( 'a' ) );
            
        }
        
    }
    
    return $tweets;
    
}

/*
 * Linkify Tweet Entities ( #hashtags, @mentions and urls.com )
 */
function kebo_twitter_linkify_entities( $text, $entities ) {
    
    if ( empty( $text ) || ! is_object( $entities ) ) {
        return $text;
    }
    
    $markers = array();
    $hashtag_html_length = 45; // Length of HTML added to Hashtags
    $mention_html_length = 33; // Length of HTML added to Mentions
    $url_html_length = 13; // Length of HTML added to URLs
    
    /*
     * Linkify Hashtags
     */
    if ( ! empty( $entities->hashtags ) ) {
            
        // One Hashtag at a time
        foreach ( $entities->hashtags as $hashtag ) {
                
            // Start offset from 0
            $offset = 0;
            // Calculate length of hastag - end minus start
            $length = $hashtag->indices[1] - $hashtag->indices[0];
                
            // If no markers, no need to offset
            if ( ! empty( $markers ) ) {
                    
                foreach ( $markers as $mark ) {
                        
                    // If the start point is past a previous marker, we need to adjust for the characters added.
                    if ( $hashtag->indices[0] > $mark['point'] ) {
                        
                        // Include previous offsets.
                        $offset = ( $offset + ( $mark['length'] ) );
                            
                    }
                        
                }
                    
            }
                
            /*
             * Replace hashtag text with an HTML link
             */
            $before = mb_substr( $text, 0, $hashtag->indices[0] + $offset, 'UTF-8' );
            $after = mb_substr( $text, $hashtag->indices[1] + $offset, mb_strlen( $text ), 'UTF-8' );
            $text = $before . '<a href="http://twitter.com/search?q=%23' . $hashtag->text . '">#' . $hashtag->text . '</a>' . $after;
                
            // Set marker so we can take into account the characters we just added.
            $markers[] = array(
                'point' => $hashtag->indices[0],
                'length' => $hashtag_html_length + $length,
            );
                
                
        }
            
    }
    
    /*
     * Linkify Mentions
     */
    if ( ! empty( $entities->user_mentions ) ) {
            
        // One Hashtag at a time
        foreach ( $entities->user_mentions as $mention ) {
                
            // Start offset from 0
            $offset = 0;
            // Calculate length of mention - end minus start
            $length = $mention->indices[1] - $mention->indices[0];
                
            // If no markers, no need to offset
            if ( ! empty( $markers ) ) {
                    
                foreach ( $markers as $mark ) {
                        
                    // If the start point is past a previous marker, we need to adjust for the characters added.
                    if ( $mention->indices[0] > $mark['point'] ) {
                        
                        // Include previous offsets.
                        $offset = ( $offset + ( $mark['length'] ) );
                            
                    }
                        
                }
                    
            }
                
            /*
             * Replace mention text with an HTML link
             */
            $before = mb_substr( $text, 0, $mention->indices[0] + $offset, 'UTF-8' );
            $after = mb_substr( $text, $mention->indices[1] + $offset, mb_strlen( $text ), 'UTF-8' );
            $text = $before . '<a href="http://twitter.com/' . $mention->screen_name . '">@' . $mention->screen_name . '</a>' . $after;
                
            // Set marker so we can take into account the characters we just added.
            $markers[] = array(
                'point' => $mention->indices[0],
                'length' => $mention_html_length + $length,
            );
                
                
        }
            
    }
    
    /*
     * Linkify URLs
     */
    if ( ! empty( $entities->urls ) ) {
            
        // One Hashtag at a time
        foreach ( $entities->urls as $url ) {
            
            $display_url = kebo_twitter_get_display_url( $url );
            
            $url_html_length = 13 + ( strlen( $url->url ) - strlen( $display_url ) );
            
            // Start offset from 0
            $offset = 0;
            // Calculate length of URL
            $length = strlen( $display_url );
                
            // If no markers, no need to offset
            if ( ! empty( $markers ) ) {
                    
                foreach ( $markers as $mark ) {
                        
                    // If the start point is past a previous marker, we need to adjust for the characters added.
                    if ( $url->indices[0] > $mark['point'] ) {
                        
                        // Include previous offsets.
                        $offset = ( $offset + ( $mark['length'] ) );
                            
                    }
                        
                }
                    
            }
                
            /*
             * Replace URL text with an HTML link
             */
            $before = mb_substr( $text, 0, $url->indices[0] + $offset, 'UTF-8' );
            $after = mb_substr( $text, $url->indices[1] + $offset, mb_strlen( $text ), 'UTF-8' );
            $text = $before . '<a href="' . $url->expanded_url . '">' . $display_url . '</a>' . $after;
                
            // Set marker so we can take into account the characters we just added.
            $markers[] = array(
                'point' => $url->indices[0],
                'length' => $url_html_length + $length,
            );
                
                
        }
            
    }
    
    return $text;
    
}

/*
 * Adds an error from the Twitter API to the error log.
 */
function kebo_twitter_add_error( $response ) {
    
    if ( is_wp_error( $response ) ) {
        
        // Add details of current WP error
        $error[] = array(
            'date' => time(),
            'code' => 1,
            'message' => $response->errors['http_request_failed'][0],
        );
        
    } else {
        
        // Add details of current error
        $error[] = array(
            'date' => time(),
            'code' => $response->errors[0]->code,
            'message' => $response->errors[0]->message,
        );
        
    }
    
    // Get currently stored errors
    $log = get_option( 'kebo_twitter_errors' );
    
    if ( $log[0] ) {
        
        // Add new error to start of array
        $errors = array_merge( $error, $log );
        
    } else {
        
        $errors = $error;
        
    }
    
    // Limit array to the latest 10 errors
    $errors = array_slice( $errors, 0, 10, false );
        
    update_option( 'kebo_twitter_errors', $errors );
        
}

/*
 * Add rel="nofollow" to links if set.
 */
function kebo_twitter_maybe_nofollow() {
    
    $options = kebo_get_twitter_options();
    
    echo ( 'nofollow' == $options['kebo_twitter_nofollow_links'] ) ? ' rel="nofollow"' : '' ;
    
}

/*
 * Gets the display URL for URLs in Tweets
 * Pass a Twitter URL Entity, returns Display URL
 */
function kebo_twitter_get_display_url( $url ) {
    
    $options = kebo_get_twitter_options();
    
    $display_url = $url->display_url;
    
    return $display_url;
    
}