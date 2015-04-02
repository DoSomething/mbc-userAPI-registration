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
$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
  'northstar_api_host' => getenv("NORTHSTAR_API_HOST"),
  'northstar_api_id' => getenv("NORTHSTAR_API_ID"),
  'northstar_api_key' => getenv("NORTHSTAR_API_KEY"),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$transactionalExchange = $mb_config->exchangeSettings('transactionalExchange');

$config['exchange'] = array(
  'name' => $transactionalExchange->name,
  'type' =>$transactionalExchange->type,
  'passive' => $transactionalExchange->passive,
  'durable' => $transactionalExchange->durable,
  'auto_delete' => $transactionalExchange->auto_delete,
);
foreach ($transactionalExchange->queues->userAPIRegistrationQueue->binding_patterns as $bindingPattern) {
  $config['queue'][] = array(
    'name' => $transactionalExchange->queues->userAPIRegistrationQueue->name,
    'passive' => $transactionalExchange->queues->userAPIRegistrationQueue->passive,
    'durable' => $transactionalExchange->queues->userAPIRegistrationQueue->durable,
    'exclusive' => $transactionalExchange->queues->userAPIRegistrationQueue->exclusive,
    'auto_delete' => $transactionalExchange->queues->userAPIRegistrationQueue->auto_delete,
    'bindingKey' => $bindingPattern,
  );
  $config['routingKey'] = $transactionalExchange->queues->userAPIRegistrationQueue->routing_key;
}


echo '------- mbc-userAPI-registrations START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserAPIRegistration($settings), 'updateUserAPI'));

echo '------- mbc-userAPI-registrations END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
