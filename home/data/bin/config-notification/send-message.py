#!/usr/local/bin/python3

# Send email if the data configuration files are in error. Reducing frequency
# of the messages over time.
# And track when the checks are done and sent.
#
# Needs write access to database.

import sys
import os
import psycopg2
import smtplib
from email.message import EmailMessage
import getpass

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/../lib/')

from dblib import get_db_settings, get_system_settings

def setup_outgoing_message( fqdn ):
    data = dict()

    data['subject'] = 'Configuration files are not valid'

    data['body'] = ('The data extraction configuratin files on this server'
                    ' have failed validation.\n\n'
                    'Check the server status files for details.\n\n'
                    'Files will remain in the processing queue.'
                    ' Replacement config files should be uploaded as soon as'
                    ' possible to allow file processing to resume.')

    data['from'] = getpass.getuser() + '@' + fqdn

    return data

def send_message( conn, fqdn ):
    contents = setup_outgoing_message( fqdn )

    message = EmailMessage()
    message['From'] = contents['from']
    message['Subject'] = contents['subject']
    message.set_content(contents['body'])

    n_email = 0

    sql = 'select address from notification_emails where config_checks'

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        n_email = n_email + 1
        address = row[0]

        message['To'] = address
        print( 'Config check: sending to', address )

        s = smtplib.SMTP('localhost')
        s.send_message( message )
        s.quit()

    cur.close()

    if n_email < 1:
       print( 'Config check: No email addresses defined for notification', file=sys.stderr )

def is_pow_of_2( x ):
    if x <= 0:
       return False 
    if x in [1,2,4,8,16,32,64,128,256]:
       return True
    return math.log2( x ).is_integer()

def get_hours_since_sent( conn ):
    n = 0

    sql = ('select extract(hours from now()-max(created_at))'
           ' from config_notifications where is_sent')

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        n = row[0]

    cur.close()

    return n

def get_count( conn, flag ):
    n = 0

    sql = 'select count(*) from config_notifications where %s' % (flag,)

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        n = row[0]

    cur.close()

    return n

def update_count( conn, flag ):
    sql = 'insert into config_notifications (%s) values (true)' % (flag,)

    cur = conn.cursor()
    cur.execute( sql )
    cur.close()

def update_send_count( conn ):
    update_count( conn, 'is_sent' )

def get_error_count( conn ):
    return get_count( conn, 'is_failure' )

def get_sent_count( conn ):
    return get_count( conn, 'is_sent' )

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

   if get_error_count( dbh ) > 0:

      system_settings = get_system_settings( dbh )

      if get_sent_count( dbh ) < 1:
         # first message
         update_send_count( dbh )
         send_message( dbh, system_settings['fqdn'] )
         print( 'Config check: sent first message' )

      else:
         # send on a schedule
         n = get_hours_since_sent( dbh )

         if is_pow_of_2(n):
            update_send_count( dbh )
            send_message( dbh, system_settings['fqdn'] )

         else:
            print( 'Config check: problem, but message not sent for count of', n )

   else:
     print( 'Config check: no problems' )

   dbh.commit()
   dbh.close()

except Exception as e:
   eror_code = 1
   print( e, file=sys.stderr )

sys.exit( exit_code )
