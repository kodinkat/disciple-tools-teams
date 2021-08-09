<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class Disciple_Tools_Teams_Tile {
    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_filter( 'dt_details_additional_tiles', [ $this, "dt_details_additional_tiles" ], 10, 2 );
        add_filter( "dt_custom_fields_settings", [ $this, "dt_custom_fields" ], 1, 2 );
        //add_action( "dt_details_additional_section", [ $this, "dt_add_section" ], 30, 2 );
    }

    /**
     * This function registers a new tile to a specific post type
     *
     * @param $tiles
     * @param string $post_type
     *
     * @return mixed
     * @todo Change the tile key and tile label
     *
     * @todo Set the post-type to the target post-type (i.e. contacts, groups, trainings, etc.)
     */
    public function dt_details_additional_tiles( $tiles, $post_type = "" ) {
        if ( $post_type === "groups" ) {
            $tiles["disciple_tools_teams"] = [ "label" => __( "Teams", 'disciple_tools' ) ];
        }

        return $tiles;
    }

    /**
     * @param array $fields
     * @param string $post_type
     *
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = "" ) {
        /**
         * @todo set the post type
         */
        if ( $post_type === "groups" ) {
            /**
             * @todo Add the fields that you want to include in your tile.
             *
             * Examples for creating the $fields array
             * Contacts
             * @link https://github.com/DiscipleTools/disciple-tools-theme/blob/256c9d8510998e77694a824accb75522c9b6ed06/dt-contacts/base-setup.php#L108
             *
             * Groups
             * @link https://github.com/DiscipleTools/disciple-tools-theme/blob/256c9d8510998e77694a824accb75522c9b6ed06/dt-groups/base-setup.php#L83
             */

            /**
             * Teams Description text field
             */
            $fields['dt_teams_description'] = [
                'name'        => __( 'Teams - Description', 'disciple_tools' ),
                'description' => _x( 'Teams - Description', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'text',
                'default'     => '',
                'tile'        => 'disciple_tools_teams',
                'icon'        => get_template_directory_uri() . '/dt-assets/images/name.svg',
            ];
        }

        return $fields;
    }

    public function dt_add_section( $section, $post_type ) {
        /**
         * @todo set the post type and the section key that you created in the dt_details_additional_tiles() function
         */
        if ( $post_type === "groups" && $section === "disciple_tools_teams" ) {
            /**
             * These are two sets of key data:
             * $this_post is the details for this specific post
             * $post_type_fields is the list of the default fields for the post type
             *
             * You can pull any query data into this section and display it.
             */
            $this_post        = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <!--
            @todo you can add HTML content to this section.
            -->

            <div class="cell small-12 medium-4">
                <!-- @todo remove this notes section-->
                <strong>You can do a number of customizations here.</strong><br><br>
                All the post-type fields:
                ( <?php echo '<code>' . esc_html( implode( ', ', array_keys( $post_type_fields ) ) ) . '</code>' ?>
                )<br><br>
                All the fields for this post:
                ( <?php echo '<code>' . esc_html( implode( ', ', array_keys( $this_post ) ) ) . '</code>' ?> )<br><br>
            </div>

        <?php }
    }
}

Disciple_Tools_Teams_Tile::instance();
