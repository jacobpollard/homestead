<?php

/*
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/gpl-3.0.html
 */

class SpecialNeedsRequestHtmlView extends ReportHtmlView {
    protected function render()
    {
        $this->tpl = $this->report->getSortedRows();
        parent::render();

        $this->tpl['TERM'] = Term::toString($this->report->getTerm());

        return PHPWS_Template::process($this->tpl, 'hms', 'admin/reports/SpecialNeedsRequest.tpl');
    }
}

?>
