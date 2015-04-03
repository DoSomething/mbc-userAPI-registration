<?php

use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/**
 * MBC_UserAPIRegistration class - functionality related to the Message Broker
 * producer mbp-user-import.
 */

class MBC_UserAPIRegistration
{

  const AURORA_API_VERSION = 1;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $toolbox;

  /**
   * Constructor for MBC_UserEvent
   *
   * @param array $credentials
   *   Secret settings from mb-secure-config.inc
   *
   * @param array $config
   *   Configuration settings from mb-config.inc
   */
  public function __construct($settings) {

    $this->settings = $settings;
    $this->toolbox = new MB_Toolbox($settings);

    $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-userAPI-registration:');
    $this->statHat->setIsProduction($this->settings['use_stathat_tracking']);
  }

  /**
   * Submit user campaign activity to the UserAPI
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function updateUserAPI($payload) {

    echo '------- MBC_UserAPIRegistration START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $payloadDetails = unserialize($payload->body);

    // There will only ever be one campaign entry in the payload
    // Skip if @mobile submission
    if (strpos($payloadDetails['email'], '@mobile') === FALSE) {

      $allowedFields = array(
       'email',
       'subscribed',
       'uid',
       'drupal_uid',
       'birthdate',
       'birthdate_timestamp',
       'activity_timestamp',
       'merge_vars',
       'mobile',
       'address1',
       'address2',
       'city',
       'stae',
       'zip',
       'hs_gradyear',
       'race',
       'religion',
       'hs_name',
       'college_name',
       'major_name',
       'degree_type',
       'sat_math',
       'sat_verbal',
       'sat_writing',
       'act_math',
       'gpa',
       'role',
       'source'
      );

      foreach($allowedFields as $field) {
        if (isset($payloadDetails[$field])) {
          if ($field == 'uid') {
            $post['drupal_uid'] = $payloadDetails['uid'];
            $post['uid'] = $payloadDetails['uid'];
          }
          elseif ($field == 'birthdate') {
            $post['birthdate'] = date('c', $payloadDetails['birthdate']);
            $post['birthdate_timestamp'] = $payloadDetails['birthdate'];
          }
          elseif ($field == 'activity_timestamp') {
            $post['drupal_register_timestamp'] = $payloadDetails['activity_timestamp'];
          }
          elseif ($field == 'merge_vars') {
            if (isset($payloadDetails['merge_vars']['FNAME'])) {
              $post['first_name'] = $payloadDetails['merge_vars']['FNAME'];
            }
            if (isset($payloadDetails['merge_vars']['LNAME'])) {
              $post['last_name'] = $payloadDetails['merge_vars']['LNAME'];
            }
          }
          else {
            $post[$field] = $payloadDetails[$field];
          }
        }
      }


      echo '------- mbc-userAPI-registration - MBC_UserAPIRegistration: $post: ' . print_r($post, TRUE) . ' - ' . date('j D M Y G:i:s Y') . ' -------', PHP_EOL;

      $mbUserApiUrl = $this->settings['ds_user_api_host'] . ':' . $this->settings['ds_user_api_port'] . '/user';
      $results = $this->toolbox->curlPOST($mbUserApiUrl, $post);

      $dsUserApiUrl = $this->settings['northstar_api_host'] . '/' . self::AURORA_API_VERSION . '/users';
      $results = $this->toolbox->curlPOST($dsUserApiUrl, $post);

      echo '------- MBC_UserAPIRegistration END #' . $payload->delivery_info['delivery_tag'] . ' - result: ' .  $result . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
    }

  }

}