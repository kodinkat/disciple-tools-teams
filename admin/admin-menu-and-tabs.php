<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Teams_Menu
 */
class Disciple_Tools_Teams_Menu {

    public $token = 'disciple_tools_teams';

    private static $_instance = null;

    /**
     * Disciple_Tools_Teams_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Teams_Menu is loaded or can be loaded.
     *
     * @return Disciple_Tools_Teams_Menu instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Teams', 'Teams', 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( ! current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page=' . $this->token . '&tab=';

        ?>
        <div class="wrap">
            <h2>DISCIPLE TOOLS : TEAMS</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>
                <a href="<?php echo esc_attr( $link ) . 'filters' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'filters' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Filters</a>
            </h2>

            <?php
            switch ( $tab ) {
                case "general":
                    $object = new Disciple_Tools_Teams_Tab_General();
                    $object->content();
                    break;
                case "filters":
                    $object = new Disciple_Tools_Teams_Tab_Filters();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}

Disciple_Tools_Teams_Menu::instance();

/**
 * Class Disciple_Tools_Teams_Tab_General
 */
class Disciple_Tools_Teams_Tab_General {
    public function content() {
        // First, handle update submissions
        $this->process_updates();

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /* $this->right_column() */ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php

        // Load scripts
        $this->load_scripts();
    }

    private function load_scripts() {
        wp_enqueue_script( 'dt_teams_admin_general_scripts', plugin_dir_url( __FILE__ ) . 'js/scripts-admin-general.js', [
            'jquery',
            'lodash'
        ], 1, true );
    }

