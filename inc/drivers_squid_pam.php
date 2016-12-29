<?php
/**
* Manages PAM repository connection with SQUID help
*
* @package   davical
* @category Technical
* @subpackage   ldap
* @author    Eric Seigne <eric.seigne@ryxeo.com>,
*            Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Eric Seigne
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("auth-functions.php");

class squidPamDrivers
{
  /**#@+
  * @access private
  */

  /**#@-*/


  /**
  * The constructor
  *
  * @param string $config path where /usr/lib/squid/pam_auth is
  */
  function __construct($config) {
      global $c;
      if (! file_exists($config)){
          $c->messages[] = sprintf(i18n( 'drivers_squid_pam : Unable to find %s file'), $config );
          $this->valid=false;
          return ;
      }
  }
}


/**
* Check the username / password against the PAM system
*/
function SQUID_PAM_check($username, $password ){
  global $c;

  $script = $c->authenticate_hook['config']['script'];
  if ( empty($script) ) $script = $c->authenticate_hook['config']['path'];
  $cmd = sprintf( 'echo %s %s | %s -n common-auth', escapeshellarg($username), escapeshellarg($password),
                                 $script);
  $auth_result = exec($cmd);
  if ( $auth_result == "OK") {
    dbg_error_log('pwauth', 'User %s successfully authenticated', $username);
    $principal = new Principal('username',$username);
    if ( !$principal->Exists() ) {
      dbg_error_log('pwauth', 'User %s does not exist in local db, creating', $username);
      $pwent = posix_getpwnam($username);
      $gecos = explode(',',$pwent['gecos']);
      $fullname = $gecos[0];
      $principal->Create( array(
                            'username' => $username,
                            'user_active' => 't',
                            'email' => sprintf('%s@%s', $username, $email_base),
                            'fullname' => $fullname
                        ));
      if ( ! $principal->Exists() ) {
        dbg_error_log( "PAM", "Unable to create local principal for '%s'", $username );
        return false;
      }
      CreateHomeCollections($username);
    }
    return $principal;
  }
  else {
    dbg_error_log( "PAM", "User %s is not a valid username (or password was wrong)", $username );
    return false;
  }

}
