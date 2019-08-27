#!/usr/local/bin/python3

# give filename as a parameter
#
# wait till the file stops changing in size, then return

import os
import sys
from time import sleep
from pathlib import Path

SIZE_CHANGE_WAIT = 12 #seconds

if len( sys.argv ) > 1:
   file = Path( sys.argv[1] )

   if file.is_file():

      # test for changes in file size
      # start with numbers that cause at least one sleep cycle

      prev_size = -1
      size      = -2
      while prev_size != size:
         #print( "sleeping", prev_size, size )
         prev_size = size
         sleep( SIZE_CHANGE_WAIT )
         size = os.stat( sys.argv[1] ).st_size

      # all done
      print( sys.argv[1] )

   else:
       print( "Error, no such file" )
       sys.exit( 1 )

else:
   print( "Error, missing file name" )
   sys.exit( 1 )
