<?php
/**
 *
 */

 namespace DoSomething\MBC_UserAPI_Registration;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use \Exception;

/**
 * MBC_UserAPIRegistration class - functionality related to the Message Broker
 * producer mbp-user-import.
 */

class MBC_UserAPI_Registration_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * cURL object to access cUrl related methods
   * @var object $mbToolboxcURL
   */
  protected $mbToolboxcURL;

  /**
   *
   * @var string $curlUrl
   */
  private $curlUrl;

  /**
   * __construct():
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->mbToolboxcURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $mbUserAPI = $this->mbConfig->getProperty('mb_user_api_config');
    $this->curlUrl = $mbUserAPI['host'];
    if (isset($mbUserAPI['port'])) {
      $this->curlUrl .= ':' . $mbUserAPI['port'];
    }
    $this->curlUrl .= '/user';
  }

  /**
   * Callback for messages arriving in the userAPIRegistrationQueue.
   *
   * @param string $payload
   *   A seralized message to be processed.
   */
  public function consumeUserAPIRegistrationQueue($payload) {

    echo '-------  mbc-userAPI-register -  MBC_UserAPI_Registration_Consumer->consumeUserAPIRegistrationQueue() START -------', PHP_EOL;

    parent::consumeQueue($payload);
    echo '** Consuming: ' . $this->message['email'], PHP_EOL;

    if ($this->canProcess()) {

      try {

        $this->setter($this->message);
        $this->process();
      }
      catch(Exception $e) {
        echo 'Error submissing user registration for email address: ' . $this->message['email'] . ' to mb-user-api. Error: ' . $e->getMessage();
      }

    }
    else {
      echo '=> ' . $this->message['email'] . ' can\'t be processed.', PHP_EOL;
      $this->messageBroker->sendAck($this->message['payload']);
    }

    echo '-------  mbc-userAPI-register -  MBC_UserAPI_Registration_Consumer->consumeUserAPIRegistrationQueue() END -------', PHP_EOL . PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (!(isset($this->message['email']))) {
      echo '- canProcess(), email not set.', PHP_EOL;
      return FALSE;
    }
    // Don't process 1234@mobile email address (legacy hack in Drupal app to support mobile registrations)
    // BUT allow processing email addresses: joe@mobilemaster.com
    $mobilePos = strpos($this->message['email'], '@mobile');
    if ($mobilePos > 0 && (strlen($this->message['email']) - $mobilePos) > 7) {
      echo '- canProcess(), Drupal app fake @mobile email address.', PHP_EOL;
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Construct values for submission to mb-users-api service.
   *
   * @param array $message
   *   The message to process based on what was collected from the queue being processed.
   */
  protected function setter($message) {

    $this->submission = [];

    $allowedFields = array(
      'email',
      'subscribed',
      'uid',
      'drupal_uid',
      'birthdate',
      'birthdate_timestamp',
      'activity_timestamp',
      'user_language',
      'merge_vars',
      'mobile',
      'address1',
      'address2',
      'city',
      'state',
      'user_county',
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
      if (isset($message[$field])) {
        if ($field == 'uid') {
          $this->submission['drupal_uid'] = $message['uid'];
          $this->submission['uid'] = $message['uid'];
        }
        elseif ($field == 'birthdate') {
          $this->submission['birthdate'] = date('c', $message['birthdate']);
          $this->submission['birthdate_timestamp'] = $message['birthdate'];
        }
        elseif ($field == 'activity_timestamp') {
          $this->submission['drupal_register_timestamp'] = $message['activity_timestamp'];
        }
        elseif ($field == 'merge_vars') {
          if (isset($payloadDetails['merge_vars']['FNAME'])) {
            $this->submission['first_name'] = $message['merge_vars']['FNAME'];
          }
          if (isset($payloadDetails['merge_vars']['LNAME'])) {
            $this->submission['last_name'] = $message['merge_vars']['LNAME'];
          }
        }
        else {
          $this->submission[$field] = $message[$field];
        }
      }
    }

  }

  /**
   * process(): Submit formatted message values to mb-users-api /user/banned.
   */
  protected function process() {

    echo '-> post: ' . print_r($post, TRUE) . ' - ' . date('j D M Y G:i:s Y') . ' -------', PHP_EOL;

    $results = $this->mbToolboxcURL->curlPOST($this->curlUrl, $this->submission);
    if ($results[1] == 200) {
      $this->messageBroker->sendAck($this->message['payload']);
    }
    else {
      throw new Exception('Error submitting registration to mb-user-api: ' . print_r($this->submission, TRUE));
    }
  }

}
