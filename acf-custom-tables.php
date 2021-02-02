<?php
/*
Plugin Name: ACF - Custom Tables
Description: Save ACF data in a custom table for faster database queries.
Author: Simone Manfredini
Author URI: http://webdesignsimone.it
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


//REGISTER CUSTOM TABLE
function trp_install_acf_table() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . "acf_data";
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id INT,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
trp_install_acf_table();


//SAVE ACF DATA IN CUSTOM TABLE
add_filter('acf/update_value', 'trp_add_columns_acf_table', 10, 4);
function trp_add_columns_acf_table( $value, $post_id, $field ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "acf_data";

    if($field['type'] != 'text' && $field['type'] != 'textarea' && $field['type'] != 'number'):
        return $value;
    endif;

    if($post_id == 'options'):
        $post_id = -1;
    endif;

    $value = str_replace('\"', '"', $value);

    if($field['type'] == 'text' || $field['type'] == 'textarea'):
        $type = 'VARCHAR(255)';
    elseif($field['type'] == 'number'):
        $type = 'INT(255)';
    endif;
    
    $campo_name = $field['name'];

    $row = $wpdb->get_results(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '$table_name' AND column_name = '$campo_name'"
    );

    if(empty($row)):
        $wpdb->query("ALTER TABLE $table_name ADD $campo_name $type");
    endif;

    $column = $wpdb->get_results(
        "SELECT id FROM $table_name
        WHERE post_id = $post_id"
    );

    if(empty($column)):
        $wpdb->insert( 
            $table_name, 
            array( 
                'post_id' => $post_id,
                $campo_name => $value, 
            ),
            array(
                '%d', 
                '%s'
            )
        );
    else:
        $wpdb->update( 
            $table_name, 
            array( 
                $campo_name => $value, 
            ),
            array( 
                'post_id' => $post_id,
            ),
            array(
                '%s'
            ), 
            array(
                '%d'
            ) 
        );
    endif;

    return $value;
}

//LOAD ACF DATA FROM CUSTOM TABLE
function my_acf_format_value( $value, $post_id, $field ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "acf_data";

    if($field['type'] != 'text' && $field['type'] != 'textarea' && $field['type'] != 'number'):
        return $value;
    endif;

    if($post_id == 'options'):
        $post_id = -1;
    endif;
    
    $campo_name = $field['name'];

    $campo = $wpdb->get_var(
        "SELECT $campo_name FROM $table_name
        WHERE post_id = $post_id"
    );

    return $campo;
}
add_filter('acf/format_value', 'my_acf_format_value', 10, 3);