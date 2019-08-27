#!/usr/local/bin/python3

# remove notifications in the database about configuration file check failures
#
# parameter 1 = db parameter file for write

import sys
import os
import psycopg2

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/lib/')

from dblib import get_db_settings

if len(sys.argv) < 2:
   print( 'Missing db config file as first parameter', file=sys.stderr )
   sys.exit( 1 )

dbfile = sys.argv[1]

if not os.path.isfile( dbfile ):
   print( 'Db config file does not exist', file=sys.stderr )
   sys.exit( 1 )

db_settings = get_db_settings( dbfile )
if not db_settings:
   print( 'Database config file is missing options.', file=sys.stderr )
   sys.exit( 1 )

exit_code = 0

db_string = 'dbname=%s user=%s password=%s' % ( db_settings['database'], db_settings['user'], db_settings['password'] )

try:
   dbh = psycopg2.connect( db_string )

   cur = dbh.cursor()

   sql = 'delete from config_notifications where is_failure';

   cur.execute( sql )

   cur.close()
   dbh.commit()
   dbh.close()

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
