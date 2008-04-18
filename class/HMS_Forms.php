
<?php

/**
 * Form objects for HMS
 *
 * @author Kevin Wilcox <kevin at tux dot appstate dot edu>
 * @author Jeremy Booker <jbooker at tux dot appstate dot edu>
 */

class HMS_Form
{

    var $error;

    function HMS_Form()
    {
        $this->error = "";
    }
    
    function set_error_msg($msg)
    {
        $this->error .= $msg;
    }

    function get_error_msg()
    {
        return $this->error;
    }

    function search_residence_halls()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = & new PHPWS_Form;

        $terms = array('0'=>"",
                       '1'=>"Spring",
                       '2'=>"Summer I",
                       '3'=>"Summer II",
                       '4'=>"Fall");
        $form->addDropBox('term', $terms);

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addWhere('is_online', '1');
        $db->addOrder('hall_name ASC');
        $results = $db->select();
        
        if($results != NULL && $results != FALSE) {
            foreach($results as $result) {
                $halls[$result['id']] = $result['hall_name'];
            }
            $form->addDropBox('hall', $halls);
        } else {
            $form->addDropBox('hall', array(''=>"Please make sure at least ONE hall is added and online!"));
        }

        $floors = array('', 1,2,3,4,5,6,7,8,9,10);
        $form->addDropBox('floor',$floors);

        $form->addText('room');
        $form->addText('bed');

        $form->addRadio('smoking', array(0, 1, 2));
        $form->setLabel('smoking', array(_("Yes"), _("No"), _("Unknown")));
        $form->setMatch('smoking', '2');
        
        $form->addRadio('type', array(0, 1, 2));
        $form->setLabel('type', array(_("Single"), _("Co-ed"), _("Unknown")));
        $form->setMatch('type', '2');
        
        $form->addRadio('status', array(0, 1, 2));
        $form->setLabel('status', array(_("Online"), _("Offline"), _("Unknown")));
        $form->setMatch('status', '2');
        
        $form->addHidden('module', 'hms');
        $form->addHidden('op', 'display_residence_hall');
        $form->addSubmit('submit', _('Search Halls'));
        $tpl = $form->getTemplate();
        $tpl['ERROR'] = $this->error;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/search_residence_halls_radio.tpl');
        return $final;
    }

    function get_usernames_for_new_grouping($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('first_roommate');
        $form->addText('second_roommate');
        $form->addText('third_roommate');
        $form->addText('fourth_roommate');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'roommate');
        $form->addHidden('op', 'save_grouping');
        $form->addSubmit('submit', _('Submit usernames'));

        $tpl = $form->getTemplate();
        $tpl['ERROR'] = $error;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_roommate_usernames.tpl');
        return $final;
    }

    function get_roommate_username($error = NULL)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('username');
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'roommate');
        $form->addHidden('op', 'verify_roommate_username');
        $form->addSubmit('submit', _('Submit Username'));

        $tpl = $form->getTemplate();
        $tpl['ERROR'] = $error;
        $tpl['MESSAGE'] =  "In order to select your roommate please provide their Appalachian email address below.<br /><br />";
        $tpl['MESSAGE'] .= "You will receive a follow-up email verifying the status of your invitation.<br /><br />";
        $tpl['MESSAGE'] .= "You will also receive an email once the individual Accepts or Rejects your invitation.<br /><br />";
        $tpl['MESSAGE'] .= "It is <b>NOT</b> necessary for the person you are inviting to also invite you. They only need to accept your invitation and you will be paired with this individual.<br /><br />";
        $tpl['MENU_LINK'] = PHPWS_Text::secureLink(_('Return to Menu'), 'hms', array('type'=>'student', 'op'=>'main'));
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    /*
    function verify_roommate_username()
    {
        $error = '';

        if(!PHPWS_Text::isValidInput($_REQUEST['username'])) {
            $error = "That is bad input. Please use letters and numbers *only*.";
            return HMS_Form::get_roommate_username($error);
        }

        PHPWS_Core::initModClass('hms', 'HMS_Roommate.php');
        
        if(HMS_Roommate::check_valid_students() != '') {
            $error = "That is not a valid username.";
            return HMS_Form::get_roommate_username($error);
        }

        # Make sure the roommate that is requested has also completed a housing application
        PHPWS_Core::initModClass('hms', 'HMS_Application.php');
        if(HMS_Application::check_for_application($_REQUEST['username']) == FALSE){
            $error = "The roommate you requested has not completed a housing application. Please ask your preferred roommate to log in and complete a housing application. Once your preferred roommate has submitted an application, you can return to this page to request that person as your roommate.";
            return HMS_Form::get_roommate_username($error);
        }

        # Make sure the roommate that is requested is not the same person who is logged in
        if($_REQUEST['username'] == $_SESSION['asu_username']){
            $error = "You cannot select yourself as your roommate.";
            return HMS_FORM::get_roommate_username($error);
        }

        PHPWS_Core::initModClass('hms', 'HMS_Roommate_Approval.php');
        if(HMS_Roommate_Approval::has_requested_someone()) {
            return HMS_Student::show_main_menu();
        }

        $_REQUEST['first_roommate'] = $_SESSION['asu_username'];
        $_REQUEST['second_roommate'] = $_REQUEST['username'];

        if(HMS_Roommate::check_consistent_genders() != '') {
            $error = "That is not an allowed roommate. You can only select roommates whose gender matches your own.";
            return HMS_Form::get_roommate_username($error);
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'roommate_approval');
        $form->addHidden('first_roommate', $_SESSION['asu_username']);
        $form->addHidden('second_roommate', $_REQUEST['username']);
        $form->addHidden('op', 'save_roommate_username');
        $form->addSubmit('submit', 'Yes, I want that roommate!');
        $form->addSubmit('cancel', 'No, that is wrong!');

        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        $tpl = $form->getTemplate();
        $tpl['ERROR'] = $error;
        $tpl['FIRST_NAME']  = HMS_SOAP::get_first_name($_REQUEST['username']);
        $tpl['LAST_NAME']   = HMS_SOAP::get_last_name($_REQUEST['username']);
        $tpl['USERNAME']    = $_REQUEST['username'];
        
        $final = PHPWS_Template::process($tpl, 'hms', 'student/verify_single_roommate.tpl');
        return $final;
    }
    */


    function get_username_for_edit_grouping($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('username');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'roommate');
        $form->addHidden('op', 'select_username_for_edit_grouping');
        $form->addSubmit('submit', _('Search for Grouping'));

        $tpl = $form->getTemplate();
        $tpl['ERROR']   = $error;
        $tpl['MESSAGE'] = "Please enter one of the ASU usernames in the roommate grouping you wish to edit:";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    function get_username_for_assignment($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('username');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'get_hall_floor_room');
        $form->addSubmit('submit', _('Submit User'));

        $tpl = $form->getTemplate();
        $tpl['ERROR']   = $error;
        $tpl['MESSAGE'] = "Please enter an ASU username to assign:";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    function get_username_for_deletion($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('username');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'verify_deletion');
        $form->addSubmit('submit', _('Submit User'));

        $tpl = $form->getTemplate();
        $tpl['ERROR']   = $error;
        $tpl['MESSAGE'] = "Please provide the ASU username of the student whose room assignment will be deleted.";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    function get_username_for_move($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('username');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'get_move_hall_floor_room');
        $form->addSubmit('submit', _('Submit User'));

        $tpl = $form->getTemplate();
        $tpl['ERROR']   = $error;
        $tpl['MESSAGE'] = "Please provide the ASU username of the student that is to be moved.";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    function get_hall_floor_room($error = NULL)
    {
        $db = new PHPWS_DB('hms_residence_hall');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('hall_name ASC');
        $halls_raw = $db->select();

        foreach($halls_raw as $hall) {
            $halls[$hall['id']] = $hall['hall_name'];
        }

        for($i = 0; $i <= 15; $i++) {
            $floors[$i] = $i;
        }

        for ($i = 1; $i <= 85; $i++) {
            $rooms[$i] = $i;
        }

        $letters = array('a'=>"a", 'b'=>"b", 'c'=>"c", 'd'=>"d");
        $meal = array('0'=>"Low", '1'=>"Standard", '2'=>"High",
            '3'=>"Super", '4'=>"None");

        $meal_option = $_REQUEST['meal_option'];

        if(!isset($_REQUEST['meal_option'])) {
            $db = new PHPWS_DB('hms_assignment');
            $db->addColumn('meal_option');
            $db->addWhere('deleted', '0');
            $db->addWhere('asu_username', $_REQUEST['username'], 'ILIKE');
            $meal_option = $db->select('one');
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addDropBox('halls', $halls);
        $form->addDropBox('floors', $floors);
        $form->addDropBox('rooms', $rooms);
        $form->addDropBox('bedroom_letter', $letters);
        $form->addDropBox('bed_letter', $letters);
        $form->addDropBox('meal_option', $meal);

        $form->setMatch('halls', $_REQUEST['halls']);
        $form->setMatch('floors', $_REQUEST['floors']);
        $form->setMatch('rooms', $_REQUEST['rooms']);
        $form->setMatch('bedroom_letter', $_REQUEST['bedroom_letter']);
        $form->setMatch('bed_letter', $_REQUEST['bed_letter']);
        $form->setMatch('meal_option', $meal_option);
        
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'verify_assignment');
        $form->addHidden('username', $_REQUEST['username']);
        $form->addSubmit('submit', _('Assign Room'));

        $tpl = $form->getTemplate();
        $tpl['MESSAGE'] =  "<h2>Assigning Student: " . $_REQUEST['username'] . "</h2><br />";
        $tpl['MESSAGE'] .= $error;
        $tpl['MESSAGE'] .= $msg;
        $tpl['MESSAGE'] .= "Please select a Hall, Floor and Room.";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_hall_floor_room.tpl');

        return $final;
    }

    function get_hall_floor($error)
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('hall_name ASC');
        $halls_raw = $db->select();
        foreach($halls_raw as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        for($ctr = 0; $ctr <= 15; $ctr++) {
            $floors[$ctr] = $ctr;
        }

        $room_range = array("0125" =>"1 - 25", 
                            "2650" => "26 - 50",
                            "5175" => "51 - 75");

        $form->addDropBox('halls', $halls);
        $form->addDropBox('floors', $floors);
        $form->addDropBox('room_range', $room_range);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'show_assignments_by_floor');
        $form->addSubmit('submit', _('Show rooms'));
        $tpl = $form->getTemplate();
       
        $tpl['MESSAGE']  = "<h2>Assignment By Floor</h2><br />";
        $tpl['MESSAGE'] .= "Please select a Hall and Floor to assign.<br />";
        $tpl['ERROR']    = $error;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_hall_floor.tpl');
        
        return $final;
    }

    function show_assignments_by_floor($msg = NULL)
    {
        PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');

        // check the hall/floor combo is good
        $db = &new PHPWS_DB('hms_floor');
        $db->addValue('id');
        $db->addWhere('building', $_REQUEST['halls']);
        $db->addWhere('floor_number', $_REQUEST['floors']);
        $db->addWhere('deleted', '1', '!=');
        $id = $db->select('one');
        if(!is_numeric($id)) {
            $error = "That is not a valid Hall/Floor combination.<br />";
            return HMS_Assignment::get_hall_floor($error);
        }
        
        // get the hall name
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hall_name');
        $db->addWhere('id', $_REQUEST['halls']);
        $hall = $db->select('one');

        // get the room number
        $rooms_sql  = "SELECT hms_room.room_number, hms_room.displayed_room_number, hms_room.id ";
        $rooms_sql .= "FROM hms_room, hms_floor ";
        $rooms_sql .= "WHERE hms_room.floor_id = hms_floor.id ";
        $rooms_sql .= "AND hms_floor.floor_number = " . $_REQUEST['floors'] . " ";
        $rooms_sql .= "AND hms_floor.building = " . $_REQUEST['halls'] . " ";
        
        if($_REQUEST['room_range'] == '0125') {
            $rooms_sql .= "AND int4(hms_room.room_number) <=  " . $_REQUEST['floors'] . "25 ";
        } else if ($_REQUEST['room_range'] == '2650') {
            $rooms_sql .= "AND int4(hms_room.room_number) > " . $_REQUEST['floors'] . "25 ";
            $rooms_sql .= "AND int4(hms_room.room_number) <= " . $_REQUEST['floors'] . "50 ";
        } else if ($_REQUEST['room_range'] == '5175') {
            $rooms_sql .= "AND int4(hms_room.room_number) > " . $_REQUEST['floors'] . "51 ";
            $rooms_sql .= "AND int4(hms_room.room_number) <= " . $_REQUEST['floors'] . "75 ";
        }

        $rooms_sql .= "AND hms_room.deleted = 0 ";
        $rooms_sql .= "ORDER BY hms_room.room_number ASC;";

        $db = &new PHPWS_DB();
        $db->setSQLQuery($rooms_sql);
        $rooms_raw = $db->select();

        if(PEAR::isError($rooms_raw)) {
            return HMS_Form::get_hall_floor("There was an error selecting that room range. Please try again or contact ESS.<br />");
        }

        if(sizeof($rooms_raw) < 1) {
            return HMS_Form::get_hall_floor("That room range does not exist for that Hall/Floor combination.<br />");
        }

        $body = '';
        
        foreach($rooms_raw as $aroom) {
            // iterate through the rooms, building the form as necessary

            $db = &new PHPWS_DB('hms_beds');
            $db->addColumn('id');
            $db->addColumn('bed_letter');
            $db->addColumn('hms_bedrooms.bedroom_letter');
            $db->addWhere('bedroom_id', 'hms_bedrooms.id');
            $db->addWhere('hms_bedrooms.room_id', 'hms_room.id');
            $db->addWhere('hms_room.id', $aroom['id']);
            $db->addWhere('hms_beds.deleted', '0');
            $beds = $db->select();

            if($beds != NULL && $beds != FALSE) {
                $body .= "<tr><th>Room Number: &nbsp;&nbsp;" . $aroom['room_number'] . "&nbsp;&nbsp;</th><th>Displayed: " . $aroom['displayed_room_number'] . "</th></tr>";

                foreach($beds as $abed) {
                    $tags['BED_NAME'] = $abed['bed_letter'];
                    $tags['BEDROOM_ID'] = $abed['bedroom_letter'];
                    $bed_id = "bed__" . $abed['id']; 
                    $edit_bed_id = "bed_" . $abed['id'];
                    $meal_option_id = "meal_option_" . $abed['id'];

                    $username = HMS_Assignment::get_asu_username($abed['id']);
                    $meal_option = HMS_Assignment::get_meal_option('bed_id', $abed['id']);

                    if(isset($_REQUEST[$bed_id]) && $_REQUEST[$bed_id] != NULL) {
                        $tags['BED_ID'] = "<input type=\"text\" name=\"bed_ " . $abed['id']  . "\" id=\"bed_id\" value=\"" . $_REQUEST[$bed_id] . "\" />";
                        $tags['MEAL_PLAN'] = "<select name=\"meal_option_" . $abed['id'] ."\">";
                       
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 0) $tags['MEAL_PLAN'] .= "<option selected value=\"0\">Low</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"0\">Low</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 1) $tags['MEAL_PLAN'] .= "<option selected value=\"1\">Standard</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"1\">Standard</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 2) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">High</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"2\">High</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 3) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">Super</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"3\">Super</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 4) $tags['MEAL_PLAN'] .= "<option selected value=\"4\">None</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"4\">None</option>";
                
                        $tags['MEAL_PLAN'] .= "</select>";
                    } else if(isset($_REQUEST[$edit_bed_id])) {
                        $tags['BED_ID'] = "<input type=\"text\" name=\"bed_ " . $abed['id']  . "\" id=\"bed_id\" value=\"" . $_REQUEST[$edit_bed_id] . "\" />";
                        $tags['MEAL_PLAN'] = "<select name=\"meal_option_" . $abed['id'] ."\">";
                       
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 0) $tags['MEAL_PLAN'] .= "<option selected value=\"0\">Low</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"0\">Low</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 1) $tags['MEAL_PLAN'] .= "<option selected value=\"1\">Standard</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"1\">Standard</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 2) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">High</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"2\">High</option>";
                        
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 3) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">Super</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"3\">Super</option>";
                
                        if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 4) $tags['MEAL_PLAN'] .= "<option selected value=\"4\">None</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"4\">None</option>";
                
                        $tags['MEAL_PLAN'] .= "</select>";
                    } else {
                        $tags['BED_ID'] = "<input type=\"text\" name=\"bed_ " . $abed['id']  . "\" id=\"bed_id\" value=\"" . $username . "\" />";
                        $tags['MEAL_PLAN'] = "<select name=\"meal_option_" . $abed['id'] ."\">";
                       
                        if($meal_option == 0) $tags['MEAL_PLAN'] .= "<option selected value=\"0\">Low</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"0\">Low</option>";
                        
                        if($meal_option == 1) $tags['MEAL_PLAN'] .= "<option selected value=\"1\">Standard</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"1\">Standard</option>";
                        
                        if($meal_option == 2) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">High</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"2\">High</option>";
                        
                        if($meal_option == 3) $tags['MEAL_PLAN'] .= "<option selected value=\"3\">Super</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"3\">Super</option>";
                        
                        if($meal_option == 4) $tags['MEAL_PLAN'] .= "<option selected value=\"4\">None</option>";
                        else $tags['MEAL_PLAN'] .= "<option value=\"4\">None</option>";
                
                        $tags['MEAL_PLAN'] .= "</select>";
                    }
                    
                    
                    $body .= PHPWS_Template::processTemplate($tags, 'hms', 'admin/bed_and_id.tpl');
                }
            }
        }
        
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('halls', $_REQUEST['halls']);
        $form->addHidden('floors', $_REQUEST['floors']);
        $form->addHidden('op', 'verify_assign_floor');
        $form->addSubmit('submit', _('Submit Floor'));

        $tags = $form->getTemplate();
        $tags['TITLE']      = "Assign Students";
        $tags['HALL']       = '<a href="./index.php?module=hms&type=hall&op=view_residence_hall&halls=' . $_REQUEST['halls'] . '">' . $hall . '</a>';
        $tags['FLOOR']      = $_REQUEST['floors'];
        $tags['BODY']       = $body;
        $tags['MESSAGE']    = $msg;
        $final = PHPWS_Template::processTemplate($tags, 'hms', 'admin/assign_floor.tpl');
        return $final; 
    }


