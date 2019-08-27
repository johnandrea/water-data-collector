#!/bin/bash

# return the date of last mod for a filename given asa parameter

if [ "$1" = "" ]; then
  exit 1
else
  if [ -e "$1" ]; then

    # 2019-02-01 17:27:50.768000000
    # returned as 
    # 20190201-172750

    stat --format="%z" "$1" | cut -c1-19 | tr -d '-' | tr -d ':' | tr ' ' '-'

  else
    exit 1
  fi
fi
