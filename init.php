<?php
session_start();

set_time_limit(60*60*5);
ini_set('max_execution_time', 60*60*5);

include_once('config.php');
include_once('db/db.php');

// Conecto con Twitter
require_once ('codebird-php.php');
\Codebird\Codebird::setConsumerKey($tw_consumer, $tw_secret); // static, see 'Using multiple Codebird instances'

$cb = \Codebird\Codebird::getInstance();
$cb->setToken($tw_token_a, $tw_token_b);

include('bot.php');
include('tweet.php');


