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
        $session = FlipSession::get_session_by_id($new_id);
        if($session != FALSE && isset($session['flipside_user']))
        {
            $flip_user = $session['flipside_user'];
            $dbr =& wfGetDB( DB_SLAVE );
            $userName = ucwords($flip_user->uid[0]);
            $s = $dbr->selectRow('user', array('user_id'), array('user_name' => $userName));
            if($s === false)
            {
                $user = new User();
                $user->loadDefaults($userName);
                $user->mEmail = $flip_user->mail[0];
                $user->mRealName = get_single_value_from_array($flip_user->givenName)." ".get_single_value_from_array($flip_user->sn);
                $user->EmailAuthenticated = wfTimestamp();
                $user->mTouched           = wfTimestamp();
                $user->addToDatabase();
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
    header('Location: https://profiles.burningflipside.com/login.php?return='.$return);
}

function flip_authonUserLogoutComplete(&$user, &$inject_html, $old_name)
{
    header('Location: https://profiles.burningflipside.com/logout.php');
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'Flipside Authentication',
    'author' => 'Patrick "Problem" Boyd', 
    'url' => 'n/a', 
    'description' => 'This extension allows authentication and single sign-on with profiles.burningflipside.com',
    'version'  => 0.1,
    'license-name' => "",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
);

$wgHooks['UserLoadFromSession'][] = 'flip_authonUserLoadFromSession';
$wgHooks['UserLoginForm'][] = 'flip_authonUserLoginForm';
$wgHooks['UserLogoutComplete'][] = 'flip_authonUserLogoutComplete';

?>
