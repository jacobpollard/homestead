<?php

class LotteryChooseRoommatesCommand extends Command {

    private $roomId;

    public function setRoomId($id){
        $this->roomId = $id;
    }

    public function getRequestVars()
    {
        return array('action'=>'LotteryChooseRoommates', 'roomId'=>$this->roomId);
    }

    public function execute(CommandContext $context)
    {
        PHPWS_Core::initModClass('hms', 'StudentFactory.php');
        PHPWS_Core::initModClass('hms', 'HousingApplication.php');
        PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');
        
        $roommates = $context->get('roommates');
        $mealPlan = $context->get('meal_plan');
        $term = PHPWS_Settings::get('hms', 'lottery_term');

        $student = StudentFactory::getStudentByUsername(UserStatus::getUsername(), $term);

        $roomId = $context->get('roomId');

        if(!isset($roomId) || is_null($roomId) || empty($roomId)){
            throw new InvalidArgumentException('Missing room id.');
        }

        # Put everything into lowercase before we get started
        foreach($roommates as $key => $username){
            $roommates[$key] = strtolower($username);
        }

        /**
         * Sanity checking
         */

        $errorCmd = CommandFactory::getCommand('LotteryShowChooseRoommates');
        $errorCmd->setRoomId($roomId);
         
        # Make sure the student assigned his/her self to a bed
        if(!in_array(UserStatus::getUsername(), $roommates)){
            NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'You must assign yourself to a bed. Please try again.');
            $errorCmd->redirect();
        }

        # Get a count of how many times each user name appears
        $counts = array_count_values($roommates);

        foreach($roommates as $roommate){
            if($roommate == NULL || $roommate == ''){
                continue;
            }

            # Make sure this user name only appears once
            if($counts[$roommate] > 1){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate may only be assigned to one bed. Please try again.");
                $errorCmd->redirect();
            }

            try {
                $studentObj = StudentFactory::getStudentByUsername($roommate, $term);
            }catch(StudentNotFoundException $e){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is not a valid user name. Please try again.");
                $errorCmd->redirect();
            }
            
            $bannerId = $studentObj->getBannerId();

            # Make sure every user name is a valid student
            if(is_null($bannerId) || empty($bannerId)){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is not a valid user name. Please try again.");
                $errorCmd->redirect();
            }

            /*
             * We can't check the student type here, because we're working in the future with students who are possibly still considered freshmen (which will always show up as type F)
             * What we can do is make sure their application term is less than the lottery term
             */
            
            $roommateAppTerm = $studentObj->getApplicationTerm();
            if(!isset($roommateAppTerm) || is_null($roommateAppTerm) || empty($roommateAppTerm)){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "The Housing Management System does not have complete student data for $roommate. Please select a different roommate.");
                $errorCmd->redirect();
            }

            # Make sure the student's application term is less than the current term
            if($studentObj->getApplicationTerm() > Term::getCurrentTerm()){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is not a continuing student. Only continuing students (i.e. not a first semester freshmen) may be selected as roommates. Please select a different roommate.");
                $errorCmd->redirect();
            }

            # Make sure the student is not withdrawn for the lottery term (again, we can't actually check for 'continuing' here)
            if($studentObj->getType() == TYPE_WITHDRAWN){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is not a continuing student. Only continuing students (i.e. not a first semester freshmen) may be selected as roommates. Please select a different roommate.");
                $errorCmd->redirect();
            }

            # Make sure every student entered the lottery and has a valid application (not cancelled)
            if(HousingApplication::checkForApplication($roommate, $term) === FALSE){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate did not re-apply for housing. Please select a different roommate.");
                $errorCmd->redirect();
            }

            # Make sure every student's gender matches, and that those are compatible with the room
            if($studentObj->getGender() != $student->getGender()){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is not the same gender as you. Please choose a roommate of the same gender.");
                $errorCmd->redirect();
            }

            # Make sure none of the students are assigned yet
            if(HMS_Assignment::checkForAssignment($roommate, $term) === TRUE){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$roommate is already assigned to a room. Please choose a different roommate.");
                $errorCmd->redirect();
            }
        }
        
        # If we've made it this far, then everything is ok.. redirect to the confirmation screen
        $confirmCmd = CommandFactory::getCommand('LotteryShowConfirm');
        $confirmCmd->setRoomId($roomId);
        $confirmCmd->setRoommates($roommates);
        $confirmCmd->setMealPlan($mealPlan);
        $confirmCmd->redirect();
    }
}


?>