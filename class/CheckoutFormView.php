<?php

class CheckoutFormView extends View {

    private $student;
    private $hall;
    private $room;
    private $bed;
    private $damages;
    private $checkin;

    public function __construct(Student $student, HMS_Residence_Hall $hall, HMS_Room $room, HMS_Bed $bed, Array $damages = null, Checkin $checkin)
    {
        $this->student      = $student;
        $this->hall         = $hall;
        $this->room         = $room;
        $this->bed          = $bed;
        $this->damages      = $damages;
        $this->checkin      = $checkin;
    }

    public function show()
    {
        $dmgTypes = DamageTypeFactory::getDamageTypeAssoc();

        $tpl = array();

        $tpl['NAME']		= $this->student->getName();
        $tpl['ASSIGNMENT']	= $this->bed->where_am_i();
        $tpl['BANNER_ID'] 	= $this->student->getBannerId();

        $dmgRows = array();

        if (isset($dmg)) {
            foreach ($this->damages as $dmg) {
                $dmgLine = array ();
                $dmgLine['REPORTED_ON'] = date("M j, Y", $dmg->getReportedOn());
                $dmgLine['CATEGORY']    = $dmgTypes[$dmg->getDamageType()]['category'];
                $dmgLine['DESCRIPTION'] = $dmgTypes[$dmg->getDamageType()]['description'];
                $dmgLine['SIDE']        = $dmg->getSide();
                $dmgLine['NOTE']        = $dmg->getNote();
                $dmgRows[] = $dmgLine;
            }
        }

        $tpl['damages_repeat'] = $dmgRows;

        // Setup dialog for adding damages
        $jsParams = array('LINK_SELECT'=>'#addDamageLink');
        javascript('addRoomDamage', $jsParams, 'mod/hms/');

        $dmgCmd = CommandFactory::getCommand('ShowAddRoomDamage');
        $dmgCmd->setRoom($this->room);
        $tpl['ADD_DAMAGE_LINK']  = $dmgCmd->getLink('Add Damage');

        $form = new PHPWS_Form('checkin_form');

        $submitCmd = CommandFactory::getCommand('CheckoutFormSubmit');
        $submitCmd->setBannerId($this->student->getBannerId());
        $submitCmd->setCheckinId($this->checkin->getId());
        $submitCmd->initForm($form);

        // Key not returned
        $form->addCheckAssoc('key_not_returned', array('key_not_returned'=>'Key not returned'));

        // Key code
        $form->addText('key_code');
        $form->setLabel('key_code', 'Key Code &#35;');
        $form->setExtra('key_code', 'autofocus');

        // Improper Checkout
        $form->addCheckAssoc('improper_checkout', array('improper_checkout'=>'Improper Check-out'));

        $form->addSubmit('submit', 'Continue');
        $form->setClass('submit', 'btn btn-primary');

        $form->mergeTemplate($tpl);
        $tpl = $form->getTemplate();

        return PHPWS_Template::process($tpl, 'hms', 'admin/checkoutForm.tpl');
    }

}

?>
