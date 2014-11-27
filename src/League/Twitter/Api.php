<?php namespace League\Twitter;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Oauth\OauthPlugin;

class Api
{
    # cache for 1 minute
    const DEFAULT_CACHE_TIMEOUT = 60;

    const API_REALM = 'Twitter API';
    const BASE_URL = 'https://api.twitter.com/1.1';
    const CHARACTER_LIMIT = 140;

    protected $version = "0.1";

    protected $consumer_key;
    protected $consumer_secret;
    protected $access_token_key;
    protected $access_token_secret;

    protected $oauth_token;
    protected $oauth_consumer;

    protected $cache_timeout;
    protected $cache_handler;

    protected $signature_method_plaintext;
    protected $signature_method_hmac_sha1;

    protected $request_headers;
    protected $default_params;
    protected $base_url;

    /**
     * @var Client
     */
    protected $http_client;

    /**
     * @param null|string $consumer_key
     * @param null|string $consumer_secret
     * @param null|string $access_token_key
     * @param null|string $access_token_secret
     * @param null|string $request_headers
     * @param null|string $cache
     * @param null|string $shortener
     * @param null|string $base_url
     * @param bool $use_gzip_compression
     * @param bool $debug_http
     * @throws \Exception if you don't create the API with token and consumer keys
     */
    public function __construct(
        $consumer_key = null,
        $consumer_secret = null,
        $access_token_key = null,
        $access_token_secret = null,
        $request_headers = null,
        $cache = null,
        // $http = static::DEFAULT_HTTP,
        $shortener = null,
        $base_url = null,
        $use_gzip_compression = false,
        $debug_http = false
    ) {
        $this->setCacheHandler($cache);
        $this->setCacheTimeout(self::DEFAULT_CACHE_TIMEOUT);

        $this->use_gzip = $use_gzip_compression;
        $this->debug_http = $debug_http;
        $this->oauth_consumer = null;
        $this->shortlink_size = 19;

        $this->initializeRequestHeaders($request_headers);
        $this->initializeUserAgent();
        $this->initializeDefaultParameters();

        $this->base_url = (is_null($base_url)) ? static::BASE_URL : $base_url;

        $this->setHttpHandler(new Client($this->base_url));

        if (!is_null($consumer_key) && is_null($access_token_key) || is_null($access_token_secret)) {
            throw new \Exception('Twitter requires OAuth Access Token for all API access');
        }
        $this->setCredentials($consumer_key, $consumer_secret, $access_token_key, $access_token_secret);
    }

    /**
     * Set the consumer_key and consumer_secret for this instance.
     *
     * @param string $consumer_key The consumer_key of the twitter account.
     * @param string $consumer_secret The consumer_secret for the twitter account.
     * @param string $access_token_key The OAuth access token key value
     * @param string $access_token_secret The OAuth access token's secret
     */
    public function setCredentials(
        $consumer_key,
        $consumer_secret,
        $access_token_key = null,
        $access_token_secret = null
    ) {
        $this->setConsumerKey($consumer_key);
        $this->setConsumerSecret($consumer_secret);
        $this->setAccessTokenKey($access_token_key);
        $this->setAccessTokenSecret($access_token_secret);
        $this->oauth_consumer = "mi_tamagochi";

        if (!is_null($consumer_key) and !is_null($consumer_secret) and
            !is_null($access_token_key) and !is_null($access_token_secret)
        ) {

            $oauth = new OauthPlugin(
                array(
                    'consumer_key' => $consumer_key,
                    'consumer_secret' => $consumer_secret,
                    'token' => $access_token_key,
                    'token_secret' => $access_token_secret
                )
            );
            $this->http_client->addSubscriber($oauth);
        }
    }


    /**
     * Fetch a URL, optionally caching for a specified time.
     *
     * @param string $url
     * @param string $http_method
     * @param array $parameters
     *
     * @throws \Exception
     * @return string
     */
    protected function fetchUrl(
        $url,
        $http_method = 'GET',
        array $parameters = null
    ) {
        $extra_params = $this->default_params;

        if ($parameters) {
            $parameters = array_merge($extra_params, $parameters);
        }

        $client = $this->http_client;

        if ($http_method === 'GET') {
            $params = $this->encodeParameters($parameters);
            $request = $client->get($url . '?' . $params, $this->request_headers);
        } elseif ($http_method === 'POST') {
			$params = $this->encodePostData($parameters);
            $request = $client->post($url, $this->request_headers, $params);
            if (isset($parameters['media'])) {
                /** @todo implement post files */
                $request->addPostFiles(array('media' => $parameters['media']));
            }

        } else {
            throw new \Exception("The Twitter API only supports GET/POST because it's lazy and weird.");
        }

        return $request->send()->getBody();
    }

    /**
     * Clear the any credentials for this instance.
     */
    public function clearCredentials()
    {
        $this->consumer_key = null;
        $this->consumer_secret = null;
        $this->access_token_key = null;
        $this->access_token_secret = null;
        $this->oauth_consumer = null;
    }

    /**
     * Return twitter search results for a given term.
     *
     * @param string $term Term to search by. Optional if you include geocode.
     * @param array $geocode Geolocation information in the form (latitude, longitude, radius)
     * @param int $since_id Returns results with an ID greater than the specified ID.
     * @param int $max_id Returns only statuses with an ID less than or equal to the specified ID.
     * @param string $until Returns tweets generated before the given date (YYYY-MM-DD).
     * @param int|string $count Number of results to return. Default is 15
     * @param string $lang Language for results as ISO 639-1 code. Default is null (all languages)
     * @param string $locale Language of the search query. Currently only 'ja' is effective.
     * @param string $result_type Type of result which should be returned. "mixed", "recent" and "popular"
     * @param string $include_entities If true, each tweet will include a node called "entities"
     *
     * @return \League\Twitter\Status[]
     */
    public function getSearch(
        $term = null,
        $geocode = null,
        $since_id = null,
        $max_id = null,
        $until = null,
        $count = 15,
        $lang = null,
        $locale = null,
        $result_type = "mixed",
        $include_entities = null
    ) {

        # Build request parameters
        $parameters = array();

        if ($since_id) {
            $parameters['since_id'] = $since_id;
        }
        if ($max_id) {
            $parameters['max_id'] = $max_id;
        }
        if ($until) {
            $parameters['until'] = $until;
        }
        if ($lang) {
            $parameters['lang'] = $lang;
        }
        if ($locale) {
            $parameters['locale'] = $locale;
        }
        if (is_null($term) and is_null($geocode)) {
            return array();
        }
        if (!is_null($term)) {
            $parameters['q'] = $term;
        }
        if (!is_null($geocode)) {
            $parameters['geocode'] = implode(',', array_map('strval', $geocode));
        }
        if ($include_entities) {
            $parameters['include_entities'] = 1;
        }

        $parameters['count'] = (int)$count;

        if (in_array($result_type, array('mixed', 'popular', 'recent'))) {
            $parameters['result_type'] = $result_type;
        }

        // Make and send requests
        $url = "{$this->base_url}/search/tweets.json";
        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        // Build and return a list of statuses
        $result = array_map(
            function ($x) {
                return new Status($x);
            },
            $data['statuses']
        );

        return $result;
    }

    /**
     * Return twitter user search results for a given term.
     *
     * @param string $term Term to search by.
     * @param int|string $page Page of results to return. Default is 1. [Optional]
     * @param int|string $count Number of results to return.  Default is 20. [Optional]
     * @param string $include_entities If true, each tweet will include a node called "entities". [Optional]
     *
     * @throws \InvalidArgumentException
     * @return array[League\Twitter\User]
     */
    public function getUsersSearch($term, $page = 1, $count = 20, $include_entities = null)
    {
        $parameters = array();

        if (!is_null($term)) {
            $parameters['q'] = $term;
        }

        if ($include_entities) {
            $parameters['include_entities'] = 1;
        }

        if ($page) {
            if (!is_numeric($page)) {
                throw new \InvalidArgumentException("'pag' must be an integer");
            }
            $parameters['p'] = $page;
        }

        $parameters['count'] = $count;

        $url = "{$this->base_url}/users/search.json";
        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        $result = array_map(
            function ($x) {
                return new User($x);
            },
            $data
        );

        return $result;
    }

