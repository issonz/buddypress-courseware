<?php
/**
 * Class to handle all WordPress admin backend stuff
 */
class BPSP_WordPress {
    /**
     * BPSP_WordPress()
     *
     * Constructor, loads all the required hooks
     */
    function BPSP_WordPress() {
        add_action('admin_menu', array(&$this, 'menus'));
        //Initialize our options
        add_option( 'bpsp_curriculum' );
        add_option( 'bpsp_allow_only_admins' );
        add_option( 'bpsp_worldcat_key' );
        add_option( 'bpsp_isbndb_key' );
    }
    
    /**
     * menus()
     *
     * Adds menus to admin area
     */
    function menus() {
        if ( is_super_admin() )
            add_submenu_page(
                'bp-general-settings',
                __( 'Courseware', 'bpsp' ),
                __( 'Courseware', 'bpsp' ),
                'manage_options',
                'bp-courseware',
                array(&$this, "screen")
            );
    }
    
    /** screen()
     *
     * Handles the wp-admin screen
     */
    function screen() {
        $nonce_name = 'courseware_options';
        $vars = array();
        $vars['nonce'] = wp_nonce_field( $nonce_name, '_wpnonce', true, false );
        $is_nonce = false;
        
        if( isset( $_POST['_wpnonce'] ) )
            check_admin_referer( $nonce_name );
        
        if( isset( $_POST['bpsp_curriculum'] ) )
            if( update_option( 'bpsp_curriculum', strtolower( $_POST['bpsp_curriculum'] ) ) )
                $vars['flash'][] = __( 'Courseware option was updated.' );
        
        if( isset( $_POST['bpsp_allow_only_admins'] ) )
            if( update_option( 'bpsp_allow_only_admins', strtolower( $_POST['bpsp_allow_only_admins'] ) ) )
                $vars['flash'][] = __( 'Courseware option was updated.' );
        if( !isset( $_POST['bpsp_allow_only_admins'] ) && isset( $_POST['bpsp_allow_only_admins_check'] ) )
            if( update_option( 'bpsp_allow_only_admins', '' ) )
                $vars['flash'][] = __( 'Courseware option was updated.' );
        
        if( isset( $_POST['worldcat_key'] ) && !empty( $_POST['worldcat_key'] ) )
            if( update_option( 'bpsp_worldcat_key', $_POST['worldcat_key'] ) )
                $vars['flash'][] = __( 'WorldCat option was updated.' );
        if( isset( $_POST['isbndb_key'] ) && !empty( $_POST['isbndb_key'] ) )
            if( update_option( 'bpsp_isbndb_key', $_POST['isbndb_key'] ) )
                $vars['flash'][] = __( 'ISBNdb option was updated.' );
        
        $current_option = get_option( 'bpsp_curriculum' );
        if( $current_option == 'us' )
            $vars['us'] = $current_option;
        elseif ( $current_option == 'eu' )
            $vars['eu'] = $current_option;
        
        $vars['bpsp_allow_only_admins'] = get_option( 'bpsp_allow_only_admins' );
        $vars['worldcat_key'] = get_option( 'bpsp_worldcat_key' );
        $vars['isbndb_key'] = get_option( 'bpsp_isbndb_key' );
        
        //Load the template
        ob_start();
        extract( $vars );
        include( BPSP_PLUGIN_DIR . '/wordpress/templates/admin.php' );
        echo ob_get_clean();
    }
    
    /**
     * get_posts( $terms, $post_types )
     *
     * A hack to query multiple custom terms
     *
     * @param Mixed $terms, a set of term slugs as keys and taxonomies as values
     * @param Mixed $post_types, a set of post types to query
     * @return Mixed $posts, a set of queried posts
     */
    function get_posts( $terms, $post_types = null ){
        if( !$post_types )
            $post_types = array( 'post' );
        $term_ids = array();
        $post_ids = array();
        $posts = array();
        // Get term ids
        foreach ( $terms as $term => $taxonomy ) {
            $t = get_term_by( 'slug', $taxonomy, $term );
            $term_ids[ $t->term_taxonomy_id ] = $term;
        }
        // Get term's objects
        foreach( $term_ids as $term_id => $taxonomy )
            $post_ids[] = get_objects_in_term( $term_id, $taxonomy );
        // Get common objects
        if( !empty( $post_ids ) ) {
            for( $i = 1; $i < count( $post_ids ); $i++ )
                $post_ids[0] = array_intersect( $post_ids[0], $post_ids[$i] );
            // return the final array
            $post_ids = reset( $post_ids );
        }
        // Get object data's of one type
        if( !empty( $post_ids ) ) {
            foreach( $post_ids as $pid )
                if( in_array( get_post_type( $pid ), $post_types ) )
                   $posts[] = get_post( $pid );
            }
        else
            return null;
        
        return $posts;
    }
}
?>