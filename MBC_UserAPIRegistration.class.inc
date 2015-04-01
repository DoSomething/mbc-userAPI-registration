<?php

<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_UserAPIRegistration class - functionality related to the Message Broker
 * producer mbp-user-import.
 */

class MBC_UserAPIRegistration
{

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

      if (isset($payloadDetails['source']) && $payloadDetails['source'] != NULL) {
        $post['source'] = $payloadDetails['source'];
      }

      echo '------- mbc-userAPI-registration - MBC_UserAPIRegistration: $post: ' . print_r($post, TRUE) . ' - ' . date('j D M Y G:i:s Y') . ' -------', PHP_EOL;

      $userApiUrl = getenv('DS_USER_API_HOST') . ':' . getenv('DS_USER_API_PORT') . '/user';

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $userApiUrl);
      curl_setopt($ch, CURLOPT_POST, count($post));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);

      echo '------- MBC_UserAPIRegistration END #' . $payload->delivery_info['delivery_tag'] . ' - result: ' .  $result . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
    }

  }

}