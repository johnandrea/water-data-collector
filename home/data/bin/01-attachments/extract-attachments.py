#!/usr/local/bin/python3

# extract all attachments from the email message
# if the attachment name is not .raw then name it .log
# and prefix with the StorX serial number + a prefix which should be the
# creation date of the original file

import sys
import email
import os
import re

def extract_attachments( out_dir, name_part, sn, m ):
    if m.is_multipart():
       n = 0

       for part in m.walk():
           n += 1

           content_type = part.get_content_type()

           if re.match( '^multipart', content_type ):
              continue

           out_name = out_dir + '/' + sn + '_' + name_part + '_' + str( n ) + '.log'

           # or is a filename given
           if part.get_filename():
              name = part.get_filename().lower()
              if name.endswith( '.raw' ):
                 out_name = out_dir + '/' + sn + '_' + name_part + '_' + name

           print( 'output as', out_name )

           with open( os.path.join( '.', out_name ), 'wb' ) as fileh:
                fileh.write( part.get_payload( decode=True ) )

if len( sys.argv ) > 3:
   file = sys.argv[1]
   out_dir = sys.argv[2]
   name_part = sys.argv[3]

   if os.path.isfile( file ):

      with open( file, 'r' ) as fileh:
           m = email.message_from_file( fileh )

           # the subject must be "STOR-X sn 9999 something else"

           if m["Subject"].startswith( 'STOR-X' ):
              sn = re.sub( '^STOR-X sn ', '',  m["Subject"] )
              sn = re.sub( ' .*', '', sn )

              extract_attachments( out_dir, name_part, sn, m )
                  
           else:
              print( 'Message subject not in expected format', file=sys.stderr )
              # don't error on exit, allow the file to be deleted

   else:
      print( 'Error: not a file', file=sys.stderr )
      sys.exit( 1 )

else:
   print( 'usage: program infile outdir namepart', file=sys.stderr )
   sys.exit( 1 )
