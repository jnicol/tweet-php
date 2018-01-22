# TweetPHP

A PHP class for querying the Twitter API and rendering tweets as an HTML list.

## Features

- Works with Twitter API v1.1
- Tweets are cached to avoid exceeding Twitter’s API request rate limits
- A fallback is provided in case the API request fails
- A configuration parameter allows you to specify how many tweets are displayed
- Dates can optionally be displayed in “Twitter style”, e.g. “12 minutes ago”
- You can customize the HTML that wraps your tweets, tweet status and meta information

## Authentication

To interact with Twitter's API you will need to create a Twitter application at: https://dev.twitter.com/apps

After creating your app you will need to take note of following API values: "Consumer key", "Consumer secret", "Access token", "Access token secret"

## Usage

Your API credentials can be passed as options to the class constructor, along with the any other configuration options:

    $TweetPHP = new TweetPHP(array(
      'consumer_key'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'consumer_secret'     => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token_secret' => 'xxxxxxxxxxxxxxxxxxxxx',
      'api_params'          => array('screen_name' => 'twitteruser')
    ));

Then you can display the results like so:

    echo $TweetPHP->get_tweet_list();

You can also retreive the raw data received from Twitter:

    $tweet_array = $TweetPHP->get_tweet_array();

## Options

Options can be overridden by passing an array of key/value pairs to the class constructor. At a minimum you must set the `consumer_key`, `consumer_secret`, `access_token`, `access_token_secret` options, as shown above.

You should also set an `api_endpoint` and `api_params`, an array of parameters to include with the call to the Twitter API.

Here is a full list of options, and their default values:

    'consumer_key'          => '',
    'consumer_secret'       => '',
    'access_token'          => '',
    'access_token_secret'   => '',
    'api_endpoint'          => 'statuses/user_timeline',
    'api_params'            => array(),
    'enable_cache'          => true,
    'cache_dir'             => dirname(__FILE__) . '/cache/', // Where on the server to save cached tweets
    'cachetime'             => 60 * 60, // Seconds to cache feed (1 hour).
    'tweets_to_retrieve'    => 25, // Specifies the number of tweets to try and fetch, up to a maximum of 200
    'tweets_to_display'     => 10, // Number of tweets to display
    'twitter_style_dates'   => false, // Use twitter style dates e.g. 2 hours ago
    'twitter_date_text'     => array('seconds', 'minutes', 'about', 'hour', 'ago'),
    'date_format'           => '%I:%M %p %b %e%O', // The defult date format e.g. 12:08 PM Jun 12th. See: http://php.net/manual/en/function.strftime.php
    'date_lang'             => null, // Language for date e.g. 'fr_FR'. See: http://php.net/manual/en/function.setlocale.php
    'twitter_template'      => '<h2>Latest tweets</h2><ul id="twitter">{tweets}</ul>',
    'tweet_template'        => '<li><span class="status">{tweet}</span><span class="meta"><a href="{link}">{date}</a></span></li>',
    'error_template'        => '<li><span class="status">Our twitter feed is unavailable right now.</span> <span class="meta"><a href="{link}">Follow us on Twitter</a></span></li>',
    'nofollow_links'        => false, // Add rel="nofollow" attribute to links
    'debug'                 => false

### Deprecated options

The following options have been deprecated. You should use `api_params` to set API parameters instead.

    'twitter_screen_name'   => ''
    'ignore_replies'        => true
    'ignore_retweets'       => true

## API endpoints

Since TweetPHP uses Twitter's [Application-only](https://developer.twitter.com/en/docs/basics/authentication/overview/application-only) API authentication model, it can only access certain GET endpoints.

It has been tested with the statuses/user_timeline endpoint (its default) and the search/tweets endpoint.

## Examples

### Fetch a user's timeline
    
    <?php
    require_once('TweetPHP.php');
    
    $TweetPHP = new TweetPHP(array(
      'consumer_key'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'consumer_secret'     => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token_secret' => 'xxxxxxxxxxxxxxxxxxxxx',
      'api_endpoint'        => 'statuses/user_timeline',
      'api_params'          => array('screen_name' => 'twitteruser')
    ));
    
    echo $TweetPHP->get_tweet_list(); 
    ?>

Note that the `api_endpoint` option could be omitted in this case, since 'statuses/user_timeline' is its default value. 

### Search for a hashtag

    <?php
    require_once('TweetPHP.php');
    
    $TweetPHP = new TweetPHP(array(
      'consumer_key'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'consumer_secret'     => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token'        => 'xxxxxxxxxxxxxxxxxxxxx',
      'access_token_secret' => 'xxxxxxxxxxxxxxxxxxxxx',
      'api_endpoint'        => 'search/tweets',
      'api_params'          => array('q' => '#php', 'result_type'=>'latest')
    ));
    
    echo $TweetPHP->get_tweet_list(); 
    ?>

## Caching

Caching is employed because Twitter rate limits how many times their feeds can be accessed per hour.

When the user timeline is first loaded, the resultant HTML list is saved as a text file on your web server. The default location for this file is: `cache/twitter.txt`

The raw Twitter response is saved as a serialized array in: `cache/twitter-array.txt`

You can change these file paths using the `cache_dir` option. For example, to set a path from your root public directory try:

    $_SERVER['DOCUMENT_ROOT'] . '/path/to/my/cache/dir/'

## Debugging

If you are experiencing problems using the script please set the `debug` option to `true`. This will set PHP's error reporting level to `E_ALL`, and will also display a debugging report.

You can also fetch the debugging report as an array or HTML list, even when the `debug` option is set to `false`:

    echo $TweetPHP->get_debug_list();
    $debug_array = $TweetPHP->get_debug_array();

## Helper methods

### autolink

Pass raw tweet text to `autolink()` and  it will convert all usernames, hashtags and URLs to HTML links. 

    $autolinked_tweet = $TweetPHP->autolink($tweet);

This might be handy if you want to process tweets yourself, using the array returned by `get_tweet_array()`.

## Credits

- Feed parsing uses Matt Harris' [tmhOAuth](https://github.com/themattharris/tmhOAuth)
- Hashtag/username parsing uses Mike Cochrane's [twitter-text-php](https://github.com/mikenz/twitter-text-php)
- Other contributors: [Matt Pugh](https://github.com/mattpugh), [Dario Bauer](https://github.com/dariobauer), [Lee Collings](https://github.com/leecollings), [Dom Abbott](https://github.com/wcdom)
