<?php
 /**
  * PHPTweet
  *
  * @author Jonathan Nicol @f6design
  * @version 1.0.0
  * @license The MIT LIcense http://opensource.org/licenses/mit-license.php
  * @link  http://f6design.com/journal/2010/10/07/display-recent-twitter-tweets-using-php/
  * 
  * Notes:
  * To interact with Twitter's API you will need to create an API KEY:
  * https://dev.twitter.com/apps
  * After creating your API Key you will need to copy the following values into the class
  * constructor: "Consumer key", "Consumer secret", "Access token", "Access token secret"
  * --
  * Caching is employed because Twitter rate limits how many times their feeds can be
  * accessed per hour.
  * --
  * Options can be overridden by passing an array of key/value pairs to the class
  * constructor. At a minimum you must set the consumer_key, consumer_secret, access_token,
  * access_token_secret and twitter_screen_name options. You may also need to change the
  * cache_file option to point at a directory/file on your web server. 
  * 
  * Credits:
  * Feed parsing: https://github.com/themattharris/tmhOAuth
  * Hashtag/username parsing: https://github.com/mikenz/twitter-text-php
  */
 class PHPTweet {
    private $tmhOAuth;
    private $options;
    private $tweet_found = false;
    private $tweet_count = 0;
    private $tweet_list;

    /**
     * Initialize a new PHPTweet object
     */
    public function  __construct ($options = array()) {
      require_once "lib/tmhOAuth/tmhOAuth.php";
      require_once "lib/twitter-text-php/lib/Twitter/Autolink.php";

      $this->options = array_merge(
      array(
          'consumer_key'          => '',
          'consumer_secret'       => '',
          'access_token'          => '',
          'access_token_secret'   => '',
          'twitter_screen_name'   => '',
          'cache_file'            => './twitter.txt', // Where on the server to save the cached tweets
          'cachetime'             => 60 * 60, // Seconds to cache feed (1 hour).
          'tweets_to_display'     => 10, // How many tweets to fetch
          'ignore_replies'        => true, // Ignore @replies
          'ignore_retweets'       => true, // Ignore retweets
          'twitter_style_dates'   => false, // Use twitter style dates e.g. 2 hours ago
          'date_format'           => 'g:i A M jS', // The dafult date format e.g. 12:08 PM Jun 12th
          'twitter_wrap_open'     => '<h2>Latest tweets</h2><ul id="twitter">',
          'twitter_wrap_close'    => '</ul>',
          'tweet_wrap_open'       => '<li><span class="status">',
          'meta_wrap_open'        => '</span><span class="meta"> ',
          'meta_wrap_close'       => '</span>',
          'tweet_wrap_close'      => '</li>'
        ),
        $options
      );

      $cache_file_timestamp = ((file_exists($this->options['cache_file']))) ? filemtime($this->options['cache_file']) : 0;

      // Show file from cache if still valid.
      if (time() - $this->options['cachetime'] < $cache_file_timestamp) {
        $this->tweet_found = true;
        // Display tweets from the cache.
        $this->tweet_list = file_get_contents($this->options['cache_file']);  
      } else {
        $this->fetch_tweets();
      }

      // In case the feed did not parse or load correctly, show a link to the Twitter account.
      if (!$this->tweet_found){
        $this->tweet_list = $twitter_wrap_open.$tweet_wrap_open.'Oops, our twitter feed is unavailable right now. '.$meta_wrap_open.'<a href="http://twitter.com/'.$twitter_user_id.'">Follow us on Twitter</a>'.$meta_wrap_close.$tweet_wrap_close.$twitter_wrap_close;
      }
    }

    private function fetch_tweets () {
      // Creates a tmhOAuth object.
      $this->tmhOAuth = new tmhOAuth(array(
        'consumer_key'    => $this->options['consumer_key'],
        'consumer_secret' => $this->options['consumer_secret'],
        'token'           => $this->options['access_token'],
        'secret'          => $this->options['access_token_secret']
      ));

      // Request Twitter timeline.
      $params = array(
        'screen_name' => $this->options['twitter_screen_name']
      );
      if ($this->options['ignore_retweets']) {
        $params['include_rts'] = 'false';
      }
      if ($this->options['ignore_replies']) {
        $params['exclude_replies'] = 'true';
      }
      $response_code = $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/user_timeline.json'), $params);
      
      if ($response_code == 200) {
        $data = json_decode($this->tmhOAuth->response['response'], true);

        // Open the twitter wrapping element.
        $html = $twitter_wrap_open;

        // Iterate over tweets.
        foreach($data as $tweet) {
          $html .=  $this->parse_tweet($tweet);
          // If we have processed enough tweets, stop.
          if ($this->tweet_count >= $this->options['tweets_to_display']){
            break;
          }
        }

        // Close the twitter wrapping element.
        $html .= $twitter_wrap_close;

        // Generate a new cache file.
        $file = fopen($this->options['cache_file'], 'w');

        // Save the contents of output buffer to the file, and flush the buffer. 
        fwrite($file, $html); 
        fclose($file);

        $this->tweet_list = $html;
      }
    }

    private function parse_tweet ($tweet) {
      $this->tweet_found = true;
      $this->tweet_count++;

      $tweet_text_raw = $tweet['text'];
      $tweet_text = Twitter_Autolink::create($tweet_text_raw, false)
        ->setNoFollow(false)->setExternal(false)->setTarget('')
        ->setUsernameClass('')
        ->setHashtagClass('')
        ->setURLClass('')
        ->addLinks();

      $tweet_time = strtotime($tweet['created_at']);

      if ($this->options['twitter_style_dates']){
        // Convert tweet timestamp into Twitter style date ("About 2 hours ago")
        $current_time = time();
        $time_diff = abs($current_time - $tweet_time);
        switch ($time_diff) {
          case ($time_diff < 60):
            $display_time = $time_diff . ' seconds ago';
            break;      
          case ($time_diff >= 60 && $time_diff < 3600):
            $min = floor($time_diff/60);
            $display_time = $min . ' minutes ago';
            break;      
          case ($time_diff >= 3600 && $time_diff < 86400):
            $hour = floor($time_diff/3600);
            $display_time = 'about ' . $hour . ' hour';
            if ($hour > 1){ $display_time .= 's'; }
            $display_time .= ' ago';
            break;          
          default:
            $display_time = date($this->options['date_format'], $tweet_time);
            break;
        }
      } else {
        $display_time = date($this->options['date_format'], $tweet_time);
      }

      $href = 'http://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str'];
      return $this->options['tweet_wrap_open'] . $tweet_text . $this->options['meta_wrap_open'] . '<a href="' . $href . '">' . $display_time . '</a>' . $this->options['meta_wrap_close'] . $this->options['tweet_wrap_close'];
    }

    public function get_tweet_list () {
      return $this->tweet_list;
    }
}