#!/usr/local/bin/python3

# output the current package file (always done, since and instrument/serial
#     may have been deleted)
# output any new (that is, market as changed) configurations,
#     then mark those as not changed
#
# parameter 1 = db parameter file for write
# parameter 2 = path where the new config files will be output
# parameter 3 = full path and name of the package file

import sys
import os
import psycopg2
import json

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/lib/')

from dblib import get_db_settings

def undo_changed( conn, id_list ):
    # unset the changed flag for each instrument/serial and older
    # but not all because something newer may have just arrived
    # (though anything newer might not be visible to this db session)

    cur = conn.cursor()

    sql = ('update framer_configs set is_changed=false'
           ' where created_at <=(select created_at from framer_configs where id=%s)'
           ' and instrument_id=(select instrument_id from framer_configs where id=%s)'
           ' and serial=(select serial from framer_configs where id=%s)')

    for instrument in id_list:
        for serial in id_list[instrument]:
            id = id_list[instrument][serial]['id']
            cur.execute( sql, (id,id,id) )

    cur.close()

def get_changed_configs( conn ):
    values = dict()

    cur = conn.cursor()

    # order by date so that we pick up the most recent for each instance

    sql = ('select c.id, i.name, c.serial, c.config'
           ' from instruments i, framer_configs c'
           ' where i.id = c.instrument_id'
           ' and c.is_usable'
           ' and i.id in (select id from instruments where is_usable)'
           ' and c.is_changed'
           ' order by c.created_at')
    cur.execute( sql )
    for row in cur:
        if row[1] not in values:
           values[row[1]] = dict()
        if row[2] not in values[row[1]]:
           values[row[1]][row[2]] = dict()
        values[row[1]][row[2]]['id'] = row[0]
        values[row[1]][row[2]]['data'] = row[3]
    cur.close()

    return values

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

def has_changes( conn ):
    value = 0
    cur = conn.cursor()
    cur.execute( 'select count(*) from framer_configs where is_changed' )
    row = cur.fetchone()
    if row:
       value = row[0]
    cur.close()
    return value > 0

if len(sys.argv) < 2:
   print( 'Missing db config file as first parameter', file=sys.stderr )
   sys.exit( 1 )
if len(sys.argv) < 3:
   print( 'Missing output path as second parameter', file=sys.stderr )
   sys.exit( 1 )
if len(sys.argv) < 4:
   print( 'Missing name of output package file as third parameter', file=sys.stderr )
   sys.exit( 1 )

dbfile = sys.argv[1]
outdir = sys.argv[2]
packagefile = sys.argv[3]

if not os.path.isfile( dbfile ):
   print( 'Db config file does not exist', file=sys.stderr )
   sys.exit( 1 )
if not os.path.isdir( outdir ):
   print( 'Output path does not exist', file=sys.stderr )
   sys.exit( 1 )
# can't test creation of the package file because it might not be needed,
# and can't overwrite existing one

db_settings = get_db_settings( dbfile )
if not db_settings:
   print( 'Database config file is missing options.', file=sys.stderr )
   sys.exit( 1 )

exit_code = 0

db_string = 'dbname=%s user=%s password=%s' % ( db_settings['database'], db_settings['user'], db_settings['password'] )

try:
   dbh = psycopg2.connect( db_string )

   # write filename to stdout
   print( packagefile )

   all_configs = list_all_active_configs( dbh )

   with open( outdir + '/' + packagefile, 'w' ) as f:
        for instrument in sorted( all_configs.keys() ):
            for serial in sorted( all_configs[instrument].keys() ):
                f.write( instrument + ' ' + serial + '\n' )

   if has_changes( dbh ):
      changed_configs = get_changed_configs( dbh )
      undo_changed( dbh, changed_configs )

      dbh.commit()

      for instrument in changed_configs:
          for serial in changed_configs[instrument]:
              filename = instrument + '.' + serial + '.conf'

              # show the new file on stdout
              print( filename )

              with open( outdir + '/' + filename, 'w' ) as f:
                   json.dump( changed_configs[instrument][serial]['data'], f, indent=2 )

   dbh.close()

except Exception as e:
   eror_code = 1
   print( '' )
   print( e, file=sys.stderr )

sys.exit( exit_code )
