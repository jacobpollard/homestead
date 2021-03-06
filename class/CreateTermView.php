<?php

class CreateTermView extends hms\View{

    public function __construct(){}

    public function show()
    {
        if(!UserStatus::isAdmin() || !Current_User::allow('hms', 'edit_terms')){
            PHPWS_Core::initModClass('hms', 'exception/PermissionException.php');
            throw new PermissionException('You do not have permission to edit terms.');
        }
        PHPWS_Core::initModClass('hms', 'HMS_Util.php');
        javascript('jquery');
        javascript('modules/hms/newTermCopyPick');

        $tpl['TITLE'] = 'Add a New Term';

        if(isset($success)){
            $tpl['SUCCESS_MSG'] = $success;
        }else if(isset($error)){
            $tpl['ERROR_MSG'] = $error;
        }

        $submitCmd = CommandFactory::getCommand('CreateTerm');

        $form = new PHPWS_Form('new_term_form');
        $submitCmd->initForm($form);

        $form->addDropBox('from_term', Term::getTermsAssoc());
        $form->setLabel('from_term', 'Copy from:');

        $form->addDropBox('year_drop',HMS_Util::get_years_2yr());
        $form->setLabel('year_drop','Year: ');

        $form->addDropBox('term_drop',Term::getSemesterList());
        $form->setLabel('term_drop','Semester: ');

        $vars = array('struct' => 'Hall structure', 'assign' => 'Assignments', 'role' => 'Roles');
        $form->addCheckAssoc('copy_pick', $vars);
        $tpl['COPY_PICK_LABEL'] = 'What to copy:';
        $form->addSubmit('submit','Add Term');

        $form->mergeTemplate($tpl);
        $tpl = $form->getTemplate();

        Layout::addPageTitle("Create Term");

        return PHPWS_Template::process($tpl, 'hms', 'admin/add_term.tpl');
    }
}

?>