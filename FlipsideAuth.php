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

function unserializesession($data)
{
    $vars=preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/',
              $data,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    for($i=0; $vars[$i]; $i++) $result[$vars[$i++]]=unserialize($vars[$i]);
    return $result;
}


function flip_authonUserLoadFromSession($user, &$result)
{
    if(isset($_COOKIE['PHPSESSID']))
    {
        $new_id = $_COOKIE['PHPSESSID'];
        require_once('/var/www/common/Autoload.php');
        $handler = new \Data\DataTableSessionHandler('profiles', 'sessions');
        $handler->open(false, false);
        $sessionData = $handler->read($new_id);
        $session = unserializesession($sessionData);
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

function get_base_uri()
{
    $ret = getenv('PROFILES_URL');
    if($ret === false)
    {
         return 'https://profiles.burningflipside.com';
    }
    return $ret;
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
    header('Location: '.get_base_uri().'/login.php?return='.$return);
}

function flip_createUserForm(&$template)
{
    header('Location: '.get_base_uri().'/register.php');
}

function flip_authonUserLogoutComplete(&$user, &$inject_html, $old_name)
{
    header('Location: '.get_base_uri().'/logout.php');
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'Flipside Authentication',
    'author' => 'Patrick "Problem" Boyd', 
    'url' => 'n/a', 
    'description' => 'This extension allows authentication and single sign-on with the burningflipside system',
    'version'  => 0.2,
    'license-name' => "",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
);

$wgHooks['UserLoadFromSession'][] = 'flip_authonUserLoadFromSession';
$wgHooks['UserLoginForm'][] = 'flip_authonUserLoginForm';
$wgHooks['UserCreateForm'][] = 'flip_createUserForm';
$wgHooks['UserLogoutComplete'][] = 'flip_authonUserLogoutComplete';

?>
