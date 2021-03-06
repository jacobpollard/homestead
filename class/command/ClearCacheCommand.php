<?php

class ClearCacheCommand extends Command {

    public function getRequestVars(){
        return array('action'=>'ClearCache');
    }

    public function execute(CommandContext $context)
    {
        PHPWS_Core::initModClass('hms', 'StudentDataProvider.php');

        $provider = StudentDataProvider::getInstance();
        $provider->clearCache();

        NQ::simple('hms', HMS_NOTIFICATION_SUCCESS, 'Cache cleared.');
    }

}

?>