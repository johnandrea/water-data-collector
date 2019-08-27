#!/usr/local/bin/python3

# give filename as a parameter
#
# output a file age in integer days (rounded down)

import os
import sys
import datetime
from pathlib import Path

if len( sys.argv ) > 1:
   file = Path( sys.argv[1] )

   if file.is_file():

      file_sec = os.stat( sys.argv[1] ).st_mtime
      system_sec = datetime.datetime.now().timestamp()

      diff = system_sec - file_sec

      print( int( diff / 86400 ) )

   else:
       print( "Error, no such file" )
       sys.exit( 1 )

else:
   print( "Error, missing file name" )
   sys.exit( 1 )
