<?php
/**************************************************************************************************/
/*
Plugin Name: Bamboo Tweets
Plugin URI:  https://www.bamboomanchester.uk/wordpress/bamboo-tweets
Author:      Bamboo
Author URI:  https://www.bamboomanchester.uk
Version:     1.2.1
Description: Bamboo Tweets provides a widget and shortcode to display the latest tweets from your Twitter account in a clean and simple list.
*/
/**************************************************************************************************/

	function bamboo_tweets_init() {

		$path = plugins_url('', __FILE__);
		if( function_exists( 'bamboo_enqueue_style' ) ) {
			bamboo_enqueue_style( 'bamboo-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' );
			bamboo_enqueue_style( 'bamboo-tweets', $path.'/bamboo-tweets.css' );
		} else {
			wp_enqueue_style( 'bamboo-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' );
			wp_enqueue_style( 'bamboo-tweets', $path.'/bamboo-tweets.css' );
		}

	}
	add_action( 'init', 'bamboo_tweets_init' );

/**************************************************************************************************/

	function bamboo_tweets_widgets_init() {

		register_widget('Bamboo_Tweets');

	}
	add_action( 'widgets_init',	'bamboo_tweets_widgets_init' );

/**************************************************************************************************/

	function bamboo_shortcode_tweets( $atts, $content = null ) {

		isset( $atts['username'] ) ? $username = $atts['username'] : $username = '';
		isset( $atts['count'] ) ? $count = $atts['count'] : $count = '';
		isset( $atts['consumer_key'] ) ? $consumer_key = $atts['consumer_key'] : $consumer_key = '';
		isset( $atts['consumer_secret'] ) ? $consumer_secret = $atts['consumer_secret'] : $consumer_secret = '';
		isset( $atts['token'] ) ? $token = $atts['token'] : $token = '';
		isset( $atts['token_secret'] ) ? $token_secret = $atts['token_secret'] : $token_secret = '';

		$html = bamboo_tweets_output_shortcode( $username, $count, $consumer_key, $consumer_secret, $token, $token_secret );

		return $html;

	}
	add_shortcode( 'bamboo-tweets',	'bamboo_shortcode_tweets' );

/**************************************************************************************************/

	function bamboo_tweets_output( $username='', $count='', $consumer_key='', $consumer_secret='', $token='', $token_secret='') {

		if( false === ( $twitter_data = get_transient( 'bamboo_tweets_widget_cache' ) ) ) {
			$twitter_data = bamboo_tweets_load( $username, $count, $consumer_key, $consumer_secret, $token, $token_secret );
	  		set_transient( 'bamboo_tweets_widget_cache', $twitter_data, 300);
		}

		if( isset( $twitter_data->errors ) ) {

			$html = $twitter_data->errors[0]->message;

		} else {

			foreach ( $twitter_data as $tweet ) {

				$html.= '<i class="fa fa-twitter"></i>';
				$html.= '<div class="tweet">';
				$html.= bamboo_tweets_parse_links( $tweet->text );
				$html.= '</div>';
				$html.= '<div class="tweet-date">';
				$html.= human_time_diff( strtotime( $tweet->created_at ) ) . " ago";
				$html.= '</div>';

			}

		}

		return $html;

	}

	function bamboo_tweets_output_shortcode( $username='', $count='', $consumer_key='', $consumer_secret='', $token='', $token_secret='' ) {

		//if( false === ( $twitter_data = get_transient( 'bamboo_tweets_widget_cache' ) ) ) {
			$twitter_data = bamboo_tweets_load( $username, $count, $consumer_key, $consumer_secret, $token, $token_secret );
	  	//	set_transient( 'bamboo_tweets_widget_cache', $twitter_data, 300);
		//}

		$html = '<div class="bamboo-tweets">';

		if( isset( $twitter_data->errors ) ) {

			$html = $twitter_data->errors[0]->message;

		} else {

			foreach ( $twitter_data as $tweet ) {

				$background = "/wp-content/uploads/placeholder.jpg";
				$has_photo = false;
				if( !is_null( $tweet->entities->media ) ) {
					foreach( $tweet->entities->media as $media ) {
						if( 'photo'==$media->type && !$has_photo) {
							$background = $media->media_url;
							$has_photo = true;
						}
					}
				}
				$retweeted = "";
				$text = $tweet->text;
				if( !is_null( $tweet->retweeted_status ) ) {
					$retweeted = "<i class=\"fa fa-retweet\"></i>" . $tweet->retweeted_status->user->screen_name;
					$text = $tweet->retweeted_status->text;
				}
				$text = bamboo_tweets_parse_links( $text );
				$date = date( "M j", strtotime( $tweet->created_at ) ) . '<sup>' . date("S", strtotime( $tweet->created_at ) . '</sup>' );
				$retweets = 0;
				$favourites = 0;

				$html.= <<<EOT

<div class="bamboo-tweet" style="background:url($background);">
	<div class="bamboo-tweet-retweeted">$retweeted</div>
	<div class="bamboo-tweet-text">$text</div>
	<div class="bamboo-tweet-date">$date</div>
	<div class="bamboo-tweet-retweets"><i class="fa fa-retweet"></i>$retweets</div>
	<div class="bamboo-tweet-favourites"><i class="fa fa-star"></i>$favourites</div>
</div>

EOT;
			}
		}

		$html.= '</div>';

		return $html;

	}