    private function process_updates() {

        // Handle team updates
        if ( isset( $_POST['main_col_team_details_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['main_col_team_details_form_nonce'] ) ), 'main_col_team_details_form_nonce' ) ) {

            // Fetch team details
            $team_id          = isset( $_POST['main_col_team_details_form_team_id'] ) ? sanitize_text_field( wp_unslash( $_POST['main_col_team_details_form_team_id'] ) ) : '';
            $team_name        = isset( $_POST['main_col_team_details_form_team_name'] ) ? sanitize_text_field( wp_unslash( $_POST['main_col_team_details_form_team_name'] ) ) : '';
            $team_description = isset( $_POST['main_col_team_details_form_team_description'] ) ? sanitize_text_field( wp_unslash( $_POST['main_col_team_details_form_team_description'] ) ) : '';
            $team_users       = isset( $_POST['main_col_team_details_form_team_users'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['main_col_team_details_form_team_users'] ) ) ) : [];

            // Assuming we have a valid team id, proceed with update - create new team if needed
            if ( ! empty( $team_id ) ) {
                if ( $team_id === '[NEW]' ) {
                    Disciple_Tools_Teams_API::create_team( $team_name, $team_description, $team_users );
                } else {
                    Disciple_Tools_Teams_API::update_team( $team_id, $team_name, $team_description, $team_users );
                }
            }
        }

    }

    private function fetch_selected_team(): string {
        if ( isset( $_POST['main_col_team_management_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['main_col_team_management_form_nonce'] ) ), 'main_col_team_management_form_nonce' ) ) {
            $selected_team = isset( $_POST['main_col_team_management_form_team_id'] ) ? sanitize_text_field( wp_unslash( $_POST['main_col_team_management_form_team_id'] ) ) : '';

            return ! empty( $selected_team ) ? $selected_team : '';
        }

        return '';
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Team Management</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_team_management(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Display team details accordingly; if selected
        $selected_team_id = $this->fetch_selected_team();
        if ( ! empty( $selected_team_id ) ) {
            $this->main_column_team_details( $selected_team_id );
        }
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    private function main_column_team_management() {
        $teams = Disciple_Tools_Teams_API::get_teams();
        ?>
        <select style="min-width: 100%;" id="main_col_team_management_select">

            <option disabled selected value>-- available teams lists --</option>

            <?php
            if ( ! empty( $teams ) ) {
                foreach ( $teams as $team ) {
                    echo '<option value="' . esc_attr( $team['ID'] ) . '">' . esc_attr( $team['name'] ) . '</option>';
                }
            }
            ?>
        </select>

        <br><br>
        <span style="float:right;">
            <a id="main_col_team_management_new_but"
               class="button float-right"><?php esc_html_e( "New Team", 'disciple_tools' ) ?></a>
        </span>

        <form method="POST" id="main_col_team_management_form">
            <input type="hidden" id="main_col_team_management_form_nonce"
                   name="main_col_team_management_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'main_col_team_management_form_nonce' ) ) ?>"/>

            <input type="hidden" id="main_col_team_management_form_team_id"
                   name="main_col_team_management_form_team_id" value=""/>

            <input type="hidden" id="main_col_team_management_form_team_name"
                   name="main_col_team_management_form_team_name" value=""/>
        </form>
        <?php
    }

    private function main_column_team_details( $team_id ) {
        if ( ! empty( $team_id ) ) {

            $team_title       = 'New Team Details';
            $team_name        = '';
            $team_description = '';
            $team_users       = [];

            if ( $team_id !== '[NEW]' ) {
                $team = Disciple_Tools_Teams_API::get_team( $team_id );
                if ( ! empty( $team ) ) {
                    $team_name        = $team['name'] ?? '';
                    $team_description = $team['dt_teams_description'] ?? '';
                    $team_title       = $team_name . ' Details';
                    $team_users       = $team['members'] ?? [];
                }
            }

            ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php echo esc_attr( $team_title ); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <?php $this->main_column_team_details_header( $team_name, $team_description ); ?>
                        <br>
                        <b>Users</b><br>
                        <hr>
                        <?php $this->main_column_team_details_users( $team_users ); ?>
                        <br>
                        <span style="float:right;">
                            <a id="main_col_team_details_update_but"
                               class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></a>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->

            <form method="POST" id="main_col_team_details_form">
                <input type="hidden" id="main_col_team_details_form_nonce"
                       name="main_col_team_details_form_nonce"
                       value="<?php echo esc_attr( wp_create_nonce( 'main_col_team_details_form_nonce' ) ) ?>"/>

                <input type="hidden" id="main_col_team_details_form_team_id"
                       name="main_col_team_details_form_team_id" value="<?php echo esc_attr( $team_id ); ?>"/>

                <input type="hidden" id="main_col_team_details_form_team_name"
                       name="main_col_team_details_form_team_name" value=""/>

                <input type="hidden" id="main_col_team_details_form_team_description"
                       name="main_col_team_details_form_team_description" value=""/>

                <input type="hidden" id="main_col_team_details_form_team_users"
                       name="main_col_team_details_form_team_users" value="[]"/>
            </form>
            <?php
        }
    }

    private function main_column_team_details_header( $team_name, $team_description ) {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Name</td>
                <td>
                    <input type="text" style="min-width: 100%;" id="main_col_team_details_header_name"
                           value="<?php echo esc_attr( $team_name ); ?>"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Description</td>
                <td>
                    <input type="text" style="min-width: 100%;" id="main_col_team_details_header_description"
                           value="<?php echo esc_attr( $team_description ); ?>"/>
                </td>
            </tr>
        </table>
        <?php
    }

    private function main_column_team_details_users( $team_users ) {
        ?>
        <table class="widefat striped" id="main_col_team_details_users_table">
            <thead>
            <tr>
                <th style="text-align: center;"><input style="margin: 0px;" type="checkbox"
                                                       id="main_col_team_details_users_table_all_users_checkbox"/></th>
                <th>Display Name</th>
            </tr>
            </thead>
            <tbody>

            <?php
            $users = Disciple_Tools_Teams_API::get_users();
            if ( ! empty( $users ) ) {
                $team_user_ids = Disciple_Tools_Teams_API::extract_team_member_ids( $team_users );
                foreach ( $users as $user ) {
                    ?>
                    <tr>
                        <td style="vertical-align: middle;text-align: center;">
                            <input style="margin: 0px;" type="checkbox"
                                   id="main_col_team_details_users_table_user_checkbox" <?php echo ( in_array( $user['contact_id'], $team_user_ids ) ) ? 'checked' : ''; ?> />
                        </td>
                        <td style="vertical-align: middle;">
                            <input type="hidden" id="main_col_team_details_users_table_user_id"
                                   value="<?php echo esc_attr( $user['contact_id'] ); ?>">
                            <input type="hidden" id="main_col_team_details_users_table_user_name"
                                   value="<?php echo esc_attr( $user['name'] ); ?>">
                            <?php echo esc_attr( $user['name'] ); ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>

            </tbody>
        </table>
        <?php
    }
}


/**
 * Class Disciple_Tools_Teams_Tab_Filters
 */
class Disciple_Tools_Teams_Tab_Filters {
    public function content() {
        // First, handle update submissions
        $this->process_updates();

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /* $this->right_column() */ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php

        // Load scripts
        $this->load_scripts();
    }

    private function load_scripts() {
        wp_enqueue_script( 'dt_teams_admin_filters_scripts', plugin_dir_url( __FILE__ ) . 'js/scripts-admin-filters.js', [
            'jquery',
            'lodash'
        ], 1, true );
    }

    private function process_updates() {

        // Team Filter Updates
        if ( isset( $_POST['main_col_team_details_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['main_col_team_details_form_nonce'] ) ), 'main_col_team_details_form_nonce' ) ) {

            // Decode incoming team filters
            $updated_team_filters = ( isset( $_POST['main_col_team_details_form_team'] ) ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['main_col_team_details_form_team'] ) ) ) : null;

            // If incoming object is valid, proceed...
            if ( ! empty( $updated_team_filters ) ) {

                // Ok, now get a handle on existing team filters
                $current_team_filters = $this->get_option_team_filters( $updated_team_filters->team_id );

                // Ensure all team member filter associations are also updated!
                Disciple_Tools_Teams_API::refresh_team_member_filter_associations( $updated_team_filters->team_id, $current_team_filters, $updated_team_filters );

                // Finally, save updated team filters
                $this->update_option_team_filters( $updated_team_filters->team_id, $updated_team_filters );
            }
        }
    }

    private function get_option_team_filters( $team_id ) {
        $option              = get_option( 'dt_teams_filters' );
        $option_team_filters = ( ! empty( $option ) ) ? json_decode( $option ) : (object) [];

        return ( isset( $option_team_filters->{$team_id} ) ) ? $option_team_filters->{$team_id} : null;
    }

    private function update_option_team_filters( $team_id, $updated_team_filters ) {
        $option              = get_option( 'dt_teams_filters' );
        $option_team_filters = ( ! empty( $option ) ) ? json_decode( $option ) : (object) [];

        $option_team_filters->{$team_id} = $updated_team_filters;

        // Save changes.
        update_option( 'dt_teams_filters', json_encode( $option_team_filters ) );
    }

    private function fetch_selected_team(): string {
        if ( isset( $_POST['main_col_available_team_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['main_col_available_team_form_nonce'] ) ), 'main_col_available_team_form_nonce' ) ) {
            $selected_team = isset( $_POST['main_col_available_team_form_team_id'] ) ? sanitize_text_field( wp_unslash( $_POST['main_col_available_team_form_team_id'] ) ) : '';

            return ! empty( $selected_team ) ? $selected_team : '';
        }

        return '';
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Available Teams</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_team(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Display team details accordingly; if selected
        $selected_team_id = $this->fetch_selected_team();
        if ( ! empty( $selected_team_id ) ) {
            $this->main_column_team_details( $selected_team_id );
        }
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    private function main_column_available_team() {
        $teams = Disciple_Tools_Teams_API::get_teams();
        ?>
        <select style="min-width: 100%;" id="main_col_available_team_select">

            <option disabled selected value>-- available teams lists --</option>

            <?php
            if ( ! empty( $teams ) ) {
                foreach ( $teams as $team ) {
                    echo '<option value="' . esc_attr( $team['ID'] ) . '">' . esc_attr( $team['name'] ) . '</option>';
                }
            }
            ?>
        </select>

        <form method="POST" id="main_col_available_team_form">
            <input type="hidden" id="main_col_available_team_form_nonce"
                   name="main_col_available_team_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'main_col_available_team_form_nonce' ) ) ?>"/>

            <input type="hidden" id="main_col_available_team_form_team_id"
                   name="main_col_available_team_form_team_id" value=""/>

            <input type="hidden" id="main_col_available_team_form_team_name"
                   name="main_col_available_team_form_team_name" value=""/>
        </form>
        <?php
    }

    private function main_column_team_details( $team_id ) {
        $team                 = Disciple_Tools_Teams_API::get_team( $team_id );
        $team_custom_filters  = Disciple_Tools_Teams_API::get_custom_filters( $team_id );
        $current_team_filters = $this->get_option_team_filters( $team_id );

        echo '<input id="main_col_team_details_custom_filters_hidden" type="hidden" value="' . esc_attr( json_encode( $team_custom_filters ) ) . '">';

        if ( ! empty( $team ) ) {
            $team_name  = $team['name'] ?? '';
            $team_title = $team_name . ' Filters';

            ?>

            <input id="main_col_team_details_team_id_hidden" type="hidden" value="<?php echo esc_attr( $team_id ); ?>">
            <input id="main_col_team_details_team_name_hidden" type="hidden"
                   value="<?php echo esc_attr( $team_name ); ?>">

            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php echo esc_attr( $team_title ); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <select style="min-width: 100%;" id="main_col_team_details_custom_filters_select">

                            <option disabled selected value>-- available custom filters by team members --</option>

                            <?php
                            foreach ( $team_custom_filters ?? [] as $key => $filters ) {
                                echo '<option disabled value>-- ' . esc_attr( $key ) . ' --</option>';
                                foreach ( $filters['filters'] as $filter ) {

                                    // Only display filters which are owned by members
                                    if ( $this->is_filter_owner( $current_team_filters->filters ?? [], $filter['ID'], $filters['user_contact_id'] ) ) {
                                        $value = $filters['user_contact_id'] . '_' . $filter['ID'];
                                        $label = $filter['name'];
                                        echo '<option value="' . esc_attr( $value ) . '">' . esc_attr( $label ) . '</option>';
                                    }
                                }
                            }
                            ?>

                        </select>
                        <br><br>
                        <span style="float:right;">
                            <a id="main_col_team_details_custom_filters_add_but"
                               class="button float-right"><?php esc_html_e( "Add Filter", 'disciple_tools' ) ?></a>
                        </span>

                        <br><br>
                        <b>Assigned Team Level Custom Filters</b><br>
                        <hr>

                        <table class="widefat striped" id="main_col_team_details_custom_filters_table">
                            <thead>
                            <tr>
                                <th>Filter Name</th>
                                <th>Owner</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Display any already assigned team filters
                            if ( ! empty( $current_team_filters ) && isset( $current_team_filters->filters ) ) {
                                foreach ( $current_team_filters->filters as $filter ) {
                                    $filter_id        = $filter->id;
                                    $filter_name      = $filter->name;
                                    $owner_contact_id = $filter->owner_contact_id;
                                    $owner_name       = $filter->owner_name;
                                    $value            = $owner_contact_id . '_' . $filter_id;
                                    ?>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            <input type="hidden"
                                                   id="main_col_team_details_custom_filters_table_row_filter_id"
                                                   value="<?php echo esc_attr( $value ); ?>">

                                            <input type="hidden"
                                                   id="main_col_team_details_custom_filters_table_row_filter_name"
                                                   value="<?php echo esc_attr( $filter_name ); ?>">

                                            <?php echo esc_attr( $filter_name ); ?>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <input type="hidden"
                                                   id="main_col_team_details_custom_filters_table_row_owner_id"
                                                   value="<?php echo esc_attr( $owner_contact_id ); ?>">

                                            <input type="hidden"
                                                   id="main_col_team_details_custom_filters_table_row_owner_name"
                                                   value="<?php echo esc_attr( $owner_name ); ?>">

                                            <?php echo esc_attr( $owner_name ); ?>
                                        </td>
                                        <td>
                                            <span style="float:right;">
                                                <a class="main-col-team-details-custom-filters-table-row-filter-remove-but button float-right">Remove</a>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            </tbody>
                        </table>

                        <br>
                        <span style="float:right;">
                            <a id="main_col_team_details_custom_filters_update_but"
                               class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></a>
                        </span>

                        <form method="POST" id="main_col_team_details_form">
                            <input type="hidden" id="main_col_team_details_form_nonce"
                                   name="main_col_team_details_form_nonce"
                                   value="<?php echo esc_attr( wp_create_nonce( 'main_col_team_details_form_nonce' ) ) ?>"/>

                            <input type="hidden" id="main_col_team_details_form_team"
                                   name="main_col_team_details_form_team" value=""/>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }
    }

    private function is_filter_owner( $current_team_filters, $filter_id, $user_contact_id ): bool {

        $filter_found = false;

        // Iterate filters, to determine owner match!
        foreach ( $current_team_filters ?? [] as $filter ) {
            if ( ( strval( $filter->id ) === strval( $filter_id ) ) ) {
                $filter_found = true;

                if ( strval( $filter->owner_contact_id ) === strval( $user_contact_id ) ) {
                    return true;
                }
            }
        }

        // As duplicates only appear post member assignments, filters not found (not yet assigned) will default to true!
        return ! $filter_found;
    }
}

