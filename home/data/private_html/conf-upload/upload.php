<?php
 include( "lib/common.inc" );
 include( "lib/web-pages.inc" );
 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/html-head-main.html" );

 include( "../../public_html/web-content/page-header.html" );
 echo "<div id='content'>\n";

 main();

 echo "</div>\n";
 include( "lib/html-tail.html" );

// ------------------------------------------------------
function main() {
  if ( $_FILES['userfile']['error'] === UPLOAD_ERR_OK
       && is_uploaded_file($_FILES['userfile']['tmp_name']) ) {

    $data = file_get_contents($_FILES['userfile']['tmp_name']);

    if ( is_json( $data ) ) {
       $needed = array();
       read_data( $data, $needed );
       if ( isset( $needed['instrument'] ) && isset( $needed['serial'] ) ) {

         $dbh = connect_for_write();
         if ( $dbh ) {
            try {
               $dbh->beginTransaction();

               save_to_db( $dbh, $data, $needed['instrument'], $needed['serial'] );

               $dbh->commit();

               echo "Upload complete";

            } catch (PDOException $e) {
              $dbh->rollBack();
              show_error( "Database error" );
            }

            $dbh = null;
         } else {
           show_error( "Database connect error" );
         }

       } else {
         show_error( "Missing instrument name and/or serial number" );
       }
    } else {
       show_error( "Upload is not in JSON format" );
    }

  } else {
    $message = null;
    switch( $_FILES['userfile']['error'] ) {
    case UPLOAD_ERR_INI_SIZE:
       $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
       break;
    case UPLOAD_ERR_FORM_SIZE:
       $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
       break;
    case UPLOAD_ERR_PARTIAL:
       $message = "The uploaded file was only partially uploaded";
       break;
    case UPLOAD_ERR_NO_FILE:
      $message = "No file was uploaded";
      break;
    case UPLOAD_ERR_NO_TMP_DIR:
      $message = "Missing a temporary folder";
      break;
    case UPLOAD_ERR_CANT_WRITE:
      $message = "Failed to write file to disk";
      break;
    case UPLOAD_ERR_EXTENSION:
      $message = "File upload stopped by extension";
      break;
    default:
      $message = "Unknown upload error";
      break;
    }

    show_error( $message );
  }
}

// ---------------------------
function read_data( $data, &$items ) {
  $obj = json_decode( $data, true );

  $needed = array('instrument', 'serial');
  foreach( $needed as $item ) {
    $items[$item] = null;
    if ( isset( $obj[$item] ) ) {
       if ( trim( $obj[$item] ) != '' ) {
          $items[$item] = strtolower( $obj[$item] );
       }
    }
  }
}

// ---------------------------
function save_to_db( $dbh, $data, $instrument, $serial ) {
  add_instrument( $dbh, $instrument );

  // before adding the new old, the old ones should be deactivated
  $sql = "update framer_configs set is_usable=false,is_changed=false"
       . " where lower(serial)=?"
       . " and instrument_id=(select id from instruments where lower(name)=?)"
       ;
  $stmt = $dbh->prepare( $sql );
  $stmt->bindParam( 1, $serial );
  $stmt->bindParam( 2, $instrument );
  $stmt->execute();
  $stmt = null;

  // now the new one

  $sql = "insert into framer_configs (config,serial,instrument_id)"
       . " values(?,?,(select id from instruments where name=?))";
  $stmt = $dbh->prepare( $sql );
  $stmt->bindParam( 1, $data );
  $stmt->bindParam( 2, $serial );
  $stmt->bindParam( 3, $instrument );
  $stmt->execute();
  $stmt = null;
}

// ---------------------------
function add_instrument( $dbh, $name ) {
  $sql = "select count(*) from instruments where lower(name) = ?";

  if ( read_one_value_with_params( $dbh, $sql, array( $name ) ) < 1 ) {
     $sql = "insert into instruments(name) values(?)";
     $stmt = $dbh->prepare( $sql );
     $stmt->bindParam( 1, $name );
     $stmt->execute();
     $stmt = null;
  }
}

?>
