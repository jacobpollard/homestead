<?php

PHPWS_Core::initModClass('hms', 'RoomChangeParticipantState.php');

/**
 * Model class to represent a student participating in a room change request.
 * @author jbooker
 * @package hms
 */
class RoomChangeParticipant {

    protected $id;

    protected $request_id;
    protected $banner_id;

    protected $from_bed;
    protected $to_bed;

    protected $hall_pref1;
    protected $hall_pref2;
    protected $cell_phone;

    // Stored in a separate DB table, lazy loaded
    protected $state;
    protected $stateChanged;

    /**
     * Create a new RoomChangeParticipant
     *
     * @param RoomChangeRequest $request
     * @param Student $student
     * @param HMS_Bed $fromBed
     */
    public function __construct(RoomChangeRequest $request, Student $student, HMS_Bed $fromBed)
    {
        $this->id = 0;
        $this->request_id    = $request->getId();
        $this->banner_id     = $student->getBannerId();
        $this->from_bed      = $fromBed->getId();

        // Set initial state
        $this->setState(new ParticipantStateNew($this, time(), null, UserStatus::getUsername()));
    }

    public function save()
    {
        $db = PdoFactory::getPdoInstance();

        // Begin a new transaction
        $db->beginTransaction();

        if ($this->id == 0) {
            // Insert for new record
            $params = array(
                    'request_id' => $this->getRequestId(),
                    'banner_id' => $this->getBannerId(),
                    'from_bed' => $this->getFromBed(),
                    'to_bed' => $this->getToBed(),
                    'hall_pref1' => $this->getHallPref1(),
                    'hall_pref2' => $this->getHallPref2(),
                    'cell_phone' => $this->getCellPhone()
            );

            $query = "INSERT INTO hms_room_change_participant (id, request_id, banner_id, from_bed, to_bed, hall_pref1, hall_pref2, cell_phone) VALUES (nextval('hms_room_change_participant_seq'), :request_id, :banner_id, :from_bed, :to_bed, :hall_pref1, :hall_pref2, :cell_phone)";
        } else {
            // Update for existing record
            $params = array(
                    'id'     => $this->getId(),
                    'to_bed' => $this->getToBed(),
                    'hall_pref1' => $this->getHallPref1(),
                    'hall_pref2' => $this->getHallPref2(),
                    'cell_phone' => $this->getCellPhone()
            );
            $query = "UPDATE hms_room_change_participant SET (to_bed, hall_pref1, hall_pref2, cell_phone) = (:to_bed, :hall_pref1, :hall_pref2, :cell_phone) WHERE id = :id";
        }


        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // If this request doesn't have an ID, then save the ID of the row inserted
        if ($this->id == 0) {
            $this->id = $db->lastInsertId('hms_room_change_participant_seq');
        }

        // If state changed, save the new state
        if ($this->stateChanged()) {
            $this->state->save();
        }

        // Close the transaction
        $db->commit();

        return true; // will throw an exception on failure, only returns true for backwards compatability
    }


    public function transitionTo(RoomChangeParticipantState $toState)
    {
        // Be sure we have the latest state
        if(is_null($this->state)){
            $this->getState();
        }

        if (!$this->state->canTransition($toState)) {
            throw new InvalidArgumentException("Invalid state change from: {$this->state->getName()} to {$toState->getName()}.");
        }

        // Set the end date on the current state
        $this->state->setEffectiveUntilDate(time());
        $this->state->update(); // Save changes to current state

        // Set the new state as the current state
        $this->setState($toState);

        $this->state->save(); // Save the new state

        // Send notifications
        $this->state->sendNotification();
    }

    public function setState(RoomChangeParticipantState $toState)
    {
        $this->state = $toState;
        $this->stateChanged = true;
    }

    public function getState()
    {
        PHPWS_Core::initModClass('hms', 'RoomChangeParticipantStateFactory.php');

        $this->state = RoomChangeParticipantStateFactory::getCurrentStateForParticipant($this);

        return $this->state;
    }

    public function stateChanged()
    {
        if ($this->stateChanged) {
            return true;
        }

        return false;
    }

    /*********************
     * Get / Set Methods *
     *********************/
    public function getId()
    {
        return $this->id;
    }

    public function getRequestId()
    {
        return $this->request_id;
    }

    public function getBannerId()
    {
        return $this->banner_id;
    }

    public function getFromBed()
    {
        return $this->from_bed;
    }

    public function getToBed()
    {
        return $this->to_bed;
    }

    public function setToBed(HMS_Bed $bed)
    {
        $this->to_bed = $bed->getId();
    }

    public function getHallPref1()
    {
        return $this->hall_pref1;
    }

    public function setHallPref1(HMS_Residence_Hall $hall)
    {
        $this->hall_pref1 = $hall->getId();
    }

    public function getHallPref2()
    {
        return $this->hall_pref2;
    }

    public function setHallPref2(HMS_Residence_Hall $hall)
    {
        $this->hall_pref2 = $hall->getId();
    }

    public function getCellPhone()
    {
        return $this->cell_phone;
    }

    public function setCellPhone($cell)
    {
        $this->cell_phone = $cell;
    }
}

class RoomChangeParticipantRestored extends RoomChangeParticipant {
    public function __construct(){}
}
?>