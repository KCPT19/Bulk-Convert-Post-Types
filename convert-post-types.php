<?php
/*
Plugin Name: Bulk Convert Post Types
Version: 1.4
Author: KCPT
Author URI: http://KCPT.org
Description: A bulk conversion utility for post types.
License: GPL2
*/

if (! defined ( 'ABSPATH' ))
    die ();


class KCPT_Bulk_Convert_Post_Type
{

    public function __construct ()
    {

        add_action (
                'admin_menu',
                array ( $this, 'adminPage' )
        );

        // i18n
        load_plugin_textdomain ( 'bulk-convert-post-types', '', plugin_dir_path ( __FILE__ ) . '/languages' );

    }

    function adminPage ()
    {

        $css = add_management_page (
                __ ( 'Bulk Convert Post Types', 'bulk-convert-post-types' ),
                __ ( 'Bulk Convert Post Types', 'bulk-convert-post-types' ),
                'manage_options',
                'bulk-convert-post-types',
                array ( $this, 'convertOptions' )
        );

        add_action (
                'admin_head-' . $css,
                array ( $this, 'css' )
        );

    }

    function css ()
    {

        ?>
        <style type="text/css">
            div.categorychecklistbox {
                float: left;
                margin: 1em 1em 1em 0;
            }

            ul.categorychecklist {
                height: 15em;
                width: 20em;
                overflow-y: scroll;
                border: 1px solid #dfdfdf;
                padding: 0 1em;
                background: #fff;
                border-radius: 4px;
                -moz-border-radius: 4px;
                -webkit-border-radius: 4px;
            }

            ul.categorychecklist ul.children {
                margin-left: 1em;
            }

            p.taginput {
                float: left;
                margin: 1em 1em 1em 0;
                width: 22em;
            }

            p.taginput input {
                width: 100%;
            }

            p.filters select {
                width: 24em;
                margin: 1em 1em 1em 0;
            }

            p.submit {
                clear: both;
            }

            p.msg {
                margin-left: 1em;
            }
        </style>
        <?php

    }

