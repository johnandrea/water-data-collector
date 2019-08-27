#!/bin/bash

mkdir -p queue/abandoned
mkdir -p queue/trouble
mkdir -p queue/01-attachments
mkdir -p queue/02-site-attachments
mkdir -p queue/03-extracted
mkdir -p data/archive
mkdir -p data/framer-conf
mkdir -p data/recent
mkdir -p data/tmp
mkdir -p data/status

chmod go-rwx . * .bash* .ssh

chmod o+x .

find public_html -type d -exec chmod go-rx {} \;
find public_html -type d -exec chmod o+x {} \;
find public_html -type f -exec chmod o+r {} \;
chmod o+x public_html

find private_html -type d -exec chmod go-rx {} \;
find private_html -type d -exec chmod o+x {} \;
find private_html -type f -exec chmod o+r {} \;
chmod o+x private_html

chmod o+x data
chmod o+rx data/archive data/recent data/status
