<?php

/**
 * CancelHousingApplicationCommand
 * 
 * Cancels a housing application and optionally un-assigns the student.
 * Inteded to be called via ajax.
 * 
 * @author jbooker
 */
class CancelHousingApplicationCommand extends Command {
    
    public function getRequestVars()
    {
        return array('action'=>'CancelHousingApplication');
    }
    
    public function execute(CommandContext $context)
    {
        if(!Current_User::allow('hms', 'cancel_housing_application')){
            PHPWS_Core::initModClass('hms', 'exception/PermissionException.php');
            throw new PermissionException('You do not have permission to cancel housing applications.');
        }
        
        // Check for a housing application id
        $applicationId = $context->get('applicationId');

        if(!isset($applicationId) || is_null($applicationId)){
            throw new InvalidArgumentException('Missing housing application id.');
        }

        // Check for a cancellation reason
        $cancelReason = $context->get('cancel_reason');
        if(!isset($cancelReason) || is_null($cancelReason)){
            throw new InvalidArgumentException('Missing cancellation reason.');
        }
        
        // Load the housing application
        PHPWS_Core::initModClass('hms', 'HousingApplicationFactory.php');
        $application = HousingApplicationFactory::getApplicationById($applicationId);
        
        // Load the student
        $student = $application->getStudent();
        $term = $application->getTerm();
        
        // Load the cancellation reasons
        $reasons = HousingApplication::getCancellationReasons();
        
        // Check for an assignment and remove it
        
        // Decide which term to use - If this application is in a past fall term, then use the current term
        if($term < Term::getCurrentTerm() && Term::getTermSem($term) == TERM_FALL){
            $assignmentTerm = Term::getCurrentTerm();
        }else{
            $assignmentTerm = $term;
        }
        
        PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');
        $assignment = HMS_Assignment::getAssignmentByBannerId($student->getBannerId(), $assignmentTerm);
        
        if(isset($assignment)){
            HMS_Assignment::unassignStudent($student, $assignmentTerm, 'Application cancellation: ' . $reasons[$cancelReason], UNASSIGN_CANCEL);
        }
        
        // Cancel the application
        $application->cancel($cancelReason);
        $application->save();

        echo 'success';
        exit;
    }
}

?>