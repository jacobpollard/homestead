<!-- BEGIN table -->
<table cellpadding="4" cellspacing="1" width="99%">
    <tr>
        <th>Name </th>
        <th>1st Choice {RLC_FIRST_CHOICE_ID_SORT}</th>
        <th>2nd Choice {RLC_SECOND_CHOICE_ID_SORT}</th>
        <th>3rd Choice {RLC_THIRD_CHOICE_ID_SORT}</th>
        <th>Gender</th>
        <th>Apply Date {DATE_SUBMITTED_SORT}</th>
        <th>Action</th>
    </tr>
<!-- BEGIN empty_table -->
    <tr>
        <td colspan="11">
            <p>{EMPTY_MESSAGE}</p>
        </td>
    </tr>
<!-- END empty_table -->
<!-- BEGIN listrows -->
    <tr {TOGGLE}>
        <td>{NAME}</td>
        <td>{1ST_CHOICE}</td>
        <td>{2ND_CHOICE}</td>
        <td>{3RD_CHOICE}</td>
        <td>{GENDER}</td>
        <td>{DATE_SUBMITTED}</td>
        <td>{ACTION}</td>
    </tr>
<!-- END listrows -->
</table>
<div class="align-center">
    {TOTAL_ROWS}<br />
    {PAGE_LABEL} {PAGES}<br />
    {LIMIT_LABEL} {LIMITS}
</div>
<!-- END table -->
{SUBMIT}
{END_FORM}
