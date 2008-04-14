<?php

/**
 * Queues up assignments so if we can't SOAP it over to Banner, Housing
 * can still do their jobs
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */

class HMS_Banner_Queue {

    var $id            = 0;
    var $type          = 0;
    var $asu_username  = null;
    var $building_code = null;
    var $bed_code      = null;
    var $meal_plan     = 'HOME';
    var $meal_code     = 0;
    var $term          = 0;
    var $queued_on     = 0;
    var $queued_by     = null;

    function HMS_Banner_Queue($id = 0)
    {
        if(!$id) {
            return;
        }

        $this->id = $id;
        $db = &new PHPWS_DB('hms_banner_queue');
        $db->addWhere('id', $this->id);
        $result = $db->loadObject($this);
        if(!$result || PHPWS_Error::logIfError($result)) {
            $this->id = 0;
        }
    }

    /**
     * Resets this process item to zero values
     */
    function reset()
    {
        $this->id        = 0;
        $this->queued_on = 0;
        $this->queued_by = null;
    }

    /**
     * Sets up the queuer and the timestamp
     */
    function stamp()
    {
        $this->queued_on = mktime();
        $this->queued_by = Current_User::getId();
    }

    /**
     * Saves this queue item
     */
    function save()
    {
        $db = new PHPWS_DB('hms_banner_queue');

        $this->stamp();

        $result = $db->saveObject($this);
        if(!$result || PHPWS_Error::logIfError($result)) {
            return FALSE;
        }
        return TRUE;
    }

    function set_id($id) {
        $this->id = $id;
    }

    function get_id() {
        return $this->id;
    }

    function assign_queue_enabled()
    {
        return PHPWS_Settings::get('hms', 'assign_queue_enabled');
    }

    function enable_assign_queue()
    {
        PHPWS_Settings::set('hms', 'assign_queue_enabled', TRUE);
        PHPWS_Settings::save('hms');
    }

    function disable_assign_queue()
    {
        // TODO: If not empty, error
        PHPWS_Settings::set('hms', 'assign_queue_enabled', FALSE);
        PHPWS_Settings::save('hms');
    }

    /**
     * Queues a Create Assignment
     */
    function queue_create_assignment($username, $term, $bldg, $bed, $mealplan, $mealcode)
    {
        $entry                = new HMS_Banner_Queue();
        $entry->type          = BANNER_QUEUE_ASSIGNMENT;
        $entry->asu_username  = $username;
        $entry->building_code = $bldg;
        $entry->bed_code      = $bed;
        $entry->meal_plan     = $mealplan;
        $entry->meal_code     = $mealcode;
        $entry->term          = $term;

        if(HMS_Banner_Queue::process_immediately()) {
            return $entry->process();
        }

        if(!$entry->save())
            return "DB Error";

        return 0;
    }

    /**
     * Queues a Remove Assignment
     *
     * NOTE: If the queue contains a Create Assignment for the same
     * user to the same room, this will NOT queue a room assignment,
     * but rather will delete the original assignment, UNLESS the 
     * $force_queue flag is set.  The $force_queue flag being true will
     * queue a removal no matter what.
     *
     * MORE NOTE: If this requires immediate processing because banner
     * commits are enabled, the it will be sent straight to Banner,
     * and so the force_queue flag will be ignored.
     */
    function queue_remove_assignment($username, $term, $bldg, $bed, $force_queue = FALSE)
    {
        $entry                = new HMS_Banner_Queue();
        $entry->type          = BANNER_QUEUE_REMOVAL;
        $entry->asu_username  = $username;
        $entry->building_code = $bldg;
        $entry->bed_code      = $bed;
        $entry->term          = $term;

        if(HMS_Banner_Queue::process_immediately()) {
            return $entry->process();
        }

        if($force_queue === TRUE) {
            if(!$entry->save())
                return "DB Error";
            return 0;
        }

        $db = &new PHPWS_DB('hms_banner_queue');
        $db->addWhere('type',          BANNER_QUEUE_ASSIGNMENT);
        $db->addWhere('asu_username',  $username);
        $db->addWhere('building_code', $bldg);
        $db->addWhere('bed_code',      $bed);
        $db->addWhere('term',          $term);
        $result = $db->count();

        if(PHPWS_Error::logIfError($result)) {
            return "DB Error";
        }

        if($result == 0) {
            if(!$entry->save())
                return "DB Error";
            return 0;
        }

        $result = $db->delete();

        if(PHPWS_Error::logIfError($result)) {
            return 'DB Error';
        }

        return 0;
    }

    /**
     * Returns TRUE if an action should be processed immediately (queue is disabled)
     * or FALSE if an action should be queued
     */
    function process_immediately() {
        PHPWS_Core::initModClass('hms', 'HMS_Term.php');
        $queue = HMS_Term::is_banner_queue_enabled(HMS_Term::get_selected_term());
        return $queue == 0;
    }

    /**
     * Processes a queued item.  This can be something actually queued,
     * or an immediate processing because the queue is disabled.
     */
    function process()
    {
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        $result = -1;

        $meal_plan = HMS_SOAP::get_plan_meal_codes($this->asu_username,
                                                   $this->building_code,
                                                   $this->meal_code);

        switch($this->type) {
            case BANNER_QUEUE_ASSIGNMENT:
                $result = HMS_SOAP::report_room_assignment(
                    $this->asu_username,
                    $this->term,
                    $this->building_code,
                    $this->bed_code,
                    $meal_plan['plan'],
                    $meal_plan['meal']);
                if($result == 0) {
                    HMS_Activity_Log::log_activity(
                        $this->asu_username,
                        ACTIVITY_ASSIGNMENT_REPORTED,
                        Current_User::getUsername(),
                        $this->term . ' ' . 
                        $this->building_code . ' ' .
                        $this->bed_code . ' ' .
                        $meal_plan['plan'] . ' ' .
                        $meal_plan['meal']);
                }
                break;
            case BANNER_QUEUE_REMOVAL:
                $result = HMS_SOAP::remove_room_assignment(
                    $this->asu_username,
                    $this->term,
                    $this->building_code,
                    $this->bed_code);
                if($result == 0) {
                    HMS_Activity_Log::log_activity(
                        $this->asu_username,
                        ACTIVITY_REMOVAL_REPORTED,
                        Current_User::getUsername(),
                        $this->term . ' ' .
                        $this->building_code . ' ' .
                        $this->bed_code . ' ');
                }
                break;
        }

        return $result;
    }
}

?>
