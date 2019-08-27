#!/usr/local/bin/python3

# print the framer package file to stdout

import sys
import os
import psycopg2

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/lib/')

from dblib import get_db_settings

def output_config_package( conn ):
    cur = conn.cursor()

    # consider only the most recent for each instance

    sql = ('select i.name, latest_configs.serial from'
           ' (select instrument_id,serial,max(created_at) as created_at from framer_configs group by instrument_id,serial)'
           ' as latest_configs'
           ' inner join instruments i on i.id = latest_configs.instrument_id'
           ' where i.is_usable'
           ' order by i.name, latest_configs.serial')
    cur.execute( sql )
    for row in cur:
        print( row[0].lower(), row[1].lower() )
    cur.close()

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

   output_config_package( dbh )

   dbh.close()

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