/**************************************************************************************************/

	function bamboo_tweets_load( $username='', $count=1, $consumer_key='', $consumer_secret='', $token='', $token_secret='' ) {

		// Construct the request
		$host = 'api.twitter.com';
		$path = '/1.1/statuses/user_timeline.json'; // api call path
		$method = 'GET';
		$query = array( // query parameters
		    'screen_name' => $username,
		    'count' => $count
		);
		$oauth = array(
		    'oauth_consumer_key' => $consumer_key,
		    'oauth_token' => $token,
		    'oauth_nonce' => (string)mt_rand(), // a stronger nonce is recommended
		    'oauth_timestamp' => time(),
		    'oauth_signature_method' => 'HMAC-SHA1',
		    'oauth_version' => '1.0'
		);
		$oauth = array_map( "rawurlencode", $oauth ); // must be encoded before sorting
		$query = array_map( "rawurlencode", $query );
		$arr = array_merge( $oauth, $query ); // combine the values THEN sort
		asort( $arr ); // secondary sort (value)
		ksort( $arr ); // primary sort (key)

		// http_build_query automatically encodes, but our parameters
		// are already encoded, and must be by this point, so we undo
		// the encoding step
		$querystring = urldecode( http_build_query( $arr, '', '&' ) );

		$url = "https://$host$path";

		// mash everything together for the text to hash
		$base_string = $method . "&" . rawurlencode( $url ) . "&" . rawurlencode( $querystring );

		// same with the key
		$key = rawurlencode( $consumer_secret ) . "&" . rawurlencode( $token_secret );

		// generate the hash
		$signature = rawurlencode( base64_encode( hash_hmac( 'sha1', $base_string, $key, true ) ) );

		// this time we're using a normal GET query, and we're only encoding the query params
		// (without the oauth params)
		$url .= "?" . http_build_query( $query );

		$oauth['oauth_signature'] = $signature; // don't want to abandon all that work!
		ksort( $oauth ); // probably not necessary, but twitter's demo does it

		// also not necessary, but twitter's demo does this too
		if( ! function_exists( "add_quotes" ) ) {
			function add_quotes( $str ) { return '"' . $str . '"'; }
		}
		$oauth = array_map( "add_quotes", $oauth );

		// this is the full value of the Authorization line
		$auth = "OAuth " . urldecode(http_build_query( $oauth, '', ', ' ) );

		// if you're doing post, you need to skip the GET building above
		// and instead supply query parameters to CURLOPT_POSTFIELDS
		$options = array(
			CURLOPT_HTTPHEADER => array("Authorization: $auth"),
			CURLOPT_HEADER => false,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false
		);

		// do it
		$feed = curl_init();
		curl_setopt_array( $feed, $options );
		$json = curl_exec( $feed );
		curl_close( $feed );

		$twitter_data = json_decode( $json );

		return $twitter_data;

	}

/**************************************************************************************************/

	function bamboo_tweets_parse_links( $str ) {

		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		$urls = array();
		$urlsToReplace = array();
		if( preg_match_all( $reg_exUrl, $str, $urls ) ) {
			$numOfMatches = count( $urls[0] );
			$numOfUrlsToReplace = 0;
			for( $i=0; $i<$numOfMatches; $i++ ) {
				$alreadyAdded = false;
				$numOfUrlsToReplace = count( $urlsToReplace );
				for( $j=0; $j<$numOfUrlsToReplace; $j++ ) {
					if( $urlsToReplace[$j] == $urls[0][$i] ) {
						$alreadyAdded = true;
					}
				}
				if( ! $alreadyAdded ) {
					array_push( $urlsToReplace, $urls[0][$i] );
				}
			}
			$numOfUrlsToReplace = count( $urlsToReplace );
			for( $i=0; $i<$numOfUrlsToReplace; $i++ ) {
				$str = str_replace( $urlsToReplace[$i], "<a target=\"_blank\" href=\"".$urlsToReplace[$i]."\">".$urlsToReplace[$i]."</a> ", $str );
			}
			return $str;
		} else {
			return $str;
		}

	}

