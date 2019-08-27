#!/usr/local/bin/python3

# data is input via stdin
# status information is on stdout
# parameter 1 = path to the database ocnfiguration file
# parameter 2 = name of the data file, not the path just the name
# parameter 3 = id of the site for the data

import sys
import os
import psycopg2

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/../framer/lib/')
sys.path.append( dir_path + '/../lib/')

from framer_util import read_framer_results,get_header_key
from dblib import get_db_settings

def is_number(s):
    try:
      float(s)
    except ValueError:
      return False

    return True

def get_unique_timestamps():
    times = dict()

    for instrument in data:
        if instrument == get_header_key():
           continue

        for timestamp in data[instrument]:
            times[timestamp] = True

    return times

def insert_data( known_columns ):
    sensor_already_reported = dict()

    for instrument in data:
        if instrument == get_header_key():
           continue

        for timestamp in data[instrument]:
            values = dict()

            for field in data[instrument][timestamp]:
                if field in known_columns:
                   value = data[instrument][timestamp][field]
                   if is_number( value ):
                      values[field] = value
                      #print( 'debug: found', field, value )
                else:
                   if field not in sensor_already_reported:
                      print( 'WARNING: data contains field with no database match:', field )
                      sensor_already_reported[field] = True

            for field in values:
                print( 'Inserting', site_id, timestamp, field, values[field] )

                stmt = 'update data set ' + field + '=%s'
                stmt += ' where data_time=%s and site_id=%s'

                cur.execute( stmt, (values[field], timestamp, site_id) )

def insert_times( times ):
    # initialize each record by adding the time and site id
    # so that later the data is added with an update

    # first step, find which of the times don't yet exist in the database

    times_to_add = []

    stmt = 'prepare pstmt (integer, timestamp) as '
    stmt += 'select count(*) from data where site_id=$1 and data_time=$2'

    cur.execute( stmt )

    for time in times:
        cur.execute( 'execute pstmt (%s, %s)', (site_id, time) )
        row = cur.fetchone()
        if row[0] < 1:
           times_to_add.append( time )

    cur.execute( 'deallocate pstmt' )

    # second step, add the new times

    if times_to_add:
       stmt = 'prepare pstmt (integer, timestamp, text) as '
       stmt += 'insert into data (site_id,data_time,data_file) values ($1,$2,$3)'

       cur.execute( stmt )

       for time in times_to_add:
           cur.execute( 'execute pstmt (%s, %s, %s)', (site_id, time, dataname) )
           #print( 'debug: inserting time', time, site_id, dataname )

       cur.execute( 'deallocate pstmt' )

def read_sensors():
    name_list = []

    cur.execute( 'select name from sensors' )

    for row in cur:
        name_list.append( row[0] )

    return name_list

if len(sys.argv) < 2:
   print( 'Missing db config file as first parameter', file=sys.stderr )
   sys.exit( 1 )
if len(sys.argv) < 3:
   print( 'Missing data name as second parameter', file=sys.stderr )
   sys.exit( 1 )
if len(sys.argv) < 4:
   print( 'Missing site id as third parameter', file=sys.stderr )
   sys.exit( 1 )

dbfile = sys.argv[1]
dataname = sys.argv[2]
site_id = sys.argv[3]

db_settings = get_db_settings( dbfile )
if not db_settings:
   print( 'Database config file is missing options.', file=sys.stderr )
   sys.exit( 1 )

error, data = read_framer_results()
if error:
   print( error, file=sys.stderr )
   sys.exit( 1 )

# with an 'ok' exit, the file will be removed from the queue
# but with 'bad' then the file needs to stay until it gets processed

exit_code = 0

db_string = 'dbname=%s user=%s password=%s' % ( db_settings['database'], db_settings['user'], db_settings['password'] )

try:
   conn = psycopg2.connect( db_string )

   cur = conn.cursor()

   sensors = read_sensors()
   if sensors:

      insert_times( get_unique_timestamps() )
      insert_data( sensors )

      conn.commit()

   else:
      error_code = 1
      print( 'Sensor names not loded from database', file=sys.stderr )

   cur.close()
   conn.close()

except Exception as e:
   eror_code = 1
   print( e, file=sys.stderr )

sys.exit( exit_code )
