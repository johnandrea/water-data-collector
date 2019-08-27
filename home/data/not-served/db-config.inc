<?php

// Database configuration

function connect_for_read() {
  return db_connect( 'data', 'data_read', 'read' );
}

function connect_for_write() {
  return db_connect( 'data', 'data_write', 'write' );
}

#-----------------------------------------------------------------------
function db_connect( $dbname, $user, $pass ) {
  try {
    $dbh = new PDO( 'pgsql:host=localhost;dbname=' . $dbname, $user, $pass,
                   array( PDO::ATTR_PERSISTENT => true) );
    $dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    $dbh = null;
  }

  return $dbh;
}

?>