    /**
     * Get the current top trending topics
     *
     * @param array $exclude Appends the exclude parameter as a request parameter.
     *
     * @return array[League\Twitter\Trend]
     */
    public function getTrendsCurrent($exclude = null)
    {
        return $this->GetTrendsWoeid(1, $exclude);
    }

    /**
     * Return the top 10 trending topics for a specific WOEID
     *
     * @param int $woeid The Yahoo! Where On Earth ID for a location.
     * @param array $exclude Appends the exclude parameter as a request parameter.
     *
     * @return array[League\Twitter\Trend]
     */
    public function getTrendsWoeid($woeid, $exclude = null)
    {
        $url = "{$this->base_url}/trends/place.json";
        $parameters = array('id' => $woeid);

        if ($exclude) {
            $parameters['exclude'] = $exclude;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);
        $timestamp = $data[0]['as_of'];

        $trends = array_map(
            function ($x) use ($timestamp) {
                $x['timestamp'] = $timestamp;
                return new Trend($x);
            },
            $data[0]['trends']
        );

        return $trends;
    }

    /**
     * Fetch a collection of the most recent Tweets and Retweets posted by the
     * authenticating user and the users they follow.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $count Specifies the number of statuses to retrieve. May not be
     *  greater than 200. Defaults to 20.
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     *  than) the specified ID. There are limits to the number of
     *  Tweets which can be accessed through the API. If the limit of
     *  Tweets has occurred since the since_id, the since_id will be
     *  forced to the oldest ID available.
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *  equal to the specified ID.
     * @param bool $trim_user When true, each tweet returned in a timeline will include a user
     *  object including only the status authors numerical ID. Omit this
     *  parameter to receive the complete user object.
     * @param bool $exclude_replies This parameter will prevent replies from appearing in the
     *  returned timeline. Using exclude_replies with the count
     *  parameter will mean you will receive up-to count tweets -
     *  this is because the count parameter retrieves that many
     *  tweets before filtering out retweets and replies.
     * @param bool $contributor_details This parameter enhances the contributors element of the
     *  status response to include the screen_name of the contributor.
     *  By default only the user_id of the contributor is included.
     * @param bool $include_entities The entities node will be disincluded when set to false.
     *  This node offers a variety of metadata about the tweet in a
     *  discreet structure, including: user_mentions, urls, and
     *  hashtags
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return array[League\Twitter\Status]
     */
    public function getHomeTimeline(
        $count = null,
        $since_id = null,
        $max_id = null,
        $trim_user = false,
        $exclude_replies = false,
        $contributor_details = false,
        $include_entities = true
    ) {

        $url = "{$this->base_url}/statuses/home_timeline.json";

        $parameters = array();

        if (!is_null($count)) {
            if (!is_numeric($count)) {
                throw new \InvalidArgumentException("'count' must be an integer");
            }

            if ((int)$count > 200) {
                throw new \InvalidArgumentException("'count' may not be greater than 200");
            }

            $parameters['count'] = $count;
        }

        if ($since_id) {
            if (!is_numeric($since_id)) {
                throw new \InvalidArgumentException("'since_id' must be an integer");
            }

            $parameters['since_id'] = $since_id;
        }

        if ($max_id) {
            if (!is_numeric($max_id)) {
                throw new \InvalidArgumentException("'max_id' must be an integer");
            }

            $parameters['max_id'] = $max_id;
        }

        if ($trim_user) {
            $parameters['trim_user'] = 1;
        }

        if ($exclude_replies) {
            $parameters['exclude_replies'] = 1;
        }

        if ($contributor_details) {
            $parameters['contributor_details'] = 1;
        }

        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }
        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        $result = array_map(
            function ($x) {
                return new Status($x);
            },
            $data
        );