/**************************************************************************************************/

	class bamboo_Tweets extends WP_Widget {

/**************************************************************************************************/

		public function __construct() {

			parent::__construct(
		 		'Bamboo_tweets',	// Base ID
				'Bamboo Tweets',	// Name
				array( 'description' => __( 'The latest tweets from your Twitter account', 'bamboo' ), )
			);

		}

/**************************************************************************************************/

	 	public function form( $instance ) {

			if ( isset( $instance['title'] ) ) {
				$title = $instance[ 'title' ];
			} else {
				$title = __( 'New title', 'bamboo' );
			}

			if ( isset( $instance['username'] ) ) {
				$username = $instance[ 'username' ];
			} else {
				$username = '';
			}

			if ( isset( $instance['count'] ) ) {
				$count = $instance[ 'count' ];
			} else {
				$count = '5';
			}

			if ( isset( $instance['token'] ) ) {
				$token = $instance[ 'token' ];
			} else {
				$token = '';
			}

			if ( isset( $instance['token_secret'] ) ) {
				$token_secret = $instance[ 'token_secret' ];
			} else {
				$token_secret = '';
			}

			if ( isset( $instance['consumer_key'] ) ) {
				$consumer_key = $instance[ 'consumer_key' ];
			} else {
				$consumer_key = '';
			}

			if ( isset( $instance['consumer_secret'] ) ) {
				$consumer_secret = $instance[ 'consumer_secret' ];
			} else {
				$consumer_secret = '';
			}

			$path = plugins_url( '', __FILE__ );
?>
	<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bamboo' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Twitter Username:', 'bamboo' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo esc_attr( $username ); ?>" />
		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of tweets to show:', 'bamboo' ); ?></label>
		<input class="small-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo esc_attr( $count ); ?>" />
		<br/><br/>
		<hr/>
		<label><strong>Twitter OAuth Settings</strong></label>

		&nbsp;&nbsp;<a href="#" onclick="window.open('<?php echo $path; ?>/bamboo-tweets-help.html', '_blank', 'width=400, height=800, location=no, menubar=no, statusbar=no, toolbar=no'); return false;">Help</a>

		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e( 'Consumer Key:', 'bamboo' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_key '); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" type="text" value="<?php echo esc_attr( $consumer_key ); ?>" />
		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e('Consumer Secret:', 'bamboo'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" type="text" value="<?php echo esc_attr( $consumer_secret ); ?>" />
		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'token' ); ?>"><?php _e( 'Access Token:', 'bamboo' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'token' ); ?>" name="<?php echo $this->get_field_name( 'token' ); ?>" type="text" value="<?php echo esc_attr( $token ); ?>" />
		<br/><br/>
		<label for="<?php echo $this->get_field_id( 'token_secret' ); ?>"><?php _e('Access Token Secret:', 'bamboo'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'token_secret' ); ?>" name="<?php echo $this->get_field_name( 'token_secret' ); ?>" type="text" value="<?php echo esc_attr( $token_secret ); ?>" />
	</p>
<?php
		}

/**************************************************************************************************/

		public function update( $new_instance, $old_instance ) {

			$instance = array();

			$instance['title']           	= strip_tags( $new_instance['title'] );
			$instance['username']        	= strip_tags( $new_instance['username'] );
			$instance['count']           	= strip_tags( $new_instance['count'] );
			$instance['consumer_key']    	= strip_tags( $new_instance['consumer_key'] );
			$instance['consumer_secret']	= strip_tags( $new_instance['consumer_secret'] );
			$instance['token']           	= strip_tags( $new_instance['token'] );
			$instance['token_secret']    	= strip_tags( $new_instance['token_secret'] );

			return $instance;

		}

/**************************************************************************************************/

		public function widget( $args, $instance ) {

			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );

			echo $before_widget;
			if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

			echo bamboo_tweets_output( $instance['username'], $instance['count'], $instance['consumer_key'], $instance['consumer_secret'], $instance['token'], $instance['token_secret'] );

			echo '<hr /><div class="bamboo-tweets-footer">Follow us on Twitter <a target="_blank" href="http://www.twitter.com/' . $instance['username'] . '">@' . $instance['username'] . '</a></div><hr />';

			echo $after_widget;

		}

/**************************************************************************************************/

	}

/**************************************************************************************************/
?>
