<?php
/**
 * mbc-userAPI-campaign.php
 *
 * Collect user campaign activity from the userAPIRegistrationQueue. Update the
 * UserAPI / database with user registration activity.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/mb-secure-config.inc';
require_once __DIR__ . '/MBC_UserAPIRegistration.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$config = array(
  'exchange' => array(
    'name' => getenv("MB_TRANSACTIONAL_EXCHANGE"),
    'type' => getenv("MB_TRANSACTIONAL_EXCHANGE_TYPE"),
    'passive' => getenv("MB_TRANSACTIONAL_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_TRANSACTIONAL_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_TRANSACTIONAL_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    'userAPIRegistration' => array(
      'name' => getenv("MB_USER_API_REGISTRATION_QUEUE"),
      'passive' => getenv("MB_USER_API_REGISTRATION_QUEUE_PASSIVE"),
      'durable' => getenv("MB_USER_API_REGISTRATION_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_USER_API_REGISTRATION_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_USER_API_REGISTRATION_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_USER_API_REGISTRATION_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
  'routingKey' => getenv("MB_USER_API_REGISTRATION_ROUTING_KEY"),
);
$settings = array(
  'northstar_api_host' => getenv("NORTHSTAR_API_HOST"),
  'northstar_api_id' => getenv("NORTHSTAR_API_ID"),
  'northstar_api_key' => getenv("NORTHSTAR_API_KEY"),
);


// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserAPIRegistration(), 'updateUserAPI'));