/*    function verify_assign_floor($msg = NULL)
    {
        $db = &new PHPWS_DB('hms_beds');

/*        $sql = "SELECT hms_beds.id AS bed_id, 
                       hms_assignment.asu_username AS asu_username,
                       hms_assignment.meal_option AS meal_option,

        $db->addWhere('hms_beds.bedroom_id',         'hms_bedrooms.id');
        $db->addWhere('hms_bedrooms.room_id',        'hms_room.id');
        $db->addWhere('hms_room.floor_id',           'hms_floor.id');
        $db->addWhere('hms_floor.building',          'hms_residence_hall.id');
        $db->addWhere('hms_beds.deleted',             0);
        $db->addWhere('hms_bedrooms.deleted',         0);
        $db->addWhere('hms_room.deleted',             0);
        $db->addWhere('hms_floor.deleted',            0);
        $db->addWhere('hms_residence_hall.deleted',   0);
        $db->addWhere('hms_bedrooms.is_online',       1);
        $db->addWhere('hms_room.is_online',           1);
        $db->addWhere('hms_floor.is_online',          1);
        $db->addWhere('hms_residence_hall.is_online', 1);
        
        $db->addColumn('hms_beds.id', NULL, 'bed_id');

        $db->addWhere('hms_residence_hall.id',  $_REQUEST['halls']);
        $db->addWhere('hms_floor.floor_number', $_REQUEST['floors']);

        $db->addOrder('hms_room.displayed_room_number');
        $db->addOrder('hms_bedrooms.bedroom_letter');
        $db->addOrder('hms_bed.bed_letter');

        $result = $db->select();

        foreach($result as $row) {
            // If the bed is in the request, then it needs to be assigned or updated.
            // Otherwise we just ignore it.
            if(isset($_REQUEST["bed__{$row['bed_id']}"]) && 
               isset($_REQUEST["meal_option_{$row['bed_id']}"])) {
                $uid  = $_REQUEST["bed__{$row['bed_id']}"];
                $meal = $_REQUEST["meal_option_{$row['bed_id']}"];
                $content .= "Bed: $bed, Meal: $meal<br />";

                // Check for valid username
/*                if(!HMS_SOAP::is_valid_student($uid)) {
                    echo "

            }
        }

        return $content;
    }*/

    function verify_assign_floor()
    {
        PHPWS_Core::initCoreClass('Form.php');
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        PHPWS_Core::initModClass('hms', 'HMS_Building.php');
        PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');

        $body = '';
        reset($_REQUEST);
        while(list($key, $uid) = each($_REQUEST))
        {
            if(substr($key,0,4) == "bed_") {
                if(substr($key, 4, 1) == "_") {
                    $bid = substr($key, 5);
                } else {
                    $bid = substr($key, 4);
                }
                if($uid == NULL) continue;
       
                // check for valid username
                $valid_username = HMS_SOAP::is_valid_student($uid);
                if(!$valid_username) {
                    $error = "$uid is not a valid student. Please remove them from the list.<br /><br />";
                    return HMS_Form::show_assignments_by_floor($error);
                }
            
                // check to see if the room's already assigned
                $assigned = HMS_Assignment::is_bed_assigned($bid);
                if($assigned) {
                    $curr_occupant = HMS_Assignment::get_asu_username($bid);
                    
                    // check to see if the person being assigned is the current occupant
                    if(strcasecmp($curr_occupant, $uid) != 0) {
                        $error = "$uid can not be assigned because $curr_occupant is already in that room. Please remove them.<br /><br />";
                        return HMS_Form::show_assignments_by_floor($error);
                    }

                }

                // check to see if the current user is currently assigned
                $assigned = HMS_Assignment::is_user_assigned($uid);
                if($assigned) {
                    $curr_bed_id = HMS_Assignment::get_bed_id('asu_username', $uid);
                    if($curr_bed_id != $bid) {
                        $error = "$uid can not be assigned because they are assigned elsewhere. Please remove their room assignment first.<br /><br />";
                        return HMS_Form::show_assignments_by_floor($error);
                    }
                }

                // check room/person compatibility
                $db = &new PHPWS_DB('hms_room');
                $db->addColumn('gender_type');
                $db->addWhere('hms_beds.id', $bid);
                $db->addWhere('hms_beds.bedroom_id', 'hms_bedrooms.id');
                $db->addWhere('hms_bedrooms.room_id', 'hms_room.id');
                $db->addWhere('hms_beds.deleted', '0');
                $db->addWhere('hms_bedrooms.deleted', '0');
                $db->addWhere('hms_room.deleted', '0');
                $room_gender = $db->select('one');

                $user_gender = HMS_SOAP::get_gender($uid, TRUE);
            
                if($room_gender != $user_gender) {
                    $error = "$uid can not be assigned because their gender is not compatible with the room gender. Please change the room gender.<br /><br />";
                    return HMS_Form::show_assignments_by_floor($error);
                }

                // see if the person has a roommate
                // if a roommate exists, make sure they are going into the same room
                PHPWS_Core::initModClass('hms', 'HMS_Roommate.php');
                if(HMS_Roommate::has_roommates($uid)) {
                    /* from here we have to do some additional parsing.
                       we need to check the db to get all the bed ids associated with a room,
                         make sure they're included in $_REQUEST and that it's only their roommates
                         that are placed in those beds.
                       on the same note we need to ascertain that each of their roommates is included and that
                         no roommate is not in a bed in the room*/
                    $error = "$uid has roommates. Please write code to handle that.<br /><br />";
                    return HMS_Form::show_assignments_by_floor($error);
                }
            
                // if we get here we know we're pretty safe to go ahead and let them assign the student 
                $db = &new PHPWS_DB('hms_room');
                $db->addColumn('room_number');
                $db->addColumn('hms_bedrooms.bedroom_letter');
                $db->addColumn('hms_beds.bed_letter');
                $db->addColumn('hms_residence_hall.hall_name');
                $db->addWhere('hms_beds.id', $bid);
                $db->addWhere('hms_beds.bedroom_id', 'hms_bedrooms.id');
                $db->addWhere('hms_bedrooms.room_id', 'hms_room.id');
                $db->addWhere('hms_room.floor_id', 'hms_floor.id');
                $db->addWhere('hms_floor.floor_number', $_REQUEST['floors']);
                $db->addWhere('hms_floor.building', 'hms_residence_hall.id');
                $db->addWhere('hms_residence_hall.id', $_REQUEST['halls']);
                $response = $db->select('row');

                $tags['BED_NAME'] = $response['bed_letter'];
                $tags['ROOM_LABEL'] = "Room ";
                $tags['ROOM_NUM']   = $response['room_number'] . " &nbsp;&nbsp;&nbsp;&nbsp;";
                $tags['BEDROOM_ID'] = $response['bedroom_letter'] . "&nbsp;&nbsp;&nbsp";
                $bed_id = "bed__" . $bid; 
                $tags['BED_ID'] = "<input type=\"text\" readonly name=\"bed_$bid\" id=\"phpws_form_bed_id\" value=\"$uid\" />";
                
                $tags['MEAL_PLAN'] = "<select name=\"meal_option_" . $bid ."\">";
              
                $meal_option_id = "meal_option_" . $bid;

                if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 0) $tags['MEAL_PLAN'] .= "<option selected value=\"0\">Low</option>";
                else $tags['MEAL_PLAN'] .= "<option value=\"0\">Low</option>";
                
                if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 1) $tags['MEAL_PLAN'] .= "<option selected value=\"1\">Standard</option>";
                else $tags['MEAL_PLAN'] .= "<option value=\"1\">Standard</option>";
                
                if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 2) $tags['MEAL_PLAN'] .= "<option selected value=\"2\">High</option>";
                else $tags['MEAL_PLAN'] .= "<option value=\"2\">High</option>";
                
                if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 3) $tags['MEAL_PLAN'] .= "<option selected value=\"3\">Super</option>";
                else $tags['MEAL_PLAN'] .= "<option value=\"3\">Super</option>";
                
                if(isset($_REQUEST[$meal_option_id]) && $_REQUEST[$meal_option_id] == 4) $tags['MEAL_PLAN'] .= "<option selected value=\"4\">None</option>";
                else $tags['MEAL_PLAN'] .= "<option value=\"4\">None</option>";
                
                $tags['MEAL_PLAN'] .= "</select>";
                    
                $body .= PHPWS_Template::processTemplate($tags, 'hms', 'admin/bed_and_id.tpl');
            }
        }
        
        $hall_name = HMS_Building::get_hall_name('id', $_REQUEST['halls']);
        
        $form = &new PHPWS_Form;
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('halls', $_REQUEST['halls']);
        $form->addHidden('floors', $_REQUEST['floors']);
        $form->addHidden('op', 'assign_floor');
        $form->addSubmit('submit', _('Submit Assignments'));
        $form->addSubmit('cancel', _('Cancel Assignments'));
        $form->addSubmit('edit', _('Edit Assignments'));

        $tags = $form->getTemplate();
        $tags['TITLE']      = "Assignment Verification";
        $tags['HALL']       = $hall_name;
        $tags['FLOOR']      = $_REQUEST['floors'];
        $tags['BODY']       = $body;
        $final = PHPWS_Template::processTemplate($tags, 'hms', 'admin/assign_floor.tpl');
        return $final; 
    }

    function verify_assignment($msg = NULL)
    {
        $sql = "
            SELECT
                hms_residence_hall.hall_name,
                hms_residence_hall.banner_building_code,
                hms_beds.banner_id,
                hms_assignment.asu_username
            FROM hms_residence_hall
            JOIN hms_floor ON 
                hms_floor.building = hms_residence_hall.id
            JOIN hms_room ON
                hms_room.floor_id = hms_floor.id
            JOIN hms_bedrooms ON
                hms_bedrooms.room_id = hms_room.id
            JOIN hms_beds ON
                hms_beds.bedroom_id = hms_bedrooms.id
            WHERE
                hms_residence_hall.deleted = 0 AND
                hms_floor.deleted = 0 AND
                hms_room.deleted = 0 AND
                hms_bedrooms.deleted = 0 AND
                hms_beds.deleted = 0 AND
                hms_residence_hall.id = {$_REQUEST['halls']} AND
                hms_floor.floor_number = {$_REQUEST['floors']} AND
                hms_room.room_number = '" .
                    $_REQUEST['floors'] .
                    str_pad($_REQUEST['rooms'], 2, '0', STR_PAD_LEFT) . "' AND
                hms_bedrooms.bedroom_letter = '{$_REQUEST['bedroom_letter']}' AND
                hms_beds.bed_letter = '{$_REQUEST['bed_letter']}'
        ";
        $results = PHPWS_DB::getRow($sql);
        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $hall_name    = $results['hall_name'];
        $new_bldg_bid = $results['banner_building_code'];
        $new_room_bid = $results['banner_id'];

        $sql = "
            SELECT
                hms_residence_hall.banner_building_code,
                hms_beds.banner_id
            FROM hms_residence_hall
            JOIN hms_floor ON 
                hms_floor.building = hms_residence_hall.id
            JOIN hms_room ON
                hms_room.floor_id = hms_floor.id
            JOIN hms_bedrooms ON
                hms_bedrooms.room_id = hms_room.id
            JOIN hms_beds ON
                hms_beds.bedroom_id = hms_bedrooms.id
            JOIN hms_assignment ON
                hms_assignment.bed_id = hms_beds.id
            WHERE
                hms_residence_hall.deleted = 0 AND
                hms_floor.deleted = 0 AND
                hms_room.deleted = 0 AND
                hms_bedrooms.deleted = 0 AND
                hms_beds.deleted = 0 AND
                hms_assignment.deleted = 0 AND
                hms_assignment.asu_username = '{$_REQUEST['username']}'
        ";
        $results = PHPWS_DB::getRow($sql);
        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $moved = false;

        if(!empty($results)) {
            $old_bldg_bid = $results['banner_building_code'];
            $old_room_bid = $results['banner_id'];
            $moved = true;
        }

        PHPWS_Core::initModClass('hms','HMS_SOAP.php');
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'create_assignment');
        $form->addHidden('username', $_REQUEST['username']);
        $form->addHidden('hall', $_REQUEST['halls']);
        $form->addHidden('hall_name', $hall_name);
        $form->addHidden('floor', $_REQUEST['floors']);
        $form->addHidden('room', $_REQUEST['rooms']);
        $form->addHidden('room_number', $_REQUEST['floors'] . str_pad($_REQUEST['rooms'], 2, '0', STR_PAD_LEFT));
        $form->addHidden('bedroom_letter', $_REQUEST['bedroom_letter']);
        $form->addHidden('bed_letter', $_REQUEST['bed_letter']);
        $form->addHidden('meal_option', $_REQUEST['meal_option']);
        $form->addSubmit('submit', _('Assign Student'));

        if($moved) {
            $form->addHidden('old_bldg_bid', $old_bldg_bid);
            $form->addHidden('new_bldg_bid', $new_bldg_bid);
            $form->addHidden('old_room_bid', $old_room_bid);
            $form->addHidden('new_room_bid', $new_room_bid);
            $form->addHidden('move', 1);
        } else {
            $form->addHidden('building_bid', $new_bldg_bid);
            $form->addHidden('room_bid',     $new_room_bid);
            $form->addHidden('new', 1);
        }

        $tpl = $form->getTemplate();
        $tpl['MESSAGE'] = "<h2>You are assigning user: " . $_REQUEST['username'] . "</h2>";
        $tpl['MESSAGE'] .= $msg;
        $tpl['HALLS']   = $hall_name; 
        $tpl['FLOORS']  = $_REQUEST['floors'];
        $tpl['ROOMS']   = $_REQUEST['floors'] . str_pad($_REQUEST['rooms'], 2, '0', STR_PAD_LEFT);
        $tpl['BEDROOM_LETTER']  = $_REQUEST['bedroom_letter'];
        $tpl['BED_LETTER']  = $_REQUEST['bed_letter'];

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_hall_floor_room.tpl');
        return $final;
    }

    function verify_deletion()
    {
        $db = new PHPWS_DB('hms_assignment');
        $db->addWhere('asu_username',          $_REQUEST['username'], 'ILIKE');
        $db->addWhere('hms_assignment.bed_id', 'hms_beds.id');
        $db->addWhere('hms_beds.bedroom_id',   'hms_bedrooms.id');
        $db->addWhere('hms_bedrooms.room_id',  'hms_room.id');
        $db->addWhere('hms_room.floor_id',     'hms_floor.id');
        $db->addWhere('hms_floor.building',    'hms_residence_hall.id');
        $db->addWhere('hms_assignment.deleted', 0);
        $db->addWhere('hms_beds.deleted', 0);
        $db->addWhere('hms_bedrooms.deleted', 0);
        $db->addWhere('hms_room.deleted', 0);
        $db->addWhere('hms_floor.deleted', 0);
        $db->addWhere('hms_residence_hall.deleted', 0);
        $db->addColumn('hms_assignment.asu_username');
        $db->addColumn('hms_beds.bed_letter');
        $db->addColumn('hms_bedrooms.bedroom_letter');
        $db->addColumn('hms_room.displayed_room_number');
        $db->addColumn('hms_floor.floor_number');
        $db->addColumn('hms_residence_hall.hall_name');
        $db->addColumn('hms_beds.banner_id');
        $db->addColumn('hms_residence_hall.banner_building_code');
        $assignment = $db->select('row');

        if(is_null($assignment)) {
            return false;
        }

        $hall_name      = $assignment['hall_name'];
        $floor_number   = $assignment['floor_number'];
        $room_number    = $assignment['displayed_room_number'];
        $bedroom_letter = $assignment['bedroom_letter'];
        $bed_letter     = $assignment['bed_letter'];

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'assignment');
        $form->addHidden('op', 'delete_assignment');
        $form->addHidden('assignment_id', $assignment['id']);
        $form->addHidden('asu_username', $assignment['asu_username']);
        $form->addHidden('hall_name', $hall_name);
        $form->addHidden('room_number', $room_number);
        $form->addHidden('room_bid', $assignment['banner_id']);
        $form->addHidden('building_bid', $assignment['banner_building_code']);
        $form->addSubmit('submit', _('Delete Assignment'));

        $tpl = $form->getTemplate();
        $tpl['MESSAGE'] = "<h2>You are deleting the room assignment for: " .
                          $assignment['asu_username'] . "</h2>";
        $tpl['HALLS']          = $hall_name; 
        $tpl['FLOORS']         = $floor_number;
        $tpl['ROOMS']          = $room_number;
        $tpl['BEDROOM_LETTER'] = $bedroom_letter;
        $tpl['BED_LETTER']     = $bed_letter;

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_hall_floor_room.tpl');
        return $final;
    }

    function select_username_for_edit_grouping()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = &new DBPager('hms_roommates', 'HMS_Roommate');
        $pager->setModule('hms');
        $pager->setTemplate('admin/roommate_search_results.tpl');
        
        $pager->db->addWhere('roommate_zero', '%' . $_REQUEST['username'] . '%', 'ILIKE', 'OR');
        $pager->db->addWhere('roommate_one', '%' . $_REQUEST['username'] . '%', 'ILIKE', 'OR');
        $pager->db->addWhere('roommate_two', '%' . $_REQUEST['username'] . '%', 'ILIKE', 'OR');
        $pager->db->addWhere('roommate_three', '%' . $_REQUEST['username'] . '%', 'ILIKE', 'OR');

        $pager->addRowTags('get_row_pager_tags');
        return $pager->get();
    }

    function edit_grouping()
    {
        if(isset($_REQUEST['id'])) {
            $db = new PHPWS_DB('hms_roommates');
            $db->addWhere('id', $_REQUEST['id']);
           
            PHPWS_Core::initModClass('hms', 'HMS_Roommate');

            $grouping = new HMS_Roommate;
            $grouping_id = $db->loadObject($grouping);

            PHPWS_Core::initCoreClass('Forms.php');
            $form = new PHPWS_Form;
            $form->addText('first_roommate', $grouping->get_roommate_zero());
            $form->addText('second_roommate', $grouping->get_roommate_one());
            $form->addText('third_roommate', $grouping->get_roommate_two());
            $form->addText('fourth_roommate', $grouping->get_roommate_three());

            $form->addHidden('module', 'hms');
            $form->addHidden('type', 'roommate');
            $form->addHidden('op', 'save_grouping');
            $form->addHidden('id', $grouping->get_id());
            $form->addSubmit('submit', _('Save Group'));

            $tpl = $form->getTemplate();

            $tpl['FIRST_ROOMMATE_NAME']     = "Kevin Michael Wilcox";
            $tpl['FIRST_ROOMMATE_YEAR']     = "Sophomore";
            $tpl['SECOND_ROOMMATE_NAME']    = "Joe Dirt";
            $tpl['SECOND_ROOMMATE_YEAR']    = "Junior";
        
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_roommates.tpl');
            return $final;
        }

        return HMS_Forms::get_username_for_edit_grouping();
    }

    function verify_break_grouping()
    {
        $db = new PHPWS_DB('hms_roommates');
        $db->addWhere('id', $_REQUEST['id']);

        PHPWS_Core::initModClass('hms', 'HMS_Roommate');

        $grouping   = new HMS_Roommate;
        $success    = $db->loadObject($grouping);

        PHPWS_Core::initCoreClass('Forms.php');
        $form = new PHPWS_Form;

        $form->addCheckbox('email_first_roommate');
        $form->addCheckbox('email_second_roommate');
        
        if($grouping->get_roommate_two() != NULL) {
            $form->addCheckbox('email_third_roommate');
        }
       
       if($grouping->get_roommate_three() != NULL) {
            $form->addCheckbox('email_fourth_roommate');
        }

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'roommate');
        $form->addHidden('op', 'break_grouping');
        $form->addHidden('id', $_REQUEST['id']);
        $form->addSubmit('submit', _('Break Group'));

        $tpl = $form->getTemplate();

        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        $tpl['FIRST_ROOMMATE']   = $grouping->get_roommate_zero();
        $tpl['FIRST_ROOMMATE']  .= ' - ' . HMS_SOAP::get_name($tpl['FIRST_ROOMMATE']);
        $tpl['SECOND_ROOMMATE']  = $grouping->get_roommate_one();
        $tpl['SECOND_ROOMMATE'] .= ' - ' . HMS_SOAP::get_name($tpl['SECOND_ROOMMATE']);
        $tpl['THIRD_ROOMMATE']   = $grouping->get_roommate_two();
        $tpl['THIRD_ROOMMATE']  .= ' - ' . HMS_SOAP::get_name($tpl['THIRD_ROOMMATE']);
        $tpl['FOURTH_ROOMMATE']  = $grouping->get_roommate_three();
        $tpl['FOURTH_ROOMMATE'] .= ' - ' . HMS_SOAP::get_name($tpl['FOURTH_ROOMMATE']);

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/verify_break_roommates.tpl');
        return $final;
    }

    function select_residence_hall_for_add_room()
    {
        $content = "";
 
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();

        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can add rooms to one!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        $content = "Please select the residence hall that needs more rooms from the list below.<br />";
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'select_floor_for_add_room');
        $form->addSubmit('submit', _('Submit'));
       
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Residence Hall";
        $tpl['CONTENT'] = $content;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_residence_hall_for_add_floor()
    {
        $content = "";

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addColumn('number_floors');
        $allhalls = $db->select();

        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can add floors to one!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
            $content .= $ahall['hall_name'] . " has " . $ahall['number_floors'] . " floors.<br />";
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'add_floor');
        $form->addSubmit('submit', _('Submit'));
       
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Residence Hall";
        $tpl['CONTENT'] = $content;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_residence_hall_for_delete_floor()
    {
        $content = "";

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addColumn('number_floors');
        $allhalls = $db->select();

        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can delete a floor!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
            $content .= $ahall['hall_name'] . " has " . $ahall['number_floors'] . " floors.<br />";
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'confirm_delete_floor');
        $form->addSubmit('submit', _('Submit'));
       
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Residence Hall";
        $tpl['CONTENT'] = $content;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_residence_hall_for_edit()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can edit a Hall!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'edit_residence_hall');
        $form->addSubmit('submit', _('Edit Hall'));
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Hall to Edit";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_residence_hall_for_overview()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addOrder('hall_name ASC');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can view it!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'view_residence_hall');
        $form->addSubmit('submit', _('View Hall'));
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Hall to View";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_residence_hall_for_edit_floor()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addOrder('hall_name');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can edit a Floor!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'floor');
        $form->addHidden('op', 'select_floor_for_edit');
        $form->addSubmit('submit', _('Submit'));
        $tpl = $form->getTemplate();
        $tpl['TITLE']   = "Select a Hall";
        $tpl['CONTENT'] = "Which residence hall has the floor to edit?";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_floor_for_add_room()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['halls']);
        $db->addColumn('number_floors');
        $db->addColumn('hall_name');
        $building = $db->select('row');
        unset($db);
        
        $hall = $building['hall_name'];
        $num_floors = $building['number_floors'];
        unset($building);
     
        $db = new PHPWS_DB('hms_floor');
        $db->addColumn('floor_number');
        $db->addColumn('number_rooms');
        $db->addWhere('building', $_REQUEST['halls']);
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('floor_number', 'ASC');
        $floors = $db->select();

        foreach($floors as $afloor) {
            $floor[$afloor['floor_number']] = $afloor['floor_number'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('floor', $floor);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'display_room_for_add');
        $form->addHidden('hall', $_REQUEST['halls']);
        $form->addSubmit('submit', 'Select Floor');

        $tpl = $form->getTemplate();
        
        $tpl['TITLE']       = "Select a Floor";
        $tpl['MESSAGE']     = "$hall has $num_floors floors. Which floor needs another room?<br />";
       
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_floor_for_edit.tpl');
        return $final;
    }

    function display_room_for_add()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hall_name');
        $db->addWhere('id', $_REQUEST['hall']);
        $hall_name = $db->select('one');

        $floor_number = $_REQUEST['floor'];
   
        $db = &new PHPWS_DB('hms_room');
        $sql = "select max(room_number) from hms_room where building_id = " . $_REQUEST['hall'] . " AND floor_number = " . $_REQUEST['floor'] . " AND deleted = 0 ";
        $db->setSQLQuery($sql);
        $room_number = $db->select('one');
        $room_number++;
       
        $db = &new PHPWS_DB('hms_floor');
        $db->addColumn('id');
        $db->addWhere('building', $_REQUEST['hall']);
        $db->addWhere('floor_number', $floor_number);
        $db->addWhere('deleted', '0');
        $floor_id = $db->select('one');

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addRadio('is_online', array(0, 1));
        $form->setLabel('is_online', array(_("No"), _("Yes") ));
        $form->setMatch('is_online', '1');

        $form->addRadio('gender_type', array(0, 1));
        $form->setLabel('gender_type', array(_("Female"), _("Male")));
        $form->setMatch('gender_type', '0');
      
        $form->addRadio('freshman_reserved', array(0, 1));
        $form->setLabel('freshman_reserved', array(_("No"), _("Yes")));
        $form->setMatch('freshman_reserved', '0');

        $bedrooms = array('1'=>'1',
                          '2'=>'2',
                          '3'=>'3',
                          '4'=>'4');
        $form->addDropBox('bedrooms_per_room', $bedrooms);
        $form->setMatch('bedrooms_per_room', '1');

        $form->addDropBox('beds_per_bedroom', $bedrooms);
        $form->setMatch('beds_per_bedroom', '2');

        $db = &new PHPWS_DB('hms_pricing_tiers');
        $prices = $db->select();

        foreach($prices as $price) {
            $pricing[$price['id']] = "$" . $price['tier_value'];
        }
        
        $form->addDropBox('pricing_tier', $pricing);
        $form->setMatch('pricing_tier', '1');
 
        $form->addRadio('is_medical', array(0, 1));
        $form->setLabel('is_medical', array(_("No"), _("Yes")));
        $form->setMatch('is_medical', '0');

        $form->addRadio('is_reserved', array(0, 1));
        $form->setLabel('is_reserved', array(_("No"), _("Yes")));
        $form->setMatch('is_reserved', '0');

        $form->addRadio('ra_room', array(0, 1));
        $form->setLabel('ra_room', array(_("No"), _("Yes")));
        $form->setMatch('ra_room', '0');

        $form->addRadio('private_room', array(0, 1));
        $form->setLabel('private_room', array(_("No"), _("Yes")));
        $form->setMatch('private_room', '0');

        $form->addRadio('is_lobby', array(0, 1));
        $form->setLabel('is_lobby', array(_("No"), _("Yes")));
        $form->setMatch('is_lobby', '0');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'add_room');
        $form->addHidden('building_id', $_REQUEST['hall']);
        $form->addHidden('floor_id', $floor_id);
        $form->addHidden('floor_number', $floor_number);
        $form->addHidden('room_number', $room_number);

        $form->addSubmit('submit', _('Add Room'));

        $tpl                        = $form->getTemplate();
        $tpl['TITLE']               = "Add a Room";
        $tpl['HALL_NAME']           = $hall_name;
        $tpl['FLOOR_NUMBER']        = $floor_number;
        $tpl['ROOM_NUMBER']         = $room_number;

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/add_room.tpl');
        return $final;
    }

    function select_floor_for_edit()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['halls']);
        $db->addColumn('number_floors');
        $db->addColumn('hall_name');
        $building = $db->select('row');
        unset($db);
        
        $hall = $building['hall_name'];
        $num_floors = $building['number_floors'];
        unset($building);
     
        $db = new PHPWS_DB('hms_floor');
        $db->addColumn('floor_number');
        $db->addWhere('building', $_REQUEST['halls']);
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('floor_number', 'ASC');
        $floors = $db->select();

        foreach($floors as $afloor) {
            $floor[$afloor['floor_number']] = $afloor['floor_number'];
        }
        /*
        for($i = 0; $i <= $num_floors; $i++) {
            $floor[$i] = "$i";
        }
        */

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('floor', $floor);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'floor');
        $form->addHidden('op', 'edit_floor');
        $form->addHidden('hall', $_REQUEST['halls']);
        $form->addSubmit('submit', 'Edit Floor');

        $tpl = $form->getTemplate();
        
        $tpl['TITLE']       = "Select a Floor";
        $tpl['MESSAGE']     = "$hall has $num_floors floors. Please select the floor to edit.<br /><br />";
       
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_floor_for_edit.tpl');
        return $final;
    }

    function select_residence_hall_for_edit_room()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can edit a Room!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'select_floor_for_edit_room');
        $form->addSubmit('submit', _('Submit'));
        $tpl = $form->getTemplate();
        $tpl['TITLE']   = "Select a Hall";
        $tpl['CONTENT'] = "Which residence hall has the room to edit?";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }

    function select_floor_for_edit_room()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['halls']);
        $db->addWhere('deleted', 0);
        $db->addColumn('number_floors');
        $db->addColumn('hall_name');
        $building = $db->select('row');
        unset($db);
        
        $hall = $building['hall_name'];
        $num_floors = $building['number_floors'];
        unset($building);
      
        $db = new PHPWS_DB('hms_floor');
        $db->addColumn('floor_number');
        $db->addWhere('building', $_REQUEST['halls']);
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('floor_number', 'ASC');
        $floors = $db->select();

        foreach($floors as $afloor) {
            $floor[$afloor['floor_number']] = $afloor['floor_number'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('floor', $floor);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'select_room_for_edit');
        $form->addHidden('hall', $_REQUEST['halls']);
        $form->addSubmit('submit', 'Submit');

        $tpl = $form->getTemplate();
        
        $tpl['TITLE']       = "Select a floor";
        $tpl['MESSAGE']     = "Which floor has the room to edit?";
        $tpl['HALL']        = "$hall";
        $tpl['NUM_FLOORS']  = "$num_floors";
       
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_floor_for_edit.tpl');
        return $final;
    }

    function select_room_for_edit()
    {
        $db = &new PHPWS_DB('hms_room');
        $db->addColumn('room_number');
        $db->addWhere('building_id', $_REQUEST['hall']);
        $db->addWhere('floor_number', $_REQUEST['floor']);
        $db->addWhere('deleted', '0');
        $db->addOrder('room_number', 'ASC');
        $rooms = $db->select('column');
        
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['hall']);
        $db->addColumn('hall_name');
        $hall_name = $db->select('one');

        foreach($rooms as $room) {
            $room_numbers[$room['room_number']] = $room['room_number'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addDropBox('room', $room_numbers);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'edit_room');
        $form->addHidden('hall', $_REQUEST['hall']);
        $form->addSubmit('submit', _('Edit Room'));
        
        $tpl = $form->getTemplate();

        $tpl['TITLE']       = "Select Room";
        $tpl['HALL']        = $hall_name;
        $tpl['FLOOR']       = $_REQUEST['floor'];
        $tpl['NUM_ROOMS']   = count($room_numbers);

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_room_for_edit.tpl');
        return $final;
    }

    function select_residence_hall_for_delete_room()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can delete a room!!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'select_floor_for_delete_room');
        $form->addSubmit('submit', _('Submit'));
        $tpl = $form->getTemplate();
        $tpl['TITLE']   = "Select a Hall";
        $tpl['CONTENT'] = "Which Hall has the room to delete?";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;

    }

    function select_floor_for_delete_room()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['halls']);
        $db->addColumn('number_floors');
        $db->addColumn('hall_name');
        $building = $db->select('row');
        unset($db);
        
        $hall = $building['hall_name'];
        $num_floors = $building['number_floors'];
        unset($building);
     
        $db = new PHPWS_DB('hms_floor');
        $db->addColumn('floor_number');
        $db->addWhere('building', $_REQUEST['halls']);
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('floor_number', 'ASC');
        $floors = $db->select();

        foreach($floors as $afloor) {
            $floor[$afloor['floor_number']] = $afloor['floor_number'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        $form->addDropBox('floor', $floor);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'select_room_for_delete');
        $form->addHidden('hall', $_REQUEST['halls']);
        $form->addSubmit('submit', 'Select Floor');

        $tpl = $form->getTemplate();
        
        $tpl['TITLE']       = "Select a Floor";
        $tpl['HALL']        = "$hall";
        $tpl['NUM_FLOORS']  = "$num_floors";
       
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_floor_for_delete_room.tpl');
        return $final;

    }

    function select_room_for_delete($msg)
    {
        $db = &new PHPWS_DB('hms_room');
        $db->addColumn('room_number');
        $db->addWhere('building_id', $_REQUEST['hall']);
        $db->addWhere('floor_number', $_REQUEST['floor']);
        $db->addWhere('deleted', '0');
        $db->addOrder('room_number', 'ASC');
        $rooms = $db->select('column');
        
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['hall']);
        $db->addColumn('hall_name');
        $hall_name = $db->select('one');

        foreach($rooms as $room) {
            $room_numbers[$room['room_number']] = $room['room_number'];
        }

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addDropBox('room', $room_numbers);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('floor', $_REQUEST['floor']);
        $form->addHidden('op', 'verify_delete_room');
        $form->addHidden('hall', $_REQUEST['hall']);
        $form->addSubmit('submit', _('Delete Room'));
        
        $tpl = $form->getTemplate();

        $tpl['TITLE']       = "Select Room to Delete";
        $tpl['MESSAGE']     = $msg;
        $tpl['HALL']        = $hall_name;
        $tpl['FLOOR']       = $_REQUEST['floor'];
        $tpl['NUM_ROOMS']   = count($room_numbers);

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_room_for_delete.tpl');
        return $final;
    }

    function verify_delete_room()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hall_name');
        $db->addWhere('id', $_REQUEST['hall']);
        $hall_name = $db->select('one');

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'delete_room');
        $form->addHidden('hall', $_REQUEST['hall']);
        $form->addHidden('room', $_REQUEST['room']);
        $form->addHidden('floor', $_REQUEST['floor']);
        $form->addSubmit('submit', _('Delete Room'));
        
        $tpl = $form->getTemplate();

        $tpl['TITLE']       = "Select Room to Delete";
        $tpl['HALL']        = $hall_name;
        $tpl['ROOM']        = $_REQUEST['room'];

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/verify_delete_room.tpl');
        return $final;
    }

    function select_residence_hall_for_delete()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $allhalls = $db->select();
        
        if($allhalls == NULL) {
            $tpl['TITLE'] = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can delete a Hall!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($allhalls as $ahall) {
            $halls[$ahall['id']] = $ahall['hall_name'];
        }

        $form->addDropBox('halls', $halls);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'delete_residence_hall');
        $form->addSubmit('submit', _('Delete Hall'));
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Hall to Delete";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_residence_hall.tpl');
        return $final;
    }
    
    function confirm_delete_floor()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('number_floors');
        $db->addColumn('hall_name');
        $db->addWhere('id', $_REQUEST['halls']);
        $last_floor = $db->select('row');
        unset($db);

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addHidden('floor', $last_floor['number_floors']);
        $form->addHidden('hall', $_REQUEST['halls']);

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', 'delete_floor');
        $form->addSubmit('delete', _('Yes, delete the floor.'));
        $form->addSubmit('cancel', _('No, cancel.'));

        $tpl            = $form->getTemplate();
        $tpl['TITLE']   = "Confirm Delete";
        $tpl['FLOOR']   = $last_floor['number_floors'];
        $tpl['HALL']    = $last_floor['hall_name'];

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/confirm_delete_floor.tpl');
        return $final;
    }
    
    function select_learning_community_for_delete()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $db = &new PHPWS_DB('hms_learning_communities');
        $all_lcs = $db->select();

        if($all_lcs == NULL) {
            $tpl['TITLE']   = "Error!";
            $tpl['CONTENT'] = "You must add a Learning Community before you can delete a Community!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }

        foreach($all_lcs as $lc) {
            $lcs[$lc['id']] = $lc['community_name'];
        }

        $form->addDropBox('lcs', $lcs);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'rlc');
        $form->addHidden('op', 'confirm_delete_learning_community');
        $form->addSubmit('submit', _('Delete Community'));
        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Select a Community to Delete";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/select_learning_community.tpl');
        return $final;
    }

    /**
     * Moved to HMS_Residence_Hall::edit_residence_hall()
     */
    /**
    function edit_residence_hall()
    {
        PHPWS_Core::initModClass('hms', 'HMS_Building.php');
        $hall = &new HMS_Building;
        $hall->id = $_REQUEST['halls'];
        
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->loadObject($hall);
        unset($db);
      
        $tpl = $this->fill_hall_data_display($hall, 'save_residence_hall') ;
        $tpl['TITLE'] = "Edit Residence Hall";
        $tpl['ERROR'] = $this->error;
        $tpl['BEDROOMS_PER_ROOM'] = $hall->bedrooms_per_room;
        $tpl['BEDS_PER_BEDROOM'] = $hall->beds_per_bedroom;
        switch($hall->numbering_scheme)
        {
            case '0':
                $tpl['NUMBERING_SCHEME'] = "Ground + First";
                break;
            case '1':
                $tpl['NUMBERING_SCHEME'] = "Ground + Second";
                break;
            case '2':
                $tpl['NUMBERING_SCHEME'] = "First + Second";
                break;
            default:
                test($hall, 1);
                break;
        }

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_hall_data.tpl');
        return $final;
    }
    **/

    function edit_floor()
    {
        PHPWS_Core::initModClass('hms', 'HMS_Floor.php');
        $floor = &new HMS_Floor;
        
        $db = &new PHPWS_DB('hms_floor');
        $db->addWhere('floor_number', $_REQUEST['floor']);
        $db->addWhere('building', $_REQUEST['hall']);
        $db->loadObject($floor);
        unset($db);

        if($floor->get_id() == NULL) {
            // error....
            $err = "That floor does not exist!";
            $this->set_error_msg($err);
            $final = $this->select_floor_for_edit();
        } else {
            $tpl = $this->fill_floor_data_display($floor, 'save_floor');
            $tpl['TITLE'] = "Edit Floor";
            $tpl['ERROR'] = $this->error;
            $tpl['FLOOR'] = $floor->get_floor_number();
            $tpl['ROOMS'] = $floor->get_number_rooms();
            $tpl['BEDROOMS_PER_ROOM'] = $floor->get_bedrooms_per_room();
            $tpl['BEDS_PER_BEDROOM'] = $floor->get_beds_per_bedroom();
            $db = &new PHPWS_DB('hms_residence_hall');
            $db->addColumn('hall_name');
            $db->addWhere('id', $_REQUEST['hall']);
            $hallname = $db->select('one');
            $tpl['BUILDING'] = $hallname;
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_floor_data.tpl');
        }
        return $final;
    }

    function edit_room()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $tpl = array();

        $db = &new PHPWS_DB('hms_room');
        $db->addWhere('deleted', '0');
        $db->addWhere('room_number', $_REQUEST['room']);
        $db->addWhere('building_id', $_REQUEST['hall']);
        $room = $db->select('row');

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hall_name');
        $db->addWhere('id', $_REQUEST['hall']);
        $hall_name = $db->select('one');

        $id                 = $room['id'];
        $room_number        = $room['room_number'];
        $disp_room_number   = $room['displayed_room_number'];
        $floor_number       = $room['floor_number'];
        $gender_type        = $room['gender_type'];
        $freshman_reserved  = $room['freshman_reserved'];
        $ra_room            = $room['ra_room'];
        $private_room       = $room['private_room'];
        $is_lobby           = $room['is_lobby'];
        $pricing_tier       = $room['pricing_tier'];
        $bedrooms_per_room  = $room['bedrooms_per_room'];
        $beds_per_bedroom   = $room['beds_per_bedroom'];
        $phone_number       = $room['phone_number'];
        $is_medical         = $room['is_medical'];
        $is_reserved        = $room['is_reserved'];
        $is_online          = $room['is_online'];

        # Check for anyone assigned to this room
        $db = &new PHPWS_DB('hms_bedrooms');
        $db->addWhere('hms_bedrooms.deleted', '0');
        $db->addWhere('hms_bedrooms.room_id', $id);
        $db->addWhere('hms_bedrooms.id', 'hms_beds.bedroom_id');
        $db->addWhere('hms_beds.deleted', '0');
        $db->addWhere('hms_beds.id', 'hms_assignment.bed_id');
        $db->addWhere('hms_assignment.deleted', '0');
        
        $result = $db->select();

        #test($result);

        if(sizeof($result) > 0){
            $room_occupied = TRUE;
        }else{
            $room_occupied = FALSE;
        }
        
        $form->addRadio('is_online', array(0, 1));
        $form->setLabel('is_online', array(_('No'), _('Yes') ));
        $form->setMatch('is_online', $is_online);

        if($room_occupied){
            if($gender_type == FEMALE){
                $tpl['GENDER_MESSAGE'] = "Female";
            }else if($gender_type == MALE){
                $tpl['GENDER_MESSAGE'] = "Male";
            }else if($gender_type == COED){
                $tpl['GENDER_MESSAGE'] = "Coed";
            }else{
                $tpl['GENDER_MESSAGE'] = "Error: Undefined gender";
            }

            $form->addHidden('gender_type', $gender_type);
        }else{
            $form->addRadio('gender_type', array(0, 1, 2));
            $form->setLabel('gender_type', array(_('Female'), _('Male'), _('Coed')));
            $form->setMatch('gender_type', $gender_type);
        }

        $form->addRadio('is_medical', array(0,1));
        $form->setLabel('is_medical', array(_('No'), _('Yes')));
        $form->setMatch('is_medical', $is_medical);

        $form->addRadio('is_reserved', array(0, 1));
        $form->setLabel('is_reserved', array(_('No'), _('Yes')));
        $form->setMatch('is_reserved', $is_reserved);
       
        $form->addText('phone_number');
        $form->setValue('phone_number', $phone_number);

        $form->addRadio('freshman_reserved', array(0, 1));
        $form->setLabel('freshman_reserved', array(_('No'), _('Yes')));
        $form->setMatch('freshman_reserved', $freshman_reserved);

        $form->addRadio('ra_room', array(0, 1));
        $form->setLabel('ra_room', array(_('No'), _('Yes')));
        $form->setMatch('ra_room', $ra_room);

        $form->addRadio('is_lobby', array(0, 1));
        $form->setLabel('is_lobby', array(_('No'), _('Yes')));
        $form->setMatch('is_lobby', $is_lobby);

        $form->addRadio('private_room', array(0, 1));
        $form->setLabel('private_room', array(_('No'), _('Yes')));
        $form->setMatch('private_room', $private_room);

        $form->addText('displayed_room_number');
        $form->setSize('displayed_room_number', 10);
        $form->setValue('displayed_room_number', $disp_room_number);
        
        $form->setSize('phone_number', 8);

        $capacity   =  array('1'=>"1",
                             '2'=>"2",
                             '3'=>"3",
                             '4'=>"4");
        $form->addDropBox('bedrooms_per_room', $capacity);
        $form->setMatch('bedrooms_per_room', $bedrooms_per_room);

        $form->addDropBox('beds_per_bedroom', $capacity);
        $form->setMatch('beds_per_bedroom', $beds_per_bedroom);

        $db = &new PHPWS_DB('hms_pricing_tiers');
        $db->addColumn('id');
        $db->addColumn('tier_value');
        $results = $db->select();
        foreach($results as $result) {
            $tiers[$result['id']] = $result['tier_value'];
        }

        $form->addDropBox('pricing_tier', $tiers);
        $form->setMatch('pricing_tier', $pricing_tier);

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'room');
        $form->addHidden('op', 'save_room');
        $form->addHidden('id', $id);
        $form->addSubmit('submit', _('Submit'));

        $form->mergeTemplate($tpl);
        $tpl = $form->getTemplate();
        
        PHPWS_Core::initModClass('hms', 'HMS_Room.php');
        if(HMS_Room::is_in_suite($id)) {
            $suite_number = HMS_Room::get_suite_number($id);
            $db = &new PHPWS_DB('hms_suite');
            $db->addWhere('id', $suite_number);
            $rooms = $db->select('row');
            
            $tpl['ROOM_ID_ZERO']    = HMS_Room::get_room_number($rooms['room_id_zero']);
            $tpl['ROOM_ID_ONE']     = HMS_Room::get_room_number($rooms['room_id_one']);
            if($rooms['room_id_two'] != NULL) {
                $tpl['ROOM_ID_TWO'] = HMS_Room::get_room_number($rooms['room_id_two']);
            }
            if($rooms['room_id_three'] != NULL) {
                $tpl['ROOM_ID_THREE'] = HMS_Room::get_room_number($rooms['room_id_three']);
            }
            $tpl['EDIT_SUITE_LINK'] = PHPWS_Text::secureLink(_('Edit Suite'), 'hms', array('type'=>'suite', 'op'=>'edit_suite', 'suite'=>$suite_number));
        } else {
            $tpl['ROOM_ID_ZERO'] = "Not in a Suite";
            $tpl['EDIT_SUITE_LINK'] = PHPWS_Text::secureLink(_('Create Suite'), 'hms', array('type'=>'suite', 'op'=>'edit_suite', 'room'=>$id));
        }

        $tpl['TITLE']           = "Edit Room";
        $tpl['HALL_NAME']       = $hall_name;
        $tpl['FLOOR_NUMBER']    = $floor_number;
        $tpl['ROOM_NUMBER']     = $room_number;
        
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_room_data.tpl');
        return $final;
    }

    function add_floor()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('deleted', '0');
        $db->addWhere('id', $_REQUEST['halls']);
        $hall = $db->select('row');
        if($hall == NULL) {
            $tpl['TITLE']   = "Error!";
            $tpl['CONTENT'] = "You must add a Residence Hall before you can add a Floor!<br />";
            $final = PHPWS_Template::process($tpl, 'hms', 'admin/title_and_message.tpl');
            return $final;
        }
        unset($db);

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addRadio('is_online', array(0, 1));
        $form->setLabel('is_online', array(_("No"), _("Yes") ));
        $form->setMatch('is_online', $hall['is_online']);

        $form->addRadio('gender_type', array(0, 1, 2));
        $form->setLabel('gender_type', array(_("Female"), _("Male"), _("Coed")));
        $form->setMatch('gender_type', $hall['gender_type']);
      
        $form->addRadio('freshman_reserved', array(0, 1));
        $form->setLabel('freshman_reserved', array(_("No"), _("Yes")));
        $form->setMatch('freshman_reserved', '0');
      
        $db = &new PHPWS_DB('hms_pricing_tiers');
        $prices = $db->select();

        foreach($prices as $price) {
            $pricing[$price['id']] = "$" . $price['tier_value'];
        }
        
        $form->addDropBox('pricing_tier', $pricing);
        $form->setMatch('pricing_tier', '1');
        $form->addCheckBox('use_pricing_tier');

        $form->addHidden('building', $hall['id']);
        $db = new PHPWS_DB('hms_floor');
        $db->addColumn('floor_number');
        $db->addWhere('building', $hall['id']);
        $db->addWhere('deleted', '1', '!=');
        $results = $db->select();
        $floor_number = 1;
        foreach($results as $result) {
            if($result['floor_number'] > $floor_number) $floor_number = $result['floor_number'];
        }
        $form->addHidden('floor_number', $floor_number + 1);
        $form->addHidden('number_rooms', $hall['rooms_per_floor']);
        $form->addHidden('bedrooms_per_room', $hall['bedrooms_per_room']);
        $form->addHidden('beds_per_bedroom', $hall['beds_per_bedroom']);
        
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('deleted', '0');
        $form->addHidden('op', 'save_new_floor');

        $form->addSubmit('submit', _('Add Floor'));

        $tpl                        = $form->getTemplate();
        $tpl['ERROR']               = $this->error;
        $tpl['TITLE']               = "Add a Floor";
        $tpl['HALL_NAME']           = $hall['hall_name'];
        $tpl['NUMBER_FLOORS']       = $hall['number_floors'];
        $tpl['FLOOR_NUMBER']        = $hall['number_floors'] + 1;
        $tpl['ROOMS_PER_FLOOR']     = $hall['rooms_per_floor'];
        $tpl['BEDROOMS_PER_ROOM']   = $hall['bedrooms_per_room'];
        $tpl['BEDS_PER_BEDROOM']    = $hall['beds_per_bedroom'];

        $final = PHPWS_Template::process($tpl, 'hms', 'admin/add_floor.tpl');
        return $final;
    }

    function add_learning_community($msg)
    {
        PHPWS_Core::initModClass('hms', 'HMS_Learning_Community.php');
        $tpl = HMS_Form::fill_learning_community_data_display();
        $tpl['TITLE'] = "Add a Learning Community";
        $tpl['MESSAGE'] = $msg;
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_learning_community_data.tpl');
        return $final;
    }
    
    function fill_learning_community_data_display($object = NULL)
    {        
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
        
        if(isset($object->community_name)) {
            $form->addText('community_name', $object->community_name);
        } else {
            $form->addText('community_name');
        }

        if(isset($object->abbreviation)) {
            $form->addText('abbreviation', $object->abbreviation);
        } else {
            $form->addText('abbreviation');
        }
        $form->setSize('abbreviation', 5);

        if(isset($object->capacity)) {
            $form->addText('capacity', $object->capacity);
        } else {
            $form->addText('capacity');
        }
        $form->setSize('capacity', 5);

        $db = new PHPWS_DB('hms_learning_communities');
        $db->addColumn('community_name');
        $names = $db->select();

        $community = '';
        if($names != NULL) {
            $community .= "The following Learning Communities exist:<br /><br />";
            foreach($names as $name) {
                $community .= $name['community_name'] . "<br />";
            }
        }

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'rlc');
        $form->addHidden('op', 'save_learning_community');
        if(isset($object->id)) {
            $form->addHidden('id', $object->id);
        }
        $form->addSubmit('submit', _('Save Learning Community'));

        $tpl = $form->getTemplate();
        $tpl['COMMUNITY'] = $community;
        return $tpl;
    }

    function fill_floor_data_display($object = NULL, $op = NULL)
    {
        if(!Current_User::authorized('add_floor') ||
           !Current_User::authorized('edit_floor')) {
            $content = "BAD BAD PERSON!<br />";
            $content .= "This event has been logged.";
            return $content;
        }

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addWhere('id', $_REQUEST['hall']);
        $db->addColumn("hall_name");
        $name = $db->select('one');
        unset($db);

        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addRadio('is_online', array(0, 1));
        $form->setLabel('is_online', array(_("No"), _("Yes")));
        if(isset($object->is_online)) {
            $form->setMatch('is_online', $object->get_is_online());
        } else {
            $form->setMatch('is_online', 2);
        }

        $form->addRadio('gender_type', array(0, 1, 2));
        $form->setLabel('gender_type', array(_("Female"), _("Male"), _("Coed")));
        if(isset($object->gender_type)) {
            $form->setMatch('gender_type', $object->get_gender_type());
        } else {
            $form->setMatch('gender_type', '3');
        }

        $form->addRadio('freshman_reserved', array(0, 1));
        $form->setLabel('freshman_reserved', array(_("No"), _("Yes")));
        if(isset($object->freshman_reserved)) {
            $form->setMatch('freshman_reserved', $object->get_freshman_reserved());
        } else {
            $form->setMatch('freshman_reserved', '0');
        }

        $form->addText('ft_movein', $object->get_ft_movein());
        $form->addText('c_movein', $object->get_c_movein());

        $db = &new PHPWS_DB('hms_pricing_tiers');
        $prices = $db->select();

        foreach($prices as $price) {
            $pricing[$price['id']] = "$" . $price['tier_value'];
        }
        
        $form->addDropBox('pricing_tier', $pricing);
        $form->setMatch('pricing_tier', '1');
        $form->addCheckBox('use_pricing_tier');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'floor');
        $form->addHidden('op', $op);
        $form->addHidden('authkey', Current_User::getAuthKey());
        $form->addHidden('is_new_floor', $object->get_is_new_floor());
        if(isset($object->id)) {
            $form->addHidden('id', $object->id);
            $form->addHidden('floor_number', $object->floor_number);
            $form->addHidden('building', $object->building);
            $form->addHidden('number_rooms', $object->number_rooms);
            $form->addHidden('bedrooms_per_room', $object->bedrooms_per_room);
            $form->addHidden('beds_per_bedroom', $object->beds_per_bedroom);
            $form->addHidden('deleted', '0');
        }
        $form->addSubmit('submit', _('Save Floor'));

        $tpl = $form->getTemplate();
        return $tpl;
    }

    /**
     * Moved to HMS_Residence_Hall::edit_residence_hall
     */
    /*
    function add_residence_hall()
    {
        PHPWS_Core::initModClass('hms', 'HMS_Building.php');
        $hall = &new HMS_Building;
        $hall->set_is_new_building(TRUE);
        $tpl = $this->fill_hall_data_display($hall, 'save_residence_hall');
    
        $halls = '<b>The following halls already exist: <br /><br />';
        $db = new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hall_name');
        $db->addWhere('deleted', '1', '!=');
        $db->addOrder('hall_name', 'ASC');
        $halls_raw = $db->select();
        foreach($halls_raw as $hall_raw) {
            $halls .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $hall_raw['hall_name'] . "<br />";
        }
        $halls .= "</b>";

        $tpl['HALLS']   = $halls;
        $tpl['ERROR'] = $this->error;
        $tpl['TITLE'] = "Add a Residence Hall";
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_hall_data.tpl');
        return $final;
    }
    */
    function display_login_screen()
    {
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;

        $form->addText('asu_username');
        $form->addPassword('password');

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hms');
        $form->addHidden('op', 'login');
        $form->addSubmit('submit', _('Login'));

        $tpl = $form->getTemplate();
        $welcome  = "Welcome to the Housing Management System.<br /><br />";
        $welcome .= "There are multiple parts to this process. These are:<br />";
        $welcome .= " - Logging in<br />";
        $welcome .= " - Agreeing to the Housing License Contract<br />";
        $welcome .= " - Completing a Housing Application<br />";
        $welcome .= " - Completing the Residential Learning Community Application if you wish to participate in a RLC<br />";
        //$welcome .= " - Completing the *OPTIONAL* student profile<br /><br />";
        $welcome .= "Please note that once you complete the Housing Application you do not have to fill out anything else.<br /><br />";
      
        $welcome .= "<br /><br />";
        $welcome .= "<b>If you are experiencing problems please read <a href=\"./index.php?module=webpage&id=1\" target=\"_blank\">this page</a>.";
        $welcome .= "<br /><br />";

        $tpl['WELCOME'] = $welcome;
        $tpl['ERROR']   = $this->get_error_msg();
        $final = PHPWS_Template::process($tpl, 'hms', 'misc/login.tpl');
        return $final;
    }

    /*
    function fill_hall_data_display($object = NULL, $op = NULL)
    {   
        PHPWS_Core::initCoreClass('Form.php');
        $form = &new PHPWS_Form;
       
        if(isset($object->hall_name)) {
            $form->addText('hall_name', $object->hall_name);
        } else {
            $form->addText('hall_name');
        }
  
        
        //$db = &new PHPWS_DB('hms_hall_communities');
        //$comms = $db->select();
        //foreach($comms as $comm) {
        //    $communities[$comm['id']] = $comm['community_name'];
        //}
        //$form->addDropBox('community', $communities);
        //if(isset($object->community)) {
        //    $form->setMatch('community', $object->community);
        //}
        

        $floors = array('1'=>"1",
                        '2'=>"2",
                        '3'=>"3",
                        '4'=>"4",
                        '5'=>"5",
                        '6'=>"6",
                        '7'=>"7",
                        '8'=>"8",
                        '9'=>"9",
                        '10'=>"10",
                        '11'=>"11",
                        '12'=>"12",
                        '13'=>"13",
                        '14'=>"14",
                        '15'=>"15");
        $form->addDropBox('number_floors', $floors);
        if(isset($object->number_floors)) {
            $form->setMatch('number_floors', $object->number_floors);
        }
      
        $form->addDropBox('numbering_scheme', array('0'=>'Ground + First', '1'=>'Ground + Second', '2'=>'First + Second'));
        if(isset($object->numbering_scheme)) {
            $form->setMatch('numbering_scheme', $object->numbering_scheme);
        }

        for($i = 1; $i < 85; $i++) {
            $rooms[$i] = $i;
        }
       
        $form->addDropBox('rooms_per_floor', $rooms);
        if(isset($object->rooms_per_floor)) {
            $form->setMatch('rooms_per_floor', $object->rooms_per_floor);
        } else {
            $form->setMatch('rooms_per_floor', '15');
        }

        $form->addDropBox('bedrooms_per_room', array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4'));
        if(isset($object->bedrooms_per_room)) {
            $form->setMatch('bedrooms_per_room', $object->bedrooms_per_room);
        } else {
            $form->setMatch('bedrooms_per_room', 2);
        }

        $form->addDropBox('beds_per_bedroom', array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4'));
        if(isset($object->beds_per_bedroom)) {
            $form->setMatch('beds_per_bedroom', $object->beds_per_bedroom);
        } else {
            $form->setMatch('beds_per_bedroom', 2);
        }

        $db = &new PHPWS_DB('hms_pricing_tiers');
        $prices = $db->select();

        foreach($prices as $price) {
            $pricing[$price['id']] = "$" . $price['tier_value'];
        }
        
        $form->addDropBox('pricing_tier', $pricing);
        $form->setMatch('pricing_tier', '1');
        $form->addCheckBox('use_pricing_tier');

        $form->addRadio('gender_type', array(0, 1, 2));
        $form->setLabel('gender_type', array(_("Female"), _("Male"), _("Coed")));
        if(isset($object->gender_type)) {
            $form->setMatch('gender_type', $object->gender_type);
        } else {
            $form->setMatch('gender_type', 2);
        }

        $form->addRadio('air_conditioned', array(0,1));
        $form->setLabel('air_conditioned', array(_("No"), _("Yes")));
        if(isset($object->air_conditioned)) {
            $form->setMatch('air_conditioned', $object->air_conditioned);
        } else {
            $form->setMatch('air_conditioned', 0);
        }

      
        $form->addRadio('is_online', array(0, 1));
        $form->setLabel('is_online', array(_("No"), _("Yes")));
        if(isset($object->is_online)) {
            $form->setMatch('is_online', $object->is_online);
        } else {
            $form->setMatch('is_online', 1);
        }

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'hall');
        $form->addHidden('op', $op);
        if(isset($object->id)) {
            $form->addHidden('id', $object->id);
        }

        if($object->get_is_new_building() == TRUE) {
            $form->addHidden('is_new_building', TRUE);
        }

        $form->addSubmit('submit', _('Save Hall'));

        $tpl = $form->getTemplate();
        return $tpl;
    }
    */
    function enter_student_search_data($error = NULL)
    {
        javascript('/modules/hms/autosuggest');
        Layout::addStyle('hms', 'css/autosuggest.css');
        
        $form = &new PHPWS_Form('student_search_form');
        
        $form->addCheckBox('enable_autocomplete');
        $form->setLabel('enable_autocomplete', 'Enable Auto-complete: ');
        $form->setExtra('enable_autocomplete', 'checked');
        
        $form->addText('username');
        $form->setExtra('username', 'autocomplete="off" ');
        
        $form->addSubmit('submit_button', _('Submit'));

        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'student');
        $form->addHidden('op', 'get_matching_students');

        $tpl = $form->getTemplate();
        $tpl['TITLE'] = "Student Search";
        $tpl['MESSAGE'] = "What ASU username would you like to look for?<br />";
        if(isset($error)) {
            $tpl['ERROR'] = $error;
        }
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/get_single_username.tpl');
        return $final;
    }

    function edit_suite($error)
    {
        PHPWS_Core::initModClass('hms', 'HMS_Room.php');

        if($_REQUEST['op'] == "save_suite") {
            $suite_id = HMS_Room::get_suite_number($_REQUEST['room_id_zero']);
        } else if(!is_null($_REQUEST['suite'])) {
            $suite_id = $_REQUEST['suite'];
        } else {
            $suite_id = NULL;
        }

        if($suite_id != NULL) {
            PHPWS_Core::initModClass('hms', 'HMS_Suite.php');
            
            $suite = &new HMS_Suite($suite_id);
             
            $room_list = HMS_Room::get_rooms_on_floor($suite->get_room_id_zero());
            $floor = HMS_Room::get_floor_number($suite->get_room_id_zero());
            $hall_name = HMS_Room::get_hall_name_from_floor_id($suite->get_room_id_zero());
            $room_list['0'] = "Select Room ";

            $form = new PHPWS_Form();
            $form->addDropBox('room_id_one', $room_list);
            $form->addDropBox('room_id_two', $room_list);
            $form->addDropBox('room_id_three', $room_list);
            
            $form->setMatch('room_id_one', $suite->get_room_id_one());            
            if($suite->get_room_id_two()) {
                $form->setMatch('room_id_two', $suite->get_room_id_two());            
            } else {
                $form->setMatch('room_id_two', '0');
            }

            if($suite->get_room_id_three()) {
                $form->setMatch('room_id_three', $suite->get_room_id_three());            
            } else {
                $form->setMatch('room_id_three', '0');
            }
            
            $form->addHidden('room_id_zero', $suite->get_room_id_zero());
            $form->addHidden('type', 'suite');
            $form->addHidden('op', 'verify_save_suite');
            $form->addHidden('new', 'false');
            $form->addHidden('floor', $floor);
            $form->addHidden('suite', $suite_id);
            $form->addHidden('hall', $hall_name);
            $form->addSubmit('submit', _('Save Suite'));

            $tpl = $form->getTemplate();
            $tpl['ROOM_ID_ZERO']    = HMS_Room::get_room_number($suite->get_room_id_zero());

        } else {

            if(isset($_REQUEST['room_id_zero'])) $_REQUEST['room'] = $_REQUEST['room_id_zero'];

            $room_list = HMS_Room::get_rooms_on_floor($_REQUEST['room']);
            $floor = HMS_Room::get_floor_number($_REQUEST['room']);
            $hall_name = HMS_Room::get_hall_name_from_floor_id($_REQUEST['room']);
            $room_list[0] = "Select Room ";
        
            $form = new PHPWS_Form();

            $form->addDropBox('room_id_one', $room_list);
            $form->addDropBox('room_id_two', $room_list);
            $form->addDropBox('room_id_three', $room_list);
           
            if(isset($_REQUEST['room_id_one'])) {
                $form->setMatch('room_id_one', $_REQUEST['room_id_one']);
            } else {
                $form->setMatch('room_id_one', '0');
            }

            if(isset($_REQUEST['room_id_two'])) {
                $form->setMatch('room_id_two', $_REQUEST['room_id_two']);
            } else {
                $form->setMatch('room_id_two', '0');
            }

            if(isset($_REQUEST['room_id_three'])) {
                $form->setMatch('room_id_three', $_REQUEST['room_id_three']);
            } else {
                $form->setMatch('room_id_three', '0');
            }
            
            $form->addHidden('room_id_zero', $_REQUEST['room']);
            $form->addHidden('type', 'suite');
            $form->addHidden('op', 'verify_save_suite');
            $form->addHidden('new', 'true');
            $form->addHidden('floor', $floor);
            $form->addHidden('hall', $hall_name);
            $form->addSubmit('submit', _('Save Suite'));

            $tpl = $form->getTemplate();
            $tpl['ROOM_ID_ZERO']    = $room_list[$_REQUEST['room']];
        } 
      
        $tpl['FLOOR_NUMBER']    = $floor;
        $tpl['HALL_NAME']       = $hall_name;
        $tpl['ERROR']           = $error;

        $content = PHPWS_Template::process($tpl, 'hms', 'admin/display_suite_data.tpl');
        return $content;
    }

    function verify_save_suite()
    {
        PHPWS_Core::initModClass('hms', 'HMS_Suite.php');

        if(HMS_Suite::room_listed_twice()) {
            $msg = "You tried to put a room in this suite twice.";
            return HMS_Form::edit_suite($msg);
        }

        if(!HMS_Suite::check_room_ids_numeric() || !HMS_Suite::check_valid_room_ids()) {
            $msg = "There was an error with those room ID's.";
            return HMS_Form::edit_suite($msg);
        }
        
        if($_REQUEST['new'] == 'true' && HMS_Suite::rooms_in_suite()) {
            $msg = "One or more of the rooms you chose are already in a suite!"; 
            return HMS_Form::edit_suite($msg);
        } else if ($_REQUEST['new'] == false) {
            $suite = &new HMS_Suite($_REQUEST['suite']);
            if(!$suite->rooms_not_in_another_suite()) {
                $msg = "One of the rooms you selected is not eligible for this suite.";
                return HMS_Form::edit_suite($msg);
            }
            unset($suite);
        }
  
        if(!HMS_Suite::rooms_same_gender()) {
            $msg = "You tried to mix rooms of separate genders. Please try different rooms.";
            return HMS_Form::edit_suite($msg);
        }

        $msg = '';

        if($rooms = HMS_Suite::check_if_rooms_are_reserved()) {
            foreach($rooms as $room) {
                $msg .= "Room $room is reserved.<br />";
            }
        }

        if($rooms = HMS_Suite::check_if_rooms_are_medical()) {
            foreach($rooms as $room) {
                $msg .= "Room $room is marked medical.<br />";
            }
        }

        $form = new PHPWS_Form();

        $form->addHidden('room_id_zero', $_REQUEST['room_id_zero']);
        $form->addHidden('room_id_one', $_REQUEST['room_id_one']);
        $form->addHidden('room_id_two', $_REQUEST['room_id_two']);
        $form->addHidden('room_id_three', $_REQUEST['room_id_three']);

        if(isset($_REQUEST['suite'])) $form->addHidden('suite', $_REQUEST['suite']);
        $form->addHidden('floor', $_REQUEST['floor']);
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'suite');
        $form->addHidden('op', 'save_suite');
        $form->addHidden('new', $_REQUEST['new']);
        $form->addSubmit('submit', _('Save Suite'));
        $form->addSubmit('cancel', _('Cancel'));

        $tpl = $form->getTemplate();

        PHPWS_Core::initModClass('hms', 'HMS_Room.php');

        $tpl['ROOM_ID_ZERO']    = HMS_Room::get_room_number($_REQUEST['room_id_zero']);
        $tpl['ROOM_ID_ONE']     = HMS_Room::get_room_number($_REQUEST['room_id_one']);
        $tpl['ROOM_ID_TWO']     = HMS_Room::get_room_number($_REQUEST['room_id_two']);
        $tpl['ROOM_ID_THREE']   = HMS_Room::get_room_number($_REQUEST['room_id_three']);
        $tpl['FLOOR_NUMBER']    = $_REQUEST['floor'];
        $tpl['HALL_NAME']       = $_REQUEST['hall'];
        $tpl['TITLE']           = "Verify Saving Suite";
        $tpl['ERROR']           = $msg;
        
        $content = PHPWS_Template::process($tpl, 'hms', 'admin/display_suite_data.tpl');
        return $content;
    }

    function show_primary_admin_panel()
    {
        $residence_halls = array("Residence Halls");

        # TO_DO - Populate $room_types variable with an array from database;
        $room_types = array("Room Types");

        $meal_plans = array(0 => "Low", 1 => "Standard", 2 => "High", 3 => "Super");

        $months = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "June",
                        7 => "July", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");

        $days = array();
        for($i=1; $i <= 31; $i++){
            $days[$i] = $i;
        }

        $year = date('Y') - 1 ;
        $years = array($year++,$year++,$year++);

        # Create the lookup form
        $lookup_form = &new PHPWS_Form('student_lookup');

        $lookup_form->addText('term');
        $lookup_form->setSize('term',4);
        $lookup_form->setMaxSize('term','3');
        $lookup_form->setTab('term',1);
        $lookup_form->setLabel('term','Term: ');

        $lookup_form->addText('student_id');
        $lookup_form->setLabel('student_id','ID #: ');
        $lookup_form->setSize('student_id',10);
        $lookup_form->setMaxSize('student_id',9);
        $lookup_form->setTab('student_id',2);

        $lookup_form->addDropBox('residence_hall_lookup',$residence_halls);
        $lookup_form->setLabel('residence_hall_lookup','Hall: ');
        $lookup_form->setTab('residence_hall_lookup',3);

        $lookup_form->addText('room_num_lookup');
        $lookup_form->setLabel('room_num_lookup','RM #: ');
        $lookup_form->setSize('room_num_lookup',4);
        $lookup_form->setMaxSize('room_num_lookup',4);
        $lookup_form->setTab('room_num_lookup',4);

        $bed_nums = array(1 => "1", 2 => "2", 3 => "3", 4 => "4");
        $lookup_form->addDropBox('bed_num_lookup',$bed_nums);
        $lookup_form->setLabel('bed_num_lookup','Bed #: ');
        $lookup_form->setTab('bed_num_lookup',5);

        $lookup_form->addSubmit('lookup_submit','Submit');
        $lookup_form->setTab('lookup_submit',6);

        # Create the display form
        $display_form = & new PHPWS_Form('display_form');

        # Personal Information
        $display_form->addText('first_name');
        $display_form->setLabel('first_name','First Name: ');
        $display_form->setSize('first_name',15);
        $display_form->setMaxSize('first_name',25);
        $display_form->setTab('first_name',7);

        $display_form->addText('last_name');
        $display_form->setLabel('last_name','Last Name: ');
        $display_form->setSize('last_name',15);
        $display_form->setMaxSize('last_name',25);
        $display_form->setTab('last_name',8);

        $display_form->addText('middle_initial');
        $display_form->setLabel('middle_initial','Middle Initial: ');
        $display_form->setSize('middle_initial',1);
        $display_form->setMaxSize('middle_initial',1);
        $display_form->setTab('middle_initial',9);

        $display_form->addText('email');
        $display_form->setLabel('email','Email Address: ');
        $display_form->setSize('email',30);
        $display_form->setMaxSize('email',50);
        $display_form->setTab('email',10);

        $display_form->addText('cell_phone');
        $display_form->setLabel('cell_phone','Cell Phone #: ');
        $display_form->setSize('cell_phone',13);
        $display_form->setMaxSize('cell_phone',13);
        $display_form->setTab('cell_phone',11);

        $display_form->addDropBox('dob_month', $months);
        $display_form->setLabel('dob_month','Date of Birth: ');
        $display_form->setTab('dob_month',12);

        $display_form->addDropBox('dob_day',$days);
        $display_form->setTab('dob_day',13);

        $display_form->addDropBox('dob_year',$years);
        $display_form->setTab('dob_year',14);

        $display_form->addRadio('class_status', array(1,2,3,4,5));
        $display_form->setLabel('class_status',array('', 'FR','SO','JR','SR','GR'));
        $display_form->setTab('class_status',15);

        $display_form->addRadio('student_type',array('freshmen','returning','transfer'));
        $display_form->setLabel('student_type',array('Freshmen','Returning','Transfer'));
        $display_form->setTab('student_type',16);

        $display_form->addRadio('gender',array('male','female'));
        $display_form->setLabel('gender',array('Male','Female'));
        $display_form->setTab('gender',17);

        $display_form->addDropBox('application_received_month',$months);
        $display_form->setLabel('application_received_month','Application Received: ');
        $display_form->setTab('application_received_month',18);

        $display_form->addDropBox('application_received_day',$days);
        $display_form->setTab('application_received_day',19);

        $display_form->addDropBox('application_received_year',$years);
        $display_form->setTab('application_received_year',20);

        # Assignment Information
        $display_form->addDropBox('assign_residence_hall',$residence_halls);
        $display_form->setLabel('assign_residence_hall','Residence Hall: ');
        $display_form->setTab('assign_residence_hall',21);

        $display_form->addText('assign_floor');
        $display_form->setLabel('assign_floor','Floor: ');
        $display_form->setSize('assign_floor',2);
        $display_form->setMaxSize('assign_floor',2);
        $display_form->setTab('assign_floor',22);

        $display_form->addText('assign_room_num');
        $display_form->setLabel('assign_room_num','Room #: ');
        $display_form->setSize('assign_room_num',4);
        $display_form->setMaxSize('assign_room_num',3);
        $display_form->setTab('assign_room_num',23);

        $display_form->addText('assign_bed_num');
        $display_form->setLabel('assign_bed_num','Bed #: ');
        $display_form->setSize('assign_bed_num',2);
        $display_form->setMaxSize('assign_bed_num',2);
        $display_form->setTab('assign_bed_num',24);

        $display_form->addText('assign_phone_num');
        $display_form->setLabel('assign_phone_num','Room Phone #: ');
        $display_form->setSize('assign_phone_num', 13);
        $display_form->setMaxSize('assign_phone_num',13);
        $display_form->setTab('assign_phone_num',25);

        $display_form->addDropBox('assign_room_type',$room_types);
        $display_form->setLabel('assign_room_type','Room Type: ');
        $display_form->setTab('assign_room_type',26);

        $display_form->addDropBox('assign_meal_option',$meal_plans);
        $display_form->setLabel('assign_meal_option','Meal Option: ');
        $display_form->setTab('assign_meal_option',27);

        $display_form->addText('assigned_by');
        $display_form->setLabel('assigned_by','Assigned by: ');
        $display_form->setSize('assigned_by',20);
        $display_form->setMaxSize('assigned_by',30);
        $display_form->setTab('assigned_by',28);

        $display_form->addDropBox('assign_month',$months);
        $display_form->setLabel('assign_month','Assignment Date: ');
        $display_form->setTab('assign_month',29);

        $display_form->addDropBox('assign_day',$days);
        $display_form->setTab('assign_day',30);

        $display_form->addDropBox('assign_year',$years);
        $display_form->setTab('assign_year',31);

        # Preference Information
        $display_form->addRadio('pref_neatness',array(1,0));
        $display_form->setLabel('pref_neatness',array("Neat", "Cluttered"));
        $display_form->setTab('pref_neatness',33);
        $template['PREF_NEATNESS_LBL'] = "Room Condition: ";

        $display_form->addRadio('pref_bedtime',array(1,0));
        $display_form->setLabel('pref_bedtime',array("Early", "Late"));
        $display_form->setTab('pref_bedtime',34);
        $template['PREF_BEDTIME_LBL'] = "Bed time: ";

        $display_form->addRadio('pref_lifestyle',array(1,0));
        $display_form->setLabel('pref_lifestyle',array("Co-ed", "Single"));
        $display_form->setTab('pref_lifestyle',35);
        $template['PREF_LIFESTYLE_LBL'] = "Lifestyle:";

        # Roommate Information
        $display_form->addText('roomate_name');
        $display_form->setLabel('roomate_name',"Name: ");
        $display_form->setSize('roomate_name',20);
        $display_form->setMaxSize('roomate_name',50);
        $display_form->setTab('roomate_name',43);

        $display_form->addText('roomate_id');
        $display_form->setLabel('roomate_id','ID #: ');
        $display_form->setSize('roomate_id',10);
        $display_form->setMaxSize('roomate_id',9);
        $display_form->setTab('roomate_id',44);

        $display_form->addText('roomate_home_phone');
        $display_form->setLabel('roomate_home_phone','Home Phone #: ');
        $display_form->setSize('roomate_home_phone',13);
        $display_form->setMaxSize('roomate_home_phone',13);
        $display_form->setTab('roomate_home_phone',45);

        $display_form->addText('paired_by');
        $display_form->SetLabel('paired_by','Paired by: ');
        $display_form->setSize('paired_by',25);
        $display_form->setMaxSize('paired_by',50);
        $display_form->setTab('paired_by',46);


        # Deposit Information
        $display_form->addDropBox('deposit_month',$months);
        $display_form->setLabel('deposit_month', 'Date: ');
        $display_form->setTab('deposit_month',36);

        $display_form->addDropBox('deposit_day',$days);
        $display_form->setTab('deposit_day',37);

        $display_form->addDropBox('deposit_year',$years);
        $display_form->setTab('deposit_year',38);

        $display_form->addText('deposit_amount');
        $display_form->setLabel('deposit_amount','Amount: ');
        $display_form->setSize('deposit_amount',8);
        $display_form->setMaxSize('deposit_amount',9);
        $display_form->setTab('deposit_amount',39);

        $display_form->addCheck('waiver_check','1');
        $display_form->setLabel('waiver_check','Waiver: ');
        $display_form->setTab('waiver_check',40);

        $display_form->addRadio('forfeiture',array('refund','credit','forfeit'));
        $display_form->setLabel('forfeiture',array('Refund','Credit', 'Forfeit'));
        $display_form->setTab('forfeiture',41);

        # Withdrawal Information
        $display_form->addRadio('withdrawal',array('registrar','admissions','student','academic_ineligible','noshow','automatic_release','contract_release'));
        $display_form->setLabel('withdrawal',array('Registrars','Admissions','Student','Academic Ineligible','No-show','Automatic Release','Contract Release'));
        $display_form->setTab('withdrawal',42);

        # Merge the forms into the template
        $lookup_form->mergeTemplate($template);
        $template = $lookup_form->getTemplate();

        $display_form->mergeTemplate($template);
        $template = $display_form->getTemplate();

        return PHPWS_Template::process($template,'hms','admin/main_admin_panel.tpl');
    }

    /**
     * Print the student profile form.
     * TODO: Make this check for an existing profile, and set defaults in the form accordingly.
     * (to allow for editing of profiles).
     */
    function show_profile_form()
    {

        require_once(PHPWS_SOURCE_DIR . 'mod/hms/inc/profile_options.php');
        PHPWS_Core::initModClass('hms','HMS_Student_Profile.php');

        $template = array();

        $template['TITLE'] = 'My Profile';
        
        $id = HMS_Student_Profile::check_for_profile($_SESSION['asu_username']);

        $profile = NULL;
        if(PEAR::isError($id)){
            PHPWS_Error::log($id);
            $template['MESSAGE'] = "Sorry, there was an error working with the database. Please contact Housing and Residence Life if you need assistance.";
            return PHPWS_Template::process($template, 'hms', 'student/student_success_failure_message.tpl');
        }else if($id !== FALSE){
            $profile = new HMS_Student_Profile($id);
            $profile_exists = TRUE;
        }else{
            $profile_exists = FALSE;
        }
       
        $profile_form = &new PHPWS_Form('profile_form');
        $profile_form->addHidden('type', 'student');
        $profile_form->addHidden('op','student_profile_submit');
        $profile_form->useRowRepeat();

        /***** About Me *****/
        $profile_form->addCheck('hobbies_checkbox',$hobbies);
        $profile_form->setLabel('hobbies_checkbox',$hobbies_labels);
        //test($profile_form,1);
        $template['HOBBIES_CHECKBOX_QUESTION'] = 'My Hobbies and Interests (check all that apply):';
        if($profile_exists){
            $profile_form->setMatch('hobbies_checkbox',HMS_Student_Profile::get_hobbies_matches($profile));
        }

        $profile_form->addCheck('music_checkbox',$music);
        $profile_form->setLabel('music_checkbox',$music_labels);
        $template['MUSIC_CHECKBOX_QUESTION'] = 'My Music Preferences (check all that apply):';
        if($profile_exists){
            $profile_form->setMatch('music_checkbox',HMS_Student_Profile::get_music_matches($profile));
        }

        $profile_form->addDropBox('political_views_dropbox',$political_views);
        $profile_form->setLabel('political_views_dropbox','I consider myself: ');
        if($profile_exists){
            $profile_form->setMatch('political_views_dropbox',$profile->get_political_view());
        }

        $profile_form->addText('alternate_email');
        $profile_form->setLabel('alternate_email','Alternate email: ');
        if($profile_exists){
            $profile_form->setValue('alternate_email',$profile->get_alternate_email());
        }

        $profile_form->addText('aim_sn');
        $profile_form->setLabel('aim_sn','AIM screen name:');
        if($profile_exists){
            $profile_form->setValue('aim_sn',$profile->get_aim_sn());
        }

        $profile_form->addText('yahoo_sn');
        $profile_form->setLabel('yahoo_sn','Yahoo! screen name: ');
        if($profile_exists){
            $profile_form->setValue('yahoo_sn',$profile->get_yahoo_sn());
        }
        
        $profile_form->addText('msn_sn');
        $profile_form->setLabel('msn_sn','MSN Screen name:');
        if($profile_exists){
            $profile_form->setValue('msn_sn',$profile->get_msn_sn());
        }

        /***** College Life *****/
        $profile_form->addDropBox('intended_major',$majors);
        $profile_form->setLabel('intended_major','My intended academic major: ');
        if($profile_exists){
            $profile_form->setMatch('intended_major',$profile->get_major());
        }

        $profile_form->addDropBox('important_experience',$experiences);
        $profile_form->setLabel('important_experience','I feel the following is the most important part of my college experience: ');
        if($profile_exists){
            $profile_form->setMatch('important_experience',$profile->get_experience());
        }
        
        /***** My Daily Life *****/
        $profile_form->addDropBox('sleep_time',$sleep_times);
        $profile_form->setLabel('sleep_time','I generally go to sleep: ');
        if($profile_exists){
            $profile_form->setMatch('sleep_time',$profile->get_sleep_time());
        }

        $profile_form->addDropBox('wakeup_time',$wakeup_times);
        $profile_form->setLabel('wakeup_time','I generally wake up: ');
        if($profile_exists){
            $profile_form->setMatch('wakeup_time',$profile->get_wakeup_time());
        }

        $profile_form->addDropBox('overnight_guests',$overnight_guests);
        $profile_form->setLabel('overnight_guests','I plan on hosting overnight guests: ');
        if($profile_exists){
            $profile_form->setMatch('overnight_guests',$profile->get_overnight_guests());
        }

        $profile_form->addDropBox('loudness',$loudness);
        $profile_form->setLabel('loudness','In my daily activities (music, conversations, etc.): ');
        if($profile_exists){
            $profile_form->setMatch('loudness',$profile->get_loudness());
        }
        
        $profile_form->addDropBox('cleanliness',$cleanliness);
        $profile_form->setLabel('cleanliness','I would describe myself as: ');
        if($profile_exists){
            $profile_form->setMatch('cleanliness',$profile->get_cleanliness());
        }
        
        $profile_form->addCheck('study_times',$study_times);
        $profile_form->setLabel('study_times',$study_times_labels);
        $template['STUDY_TIMES_QUESTION'] = 'I prefer to study (check all that apply):';
        if($profile_exists){
            $profile_form->setMatch('study_times',HMS_Student_Profile::get_study_matches($profile));
        }
        
        $profile_form->addDropBox('free_time',$free_time);
        $profile_form->setLabel('free_time','If I have free time I would rather: ');
        if($profile_exists){
            $profile_form->setMatch('free_time', $profile->get_free_time());
        }

        $profile_form->addSubmit('Submit');

        $profile_form->mergeTemplate($template);
        $template = $profile_form->getTemplate();

        return PHPWS_Template::process($template,'hms','student/profile_form.tpl');
    }

    function display_reports()
    {
        $form = &new PHPWS_Form;
        $reports = array('housing_apps' =>'Housing Applications Received',
                         'housing_asss' =>'Housing Assignments Made',
                         'unassd_rooms' =>'Currently Unassigned Rooms',
                         'unassd_beds'  =>'Currently Unassigned Beds',
                         'reqd_roommate'=>'Unconfirmed Roommates',
                         'assd_alpha'   =>'Assigned Students',
/*                         'special'      =>'Special Circumstances',
                         'hall_structs' =>'Hall Structures');*/
                         'unassd_apps'  =>'Unassigned Applicants',
                         'no_ban_data'  =>'Students Without Banner Data',
                         'no_deposit'   =>'Assigned Students with No Deposit',
                         'bad_type'     =>'Assigned Students Withdrawn or with Bad Type',
                         'gender'       =>'Gender Mismatches');
        $form->addDropBox('reports', $reports);
        $form->addSubmit('submit', _('Run Report'));
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'reports');
        $form->addHidden('op', 'run_report');
        $tpl = $form->getTemplate();
        $final = PHPWS_Template::process($tpl, 'hms', 'admin/display_reports.tpl');
        return $final;
    }
};
?>
