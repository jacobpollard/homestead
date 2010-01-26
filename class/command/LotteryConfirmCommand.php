<?php

class LotteryConfirmCommand extends Command {

    private $roomId;
    private $mealPlan;

    public function setRoomId($id){
        $this->roomId = $id;
    }

    public function setRoommates($roommates){
        $this->roommates = $roommates;
    }

    public function setMealPlan($plan){
        $this->mealPlan = $plan;
    }

    public function getRequestVars(){
        $vars = array('action'=>'LotteryConfirm');

        $vars['roomId'] = $this->roomId;
        $vars['mealPlan'] = $this->mealPlan;

        return $vars;
    }

    public function execute(CommandContext $context)
    {
        PHPWS_Core::initModClass('hms', 'StudentFactory.php');

        $roomId = $context->get('roomId');
        $roommates = $context->get('roommates');
        $mealPlan = $context->get('mealPlan');

        $term = PHPWS_Settings::get('hms', 'lottery_term');

        $student = StudentFactory::getStudentByUsername(UserStatus::getUsername(), $term);

        $errorCmd = CommandFactory::getCommand('LotteryShowConfirm');
        $errorCmd->setRoomId($roomId);
        $errorCmd->setRoommates($roommates);
        $errorCmd->setMealPlan($mealPlan);
        
        $successCmd = CommandFactory::getCommand('LotteryShowConfirmed');
        $successCmd->setRoomId($roomId);

        PHPWS_Core::initCoreClass('Captcha.php');
        $captcha = Captcha::verify(TRUE); // returns the words entered if correct, FALSE otherwise
        if($captcha === FALSE) {
            NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'Sorry, the words you eneted were incorrect. Please try again.');
            $errorCmd->redirect();
        }

        PHPWS_Core::initModClass('hms', 'HMS_Room.php');
        PHPWS_Core::initModClass('hms', 'HMS_Bed.php');
        PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');
        PHPWS_Core::initModClass('hms', 'HMS_Lottery.php');
        PHPWS_Core::initModClass('hms', 'StudentFactory.php');
        PHPWS_Core::initModClass('hms', 'HMS_Email.php');
        PHPWS_Core::initModClass('hms', 'HMS_Activity_Log.php');
        PHPWS_Core::initModClass('hms', 'HMS_Util.php');

        $room = new HMS_Room($roomId);

        foreach($roommates as $bed_id => $username){
            # Double check the student is valid
            try{
                $roommate = StudentFactory::getStudentByUsername($username, $term);
            }catch(StudentNotFoundException $e){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$username is not a valid student. Please choose a different roommate.");
                $errorCmd->redirect();
            }

            # Make sure the bed is still empty
            $bed = new HMS_Bed($bed_id);

            if($bed->has_vacancy() != TRUE){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'One or more of the beds in the room you selected is no longer available. Please try again.');
                $errorCmd->redirect();
            }

            # Make sure none of the needed beds are reserved
            if($bed->is_lottery_reserved()){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'One or more of the beds in the room you selected is no longer available. Please try again.');
                $errorCmd->redirect();
            }

            # Double check the genders are all the same as the person logged in
            if($student->getGender() != $roommate->getGender()){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$username is a different gender. Please choose a roommate of the same gender.");
                $errorCmd->redirect();
            }

            # Double check the genders are the same as the room (as long as the room isn't COED)
            if($room->gender_type != COED && $roommate->getGender() != $room->gender_type){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$username is a different gender. Please choose a roommate of the same gender.");
                $errorCmd->redirect();
            }

            # Double check the students' elligibilities
            if(HMS_Lottery::determineEligibility($username) !== TRUE){
                NQ::simple('hms', HMS_NOTIFICATION_ERROR, "$username is not eligibile for assignment.");
                $errorCmd->redirect();
            }
        }

        # If the room's gender is 'COED' and no one is assigned to it yet, switch it to the student's gender
        if($room->gender_type == COED && $room->get_number_of_assignees() == 0){
            $room->gender_type = $student->getGender();
            $room->save();
        }

        # Assign the student to the requested bed
        $bed_id = array_search(UserStatus::getUsername(), $roommates); // Find the bed id of the student who's logged in

        try{
            $result = HMS_Assignment::assignStudent($student, PHPWS_Settings::get('hms', 'lottery_term'), NULL, $bed_id, $mealPlan, 'Confirmed lottery invite', TRUE);
        }catch(Exception $e){
            NQ::simple('hms', HMS_NOTIFICATION_ERROR, 'Sorry, there was an error creating your room assignment. Please try again or contact Housing & Residence Life');
            $errorCmd->redirect();
        }
        
        # Log the assignment
        HMS_Activity_Log::log_activity(UserStatus::getUsername(), ACTIVITY_LOTTERY_ROOM_CHOSEN, UserStatus::getUsername(), 'Captcha: ' . $captcha);

        $requestor_name = $student->getName();

        foreach($roommates as $bed_id => $username){
            // Skip the current user
            if($username == $student->getUsername()){
                continue;
            }

            # Reserve the bed for the roommate
            $expires_on = mktime() + (INVITE_TTL_HRS * 3600);
            $bed = new HMS_Bed($bed_id);
            if(!$bed->lottery_reserve($username, $student->getUsername(), $expires_on)){
                NQ::smiple('hms', HMS_NOTIFICATION_WARNING, "You were assigned, but there was a problem reserving space for your roommates. Please contact Housing & Residence Life.");
                $successCmd->redirect();
            }

            HMS_Activity_Log::log_activity($username, ACTIVITY_LOTTERY_REQUESTED_AS_ROOMMATE, $student->getUsername(), 'Expires: ' . HMS_Util::get_long_date_time($expires_on));

            # Invite the selected roommates
            $roomie = StudentFactory::getStudentByUsername($username, $term);
            $term = PHPWS_Settings::get('hms', 'lottery_term');
            $year = Term::toString($term) . ' - ' . Term::toString(Term::getNextTerm($term));
            HMS_Email::send_lottery_roommate_invite($roomie, $student, $expires_on, $room->where_am_i(), $year);
        }
        
        HMS_Email::send_lottery_assignment_confirmation($student, $room->where_am_i(), $term);
        
        $successCmd->redirect();
    }
}

?>