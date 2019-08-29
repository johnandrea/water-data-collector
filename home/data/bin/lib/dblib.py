import re

def get_db_settings( filename ):
    # The file should consist of lines like this:
    #     database thisdb
    #     user  thisdbuser
    #     password  thisuserpass
    # as keyword space values.
    # comments are ignored.
    # Return the info in a dict.

    values = dict()

    db     = None
    user   = None
    passwd = None

    with open( filename ) as f:
         for line in f:
             line = re.sub( r'#.*', '', line.strip() )
             if line:
                parts = line.split()
                if len( parts ) > 1:
                   if parts[0] == 'database': db     = parts[1]
                   if parts[0] == 'user':     user   = parts[1]
                   if parts[0] == 'password': passwd = parts[1]
    if db and user and passwd:
       values['database'] = db
       values['user']     = user
       values['password'] = passwd

    return values

def get_system_settings( conn ):
    data = dict()

    sql = 'select name,value from settings'

    cur = conn.cursor()
    cur.execute( sql )

    for row in cur:
        data[row[0]] = row[1]

    cur.close()

    return data
