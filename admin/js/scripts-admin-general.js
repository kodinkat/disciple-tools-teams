jQuery(function ($) {

  $(document).on('change', '#main_col_team_management_select', function () {
    handle_team_selection();
  });

  $(document).on('click', '#main_col_team_management_new_but', function () {
    handle_new_team_request();
  });

  $(document).on('click', '#main_col_team_details_users_table_all_users_checkbox', function () {
    handle_group_user_selections();
  });

  $(document).on('click', '#main_col_team_details_update_but', function () {
    handle_team_details_updates();
  });

  function handle_team_selection() {
    let selected_team_id = $('#main_col_team_management_select').val();
    let selected_team_label = $('#main_col_team_management_select option:selected').val();

    if (selected_team_id) {

      // Set id & name
      $('#main_col_team_management_form_team_id').val(selected_team_id);
      $('#main_col_team_management_form_team_name').val(selected_team_label);

      // Submit team selection request
      $('#main_col_team_management_form').submit();
    }
  }

  function handle_new_team_request() {

    // Set id to NEW
    $('#main_col_team_management_form_team_id').val('[NEW]');

    // Submit new team request
    $('#main_col_team_management_form').submit();
  }

  function handle_team_details_updates() {

    // Fetch team values
    let team_name = $('#main_col_team_details_header_name').val();
    let team_description = $('#main_col_team_details_header_description').val();

    // Identify selected users
    let team_users = [];
    $('#main_col_team_details_users_table > tbody > tr').each(function () {
      if ($(this).find('#main_col_team_details_users_table_user_checkbox').prop('checked')) {
        team_users.push({
          id: $(this).find('#main_col_team_details_users_table_user_id').val(),
          name: $(this).find('#main_col_team_details_users_table_user_name').val()
        });
      }
    });

    if (team_name && team_description) {

      // Set form values
      $('#main_col_team_details_form_team_name').val(team_name);
      $('#main_col_team_details_form_team_description').val(team_description);
      $('#main_col_team_details_form_team_users').val(JSON.stringify(team_users));

      // Submit updated form details
      $('#main_col_team_details_form').submit();
    }
  }

  function handle_group_user_selections() {
    let users_group_checkbox = $('#main_col_team_details_users_table_all_users_checkbox');

    // Iterate over the users table
    $('#main_col_team_details_users_table tbody tr').each(function () {
      $(this).find('#main_col_team_details_users_table_user_checkbox').prop('checked', users_group_checkbox.prop('checked'));
    });
  }
});
