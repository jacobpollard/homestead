<?php

class AcceptSpecialInterestCommand extends Command {

    private $group;
    private $id;

    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getRequestVars()
    {
        $requestVars = array('action' => 'AcceptSpecialInterest',
                             'group'  => $this->group,
                             'id'     => $this->id);

        return $requestVars;
    }

    public function execute(CommandContext $context)
    {
        # Check permissions
        if(!Current_User::allow('hms', 'special_interest_approval')){
            PHPWS_Core::initModClass('hms', 'exception/PermissionException.php');
            throw new PermissionException('You do not have permission to approve special interest group requests.');
        }

        PHPWS_Core::initModClass('hms', 'LotteryApplication.php');

        if(is_null($context->get('id'))){
            throw new InvalidArgumentException('Missing application id.');
        }

        $app = new LotteryApplication($context->get('id'));
        $app->special_interest = $context->get('group');

        $result = $app->save();

        NQ::simple('hms', HMS_NOTIFICATION_SUCCESS, "Accepted {$app->getUsername()}");

        $cmd = CommandFactory::getCommand('ShowSpecialInterestGroupApproval');
        $cmd->setGroup($context->get('group'));
        $cmd->redirect();
    }
}
?>
