<?php

require_once(PHPWS_SOURCE_DIR . 'mod/hms/inc/defines.php');

class HMS_Login
{
    public function display_login_screen($error = NULL)
    {
        $values = array('ADDITIONAL'=>'The Housing Management System will <strong>not</strong> work without having your web browser\'s cookie features enabled.  Please read about <a href="http://www.google.com/cookies.html" target="_blank">how to enable cookies</a>.');
        $tpl['COOKIE_WARNING'] = Layout::getJavascript('cookietest', $values);

        # If the user has cookies enabled (and therefore is not being shown the cookie warning message...
        if(is_null($tpl['COOKIE_WARNING'])){
            $tpl['LOGIN_LINK'] = HMS_LOGIN_LINK; // a dummy tag to make the actual login content show
        }

        Layout::add(PHPWS_Template::process($tpl, 'hms', 'misc/login.tpl'));
    }

    public function login_user()
    {
        /*
        if(!file_exists(AXP_LOCATION)) {
            return "A critical error has occurred in the Housing Management System.  Please tell Electronic Student Services that their AXP driver is missing.";
        }
        */
        /*
        require_once(PHPWS_SOURCE_DIR . AXP_LOCATION);

        if($type = axp_authorize($_REQUEST['asu_username'], $_REQUEST['password'])) {
            return HMS_Login::student_login();
        } else {
        */
            if(Current_User::loginUser($_REQUEST['asu_username'], $_REQUEST['password'])){
                Current_User::getLogin();
                return ADMIN;
            } else {
                return BADTUPLE;
            }
            //        }
        
    }

    public function student_login($username)
    {
        PHPWS_Core::initModClass('hms','HMS_Deadlines.php');
        PHPWS_Core::initModClass('hms','HMS_SOAP.php');
        $deadlines = HMS_Deadlines::get_deadlines();

        $username = strtolower(trim($username));

        # Log the student's login in their activity log
        PHPWS_Core::initModClass('hms','HMS_Activity_Log.php');
        HMS_Activity_Log::log_activity($username,ACTIVITY_LOGIN, $username, NULL); 

        # Setup the session variable
        $_SESSION['asu_username'] = $username;
        $_SESSION['application_term'] = HMS_SOAP::get_application_term($username);
        $_REQUEST['op'] = 'main';
        return STUDENT;
    }

    public function admin_login()
    {
        Current_User::loginUser(HMS_ADMIN_USER, HMS_ADMIN_PASS);
        Current_User::getLogin();
        $_SESSION['asu_username'] = $_REQUEST['asu_username'];
        return ADMIN;
    }

    public function fake_login($username){
        if( !Current_User::isLogged() ) {
            require_once(PHPWS_SOURCE_DIR . '/mod/hms/inc/accounts.php');
            Current_User::loginUser(HMS_STUDENT_USER, HMS_STUDENT_PASS);
            Current_User::getLogin();
        }
        HMS_Login::student_login($username);
    }

    public function show_fake_login()
    {
        $form = new PHPWS_Form();
        $form->addText('username');

        $form->addHidden('module', 'hms');
        $form->addHidden('action', 'fake_login');

        $form->addSubmit('login_button', 'Login');

        Layout::add(PHPWS_Template::process($form->getTemplate(), 'hms', 'misc/fake_login.tpl'));
    }
};

?>
