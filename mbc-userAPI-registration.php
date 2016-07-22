<?php
/**
 * mbc-userAPI-registration.php
 *
 * Collect user registration messages from the userAPIRegistrationQueue. Update the
 * UserAPI / database with user registration activity.
 */
    
use DoSomething\MBC_UserAPI_Registration\MBC_UserAPI_Registration_Consumer;

date_default_timezone_set('America/New_York');

define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on the same queue.
define('QOS_SIZE', 1);
    
// Manage enviroment setting
if (isset($_GET['environment']) && allowedEnviroment($_GET['environment'])) {
    define('ENVIRONMENT', $_GET['environment']);
} elseif (isset($argv[1])&& allowedEnviroment($argv[1])) {
    define('ENVIRONMENT', $argv[1]);
} elseif ($env = loadConfig()) {
    echo 'environment.php exists, ENVIRONMENT defined as: ' . ENVIRONMENT, PHP_EOL;
} elseif (allowedEnviroment('local')) {
    define('ENVIRONMENT', 'local');
}

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/mbc-userAPI-registration.config.inc';

// Kick off
echo '------- mbc-userAPI-registrations START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

$mb = $mbConfig->getProperty('messageBroker');
$mb->consumeMessage(array(new MBC_UserAPI_Registration_Consumer(), 'consumeUserAPIRegistrationQueue'), QOS_SIZE);

echo '------- mbc-userAPI-registrations END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

/**
 * Test if environment setting is a supported value.
 *
 * @param string $setting Requested enviroment setting.
 *
 * @return boolean
 */
function allowedEnviroment($setting)
{
    
    $allowedEnviroments = [
        'local',
        'dev',
        'prod'
    ];
    
    if (in_array($setting, $allowedEnviroments)) {
        return true;
    }
    
    return false;
}

/**
 * Gather configuration settings for current application environment.
 *
 * @return boolean
 */
function loadConfig() {
    
    // Check that environment config file exists
    if (!file_exists (environment.php)) {
        return false;
    }
    include('./environment.php');
    
    return true;
}
