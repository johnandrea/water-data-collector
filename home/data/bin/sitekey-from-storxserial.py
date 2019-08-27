#!/usr/local/bin/python3

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
if len(sys.argv) < 3:
   print( 'Missing storx serial as second parameter', file=sys.stderr )
   sys.exit( 1 )

dbfile = sys.argv[1]
storx  = sys.argv[2]

if not os.path.isfile( dbfile ):
   print( 'Db config file does not exist', file=sys.stderr )
   sys.exit( 1 )
if not storx:
   print( 'Storx serial is empty', file=sys.stderr )
   print( '' )
   sys.exit( 1 )

db_settings = get_db_settings( dbfile )
if not db_settings:
   print( 'Database config file is missing options.', file=sys.stderr )
   sys.exit( 1 )

exit_code = 0

db_string = 'dbname=%s user=%s password=%s' % ( db_settings['database'], db_settings['user'], db_settings['password'] )

try:
   conn = psycopg2.connect( db_string )

   cur = conn.cursor()

   cur.execute( 'select web_key from sites where storx_serial=%s', (storx,) )

   row = cur.fetchone()

   cur.close()
   conn.close()

   if row:
      print( row[0] )
   else:
      eror_code = 1
      print( '' )

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
