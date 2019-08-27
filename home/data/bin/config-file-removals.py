#!/usr/local/bin/python3

import sys
import os
import psycopg2

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/lib/')

from dblib import get_db_settings

def list_all_active_configs( conn ):
    values = dict()

    cur = conn.cursor()

    # order by date so that we pick up the most recent for each instance

    sql = ('select c.id, i.name, c.serial'
           ' from instruments i, framer_configs c'
           ' where i.id = c.instrument_id'
           ' and c.is_usable'
           ' and i.id in (select id from instruments where is_usable)'
           ' order by c.created_at')
    cur.execute( sql )
    for row in cur:
        if row[1] not in values:
           values[row[1]] = dict()
        values[row[1]][row[2]] = row[0]
    cur.close()

    return values

if len(sys.argv) < 2:
   print( 'Missing db config file as first parameter', file=sys.stderr )
   sys.exit( 1 )
if len(sys.argv) < 3:
   print( 'Missing output path as second parameter', file=sys.stderr )
   sys.exit( 1 )

dbfile = sys.argv[1]
outdir = sys.argv[2]

if not os.path.isfile( dbfile ):
   print( 'Db config file does not exist', file=sys.stderr )
   sys.exit( 1 )
if not os.path.isdir( outdir ):
   print( 'Output path does not exist', file=sys.stderr )
   sys.exit( 1 )

db_settings = get_db_settings( dbfile )
if not db_settings:
   print( 'Database config file is missing options.', file=sys.stderr )
   sys.exit( 1 )

exit_code = 0

db_string = 'dbname=%s user=%s password=%s' % ( db_settings['database'], db_settings['user'], db_settings['password'] )

try:
   dbh = psycopg2.connect( db_string )

   all_configs = list_all_active_configs( dbh )

   dbh.close()

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
