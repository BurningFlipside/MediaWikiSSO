<?php

function get_single_value_from_array($array)
{
    if(!is_array($array))
    {
        return $array;
    }
    if(isset($array[0]))
    {
        return $array[0];
    }
    else
    {
        return '';
    }
}

function flip_authonUserLoadFromSession($user, &$result)
{
    if(isset($_COOKIE['PHPSESSID']))
    {
        $new_id = $_COOKIE['PHPSESSID'];
        require_once('/var/www/common/class.FlipSession.php');
        $session = FlipSession::getSessionById($new_id);
        if($session != FALSE && isset($session['AuthMethod']) && isset($session['AuthData']))
        {
            $auth = \AuthProvider::getInstance();
            $flip_user = $auth->getUser($session['AuthData'], $session['AuthMethod']);
            $dbr =& wfGetDB( DB_SLAVE );
            $userName = ucwords($flip_user->uid);
            $s = $dbr->selectRow('user', array('user_id'), array('user_name' => $userName), __METHOD__);
            if($s === false)
            {
                $s = $dbr->selectRow('user', array('user_id'), array('user_email' => $flip_user->mail), __METHOD__);
                if($s === false)
                {
                    $user = User::newFromName($userName);
                    if(!$user->isLoggedIn())
                    {
                        $user->mEmail = $flip_user->mail;
                        $user->mRealName = $flip_user->givenName." ".$flip_user->sn;
                        $user->EmailAuthenticated = wfTimestamp();
                        $user->mTouched           = wfTimestamp();
                        $res = $user->addToDatabase();
                    }
                }
                else
                {
                    $user->mId = $s->user_id;
                    $dbw =& wfGetDB( DB_MASTER );
                    $dbw->update('user', array('user_name' => $userName), array('user_id' => $s->user_id), __METHOD__);
                }
            }
            else
            {
                $user->mId = $s->user_id;
            }
            if($user->loadFromDatabase())
            {
                $result = true;
            }
        }
    }
    return true;
}

function flip_authonUserLoginForm(&$template)
{
    global $wgArticlePath;

    $return = 'http';
    if(isset($_SERVER['HTTPS'])) {$return .= "s";}
        $return .= "://";
    $return .= $_SERVER['SERVER_NAME'].$wgArticlePath;
    $local_path = 'Main_Page';
    if(isset($_GET['returnto']))
    {
        $local_path = $_GET['returnto'];
    }
    $return = strtr($return, array('$1'=>$local_path)); 
    header('Location: '.getenv('PROFILES_URL').'/login.php?return='.$return);
}

function flip_createUserForm(&$template)
{
    header('Location: '.getenv('PROFILES_URL').'/register.php');
}

function flip_authonUserLogoutComplete(&$user, &$inject_html, $old_name)
{
    header('Location: '.getenv('PROFILES_URL').'/logout.php');
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'Flipside Authentication',
    'author' => 'Patrick "Problem" Boyd', 
    'url' => 'n/a', 
    'description' => 'This extension allows authentication and single sign-on with '.getenv('PROFILES_URL').'',
    'version'  => 0.1,
    'license-name' => "",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
);

$wgHooks['UserLoadFromSession'][] = 'flip_authonUserLoadFromSession';
$wgHooks['UserLoginForm'][] = 'flip_authonUserLoginForm';
$wgHooks['UserCreateForm'][] = 'flip_createUserForm';
$wgHooks['UserLogoutComplete'][] = 'flip_authonUserLogoutComplete';

?>
