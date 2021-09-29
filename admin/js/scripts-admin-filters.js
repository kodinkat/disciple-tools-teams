jQuery(function ($) {

  $(document).on('change', '#main_col_available_team_select', function () {
    handle_team_selection();
  });

  $(document).on('click', '#main_col_team_details_custom_filters_add_but', function () {
    handle_add_filter_request();
  });

  $(document).on('click', '.main-col-team-details-custom-filters-table-row-filter-remove-but', function (e) {
    handle_remove_filter_request(e);
  });

  $(document).on('click', '#main_col_team_details_custom_filters_update_but', function () {
    handle_add_team_filters_request();
  });

  function handle_team_selection() {
    let selected_team_id = $('#main_col_available_team_select').val();
    let selected_team_label = $('#main_col_available_team_select option:selected').text();

    if (selected_team_id) {

      // Set id & name
      $('#main_col_available_team_form_team_id').val(selected_team_id);
      $('#main_col_available_team_form_team_name').val(selected_team_label);

      // Submit team selection request
      $('#main_col_available_team_form').submit();
    }
  }

  function handle_add_filter_request() {
    let selected_filter_id = $('#main_col_team_details_custom_filters_select').val();
    let selected_filter_label = $('#main_col_team_details_custom_filters_select option:selected').text();

    // Is selection valid and not already added
    if (selected_filter_id && !filter_already_assigned(selected_filter_id)) {

      // Assign new filter row!
      let html = create_filter_row_html(selected_filter_id, selected_filter_label);
      $('#main_col_team_details_custom_filters_table > tbody:last-child').append(html);
    }
  }

  function handle_remove_filter_request(evt) {
    // Obtain handle onto deleted row
    let row = evt.currentTarget.parentNode.parentNode.parentNode;

    // Remove row from parent table
    row.parentNode.removeChild(row);
  }

  function filter_already_assigned(filter_id) {
    let already_assigned = false;

    $('#main_col_team_details_custom_filters_table > tbody > tr').each(function (idx, tr) {
      let current_filter_id = $(tr).find('#main_col_team_details_custom_filters_table_row_filter_id').val();
      if (current_filter_id && current_filter_id === filter_id) {
        already_assigned = true;
      }
    });
    return already_assigned;
  }

  function create_filter_row_html(filter_id, filter_label) {
    let html = '<tr>';

    html += '<td style="vertical-align: middle;">';
    html += '<input type="hidden" id="main_col_team_details_custom_filters_table_row_filter_id" value="' + filter_id + '">'
    html += '<input type="hidden" id="main_col_team_details_custom_filters_table_row_filter_name" value="' + filter_label + '">'
    html += filter_label;
    html += '</td>';

    html += '<td style="vertical-align: middle;">';

    let owner_details = fetch_owner_details(filter_id);
    html += '<input type="hidden" id="main_col_team_details_custom_filters_table_row_owner_id" value="' + owner_details['id'] + '">'
    html += '<input type="hidden" id="main_col_team_details_custom_filters_table_row_owner_name" value="' + owner_details['name'] + '">'
    html += owner_details['name'];
    html += '</td>';

    html += '<td>';
    html += '<span style="float:right;">';
    html += '<a class="main-col-team-details-custom-filters-table-row-filter-remove-but button float-right">Remove</a>';
    html += '</span>';
    html += '</td>';

    html += '</tr>';

    return html;
  }

  function fetch_owner_details(filter_id) {

    let details = {};

    // First, extract contact id prefix
    let filter_id_tokens = filter_id.split('_', 2);

    // Next, fetch custom filters
    let custom_filters = JSON.parse($('#main_col_team_details_custom_filters_hidden').val());

    // Iterate filters in search of contact id match
    for (const [key, value] of Object.entries(custom_filters)) {
      // Both contact ids should be int values!
      if (parseInt(filter_id_tokens[0]) === parseInt(value['user_contact_id'])) {
        details['id'] = value['user_contact_id'];
        details['name'] = value['user_name'];
      }
    }

    return details;
  }

  function handle_add_team_filters_request() {
    let team_id = $('#main_col_team_details_team_id_hidden').val();
    let team_name = $('#main_col_team_details_team_name_hidden').val();
    let filters = [];

    // Only if we have a valid team selected
    if (team_id && team_name) {

      // Iterate over table filter rows
      $('#main_col_team_details_custom_filters_table > tbody > tr').each(function (idx, tr) {

        // Source current row values
        let filter_id = $(tr).find('#main_col_team_details_custom_filters_table_row_filter_id').val();
        let filter_name = $(tr).find('#main_col_team_details_custom_filters_table_row_filter_name').val();
        let owner_contact_id = $(tr).find('#main_col_team_details_custom_filters_table_row_owner_id').val();
        let owner_name = $(tr).find('#main_col_team_details_custom_filters_table_row_owner_name').val();

        // Ensure all filter values present
        if (filter_id && filter_name && owner_contact_id && owner_name) {

          // Create new filter object and add to master filters
          filters.push({
            "id": filter_id.split('_', 2)[1], // Remove owner id prefix!
            "name": filter_name,
            "owner_contact_id": owner_contact_id,
            "owner_name": owner_name
          });
        }
      });
    }

    // Package within a teams object
    let team_obj = {
      "team_id": team_id,
      "team_name": team_name,
      "filters": filters
    };

    // Save updated teams object
    $('#main_col_team_details_form_team').val(JSON.stringify(team_obj));

    // Trigger form post..!
    $('#main_col_team_details_form').submit();
  }

});
