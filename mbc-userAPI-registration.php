<?php
/**
 * mbc-user-campaign.php
 *
 * Collect user campaign activity from the userCampaignActivityQueue. Update the
 * UserAPI / database with user activity.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

// @todo: Move MBC_UserAPICampaignActivity to class.inc file
// require __DIR__ . '/MBC_UserAPICampaignActivity.class.inc';

class MBC_UserAPIRegistration
{

  /**
   * Submit user campaign activity to the UserAPI
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function updateUserAPI($payload) {
    
    echo '------- MBC_UserAPIRegistration START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s:u T Y') . ' -------', "\n";

    $payloadDetails = unserialize($payload->body);

    // There will only ever be one campaign entry in the payload
    $post = array(
      'email' => $payloadDetails['email'],
      'drupal_uid' => $payloadDetails['uid'],
      'birthdate_timestamp' => $payloadDetails['birthdate'],
      'drupal_register_timestamp' => $payloadDetails['activity_timestamp'],
    );
    if (isset($payloadDetails['mobile']) && $payloadDetails['mobile'] != NULL) {
      $post['mobile'] = $payloadDetails['mobile'];
    }
    
    echo '------- MBC_UserAPIRegistration $post: ' . print_r($post, TRUE) . ' -------', "\n";

    $userApiUrl = getenv('DS_USER_API_HOST') . ':' . getenv('DS_USER_API_PORT') . '/user';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userApiUrl);
    curl_setopt($ch, CURLOPT_POST, count($post));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);

    // Remove entry from queue
    // @todo: Skipping sendAck as consumeMessage issues a $channel->close(); which might explain the:
    /*
       PHP Fatal error:  Uncaught exception 'PhpAmqpLib\Exception\AMQPProtocolChannelException' with message
       'PRECONDITION_FAILED - unknown delivery tag 1' in /opt/rabbit/mbc-userAPI-registration/vendor/videlalvaro/php-amqplib/PhpAmqpLib/Channel/AMQPChannel.php:115
     */
    // MessageBroker::sendAck($payload);
    
    echo '------- MBC_UserAPIRegistration $payload: ' . print_r($payload, TRUE) . ' -------', "\n";
    echo '------- MBC_UserAPIRegistration END #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s:u T Y') . ' -------', "\n";
  }

}

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

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserAPIRegistration(), 'updateUserAPI'));