        return $result;
    }

    /**
     * Fetch the sequence of public Status messages for a single user.
     *
     * @param null $user_id
     * @param null $screen_name
     * @param int $count Specifies the number of statuses to retrieve. May not be
     *  greater than 200. Defaults to 20. [Optional]
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     *  than) the specified ID. There are limits to the number of
     *  Tweets which can be accessed through the API. If the limit of
     *  Tweets has occurred since the since_id, the since_id will be
     *  forced to the oldest ID available. [Optional]
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *  equal to the specified ID. [Optional]
     * @param null $include_rts
     * @param bool $trim_user When true, each tweet returned in a timeline will include a user
     *  object including only the status authors numerical ID. Omit this
     *  parameter to receive the complete user object. [Optional]
     * @param bool $exclude_replies This parameter will prevent replies from appearing in the
     *  returned timeline. Using exclude_replies with the count
     *  parameter will mean you will receive up-to count tweets -
     *  this is because the count parameter retrieves that many
     *  tweets before filtering out retweets and replies.
     *  [Optional]
     * @throws \InvalidArgumentException
     * @internal param \League\Twitter\user_id $int Specifies the ID of the user for whom to return the
     *  user_timeline. Helpful for disambiguating when a valid user ID
     *  is also a valid screen name. [Optional]
     * @internal param \League\Twitter\screen_name $int Specifies the screen name of the user for whom to return the
     *  user_timeline. Helpful for disambiguating when a valid screen
     *  name is also a user ID. [Optional]
     * @internal param \League\Twitter\include_rts $bool If true, the timeline will contain native retweets (if they
     *  exist) in addition to the standard stream of tweets. [Optional]
     * @return array[League\Twitter\Status]
     */
    public function getUserTimeline(
        $user_id = null,
        $screen_name = null,
        $count = null,
        $since_id = null,
        $max_id = null,
        $include_rts = null,
        $trim_user = null,
        $exclude_replies = null
    ) {

        $parameters = array();

        $url = "{$this->base_url}/statuses/user_timeline.json";

        if ($user_id) {
            $parameters['user_id'] = $user_id;
        } elseif ($screen_name) {
            $parameters['screen_name'] = $screen_name;
        }

        if ($since_id) {
            if (!is_numeric($since_id)) {
                throw new \InvalidArgumentException("'since_id' must be an integer");
            }
            $parameters['since_id'] = (int)$since_id;
        }

        if ($max_id) {
            if (!is_numeric($max_id)) {
                throw new \InvalidArgumentException("'max_id' must be an integer");
            }
            $parameters['max_id'] = (int)$max_id;
        }

        if ($count) {
            if (!is_numeric($count)) {
                throw new \InvalidArgumentException("'count' must be an integer");
            }
            $parameters['count'] = (int)$count;
        }

        if ($include_rts) {
            $parameters['include_rts'] = 1;
        }

        if ($trim_user) {
            $parameters['trim_user'] = 1;
        }

        if ($exclude_replies) {
            $parameters['exclude_replies'] = 1;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        $result = array_map(
            function ($x) {
                return new Status($x);
            },
            $data
        );

        return $result;
    }

    /**
     * Returns a single status message, specified by the id parameter.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $id The numeric ID of the status you are trying to retrieve.
     * @param bool $trim_user When set to True, each tweet returned in a timeline will
     *  include a user object including only the status authors numerical ID.
     *  Omit this parameter to receive the complete user object. [Optional]
     * @param bool $include_my_retweet When set to True, any Tweets returned that have
     * been retweeted by the authenticating user will include an additional
     *  current_user_retweet node, containing the ID of the source status for the retweet. [Optional]
     * @param bool $include_entities If false, the entities node will be disincluded.
     *  This node offers a variety of metadata about the tweet in a discreet structure,
     * including: user_mentions, urls, and hashtags. [Optional]
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \League\Twitter\Status
     */
    public function getStatus(
        $id,
        $trim_user = false,
        $include_my_retweet = true,
        $include_entities = true
    ) {
        $url = "{$this->base_url}/statuses/show.json";

        if (!is_numeric($id)) {
            throw new \InvalidArgumentException("'id' must be an integer");
        }

        $parameters = array('id' => (int)$id);

        if ($trim_user) {
            $parameters['trim_user'] = 1;
        }
        if ($include_my_retweet) {
            $parameters['include_my_retweet'] = 1;
        }
        if (!$include_entities) {
            $parameters['include_entities'] = 'none';
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);
    }

    /**
     * Destroys the status specified by the required ID parameter.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $id The numeric ID of the status you are trying to retrieve.
     * @param bool $trim_user When set to True, each tweet returned in a timeline will
     *  include a user object including only the status authors numerical ID.
     *  Omit this parameter to receive the complete user object. [Optional]
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \League\Twitter\Status
     */
    public function destroyStatus($id, $trim_user = false)
    {

        if (!is_numeric($id)) {
            throw new \InvalidArgumentException("'id' must be an integer");
        }

        $post_data = array('id' => $id);

        $url = "{$this->base_url}/statuses/destroy/{$id}.json";

        if ($trim_user) {
            $post_data['trim_user'] = 1;
        }

        $json = $this->fetchUrl($url, 'POST', $post_data);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);
    }

    protected function calculateStatusLength($status, $linksize = 19)
    {
        $dummy_link = sprintf('https://-%d-chars%s/', $linksize, str_repeat('-', $linksize - 18));

        $parts = array_map(
            function ($part) use ($dummy_link) {

                // If its not a URL, carry on with whatever it is
                if (!(strpos($part, 'http://') or strpos($part, 'https://'))) {
                    return $part;
                } else {
                    // It is a URL, return the dummy link
                    return $dummy_link;
                }

            },
            explode(' ', $status)
        );

        return strlen(implode(' ', $parts));
    }

    /**
     * Post a twitter status message from the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @link https://dev.twitter.com/docs/api/1.1/post/statuses/update
     *
     * @param string $status The message text to be posted. Must be less
     *   than or equal to 140 characters.
     * @param int|null $in_reply_to_status_id The ID of an existing status that the status to
     *   be posted is in reply to.  This implicitly sets the in_reply_to_user_id
     *   attribute of the resulting status to the user ID of the message being replied
     *   to. Invalid/missing status IDs will be ignored. [Optional]
     * @param float $latitude Latitude coordinate of the tweet in degrees.
     *   Will only work in conjunction with longitude argument. Both longitude and
     *   latitude will be ignored by twitter if the user has a false
     *   geo_enabled setting. [Optional]
     * @param float $longitude Longitude coordinate of the tweet in degrees.
     *   Will only work in conjunction with latitude argument. Both longitude and
     *   latitude will be ignored by twitter if the user has a false
     *   geo_enabled setting. [Optional]
     * @param string $place_id A place in the world. These IDs can
     * be retrieved from ET geo/reverse_geocode. [Optional]
     * @param bool $display_coordinates Whether or not to put a pin on the exact
     *   coordinates a tweet as been sent from. [Optional]
     * @param bool $trim_user If true the returned payload will only contain the
     *   user IDs, se the payload will contain the full user data item. [Optional]
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \League\Twitter\Status
     */
    public function postUpdate(
        $status,
        $in_reply_to_status_id = null,
		$media_ids = null,
        $latitude = null,
        $longitude = null,
        $place_id = null,
        $display_coordinates = false,
        $trim_user = false
    ) {

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = "{$this->base_url}/statuses/update.json";

        if ($this->calculateStatusLength($status, $this->shortlink_size) > static::CHARACTER_LIMIT) {
            throw new \InvalidArgumentException(
                "Text must be less than or equal to {static::CHARACTER_LIMIT} characters."
            );
        }

        $data = array('status' => $status);
        if ($in_reply_to_status_id) {
            $data['in_reply_to_status_id'] = $in_reply_to_status_id;
        }
        if ($media_ids) {
            $data['media_ids'] = $media_ids;
        }
        if (!(is_null($latitude) or is_null($longitude))) {
            $data['lat'] = (string)$latitude;
            $data['long'] = (string)$longitude;
        }
        if (!(is_null($place_id))) {
            $data['place_id'] = (string)$place_id;
        }
        if ($display_coordinates) {
            $data['display_coordinates'] = 'true';
        }
        if ($trim_user) {
            $data['trim_user'] = 'true';
        }
        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);
    }

    public function uploadImg(
        $media
    ) {

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = "https://upload.twitter.com/1.1/media/upload.json";

        $data = array('media' => $media);
       
        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
		var_dump($data);
        return $data;
    }	
    /**
     * Post a twitter status message containing media from the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @link https://dev.twitter.com/docs/api/1.1/post/statuses/update_with_media
     *
     * @param string $status The message text to be posted. Must be less
     *   than or equal to 140 characters.
     * @param string $media The location of the media to be sent with the status.
     * @param int $in_reply_to_status_id The ID of an existing status that the status to
     *   be posted is in reply to.  This implicitly sets the in_reply_to_user_id
     *   attribute of the resulting status to the user ID of the message being replied
     *   to. Invalid/missing status IDs will be ignored. [Optional]
     * @param float $latitude Latitude coordinate of the tweet in degrees.
     *   Will only work in conjunction with longitude argument. Both longitude and
     *   latitude will be ignored by twitter if the user has a false
     *   geo_enabled setting. [Optional]
     * @param float $longitude Longitude coordinate of the tweet in degrees.
     *   Will only work in conjunction with latitude argument. Both longitude and
     *   latitude will be ignored by twitter if the user has a false
     *   geo_enabled setting. [Optional]
     * @param string $place_id A place in the world. These IDs can
     * be retrieved from ET geo/reverse_geocode. [Optional]
     * @param bool $display_coordinates Whether or not to put a pin on the exact
     *   coordinates a tweet as been sent from. [Optional]
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \League\Twitter\Status
     */
    public function postUpdateWithMedia(
        $status,
        $media,
        $in_reply_to_status_id = null,
        $latitude = null,
        $longitude = null,
        $place_id = null,
        $display_coordinates = false
    ) {

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = "{$this->base_url}/statuses/update_with_media.json";

        if ($this->calculateStatusLength($status, $this->shortlink_size) > static::CHARACTER_LIMIT) {
            throw new \InvalidArgumentException(
                "Text must be less than or equal to {static::CHARACTER_LIMIT} characters."
            );
        }

        $data = array(
            'status' => $status,
            'media' => $media
        );

        if ($in_reply_to_status_id) {
            $data['in_reply_to_status_id'] = $in_reply_to_status_id;
        }
        if (!(is_null($latitude) or is_null($longitude))) {
            $data['lat'] = $latitude;
            $data['long'] = $longitude;
        }
        if (!(is_null($place_id))) {
            $data['place_id'] = $place_id;
        }
        if ($display_coordinates) {
            $data['display_coordinates'] = 'true';
        }

        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);

    }

    /**
     * Post one or more twitter status messages from the authenticated user.
     *
     * Unlike api.PostUpdate, this method will post multiple status updates
     * if the message is longer than 140 characters.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @link https://dev.twitter.com/docs/api/1.1/post/statuses/update
     *
     * @param string $status The message text to be posted. May be longer than 140 characters.
     * @param string $continuation The character string, if any, to be appended to all but the
     *   last message.  Note that Twitter strips trailing '...' strings from messages.
     *   Consider using the unicode \u2026 character (horizontal ellipsis) instead.
     * @param array $args See League\Twitter::postUpdate() for a list of accepted parameters.
     *
     * @return \League\Twitter\Status
     * @todo implement
     */
    public function postUpdates($status, $continuation = null, array $args = null)
    {

        $results = array();

        if (is_null($continuation)) {
            $continuation = '';
        }

        $line_length = static::CHARACTER_LIMIT - strlen($continuation);
        $lines = wordwrap($status, $line_length);
        /*
        $last_line = array_pop($lines);
        foreach ($lines as $line) {
            $results[] = $this->postUpdate($line . $continuation, $args);
        } */
        $results[] = $this->postUpdate($lines, $args);
        return $results;
    }

    /**
     * Retweet a tweet with the Retweet API.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $original_id The ID of the status to be retweeted.
     * @param bool $trim_user If true the returned payload will only contain the
     *   user IDs, se the payload will contain the full user data item.
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \League\Twitter\Status
     */
    public function postRetweet($original_id, $trim_user = false)
    {
        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        if (!is_numeric($original_id)) {
            throw new \InvalidArgumentException("'original_id' must be an integer");
        }
        if (!($original_id <= 0)) {
            throw new \InvalidArgumentException("'original_id' must be a positive number");
        }

        $url = sprintf('%s/statuses/retweet/%s.json', $this->base_url, $original_id);

        $data = array('id' => $original_id);

        if ($trim_user) {
            $data['trim_user'] = 'true';
        }

        $json = $this->fetchUrl($url, 'GET', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);
    }

    /**
     * Fetch the sequence of retweets made by the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $count The number of status messages to retrieve.
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     * than) the specified ID. Th pear channel-discover pear.phpmd.orgere are limits to
     * the number of Tweets which can be accessed through the API. If the limit of Tweets
     * has occurred since the since_id, the since_id will be forced to the oldest ID available.
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *   equal to the specified ID.
     * @param bool|int $trim_user If true the returned payload will only contain the user IDs,
     *   otherwise the payload will contain the full user data item.
     *
     * @return array[League\Twitter\Status]
     */
    public function getUserRetweets(
        $count = null,
        $since_id = null,
        $max_id = null,
        $trim_user = false
    ) {
        return $this->getUserTimeline(
            $since_id,
            $count,
            $max_id,
            $trim_user,
            $exclude_replies = true,
            $include_rts = true
        );
    }

    /**
     * Get status messages representing the 20 most recent replies.
     *
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     *   than) the specified ID. There are limits to the number of Tweets which can be
     *   accessed through the API. If the limit of Tweets has occurred since the since_id,
     *   the since_id will be forced to the oldest ID available.
     * @param int $count The number of status messages to retrieve.
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *   equal to the specified ID.
     * @param bool|int $trim_user If true the returned payload will only contain the user IDs,
     *   otherwise the payload will contain the full user data item.
     *
     * @return array[League\Twitter\Status]
     */
    public function getReplies($since_id = null, $count = null, $max_id = null, $trim_user = false)
    {
        return $this->getUserTimeline(
            $since_id,
            $count,
            $max_id,
            $trim_user,
            $exclude_replies = false,
            $include_rts = false
        );
    }

    /**
     * Get retweets of a tweet
     *
     * @param int $status_id The ID of the tweet for which retweets should be searched for.
     * @param int $count The number of status messages to retrieve.
     * @param bool|int $trim_user If true the returned payload will only contain the user IDs,
     *   otherwise the payload will contain the full user data item.
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return array[League\Twitter\Status]
     */
    public function getRetweets($status_id, $count = null, $trim_user = false)
    {

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = sprintf('%s/statuses/retweets/%s.json', $this->base_url, $status_id);
        $parameters = array();
        if ($trim_user) {
            $parameters['trim_user'] = 'true';
        }
        if ($count) {
            if (!is_numeric($count)) {
                throw new \InvalidArgumentException('"count" must be an integer');
            }

            $parameters['count'] = (int)$count;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        $result = array_map(
            function ($x) {
                return new Status($x);
            },
            $data
        );

        return $result;
    }

    /**
     * Get recent tweets of the user that have been retweeted by others.
     *
     * @param int $count The number of retweets to retrieve, up to 100. If omitted, 20 is assumed.
     * @param int $since_id Returns results with an ID greater than (newer than) this ID.
     * @param int $max_id Returns results with an ID less than or equal to this ID.
     * @param bool $trim_user When True, the user object for each tweet will only be an ID.
     * @param bool $include_entities When True, the tweet entities will be included.
     * @param bool $include_user_entities When True, the user entities will be included.
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return array[League\Twitter\Status]
     */
    public function getRetweetsOfMe(
        $count = null,
        $since_id = null,
        $max_id = null,
        $trim_user = false,
        $include_entities = true,
        $include_user_entities = true
    ) {

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = sprintf('%s/statuses/retweets_of_me.json', $this->base_url);

        $parameters = array();

        if ($count) {
            if (!is_numeric($count)) {
                throw new \InvalidArgumentException('"count" must be an integer');
            }

            if ($count > 100) {
                throw new \Exception("'count' may not be greater than 100");
            }

            $parameters['count'] = (int)$count;
        }

        if ($count) {
            $parameters['count'] = $count;
        }
        if ($since_id) {
            $parameters['since_id'] = $since_id;
        }
        if ($max_id) {
            $parameters['max_id'] = $max_id;
        }
        if ($trim_user) {
            $parameters['trim_user'] = $trim_user;
        }
        if (!$include_entities) {
            $parameters['include_entities'] = $include_entities;
        }
        if (!$include_user_entities) {
            $parameters['include_user_entities'] = $include_user_entities;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        $result = array_map(
            function ($x) {
                return new Status($x);
            },
            $data
        );

        return $result;
    }

    /**
     * Fetch users who are friends with the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param $args
     * @throws \Exception
     * @internal param int $user_id The twitter id of the user whose friends you are fetching.
     *   If not specified, defaults to the authenticated user.
     * @internal param string $screen_name The twitter name of the user whose friends you are fetching.
     *   If not specified, defaults to the authenticated user.
     * @internal param int $cursor Should be set to -1 for the initial call and then is used to
     *   control what result page Twitter returns.
     * @internal param bool $skip_status If true the statuses will not be returned in the user items.
     * @internal param bool $include_user_entities When true, the user entities will be included.
     *
     * @return array[League\Twitter\User]
     */
    public function getFriends($args)
    {
        extract($args);

        isset($user_id) or $user_id = null;
        isset($screen_name) or $screen_name = null;
        isset($cursor) or $cursor = -1;
        isset($skip_status) or $skip_status = false;
        isset($include_user_entities) or $include_user_entities = false;

        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }

        $url = sprintf('%s/friends/list.json', $this->base_url);

        $result = array();
        $parameters = array();

        if ($user_id) {
            $parameters['user_id'] = $user_id;
        }

        if ($screen_name) {
            $parameters['screen_name'] = $screen_name;
        }

        if ($skip_status) {
            $parameters['skip_status'] = true;
        }
        if ($include_user_entities) {
            $parameters['include_user_entities'] = true;
        }
        while (true) {
            $parameters['cursor'] = $cursor;
            $json = $this->fetchUrl($url, 'GET', $parameters);
            $data = $this->parseAndCheckTwitter($json);

            $result = array_map(
                function ($x) {
                    return new User($x);
                },
                $data['users']
            );

            $result += $result;

            if (array_key_exists('next_cursor', $data)) {
                if ($data['next_cursor'] == 0 or $data['next_cursor'] == $data['previous_cursor']) {
                    break;
                } else {
                    $cursor = $data['next_cursor'];
                }
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Fetch users who are friends with the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id The id of the user to retrieve the id list for.
     * @param string $screen_name The screen_name of the user to retrieve the id list for.
     * @param int $cursor Specifies the Twitter API Cursor location to start at.
     * @param bool $stringify_ids If true then twitter will return the ids as strings instead of integers.
     * @param int $count The number of status messages to retrieve.
     *
     * @throws \Exception
     * @return array[int]
     */
    public function getFriendIDs(
        $user_id = null,
        $screen_name = null,
        $cursor = -1,
        $stringify_ids = false,
        $count = null
    ) {

        $url = sprintf('%s/friends/ids.json', $this->base_url);
        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }
        $parameters = array();
        if (!is_null($user_id)) {
            $parameters['user_id'] = $user_id;
        }
        if (!is_null($screen_name)) {
            $parameters['screen_name'] = $screen_name;
        }
        if ($stringify_ids) {
            $parameters['stringify_ids'] = true;
        }
        if (!is_null($count)) {
            $parameters['count'] = $count;
        }

        $result = array();

        while (true) {
            $parameters['cursor'] = $cursor;
            $json = $this->fetchUrl($url, 'GET', $parameters);
            $data = $this->parseAndCheckTwitter($json);

            foreach ($data['ids'] as $x) {
                $result += $x;
            }

            if (array_key_exists('next_cursor', $data)) {
                if ($data['next_cursor'] == 0 or $data['next_cursor'] == $data['previous_cursor']) {
                    break;
                } else {
                    $cursor = $data['next_cursor'];
                }
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Returns a list of twitter user id's for every person that is following the specified user.
     *
     * @param int $user_id The id of the user to retrieve the id list for
     * @param string $screen_name The screen_name of the user to retrieve the id list for
     * @param int $cursor Specifies the Twitter API Cursor location to start at.
     * @param bool|int $stringify_ids if True then twitter will return the ids as strings instead of int
     * @param int $count The number of status messages to retrieve. [Optional]
     *
     * @throws \Exception
     * @return array[int]
     */
    public function getFollowerIDs(
        $user_id = null,
        $screen_name = null,
        $cursor = -1,
        $stringify_ids = false,
        $count = null
    ) {
        $url = sprintf('%s/followers/ids.json', $this->base_url);

        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }

        $parameters = array();

        if (!is_null($user_id)) {
            $parameters['user_id'] = $user_id;
        }

        if (!is_null($screen_name)) {
            $parameters['screen_name'] = $screen_name;
        }

        if ($stringify_ids) {
            $parameters['stringify_ids'] = true;
        }

        if (!is_null($count)) {
            $parameters['count'] = $count;
        }

        $result = array();
        while (true) {
            $parameters['cursor'] = $cursor;
            $json = $this->fetchUrl($url, 'GET', $parameters);
            $data = $this->parseAndCheckTwitter($json);

            foreach ($data['ids'] as $x) {
                $result += $x;
            }

            if (array_key_exists('next_cursor', $data)) {
                if ($data['next_cursor'] == 0 or $data['next_cursor'] == $data['previous_cursor']) {
                    break;
                } else {
                    $cursor = $data['next_cursor'];
                }
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Fetch an array of users, one for each follower
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id The twitter id of the user whose followers you are fetching.
     * @param string $screen_name The twitter name of the user whose followers you are fetching.
     * @param int $cursor Should be set to -1 for the initial call and then is used to control
     * what result page Twitter returns
     * @param bool $skip_status
     * @param bool $include_user_entities When true the user entities will be included.
     *
     * @throws \Exception
     * @internal param bool $stringify_ids If true then twitter will return the ids as strings instead of integers.
     * @return array[League\Twitter\User]
     */
    public function getFollowers(
        $user_id = null,
        $screen_name = null,
        $cursor = -1,
        $skip_status = false,
        $include_user_entities = false
    ) {
        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }
        $url = "{$this->base_url}/followers/list.json";
        $result = array();
        $parameters = array();

        if (!is_null($user_id)) {
            $parameters['user_id'] = $user_id;
        }

        if (!is_null($screen_name)) {
            $parameters['screen_name'] = $screen_name;
        }

        if ($skip_status !== false) {
            $parameters['skip_status'] = true;
        }

        if ($include_user_entities !== false) {
            $parameters['include_user_entities'] = true;
        }

        while (true) {
            $parameters['cursor'] = $cursor;
            $json = $this->fetchUrl($url, 'GET', $parameters);
            $data = $this->parseAndCheckTwitter($json);

            $result = array_map(
                function ($x) {
                    return new User($x);
                },
                $data['users']
            );

            $result += $result;

            if (isset($data['next_cursor'])) {
                if ($data['next_cursor'] == 0 or $data['next_cursor'] === $data['previous_cursor']) {
                    break;
                } else {
                    $cursor = $data['next_cursor'];
                }
            } else {
                break;
            }
        }
        return $result;
    }

    /**
     * Fetch extended information for the specified users.
     *
     * Users may be specified either as lists of either user_ids,
     * screen_names, or League\Twitter\User objects. The list of users that
     * are queried is the union of all specified parameters.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id The twitter id of the user you are looking up
     * @param string $screen_name The twitter csv string of users you are looking up
     * @param array [League\Twitter\User] $users User objects to look up
     * @param bool $include_entities
     * @throws \Exception
     * @internal param bool $include_user_entities The entities node that may appear within embedded
     *   statuses will be disincluded when set to false
     *
     * @return array[League\Twitter\User]
     */
    public function usersLookup($user_id = null, $screen_name = null, $users = null, $include_entities = true)
    {
        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        if (!$user_id and !$screen_name and !$users) {
            throw new \Exception("Specify at least one of user_id, screen_name, or users.");
        }

        $url = "{$this->base_url}/users/lookup.json";

        $parameters = array();
        $uids = array();

        if ($user_id) {
            $uids[] = $user_id;
        }

        if ($users) {
            $uids = array_merge(
                $uids,
                array_map(
                    function ($user) {
                        return $user->id;
                    },
                    $users
                )
            );
        }

        if ($uids !== array()) {
            $parameters['user_id'] = implode(',', $uids);
        }

        if ($screen_name) {
            $parameters['screen_name'] = implode(',', $screen_name);
        }

        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        try {
            $data = $this->parseAndCheckTwitter($json);
        } catch (Exception $e) {
            if ($e->getCode() == 34) {
                $data = array();
            } else {
                throw new \Exception;
            }
        }

        return array_map(
            function ($user) {
                return new User($user);
            },
            $data
        );
    }

    /**
     * Fetch extended information for the specified users.
     *
     * Users may be specified either as arrays of either user_ids,
     * screen_names, or League\Twitter\User objects. The list of users that
     * are queried is the union of all specified parameters.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id The twitter id of the user to retrieve
     * @param string $screen_name The twitter name of the user whose followers you are fetching.
     * @param bool $include_entities
     * @throws \Exception
     * @internal param bool $include_user_entities If set to false, the 'entities' node will not be included
     *
     * @return \League\Twitter\User
     */
    public function getUser($user_id = null, $screen_name = null, $include_entities = true)
    {
        $url = "{$this->base_url}/users/show.json";
        $parameters = array();

        if (!$this->oauth_consumer) {
            throw new \Exception('The League\Twitter\Api instance must be authenticated.');
        }

        if ($user_id) {
            $parameters['user_id'] = $user_id;
        } elseif ($screen_name) {
            $parameters['screen_name'] = $screen_name;
        } else {
            throw new \Exception("Specify at least one of user_id or screen_name.");
        }

        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);
        return new User($data);
    }

    /**
     * Returns a list of the direct messages sent to the authenticating user
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     *   than) the specified ID. There are limits to the number of Tweets which can be
     *   accessed through the API. If the limit of Tweets has occurred since the since_id,
     *   the since_id will be forced to the oldest ID available.
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *   equal to the specified ID.
     * @param int $count Specifies the number of direct messages to try and retrieve,
     *   up to a maximum of 200. The value of count is best thought of as a limit to the
     *   number of Tweets to return because suspended or deleted content is removed after
     *   the count has been applied.
     * @param bool $include_entities The entities node will not be included when set to false.
     * @param bool $skip_status When set to True statuses will not be included in the returned
     *   user objects.
     *
     * @throws \Exception
     * @return array[League\Twitter\DirectMessage]
     */
    public function getDirectMessages(
        $since_id = null,
        $max_id = null,
        $count = null,
        $include_entities = true,
        $skip_status = false
    ) {
        $url = "{$this->base_url}/direct_messages.json";

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $parameters = array();

        if ($since_id) {
            $parameters['since_id'] = $since_id;
        }

        if ($max_id) {
            $parameters['max_id'] = $max_id;
        }

        if ($count) {
            if (!is_numeric($count)) {
                throw new \Exception("count must be an integer");
            }
            $parameters['count'] = $count;
        }

        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }

        if ($skip_status !== false) {
            $parameters['skip_status'] = 1;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        return array_map(
            function ($message) {
                return new DirectMessage($message);
            },
            $data
        );
    }

    /**
     * Returns a list of the direct messages sent by the authenticating user
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $since_id Returns results with an ID greater than (that is, more recent
     *   than) the specified ID. There are limits to the number of Tweets which can be
     *   accessed through the API. If the limit of Tweets has occurred since the $since_id,
     *   the $since_id will be forced to the oldest ID available.
     * @param int $max_id Returns results with an ID less than (that is, older than) or
     *   equal to the specified ID.
     * @param int $count Specifies the number of direct messages to try and retrieve,
     *   up to a maximum of 200. The value of count is best thought of as a limit to the
     *   number of Tweets to return because suspended or deleted content is removed after
     *   the count has been applied.
     * @param int $page Specifies the page of results to retrieve. Note: there are pagination limits.
     * @param bool $include_entities The entities node will not be included when set to false.
     *
     * @throws \Exception
     * @return array[League\Twitter\DirectMessage]
     */
    public function getSentDirectMessages(
        $since_id = null,
        $max_id = null,
        $count = null,
        $page = null,
        $include_entities = true
    ) {
        $url = "{$this->base_url}/direct_messages/sent.json";

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }
        $parameters = array();

        if ($since_id) {
            $parameters['since_id'] = $since_id;
        }
        if ($page) {
            $parameters['page'] = $page;
        }
        if ($max_id) {
            $parameters['max_id'] = $max_id;
        }
        if ($count) {
            if (!is_numeric($count)) {
                throw new \Exception("count must be an integer");
            }
            $parameters['count'] = (int)$count;
        }

        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        return array_map(
            function ($message) {
                return new DirectMessage($message);
            },
            $data
        );
    }

    /**
     * Returns a list of the direct messages sent by the authenticating user
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param string $text The message text to be posted.  Must be less than 140 characters.
     * @param int $user_id A list of user_ids to retrieve extended information.
     * @param string $screen_name A list of screen_names to retrieve extended information.
     *
     * @throws \Exception
     * @return \League\Twitter\DirectMessage
     */
    public function postDirectMessage($text, $user_id = null, $screen_name = null)
    {
        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $url = "{$this->base_url}/direct_messages/new.json";
        $post = array('text' => $text);

        if ($user_id) {
            $post['user_id'] = $user_id;
        } elseif ($screen_name) {
            $post['screen_name'] = $screen_name;
        } else {
            throw new \Exception("Specify at least one of user_id or screen_name.");
        }

        $json = $this->fetchUrl($url, 'POST', $post);
        $data = $this->parseAndCheckTwitter($json);

        return new DirectMessage($data);
    }

    /**
     * Destroys the direct message specified in the required ID parameter.
     *
     * The League\Twitter\Api instance must be authenticated, and the
     * authenticating user must be the recipient of the specified direct message.
     *
     * @param int $id The id of the direct message to be destroyed.
     * @param bool|string $include_entities The entities node will not be included when set to false.
     *
     * @return \League\Twitter\DirectMessage
     */
    public function destroyDirectMessage($id, $include_entities = true)
    {
        $url = "{$this->base_url}/direct_messages/destroy.json";
        $post = array('id' => $id);

        if (!$include_entities) {
            $post['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, $post);
        $data = $this->parseAndCheckTwitter($json);

        return new DirectMessage($data);
    }

    /**
     * Befriends the user specified by the user_id or screen_name.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id A user_id to follow
     * @param string $screen_name A screen_name to follow
     * @param bool $follow Set to false to disable notifications for the target user
     *
     * @throws \Exception
     * @return \League\Twitter\User
     */
    public function createFriendship($user_id = null, $screen_name = null, $follow = true)
    {
        $url = "{$this->base_url}/friendships/create.json";

        $data = array();

        if ($user_id) {
            $data['user_id'] = $user_id;
        } elseif ($screen_name) {
            $data['screen_name'] = $screen_name;
        } else {
            throw new \Exception("Specify at least one of user_id or screen_name.");
        }

        $data['follow'] = $follow ? 'true' : 'false';

        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);

        return new User($data);
    }

    /**
     * Discontinues friendship with a user_id or screen_name.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id A user_id to follow
     * @param string $screen_name A screen_name to follow
     *
     * @throws \Exception
     * @return \League\Twitter\User
     */
    public function destroyFriendship($user_id = null, $screen_name = null)
    {
        $url = "{$this->base_url}/friendships/destroy.json";
        $data = array();
        if ($user_id) {
            $data['screen_name'] = $user_id;
        } elseif ($screen_name) {
            $data['screen_name'] = $screen_name;
        } else {
            throw new \Exception("Specify at least one of user_id or screen_name.");
        }
        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new User($data);
    }

    /**
     * Favorites the specified status object or id as the authenticating user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param \League\Twitter\Status $status The League\Twitter\Status object to mark as a favorite
     * @param int $id The id of the twitter status to mark as a favorite
     * @param bool $include_entities include_entities The entities node will be omitted when set to false.
     *
     * @throws \Exception
     * @return \League\Twitter\Status
     */
    public function createFavorite($status = null, $id = null, $include_entities = true)
    {
        $url = "{$this->base_url}/favorites/create.json";
        $post = array();

        if ($id) {
            $post['id'] = $id;
        } elseif ($status) {
            $post['id'] = $status->getId();
        } else {
            throw new \Exception("Specify id or status");
        }

        if (!$include_entities) {
            $post['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'POST', $post);
        $data = $this->parseAndCheckTwitter($json);

        return new Status($data);
    }

    /**
     * Un-Favorites the specified status object or id as the authenticating user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param \League\Twitter\Status $status The status to mark as a favorite
     * @param int $id The id of the twitter status to unmark as a favorite
     * @param bool $include_entities The entities node will be omitted when set to false.
     *
     * @throws \Exception
     * @return \League\Twitter\Status
     */
    public function destroyFavorite($status = null, $id = null, $include_entities = true)
    {
        $url = "{$this->base_url}/favorites/destroy.json";
        $data = array();

        if ($id) {
            $data['id'] = $id;
        } elseif ($status) {
            $data['id'] = $status->getId();
        } else {
            throw new \Exception("Specify id or status");
        }

        if (!$include_entities) {
            $data['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new Status($data);
    }

    /**
     * Return a list of Status objects representing favorited tweets.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $user_id A user_id to follow
     * @param string $screen_name A screen_name to follow
     * @param int $count
     * @param int $since_id
     * @param int $max_id
     * @param bool $include_entities The entities node will be omitted when set to false.
     *
     * @throws \Exception
     * @return \League\Twitter\Status
     */
    public function getFavorites(
        $user_id = null,
        $screen_name = null,
        $count = null,
        $since_id = null,
        $max_id = null,
        $include_entities = true
    ) {
        $parameters = array();

        $url = "{$this->base_url}/favorites/list.json";

        if ($user_id) {
            $parameters['user_id'] = $user_id;
        } elseif ($screen_name) {
            $parameters['screen_name'] = $screen_name;
        }

        if ($since_id) {
            if (!is_numeric($since_id)) {
                throw new \Exception("$since_id must be an integer");
            }
            $parameters['since_id'] = (int)$since_id;
        }

        if ($max_id) {
            if (!is_numeric($max_id)) {
                throw new \Exception("$max_id must be an integer");
            }
            $parameters['max_id'] = (int)$max_id;
        }

        if ($count) {
            if (!is_numeric($count)) {
                throw new \Exception("$count must be an integer");
            }
            $parameters['count'] = (int)$count;
        }

        if ($include_entities) {
            $parameters['include_entities'] = true;
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        return array_map(
            function ($status) {
                return new Status($status);
            },
            $data
        );
    }

    /**
     * Return a list of Status objects representing favorited tweets.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param int $count
     * @param int $since_id
     * @param int $max_id
     * @param bool $trim_user
     * @param bool $contributor_details
     * @param bool $include_entities
     *
     * @throws \Exception
     * @internal param int $user_id
     * @internal param string $screen_name
     * @return \League\Twitter\Status
     */
    public function getMentions(
        $count = null,
        $since_id = null,
        $max_id = null,
        $trim_user = false,
        $contributor_details = false,
        $include_entities = true
    ) {
        $url = "{$this->base_url}/statuses/mentions_timeline.json";

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $parameters = array();

        if ($count) {
            if (!is_numeric($count)) {
                throw new \Exception("count must be an integer");
            }
            $parameters['count'] = (int)$count;
        }

        if ($since_id) {
            if (!is_numeric($since_id)) {
                throw new \Exception("since_id must be an integer");
            }
            $parameters['since_id'] = (int)$since_id;
        }

        if ($max_id) {
            if (!is_numeric($max_id)) {
                throw new \Exception("max_id must be an integer");
            }
            $parameters['max_id'] = (int)$max_id;
        }

        if ($trim_user) {
            $parameters['trim_user'] = 1;
        }
        if ($contributor_details) {
            $parameters['contributor_details'] = 'true';
        }
        if (!$include_entities) {
            $parameters['include_entities'] = 'false';
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        return array_map(
            function ($status) {
                return new Status($status);
            },
            $data
        );
    }

    /**
     * Creates a new list with the give name for the authenticated user.
     *
     * The League\Twitter\Api instance must be authenticated.
     *
     * @param string $name
     * @param string $mode Either 'public' or 'private'
     * @param int $description
     *
     * @throws \Exception
     * @return \League\Twitter\ListTwitter
     */
    public function createList($name, $mode = null, $description = null)
    {

        $url = "{$this->base_url}/lists/create.json";

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $post_data = array('name' => $name);

        if (!is_null($mode)) {
            $post_data['mode'] = $mode;
        }
        if (!is_null($description)) {
            $post_data['description'] = $description;
        }

        $json = $this->fetchUrl($url, 'POST', $post_data);
        $data = $this->parseAndCheckTwitter($json);
        return new ListTwitter($data);
    }

    public function destroyList(
        $owner_screen_name = false,
        $owner_id = false,
        $list_id = null,
        $slug = null
    ) {
        $url = "{$this->base_url}/lists/destroy.json";
        $data = array();

        if ($list_id) {
            if (!is_numeric($list_id)) {
                throw new \Exception("list_id must be an integer");
            }
            $data['list_id'] = (int)$list_id;

        } elseif ($slug) {
            $data['slug'] = $slug;

            if ($owner_id) {
                if (!is_numeric($owner_id)) {
                    throw new \Exception("owner_id must be an integer");
                }
                $data['owner_id'] = (int)$owner_id;
            } elseif ($owner_screen_name) {
                $data['owner_screen_name'] = $owner_screen_name;

            } else {
                throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
            }

        } else {
            throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
        }

        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new ListTwitter($data);
    }

    public function createSubscription(
        $owner_screen_name = false,
        $owner_id = false,
        $list_id = null,
        $slug = null
    ) {
        $url = "{$this->base_url}/lists/subscribers/create.json";
        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $data = array();

        if ($list_id) {
            if (!is_numeric($list_id)) {
                throw new \Exception("list_id must be an integer");
            }
            $data['list_id'] = (int)$list_id;

        } elseif ($slug) {
            $data['slug'] = $slug;

            if ($owner_id) {
                if (!is_numeric($owner_id)) {
                    throw new \Exception("owner_id must be an integer");
                }
                $data['owner_id'] = (int)$owner_id;
            } elseif ($owner_screen_name) {
                $data['owner_screen_name'] = $owner_screen_name;

            } else {
                throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
            }

        } else {
            throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
        }

        $json = $this->fetchUrl($url, $data);
        $data = $this->parseAndCheckTwitter($json);
        return new ListTwitter($data);
    }

    /**
     * Destroys the subscription to a list for the authenticated user
     *
     * The League\Twitter\Api instance must be authenticated.
     */
    public function destroySubscription(
        $owner_screen_name = false,
        $owner_id = false,
        $list_id = null,
        $slug = null
    ) {
        $url = "{$this->base_url}/lists/subscribers/destroy.json";

        if (!$this->oauth_consumer) {
            throw new \Exception("The League\Twitter\Api instance must be authenticated.");
        }

        $data = array();

        if ($list_id) {
            if (!is_numeric($list_id)) {
                throw new \Exception("list_id must be an integer");
            }
            $data['list_id'] = (int)$list_id;

        } elseif ($slug) {
            $data['slug'] = $slug;

            if ($owner_id) {
                if (!is_numeric($owner_id)) {
                    throw new \Exception("owner_id must be an integer");
                }
                $data['owner_id'] = (int)$owner_id;
            } elseif ($owner_screen_name) {
                $data['owner_screen_name'] = $owner_screen_name;

            } else {
                throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
            }

        } else {
            throw new \Exception("Identify list by list_id or owner_screen_name/owner_id and slug");
        }

        $json = $this->fetchUrl($url, 'POST', $data);
        $data = $this->parseAndCheckTwitter($json);
        return new ListTwitter($data);
    }

    /**
     * Obtain a collection of the lists the specified user is subscribed to, 20
     * lists per page by default. Does not include the user's own lists.
     *
     * The League\Twitter\Api instance must be authenticated.
     */
    public function getSubscriptions($user_id = null, $screen_name = null, $count = 20, $cursor = -1)
    {
        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }

        $url = "{$this->base_url}/lists/subscriptions.json";


        if (!is_numeric($cursor)) {
            throw new \Exception("cursor must be an integer");
        }
        if (!is_numeric($count)) {
            throw new \Exception("count must be an integer");
        }

        $parameters = array(
            'cursor' => $cursor,
            'count' => $count,
        );

        if (!is_null($user_id)) {
            if (!is_numeric($count)) {
                throw new \Exception('user_id must be an integer');
            }
            $parameters['user_id'] = $user_id;

        } elseif (!is_null($screen_name)) {
            $parameters['screen_name'] = $screen_name;
        } else {
            throw new \Exception('Specify user_id or screen_name');
        }

        $json = $this->fetchUrl($url, 'GET', $parameters);
        $data = $this->parseAndCheckTwitter($json);

        return array_map(
            function ($list) {
                return new ListTwitter($list);
            },
            $data['lists']
        );
    }

    public function getLists($user_id = null, $screen_name = null, $count = null, $cursor = -1)
    {

        if (!$this->oauth_consumer) {
            throw new \Exception("League\Twitter\Api instance must be authenticated");
        }

        $url = "{$this->base_url}/lists/ownerships.json";

        $result = array();
        $parameters = array();

        if (!is_null($user_id)) {
            if (!is_numeric($user_id)) {
                throw new \Exception('user_id must be an integer');
            }
            $parameters['user_id'] = (int)$user_id;
        } elseif (!is_null($screen_name)) {
            $parameters['screen_name'] = $screen_name;
        } else {
            throw new \Exception('Specify user_id or screen_name');
        }

        if (!is_null($count)) {
            $parameters['count'] = $count;
        }

        while (true) {
            $parameters['cursor'] = $cursor;
            $json = $this->fetchUrl($url, 'GET', $parameters);
            $data = $this->parseAndCheckTwitter($json);

            $result = array_merge(
                $result,
                array_map(
                    function ($list) {
                        return new ListTwitter($list);
                    },
                    $data['lists']
                )
            );

            if (isset($data['next_cursor'])) {
                if ($data['next_cursor'] == 0 or $data['next_cursor'] == $data['previous_cursor']) {
                    break;
                } else {
                    $cursor = $data['next_cursor'];
                }
            } else {
                break;
            }
        }

        return $result;
    }

    public function verifyCredentials()
    {
        if (!$this->oauth_consumer) {
            throw new \Exception("Api instance must first be given user credentials.");
        }
        $url = "{$this->base_url}/account/verify_credentials.json";

        try {
            $json = $this->fetchUrl($url, 'GET', array(), array('no_cache' => true));
        } catch (BadResponseException $e) {

            if ($e->getCode() == 401) {
                return null;
            } else {
                throw $e;
            }
        }

        $data = $this->parseAndCheckTwitter($json);
        return new User($data);
    }

    /**
     * Override the default cache.  Set to null to prevent caching.
     *
     * @param string $cache
     */
    public function setCacheHandler($cache)
    {
        $this->cache_handler = $cache;
    }

    /**
     * Override the default cache timeout.
     *
     * @param int $cache_timeout
     */
    public function setCacheTimeout($cache_timeout)
    {
        $this->cache_timeout = $cache_timeout;
    }

    /**
     * Override the default http.
     *
     * @param mixed $http
     */
    public function setHttpHandler($http)
    {
        $this->http_client = $http;
    }


    /**
     * Override the default user agent.
     *
     * @param string $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->request_headers['User-Agent'] = $user_agent;
    }

    /**
     * Set the X-Twitter HTTP headers that will be sent to the server.
     *
     * @param string $client
     * @param string $url
     * @param string $version
     */
    public function setXTwitterHeaders($client, $url, $version)
    {
        $this->request_headers['X-Twitter-Client'] = $client;
        $this->request_headers['X-Twitter-Client-URL'] = $url;
        $this->request_headers['X-Twitter-Client-Version'] = $version;
    }

    /**
     * Suggest the "from source" value to be displayed on the Twitter web site.
     *
     * The value of the 'source' parameter must be first recognized by
     * the Twitter server.  New source values are authorized on a case by
     * case basis by the Twitter development team.
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->default_params['source'] = $source;
    }

    /**
     * Fetch the rate limit status for the currently authorized user.
     *
     * Returns an array containing the time the limit will reset (reset_time),
     * the number of remaining hits allowed before the reset (remaining_hits),
     * the number of hits allowed in a 60-minute period (hourly_limit), and
     * the time of the reset in seconds since The Epoch (reset_time_in_seconds).
     *
     * @param string $resources CSV list of resource families
     *
     * @return array
     */
    public function getRateLimitStatus($resources = null)
    {
        $parameters = array();
        if (!is_null($resources)) {
            $parameters['resources'] = $resources;
        }

        $url = "{$this->base_url}/application/rate_limit_status.json";
        $json = $this->fetchUrl($url, 'GET', $parameters, array('no_cache' => true));
        $data = $this->parseAndCheckTwitter($json);
        return $data;
    }

    /**
     * Determines the minimum number of seconds that a program must wait
     * before hitting the server again without exceeding the rate_limit
     * imposed for the currently authenticated user.
     *
     * @return int The minimum second interval that a program must use so as to not
     *   exceed the rate_limit imposed for the user.
     */
    public function maximumHitFrequency()
    {
        $rate_status = $this->getRateLimitStatus();
        $reset_time = isset($rate_status['reset_time']) ? $rate_status['reset_time'] : null;
        $limit = isset($rate_status['remaining_hits']) ? $rate_status['remaining_hits'] : null;

        if ($reset_time) {
            # put the reset time into a DateTime object
            $reset_date = new \DateTime("@{$reset_time}", new \DateTimeZone('UTC'));
            $reset_date->add(new \DateInterval('P1h'));

            $current_date = new \DateTime('now', new \DateTimeZone('UTC'));

            # find the difference in time between now and the reset time + 1 hour
            $seconds = ($reset_date->getTimestamp() - $current_date->getTimestamp());

            if (!$limit) {
                return (int)$seconds;
            }

            # determine the minimum number of seconds allowed as a regular interval
            $max_frequency = (int)($seconds / $limit) + 1;

            # return the number of seconds
            return $max_frequency;
        }
        return 60;
    }

    protected function buildUrl($url, $path_elements = null, $extra_params = null)
    {
        // Break url into constituent parts
        $url_parts = parse_url($url);

        // Add any additional path elements to the path
        if ($path_elements) {
            // Filter out the path elements that have a value of null
            $p = array_filter($path_elements);

            if (!empty($url_parts['path']) && !ends_with($url_parts['path'], '/')) {
                $url_parts['path'] .= '/';
            }
            $url_parts['path'] .= implode('/', $p);
        }

        // Add any additional query parameters to the query string
        if ($extra_params and count($extra_params) > 0) {
            $extra_query = $this->encodeParameters($extra_params);

            // Add it to the existing query
            if (!empty($url_parts['query'])) {
                $url_parts['query'] .= '&' . $extra_query;
            } else {
                $url_parts['query'] = $extra_query;
            }
        }

        // Return the rebuilt URL
        return http_build_url($url_parts);
    }

    protected function initializeRequestHeaders($request_headers)
    {
        if (!empty($request_headers)) {
            $this->request_headers = $request_headers;
        } else {
            $this->request_headers = array();
        }
    }

    protected function initializeUserAgent()
    {
        $user_agent = 'League\Twitter PHP Library v' . $this->version;
        $this->setUserAgent($user_agent);
    }

    protected function initializeDefaultParameters()
    {
        $this->default_params = array();
    }

    /**
     * @param Response $response
     * @return mixed
     */
    protected function decompressGzippedResponse($response)
    {
        $raw_data = $response->getBody();
        if ($response->getHeader('content-encoding') === 'gzip') {
            // return gzip.GzipFile(fileobj=StringIO.StringIO(raw_data)).read();
        }
        return $raw_data;
    }

    /**
     * Return a string in key=value&key=value form
     */
    protected function encodeParameters($parameters)
    {
        if (empty($parameters)) {
            return null;
        }

        $utf8_params = array();
        foreach ($parameters as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $utf8_params[$key] = utf8_encode($value);
        }

        return http_build_query($utf8_params);
    }

    /**
     * Return a string in key=value&key=value form
     */
    protected function encodePostData($post_data)
    {
        if (is_null($post_data)) {
            return null;
        }

        $utf8_params = array();
        foreach ($post_data as $key => $value) {
            $utf8_params[$key] = utf8_encode($value);
        }
		return $utf8_params;
        return http_build_query($utf8_params);
    }

    /**
     * Try and parse the JSON returned from Twitter and return
     * an empty array if there is any error. This is a purely
     * defensive check because during some Twitter network outages
     * it will return an HTML failwhale page.
     */
    protected function parseAndCheckTwitter($json)
    {
        $data = null;

        try {
            //$jsonVal = $json->__toString();
            $data = json_decode($json, true);
            $this->checkForTwitterError($data);
        } catch (Exception $e) {
            if (strpos($json, '<title>Twitter / Over capacity</title>')) {
                throw new \Exception("Capacity Error");
            }
            if (strpos($json, '<title>Twitter / Error</title>')) {
                throw new \Exception("Technical Error");
            }
        }

        if ($data === false) {
            throw new \Exception("Invalid JSON");
        }

        return $data;
    }

    /**
     * Raises a TwitterError if twitter returns an error message.
     * @params
     * @throws \Exception Error from the JSON API
     */
    public function checkForTwitterError($data)
    {
        # Twitter errors are relatively unlikely, so it is faster
        # to check first, rather than try and catch the \Exception
        if (array_key_exists('error', $data)) {
            throw new \Exception($data['error']);
        }
        if (array_key_exists('errors', $data)) {
            throw new \Exception($data['errors']);
        }
    }

    /**
     * @param mixed $access_token_key
     */
    public function setAccessTokenKey($access_token_key)
    {
        $this->access_token_key = $access_token_key;
    }

    /**
     * @return mixed
     */
    public function getAccessTokenKey()
    {
        return $this->access_token_key;
    }

    /**
     * @param mixed $access_token_secret
     */
    public function setAccessTokenSecret($access_token_secret)
    {
        $this->access_token_secret = $access_token_secret;
    }

    /**
     * @return mixed
     */
    public function getAccessTokenSecret()
    {
        return $this->access_token_secret;
    }

    /**
     * @param mixed $consumer_key
     */
    public function setConsumerKey($consumer_key)
    {
        $this->consumer_key = $consumer_key;
    }

    /**
     * @return mixed
     */
    public function getConsumerKey()
    {
        return $this->consumer_key;
    }

    /**
     * @param mixed $consumer_secret
     */
    public function setConsumerSecret($consumer_secret)
    {
        $this->consumer_secret = $consumer_secret;
    }

    /**
     * @return mixed
     */
    public function getConsumerSecret()
    {
        return $this->consumer_secret;
    }
}
