<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * EMERSON: Framework Installer Migration_Search and Replace Class
 * This class will throughly update hostnames and URLs inside the target database after a successful reference site installation.
 * Adopted from here: https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
 * Extended for MySQLi and Framework installer implementation
 * @since    1.8.7
*/

class Framework_Installer_Migration_Search_Replace {	
	
	public function __construct(){
		
	}
	
	/**
	* @param array $configuration
	*     		$configuration =	array(					
					'srch'  	 => 'original_url',
					'rplc'		 => 'target_url'
			);
	*/
	
	public function Framework_Installer_Migration_Search_Replace($configuration) {
		
			/** CONFIGURATION DEFAULTS */
		
			$configuration['char'] = 'utf8';						
			$configuration['char'] = 'guid';
			
			if (!(isset($configuration['tables']))) {
				//User does not table, use 'all tables' default
				$configuration['tables'] =  array('all_tables');
			}
			
			$charset_used = $configuration['char'];
			//Define connection
			//WordPress wpdb database object
			global $wpdb;
			
			//Use WordPress wpdb Class magic getter
			$use_mysqli_defined= $wpdb->__get( 'use_mysqli' );
			
			///Check if this site is utilizing mysqli
			if ($use_mysqli_defined) {
								
				//Using MySQLi				
				//Let's retrieved the WordPress database connection handle				
				$connection=$wpdb->dbh;				
				if ($connection) {
					//Connected to database!
					//Use the configuration
					if ((isset($configuration['srch'])) && (isset($configuration['rplc'])) && (isset($configuration['tables'])))
				
						$srch=$configuration['srch'];
					$rplc=$configuration['rplc'];
					$user_tables_setting=$configuration['tables'];
						
					if ((is_array($user_tables_setting)) && (!(empty($user_tables_setting)))) {
						//User table setting defined
						$counted=count($user_tables_setting);
						
						//Get all tables in dB						
						//Multisite compatible: Discover-WP
						//Let's retrieved the currently installed site tables limiting to only its specified table prefix
						global $all_tables;
						$all_tables=array();
						$specific_site_prefix = str_replace( '_', '\_', $wpdb->prefix );
						$all_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$specific_site_prefix}%'" );
						
						if ((in_array('all_tables',$user_tables_setting)) && ($counted===1)) {
				
							//Retrieve all tables automatically, user likes to do the searching in all tables
							$tables=$all_tables;
				
						} elseif ((!(in_array('all_tables',$user_tables_setting))) && ($counted > 0)) {
								
							//User provided custom lists of tables
							//Filter user table setting for non-existing tables
							$tables = array_filter( $user_tables_setting, array($this,'migration_check_table_array' ));
				
						} else {
							//Bail out without any warnings
							return;
						}
				
						if (isset($tables)) {
								
							//Proceed to process..
							$connection->set_charset($charset_used);
							$report = $this->migration_icit_srdb_replacer( $connection, $srch, $rplc, $tables, FALSE );
								
							if ((is_array($report)) && (!(empty($report)))) {
								//Done, report.
								$time = array_sum( explode( ' ', $report[ 'end' ] ) ) - array_sum( explode( ' ', $report[ 'start' ] ) );
								return;
								/*
								printf( 'In the process of replacing "%s" with "%s" we scanned %d tables with a total of %d rows, %d cells were changed and %d db update performed and it all took %f seconds.', $srch, $rplc, $report[ 'tables' ], $report[ 'rows' ], $report[ 'change' ], $report[ 'updates' ], $time );
								*/
							}
						}
					} else {
						return;
						/*
						 * Here tables are not configured							
						*/
				
					}
				} else {
					/*
					 * Here we are having issues with MySQL server connection..
					*/
					return;
				}				
			}	
	}

	public function migration_icit_srdb_replacer( $connection, $search = '', $replace = '', $tables = array( ), $isRegEx = false ) {
		$guid=0;
		$exclude_cols=array('guid');
	
		$report = array( 'tables' => 0,
				'rows' => 0,
				'change' => 0,
				'updates' => 0,
				'start' => microtime( ),
				'end' => microtime( ),
				'errors' => array( ),
		);
	
		if ( is_array( $tables ) && ! empty( $tables ) ) {
			foreach( $tables as $table ) {
				$report[ 'tables' ]++;
	
				$columns = array( );
	
				// Get a list of columns in this table			 
				$fields= $connection->query('DESCRIBE ' . $table);			
				
				while( $column = $fields->fetch_assoc() )
					$columns[ $column[ 'Field' ] ] = $column[ 'Key' ] == 'PRI' ? true : false;
	
				// Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley			
				$row_count = $connection->query('SELECT COUNT(*) FROM ' . $table);
				$rows_result = mysqli_fetch_array( $row_count );
				
				$row_count = $rows_result[ 0 ];
				if ( $row_count == 0 )
					continue;
	
				$page_size = 50000;
				$pages = ceil( $row_count / $page_size );
	
				for( $page = 0; $page < $pages; $page++ ) {
	
					$current_row = 0;
					$start = $page * $page_size;
					$end = $start + $page_size;
					// Grab the content of the table				
					$data = $connection->query("SELECT * FROM $table LIMIT $start, $end");
					if ( ! $data )
						$report[ 'errors' ][] =$connection->error;
	
					while ( $row = $data->fetch_assoc() ) {
	
						$report[ 'rows' ]++; // Increment the row counter
						$current_row++;
	
						$update_sql = array( );
						$where_sql = array( );
						$upd = false;
	
						foreach( $columns as $column => $primary_key ) {
							if ( $guid == 1 && in_array( $column, $exclude_cols ) )
								continue;
	
							$edited_data = $data_to_fix = $row[ $column ];
	
							// Run a search replace on the data that'll respect the serialisation.
							$edited_data = $this->migration_recursive_unserialize_replace( $search, $replace, $data_to_fix, $isRegEx );
	
							// Something was changed
							if ( $edited_data != $data_to_fix ) {
								$report[ 'change' ]++;
								$update_sql[] = $column . ' = "' . $connection->real_escape_string( $edited_data ) . '"';
								//$update_sql[] = $column . ' = "' . $edited_data . '"';
								$upd = true;
							}
	
							if ( $primary_key )
								$where_sql[] = $column . ' = "' . $connection->real_escape_string( $data_to_fix ) . '"';
							    //$where_sql[] = $column . ' = "' . $data_to_fix  . '"';
						}
	
						if ( $upd && ! empty( $where_sql ) ) {
							$sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );					
							
							$result = $connection->query($sql);
							
							if ( ! $result )
								$report[ 'errors' ][] = $connection->error;
							else
								$report[ 'updates' ]++;
	
						} elseif ( $upd ) {
							$report[ 'errors' ][] = sprintf( '"%s" has no primary key, manual change needed on row %s.', $table, $current_row );
						}
	
					}
				}
			}
	
		}
		$report[ 'end' ] = microtime( );
	
		return $report;
	}
	
	public function migration_recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false, $isRegEx = false ) {
		
		try {
	
			if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
				$data = $this->migration_recursive_unserialize_replace( $from, $to, $unserialized, true, $isRegEx );
			}
	
			elseif ( is_array( $data ) ) {
				$_tmp = array( );
				foreach ( $data as $key => $value ) {
					$_tmp[ $key ] = $this->migration_recursive_unserialize_replace( $from, $to, $value, false, $isRegEx );
				}
	
				$data = $_tmp;
				unset( $_tmp );
			}
	
			else {
				if ( is_string( $data ) )
				{
					if ($isRegEx)
						$data = preg_replace( $from, $to, $data );
					else
						$data = str_replace( $from, $to, $data );
	
				}
			}
	
			if ( $serialised )
				return serialize( $data );
	
		} catch( Exception $error ) {
	
		}
	
		return $data;
	}
	
	public function migration_check_table_array( $table = '' ){
		global $all_tables;
		return in_array( $table, $all_tables );
	}

}