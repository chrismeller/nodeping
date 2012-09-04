<?php

	class Nodeping {

		const VERSION = 0.1;
		const API_VERSION = 1;

		const BASE_URL = 'https://api.nodeping.com/api/1/';

		private $token;
		private $account;

		public function __construct ( $token, $account = null ) {
			$this->token = $token;
		}

		public static function factory ( $token, $account = null ) {
			$class = __CLASS__;

			return new $class( $token, $account );
		}

		public function get_accounts ( ) {

			$url = $this->build_url( 'accounts' );

			$accounts = $this->get( $url );

			return $accounts;

		}

		public function get_account ( $account_id ) {

			$options = array(
				'customerid' => $account_id,
			);

			$url = $this->build_url( 'accounts', $options );

			$account = $this->get( $url );

			return $account;

		}

		/**
		 * Get all the checks for your account.
		 *
		 * @return Nodeping_Check[] Array of Nodeping_Check objects.
		 */
		public function get_checks ( ) {

			$url = $this->build_url( 'checks' );

			$checks = $this->get( $url );

			// we want to format the checks and use standardized keys, so here we go
			$cs = array();

			foreach ( $checks as $check ) {
				$c = new Nodeping_Check( $check );

				$cs[ $c->id ] = $c;
			}

			return $cs;

		}

		/**
		 * Get all the results for a given Check ID.
		 *
		 * Note that the smaller of $span or $limit will be used if both are provided.
		 *
		 * @param string $check_id The ID of the check to fetch results for.
		 * @param int $span Number of hours to retrieve results for.
		 * @param int $limit Number of records to retrieve. Defaults to 300. Max 43201.
		 * @return Nodeping_Result[] Array of Nodeping_Result objects.
		 */
		public function get_check_results ( $check_id, $span = null, $limit = null ) {

			$options = array(
				'id' => $check_id,
				'clean' => true,
			);

			if ( $span !== null ) {
				$options['span'] = $span;
			}

			if ( $limit !== null ) {
				$options['limit'] = $limit;
			}

			$url = $this->build_url( 'results', $options );

			$results = $this->get( $url );

			// we want to format the results and use keys that actually mean something, so here we go
			$rs = array();

			foreach ( $results as $result ) {
				$r = new Nodeping_Result( $result );

				$rs[ $r->id ] = $r;
			}

			return $rs;

		}

		protected function get ( $url ) {

			$options = array(
				'http' => array(
					'timeout' => 30,
				)
			);

			$context = stream_context_create( $options );

			$result = file_get_contents( $url, false, $context );

			if ( $result === false ) {
				throw new Nodeping_Exception('Error while getting a response from Nodeping');
			}

			$result = json_decode( $result );

			if ( isset( $result->error ) ) {
				throw new Nodeping_Exception( $result->error );
			}

			return $result;

		}

		private function build_url ( $uri, $query_params = array() ) {

			$query_params['token'] = $this->token;

			if ( $this->account != null ) {
				$query_params['customerid'] = $this->account;
			}

			$query = http_build_query( $query_params );

			$url = static::BASE_URL . ltrim( $uri, '/' ) . '?' . $query;

			return $url;

		}

	}

	class Nodeping_Exception extends Exception {}

	class Nodeping_Result {

		/**
		 * @var ID of the result record.
		 */
		public $id;

		/**
		 * @var The type of check: DNS, FTP, HTTP, HTTPCONTENT, etc.
		 */
		public $type;

		/**
		 * @var The target of the check. Generally a URL or hostname.
		 */
		public $target;

		/**
		 * @var The threshold for timeout of this check, in seconds.
		 */
		public $threshold;

		/**
		 * @var The interval period between checks, in seconds.
		 */
		public $interval;

		/**
		 * @var Customer ID that owns this check.
		 */
		public $customer_id;

		/**
		 * @var Unix Timestamp value of when the check was scheduled to run.
		 */
		public $scheduled_ts;

		/**
		 * @var Unix Timestamp value of when the check actually ran.
		 */
		public $ts;

		/**
		 * @var Short text showing the result. Varies by check type, but for HTTP this is the HTTP status code returned.
		 */
		public $status;

		/**
		 * @var Amount of time the check ran. This is the value that gets charted. Should be in milliseconds.
		 */
		public $runtime;

		/**
		 * @var Unix Timestamp value of when the check completed.
		 */
		public $completed_ts;

		/**
		 * @var Locations the check ran at and the timestamps for each. It's up to you to decipher these...
		 */
		public $locations;

		/**
		 * @var Message regarding the result of the check. Varies by check type, but generally an error code, if there is one.
		 */
		public $message;

		/**
		 * @var The queue the check was in when it ran. Probably useless for everyone but NP themselves.
		 */
		public $queue;

		/**
		 * @var Boolean: Did the check succeed or fail?
		 */
		public $success;

		/**
		 * @var The Check ID this result is for, parsed from the $id value;
		 */
		public $check_id;

		public function __construct ( $result = null ) {

			if ( $result != null ) {

				$this->id = $result->_id;
				$this->type = $result->t;
				$this->customer_id = $result->ci;
				$this->target = isset( $result->tg ) ? $result->tg : null;
				$this->threshold = $result->th;
				$this->interval = $result->i;
				$this->scheduled_ts = $result->ra;
				$this->queue = $result->q;
				$this->ts = $result->s;
				$this->status = $result->sc;
				$this->runtime = $result->rt;
				$this->completed_ts = $result->e;
				$this->locations = isset( $result->l ) ? $result->l : array();
				$this->message = isset( $result->m ) ? $result->m : null;
				$this->success = $result->su;

				$id_pieces = explode( '-', $this->id );

				// pop the actual result ID off the end
				array_pop( $id_pieces );

				// and put the rest back together as the check id
				$this->check_id = implode( '-', $id_pieces );

			}
		}

	}

	class Nodeping_Check {

		/**
		 * @var ID of the check.
		 */
		public $id;

		/**
		 * @var string Customer ID that owns this check.
		 */
		public $customer_id;

		/**
		 * @var string Textual label you assigned to this check.
		 */
		public $label;

		/**
		 * @var int The interval period between checks, in seconds.
		 */
		public $interval;

		/**
		 * @var array An array of all the people that will be notified. It's up to you to parse these...
		 */
		public $notifications = array();

		/**
		 * @var int Unix Timestamp value of when the check was created.
		 */
		public $created_ts;

		/**
		 * @var string The type of check: DNS, FTP, HTTP, HTTPCONTENT, etc.
		 */
		public $type;

		/**
		 * @var string Whether the check is enabled or not. Will be "active" when it is.
		 */
		public $enable;

		/**
		 * @var bool Whether the check is enabled or not. Parsed out from the textual $enable.
		 */
		public $enabled;

		/**
		 * @var bool Is this check public?
		 */
		public $public;

		/**
		 * @var int Unix Timestamp value of when the check was last modified.
		 */
		public $modified_ts;

		/**
		 * @var object All the parameters related to the check - target, etc. It's up to you to parse these, they will vary by check type.
		 */
		public $parameters;

		/**
		 * @var string Some kind of hex UUID. I got nothing...
		 */
		public $uuid;

		/**
		 * @var string The status. Should always be 'assigned' for a GET operation, but may be 'modified', etc. when requesting updates.
		 */
		public $status;

		/**
		 * @var string The queue, presumably that this request was served from.
		 */
		public $queue;

		/**
		 * @var string The textual description assigned to this check, if there is one.
		 */
		public $description;

		public function __construct ( $result = null ) {

			if ( $result != null ) {

				$this->id = $result->_id;
				$this->customer_id = $result->customer_id;
				$this->label = $result->label;
				$this->interval = $result->interval;
				$this->notifications = $result->notifications;
				$this->created_ts = $result->created;
				$this->type = $result->type;
				$this->enable = $result->enable;
				$this->public = (bool)$result->public;
				$this->modified_ts = $result->modified;
				$this->parameters = $result->parameters;
				$this->uuid = $result->uuid;
				$this->status = $result->status;
				$this->queue = isset( $result->queue ) ? $result->queue : null;
				$this->description = isset( $result->description ) ? $result->description : null;

				// parse out the textual 'enable' field and create a boolean enabled for convenience
				if ( $this->enable == 'active' ) {
					$this->enabled = true;
				}
				else {
					$this->enabled = false;
				}

			}
		}

	}

?>