    function convertOptions ()
    {

        if ( current_user_can ( 'edit_posts' ) && current_user_can ( 'edit_pages' ) ) {

            $hidden_field_name = 'bulk_convert_post_submit_hidden';

            if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {

                $this->convert ();

            }

            // $hidden_field_name

            ?>
            <div class="wrap">
                <?php if ( ! isset( $_POST[ $hidden_field_name ] ) || $_POST[ $hidden_field_name ] != 'Y' ) { ?>
                    <form method="post">
                        <h2><?php _e ( 'Convert Post Types', 'bulk-convert-post-types' ); ?></h2>

                        <p><?php _e ( 'With great power comes great responsibility. This process could <strong>really</strong> screw up your database. Please <a href="http://www.ilfilosofo.com/blog/wp-db-backup">make a backup</a> before proceeding.',
                                    'bulk-convert-post-types' ); ?></p>
                        <input type="hidden" name="<?php echo esc_attr ( $hidden_field_name ); ?>" value="Y">

                        <p class="filters">
                            <?php
                            $typeselect = '';
                            if ( isset( $_POST[ 'convert_cat' ] ) ) {
                                $convert_cat = $_POST[ 'convert_cat' ];
                            } else {
                                $convert_cat = '';
                            }
                            $post_types = get_post_types ( array ( 'public' => true ) );
                            foreach ( $post_types as $type ) {
                                $typeselect .= "<option value=\"" . esc_attr ( $type ) . "\">";
                                $typeselect .= esc_html ( $type );
                                $typeselect .= "</option>";
                            }
                            ?>
                            <select name="old_post_type">
                                <option value="-1"><?php _e ( "Convert from...",
                                            'bulk-convert-post-types' ); ?></option>
                                <?php echo $typeselect; ?>
                            </select>

                            <select name="new_post_type">
                                <option value="-1"><?php _e ( "Convert to...", 'bulk-convert-post-types' ); ?></option>
                                <?php echo $typeselect; ?>
                            </select>

                            <?php wp_dropdown_categories ( 'name=convert_cat&show_option_none=Limit posts to category...&hide_empty=0&hierarchical=1&selected=' . $convert_cat ); ?>

                            <?php wp_dropdown_pages ( 'name=page_parent&show_option_none=Limit pages to children of...' ); ?>

                        </p>
                        <?php global $wp_taxonomies;
                        $nonhierarchical = ''; ?>
                        <?php if ( is_array ( $wp_taxonomies ) ) : ?>
                            <h4><?php _e ( 'Assign custom taxonomy terms', 'bulk-convert-post-types' ); ?></h4>
                            <?php foreach ( $wp_taxonomies as $tax ) :
                                if ( ! in_array ( $tax->name,
                                        array ( 'nav_menu', 'link_category', 'podcast_format', 'format' ) )
                                ) : ?>
                                    <?php
                                    if ( ! is_taxonomy_hierarchical ( $tax->name ) ) :
                                        // non-hierarchical
                                        $nonhierarchical .= '<p class="taginput"><label>' . esc_html ( $tax->label ) . '<br />';
                                        $nonhierarchical .= '<input type="text" name="' . esc_attr ( $tax->name ) . '" class="widefloat" /></label></p>';
                                    else:
                                        // hierarchical
                                        ?>
                                        <div class="categorychecklistbox">
                                            <label>
                                                <?php echo esc_html ( $tax->label ); ?><br/>
                                                <?php if( $tax->name === "category" ): ?>
                                                        <strong>This will remove all other categories and only set what is selected below.</strong>
                                                <?php endif; ?>

                                            </label>
                                                <ul class="categorychecklist">
                                                    <?php
                                                    wp_terms_checklist ( 0, array (
                                                                    'descendants_and_self' => 0,
                                                                    'selected_cats'        => false,
                                                                    'popular_cats'         => false,
                                                                    'walker'               => null,
                                                                    'taxonomy'             => $tax->name,
                                                                    'checked_ontop'        => true,
                                                            )
                                                    );
                                                    ?>
                                                </ul></div>
                                        <?php
                                    endif;
                                    ?>
                                    <?php
                                endif;
                            endforeach;
                            echo '<br class="clear" />' . $nonhierarchical;
                            ?>

                        <?php endif; ?>

                        <p class="submit">
                            <input type="submit" name="submit" class="primary button"
                                   value="<?php _e ( 'Convert &raquo;', 'bulk-convert-post-types' ); ?>"/>
                        </p>
                    </form>

                <?php } // if $hidden_field_name ?>

            </div>

        <?php } // if user can
    }

