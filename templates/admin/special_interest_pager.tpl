<table id="special-interest-table">
    <tr>
        <th>Name {ASU_USERNAME_SORT}</th>
        <th>User Name</th>
        <th>Banner ID</th>
        <th>Action</th>
    </tr>
    <!-- BEGIN empty_table -->
    <tr>
        <td colspan="4">{EMPTY_MESSAGE}</td>
    </tr>
    <!-- END empty_table-->
    <!-- BEGIN listrows -->
    <tr class="{ROW_CLASS}">
        <td>{NAME}</td>
        <td>{USER}</td>
        <td>{BANNER_ID}</td>
        <td>{ACTION}</td>
    </tr>
    <!-- END listrows -->
</table>
<div class="align-center">
    {TOTAL_ROWS}<br />
    {PAGE_LABEL} {PAGES}<br />
    {LIMIT_LABEL} {LIMITS}<br />
    {CSV_REPORT}
</div>
