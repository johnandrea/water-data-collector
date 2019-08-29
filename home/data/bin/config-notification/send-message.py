#!/usr/local/bin/python3

# Send email is the data configuration files are in error.

import sys
import os
import psycopg2
import smtplib
from email.message import EmailMessage

# setup a few library files
dir_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append( dir_path + '/../lib/')

from dblib import get_db_settings

def send_messages( n, conn, message ):
    n_email = 0

    sql = 'select address from notification_emails where config_checks'

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        n_email = n_email + 1
        address = row[0]

        address = 'jandrea' # debug

        print( 'sending to', address )

        message['To'] = address

        print( message )

        s = smtplib.SMTP('localhost@localdomain')
        s.send_message( message )
        s.quit()

    cur.close()

    if n_email < 1:
       print( 'No email addresses defined for notification', file=sys.stderr )

def is_pow_of_2( x ):
    if x <= 0:
       return False 
    if x in [1,2,4,8,16,32,64,128,256]:
       return True
    return math.log2( x ).is_integer()

def get_error_count( conn ):
    n = 0

    sql = 'select count(*) from config_notifications where is_failure'

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        n = row[0]

    cur.close()

    return n

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

   # don't process every time, use powers of 2

   n = get_error_count( dbh )

   if is_pow_of_2(n):
      subject = 'Configuration files are not valid'
      body    = ('The data extraction configuratin files on this server'
                 ' have failed validation.\n\n'
                 ' Check the server status file for details\n\n'
                 ' A replacement should be uploaded as soon as possible.')

      print( 'problem count', n )

      message = EmailMessage()
      message['From'] = 'do-not-reply@localhost'
      message['Subject'] = subject
      message.set_content(body)

      send_messages( n, dbh, message )

   else:
      if n > 0:
         print( 'problem, but message not sent for count of', n )
      else:
         print( 'no problems' )

   dbh.close()

except Exception as e:
   eror_code = 1
   print( e, file=sys.stderr )

sys.exit( exit_code )