    function convert ()
    {

        global $wp_taxonomies;

        $postCount = 0;

        $newPostType  = $_POST[ 'new_post_type' ];
        $oldPostType  = $_POST[ 'old_post_type' ];
        $convertCat   = ( ( isset( $_POST[ 'convert_cat' ] ) and ! empty( $_POST[ 'convert_cat' ] ) ) ? $_POST[ 'convert_cat' ] : false );
        $postParent   = ( ( isset( $_POST[ 'page_parent' ] ) and ! empty ( $_POST[ 'page_parent' ] ) ) ? $_POST[ 'page_parent' ] : false );
        $postCategory = ( ( isset( $_POST[ 'post_category' ] ) and ! empty( $_POST[ 'post_category' ] ) ) ? $_POST[ 'post_category' ] : false );
        $taxonomy     = ( ( isset( $_POST[ 'tax_input' ] ) and ! empty( $_POST[ 'tax_input' ] ) ) ? $_POST[ 'tax_input' ] : false );

        // check for invalid post type choices
        if ( $newPostType == -1 || $oldPostType == -1 ) {
            echo '<p class="error">' . __ ( 'Could not convert posts. One of the post types was not set.',
                            'bulk-convert-post-types' ) . '</p>';

            return;
        }
        if ( ! post_type_exists ( $newPostType ) || ! post_type_exists ( $oldPostType ) ) {
            echo '<p class="error">' . __ ( 'Could not convert posts. One of the selected post types does not exist.',
                            'bulk-convert-post-types' ) . '</p>';

            return;
        }

        $query = array (
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'post_type'      => $oldPostType
        );

        if ( $convertCat && $convertCat > 1 ) {
            $query[ 'cat' ] = $convertCat;
        }

        if ( $postParent && $postParent > 0 ) {
            $query[ 'post_parent' ] = $postParent;
        }

        $items = get_posts ( $query );

        if ( ! is_array ( $items ) ) {
            echo '<p class="error">' .
                    __ ( 'Could not find any posts matching your criteria.', 'bulk-convert-post-types' ) .
                    '</p>';

            return;
        }

        $postCount = count( $items );

        foreach ( $items as $post ) {

            // Update the post into the database
            $update[ 'ID' ] = $post->ID;
            if ( $newPostType and ! $new_post_type_object = get_post_type_object ( $newPostType ) ) {

                echo '<p class="error">' .
                        sprintf (
                                __ ( 'Could not convert post #%d. %s', 'bulk-convert-post-types' ),
                                $post->ID,
                                _ ( 'The new post type was not valid.' )
                        ) .
                        '</p>';

            } else {

                set_post_type ( $post->ID, $new_post_type_object->name );

                // handle post categories now; otherwise all posts will receive the default
                if ( 'post' == $new_post_type_object->name && $postCategory ) {

                    wp_set_post_terms ( $post->ID, $postCategory, 'category', false );

                } elseif( $postCategory and is_array( $postCategory ) ) {

                    $taxonomiesPossible = get_object_taxonomies( $new_post_type_object->name );

                    if( in_array( 'category', $taxonomiesPossible ) ) {

                        wp_set_object_terms( $post->ID, $postCategory, 'category', false );

                    }

                }

                // WPML support. Thanks to Jenny Beaumont! http://www.jennybeaumont.com/post-type-switcher-wpml-fix/
                if ( function_exists ( 'icl_object_id' ) ) {

                    // adjust field 'element_type' in table 'wp_icl_translations'
                    // from 'post_OLDNAME' to 'post_NEWNAME'
                    // the post_id you look for is in column: 'element_id'

                    if ( $post->post_type == 'revision' ) {

                        if ( is_array ( $post->ancestors ) ) {

                            $ID = $post->ancestors[ 0 ];

                        }

                    } else {

                        $ID = $post->ID;

                    }

                    global $wpdb;

                    $wpdb->update (
                            $wpdb->prefix . 'icl_translations',
                            array ( 'element_type' => 'post_' . $new_post_type_object->name ),
                            array ( 'element_id' => $ID, 'element_type' => 'post_' . $post->post_type )
                    );

                    $wpdb->print_error ();
                }
            }

            // set new taxonomy terms
            foreach ( $wp_taxonomies as $tax ) {

                // hierarchical custom taxonomies
                if ( $taxonomy and isset( $taxonomy[ $tax->name ] ) and ! empty( $taxonomy[ $tax->name ] ) and is_array ( $taxonomy[ $tax->name ] ) ) {

                    wp_set_post_terms ( $post->ID, $taxonomy[ $tax->name ], $tax->name, false );
                    echo '<p class="msg">' .
                            sprintf (
                                    __ ( 'Set %s to %s', 'bulk-convert-post-types' ),
                                    $tax->label, $term->$name
                            ) .
                            '</p>';
                }
                // all flat taxonomies
                if ( isset( $_POST[ $tax->name ] ) && ! empty( $_POST[ $tax->name ] ) && 'post_category' != $tax->name ) {

                    wp_set_post_terms ( $post->ID, $_POST[ $tax->name ], $tax->name, false );

                    if ( 'post_category' == $tax->name ) {

                        echo '<p class="msg">' .
                                sprintf (
                                        __ ( 'Set %s to %s', 'bulk-convert-post-types' ),
                                        $tax->label,
                                        join ( ', ', $_POST[ $tax->name ] )
                                ) .
                                '</p>';

                    } else {

                        echo '<p class="msg">' .
                                sprintf (
                                        __ ( 'Set %s to %s', 'bulk-convert-post-types' ),
                                        $tax->label,
                                        $_POST[ $tax->name ]
                                ) .
                                '</p>';

                    }

                }

            }

        }

        echo '<div class="updated"><p><strong>' .
                $postCount .
                __ ( ' posts converted.', 'bulk-convert-post-types' ) .
                '</strong></p></div>';

    }
}

$kcptBulkConvertPostTypes = new KCPT_Bulk_Convert_Post_Type();