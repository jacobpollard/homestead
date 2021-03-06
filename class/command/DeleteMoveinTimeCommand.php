<?php
PHPWS_Core::initModClass('hms', 'CommandFactory.php');

class DeleteMoveinTimeCommand extends Command {
    protected $id;

    public function getRequestVars(){
        return array('action' => 'DeleteMoveinTime',
                     'id'     => $this->id
                     );
    }

    public function setId($id){
        if(is_numeric($id))
            $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function execute(CommandContext $context){
        $cmd = CommandFactory::getCommand('ShowMoveinTimesView');
        $id = $context->get('id');
        if(is_null($id) || !is_numeric($id)){
            NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'Invalid id selected for deletion.');
            $cmd->redirect();
        }

        $movein_time = new HMS_Movein_Time();
        $movein_time->id = $id;
        $result = $movein_time->delete();

        if(!$result || PHPWS_Error::logIfError($result)){
            NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'Database error while attempting to delete movein time.');
        } else {
            NQ::simple('hms', HMS_NOTIFICATION_SUCCESS, 'Movein time deleted successfully.');
        }

        $cmd->redirect();
    }
}
?>