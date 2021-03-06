<?php

/**
 * HMS Guest Controller
 * Controls information that Guests have access to (the login screen, for now).
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */

PHPWS_Core::initModClass('hms', 'HMS.php');

class GuestHMS extends HMS
{
    public function process()
    {
        $this->context->setDefault('action', 'ShowFrontPage');
        parent::process();

        PHPWS_Core::initModClass('hms', 'GuestView.php');
        $view = new GuestView();
        $view->setMain($this->context->getContent());
        
        PHPWS_Core::initModClass('hms', 'HMSNotificationView.php');
        $nv = new HMSNotificationView();
        $nv->popNotifications();
        $view->addNotifications($nv->show());
        
        $view->show();

        $this->saveState();
    }
}

?>