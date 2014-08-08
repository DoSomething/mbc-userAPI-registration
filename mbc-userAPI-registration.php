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

    echo '------- MBC_UserAPIRegistration START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";

    $payloadDetails = unserialize($payload->body);

    // There will only ever be one campaign entry in the payload
    // Skip if @mobile submission
    if (strpos($payloadDetails['email'], '@mobile') === FALSE) {
      $post = array(
        'email' => $payloadDetails['email'],
        'subscribed' => $payloadDetails['subscribed'],
      );
      if (isset($payloadDetails['uid']) && $payloadDetails['uid'] != NULL) {
        $post['drupal_uid'] = $payloadDetails['uid'];
      }
      elseif (isset($payloadDetails['drupal_uid']) && $payloadDetails['drupal_uid'] != NULL) {
        $post['drupal_uid'] = $payloadDetails['drupal_uid'];
      }
      if (isset($payloadDetails['birthdate']) && $payloadDetails['birthdate'] != NULL) {
        $post['birthdate_timestamp'] = $payloadDetails['birthdate'];
      }
      elseif (isset($payloadDetails['birthdate_timestamp']) && $payloadDetails['birthdate_timestamp'] != NULL) {
        $post['birthdate_timestamp'] = $payloadDetails['birthdate_timestamp'];
      }
      if (isset($payloadDetails['activity_timestamp']) && $payloadDetails['activity_timestamp'] != NULL) {
        $post['drupal_register_timestamp'] = $payloadDetails['activity_timestamp'];
      }
      if (isset($payloadDetails['merge_vars']['LNAME']) && $payloadDetails['merge_vars']['LNAME'] != NULL) {
        $post['last_name'] = $payloadDetails['merge_vars']['LNAME'];
      }
      if (isset($payloadDetails['merge_vars']['FNAME']) && $payloadDetails['merge_vars']['FNAME'] != NULL) {
        $post['first_name'] = $payloadDetails['merge_vars']['FNAME'];
      }
      if (isset($payloadDetails['mobile']) && $payloadDetails['mobile'] != NULL) {
        $post['mobile'] = $payloadDetails['mobile'];
      }
      if (isset($payloadDetails['address1']) && $payloadDetails['address1'] != NULL) {
        $post['address1'] = $payloadDetails['address1'];
      }
      if (isset($payloadDetails['address2']) && $payloadDetails['address2'] != NULL) {
        $post['address2'] = $payloadDetails['address2'];
      }
      if (isset($payloadDetails['city']) && $payloadDetails['city'] != NULL) {
        $post['city'] = $payloadDetails['city'];
      }
      if (isset($payloadDetails['state']) && $payloadDetails['state'] != NULL) {
        $post['state'] = $payloadDetails['state'];
      }
      if (isset($payloadDetails['zip']) && $payloadDetails['zip'] != NULL) {
        $post['zip'] = $payloadDetails['zip'];
      }
      if (isset($payloadDetails['hs_gradyear']) && $payloadDetails['hs_gradyear'] != NULL) {
        $post['hs_gradyear'] = $payloadDetails['hs_gradyear'];
      }
      if (isset($payloadDetails['race']) && $payloadDetails['race'] != NULL) {
        $post['race'] = $payloadDetails['race'];
      }
      if (isset($payloadDetails['religion']) && $payloadDetails['religion'] != NULL) {
        $post['religion'] = $payloadDetails['religion'];
      }
      if (isset($payloadDetails['hs_name']) && $payloadDetails['hs_name'] != NULL) {
        $post['hs_name'] = $payloadDetails['hs_name'];
      }
      if (isset($payloadDetails['college_name']) && $payloadDetails['college_name'] != NULL) {
        $post['college_name'] = $payloadDetails['college_name'];
      }
      if (isset($payloadDetails['major_name']) && $payloadDetails['major_name'] != NULL) {
        $post['major_name'] = $payloadDetails['major_name'];
      }
      if (isset($payloadDetails['degree_type']) && $payloadDetails['degree_type'] != NULL) {
        $post['degree_type'] = $payloadDetails['degree_type'];
      }
      if (isset($payloadDetails['sat_math']) && $payloadDetails['sat_math'] != NULL) {
        $post['sat_math'] = $payloadDetails['sat_math'];
      }
      if (isset($payloadDetails['sat_verbal']) && $payloadDetails['sat_verbal'] != NULL) {
        $post['sat_verbal'] = $payloadDetails['sat_verbal'];
      }
      if (isset($payloadDetails['sat_writing']) && $payloadDetails['sat_verbal'] != NULL) {
        $post['sat_writing'] = $payloadDetails['sat_writing'];
      }
      if (isset($payloadDetails['act_math']) && $payloadDetails['act_math'] != NULL) {
        $post['act_math'] = $payloadDetails['act_math'];
      }
      if (isset($payloadDetails['gpa']) && $payloadDetails['gpa'] != NULL) {
        $post['gpa'] = $payloadDetails['gpa'];
      }
      if (isset($payloadDetails['role']) && $payloadDetails['role'] != NULL) {
        $post['role'] = $payloadDetails['role'];
      }

      $userApiUrl = getenv('DS_USER_API_HOST') . ':' . getenv('DS_USER_API_PORT') . '/user';

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $userApiUrl);
      curl_setopt($ch, CURLOPT_POST, count($post));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);

      echo '------- MBC_UserAPIRegistration END #' . $payload->delivery_info['delivery_tag'] . ' - result: ' .  $result . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";
    }

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
