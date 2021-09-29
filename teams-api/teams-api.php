<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Teams_API
 */
class Disciple_Tools_Teams_API {

    public static function get_teams(): array {
        global $wpdb;

        // Fetch team ids
        $team_ids = $wpdb->get_results( "
        SELECT DISTINCT(pm.post_id)
        FROM $wpdb->posts p
        LEFT JOIN $wpdb->postmeta as pm ON (p.ID = pm.post_id AND pm.meta_key = 'group_type')
        WHERE pm.meta_value = 'team';
        ", ARRAY_A );

        // Fetch team objects
        if ( ! empty( $team_ids ) ) {
            $teams = [];
            foreach ( $team_ids as $id ) {
                $team = DT_Posts::get_post( 'groups', $id['post_id'], false, false );
                if ( ! empty( $team ) && ! is_wp_error( $team ) && isset( $team['group_type']['key'] ) && $team['group_type']['key'] === 'team' ) {
                    $teams[] = $team;
                }
            }

            return $teams;
        }

        return [];
    }

    public static function get_team( $team_id ): array {
        $team = DT_Posts::get_post( 'groups', $team_id, false, false, false );
        if ( ! empty( $team ) && ! is_wp_error( $team ) && isset( $team['group_type']['key'] ) && $team['group_type']['key'] === 'team' ) {
            return $team;
        }

        return [];
    }

    public static function extract_team_member_ids( $members ): array {
        $ids = [];
        if ( ! empty( $members ) && is_array( $members ) ) {
            foreach ( $members as $member ) {
                $ids[] = $member['ID'];
            }
        }

        return $ids;
    }

    public static function create_team( $team_name, $team_description, $team_users ): array {
        $fields                         = [];
        $fields['group_status']         = 'inactive';
        $fields['group_type']           = 'team';
        $fields['name']                 = $team_name;
        $fields['dt_teams_description'] = $team_description;

        if ( ! empty( $team_users ) ) {
            foreach ( $team_users as $user ) {
                $fields['members']['values'][] = [
                    'value' => $user->id
                ];
            }
        }

        $team = DT_Posts::create_post( 'groups', $fields, false, false );
        if ( ! empty( $team ) && ! is_wp_error( $team ) ) {
            return $team;
        }

        return [];
    }

    public static function update_team( $team_id, $team_name, $team_description, $team_users ): array {
        $fields                         = [];
        $fields['name']                 = $team_name;
        $fields['dt_teams_description'] = $team_description;

        // Refresh all members by default, in the event all have been removed!
        self::remove_team_members( $team_id );

        if ( ! empty( $team_users ) ) {
            foreach ( $team_users as $user ) {
                $fields['members']['values'][] = [
                    'value' => $user->id
                ];
            }
        }

        $team = DT_Posts::update_post( 'groups', $team_id, $fields, false, false );
        if ( ! empty( $team ) && ! is_wp_error( $team ) ) {
            return $team;
        }

        return [];
    }

    private static function remove_team_members( $team_id ): bool {
        $team   = self::get_team( $team_id );
        $fields = [];

        if ( ! empty( $team ) && isset( $team['members'] ) && count( $team['members'] ) > 0 ) {
            foreach ( $team['members'] as $member ) {
                $fields['members']['values'][] = [
                    'value'  => $member['ID'],
                    'delete' => true
                ];
            }
        }

        // Update team if members have been flagged for deletion
        if ( ! empty( $fields ) ) {
            $updated_team = DT_Posts::update_post( 'groups', $team_id, $fields, false, false );

            return ( ! empty( $updated_team ) && ! is_wp_error( $updated_team ) );
        }

        return false;
    }

    public static function get_users(): array {
        $users = Disciple_Tools_Users::get_assignable_users_compact( null, true );

        if ( ! empty( $users ) && ! is_wp_error( $users ) ) {
            return $users;
        }

        return [];
    }

    public static function get_user_by_contact_id( $users, $user_contact_id ): array {
        foreach ( $users as $user ) {
            if ( isset( $user['contact_id'] ) && intval( $user['contact_id'] ) === intval( $user_contact_id ) ) {
                return $user;
            }
        }

        return [];
    }

    public static function get_custom_filters( $team_id ): array {
        $custom_filters = [];

        // First, fetch team details
        $team = self::get_team( $team_id );
        if ( ! empty( $team ) ) {

            // Actual user objects on standby!
            $users = self::get_users();

            // For each team member, attempt to locate any assigned filters
            foreach ( $team['members'] ?? [] as $member ) {

                // Get team member's corresponding user details
                $user = self::get_user_by_contact_id( $users, $member['ID'] );
                if ( ! empty( $user ) ) {

                    // Any custom filters...?
                    $filters      = [];
                    $user_filters = self::get_user_filters( false, 'contacts', $user['ID'] );
                    foreach ( $user_filters ?? [] as $filter ) {
                        //if ( isset( $filter['type'] ) && $filter['type'] === 'custom_filter' ) {
                        $filters[] = $filter;
                        //}
                    }

                    // Only package if custom filters have been detected
                    if ( ! empty( $filters ) ) {
                        $custom_filters[ $user['name'] ] = [
                            'user_id'         => $user['ID'],
                            'user_contact_id' => $user['contact_id'],
                            'user_name'       => $user['name'],
                            'filters'         => $filters
                        ];
                    }
                }
            }
        }

        return $custom_filters;
    }

    private static function get_user_filters( $fetch_all_post_types, $post_type, $user_id ) {
        $filters = maybe_unserialize( get_user_option( "saved_filters", $user_id ) );

        if ( $fetch_all_post_types ) {
            return $filters ?? [];
        } else {
            return $filters[ $post_type ] ?? [];
        }
    }

    private static function get_user_filter( $post_type, $user_id, $filter_id ) {
        foreach ( self::get_user_filters( false, $post_type, $user_id ) as $filter ) {
            if ( ! empty( $filter ) && isset( $filter["ID"] ) && ( $filter["ID"] === $filter_id ) ) {
                return $filter;
            }
        }

        return null;
    }

    private static function delete_user_filter( $post_type, $user_id, $filter_id ): bool {
        $filters = self::get_user_filters( true, $post_type, $user_id );
        if ( ! isset( $filters[ $post_type ] ) ) {
            $filters[ $post_type ] = [];
        }

        $index_to_remove = null;
        foreach ( $filters[ $post_type ] as $index => $filter ) {
            if ( $filter["ID"] === $filter_id ) {
                $index_to_remove = $index;
            }
        }

        if ( $index_to_remove !== null ) {
            unset( $filters[ $post_type ][ $index_to_remove ] );
            $filters[ $post_type ] = array_values( $filters[ $post_type ] );
            update_user_option( $user_id, "saved_filters", $filters );

            return true;
        }

        return false;
    }

    private static function save_user_filter( $post_type, $user_id, $filter ): bool {
        if ( ! empty( $filter ) && isset( $filter["ID"] ) ) {
            $filter  = filter_var_array( $filter, FILTER_SANITIZE_STRING );
            $filters = self::get_user_filters( true, $post_type, $user_id );
            if ( ! isset( $filters[ $post_type ] ) ) {
                $filters[ $post_type ] = [];
            }

            $updated = false;
            foreach ( $filters[ $post_type ] as $index => $f ) {
                if ( $f["ID"] === $filter["ID"] ) {
                    $filters[ $post_type ][ $index ] = $filter;
                    $updated                         = true;
                }
            }

            if ( $updated === false ) {
                $filters[ $post_type ][] = $filter;
            }
            update_user_option( $user_id, "saved_filters", $filters );

            return true;
        }

        return false;
    }

    public static function refresh_team_member_filter_associations( $team_id, $current_team_filters, $updated_team_filters ) {

        // First, fetch team details
        $team = self::get_team( $team_id );
        if ( ! empty( $team ) ) {

            // Actual user objects on standby!
            $users = self::get_users();

            // Delete current stale filter associations
            self::update_filter_associations( true, $current_team_filters, $team, $users );

            // Assign newly updated filter associations
            self::update_filter_associations( false, $updated_team_filters, $team, $users );

        }
    }

    private static function update_filter_associations( $delete, $team_filters, $team, $users ) {
        // Ensure team filters are present
        if ( ! empty( $team_filters ) && isset( $team_filters->filters ) ) {
            // Iterate over team filters
            foreach ( $team_filters->filters as $filter ) {
                // Now, iterate over team members
                foreach ( $team['members'] ?? [] as $member ) {
                    // Ignore original filter owner
                    if ( intval( $filter->owner_contact_id ) !== intval( $member['ID'] ) ) {
                        // Get team member's corresponding user details
                        $user = self::get_user_by_contact_id( $users, $member['ID'] );
                        if ( ! empty( $user ) ) {
                            // Process associated filter accordingly
                            if ( $delete ) {
                                self::delete_user_filter( 'contacts', $user['ID'], $filter->id );
                            } else {

                                // Obtain handle onto owner's filter; which is to be copied across team members
                                $owner        = self::get_user_by_contact_id( $users, $filter->owner_contact_id );
                                $owner_filter = self::get_user_filter( 'contacts', $owner['ID'], $filter->id );

                                // If we have a valid filter, then copy away! :)
                                if ( ! empty( $owner_filter ) ) {
                                    self::save_user_filter( 'contacts', $user['ID'], $owner_filter );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
