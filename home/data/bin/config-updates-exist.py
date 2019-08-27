#!/usr/local/bin/python3

# Return the string "y" if there are new framer configurations, otherwise "n"
# At the same time time the exit codes are used, a failure returned
# if there is something wrong.
#  As such "y" can only be returned in combination with a success exit code.

import sys
import os
import psycopg2

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/lib/')

from dblib import get_db_settings

def output_update_exists( conn ):
    result = 'n'
    
    cur = conn.cursor()

    sql = ('select count(*) from framer_configs'
           ' where is_usable'
           ' and is_changed'
           ' and instrument_id in (select id from instruments where is_usable)')

    cur.execute( sql )

    for row in cur:
        if row[0] > 0:
           result = 'y'

    cur.close()

    print( result )

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

   output_update_exists( dbh )

   dbh.close()

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
