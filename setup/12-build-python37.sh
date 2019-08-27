#!/bin/bash

#https://tecadmin.net/install-python-3-7-on-centos/
#with additional options

yum -y install automake make bison byacc flex cpp gcc gcc-c++ glibc-devel patch
yum -y install patchutils zlib-devel libxml2-devel expat-devel
yum -y install readline-devel
yum -y install libffi-devel openssl-devel

cd /tmp
wget https://www.python.org/ftp/python/3.7.3/Python-3.7.3.tgz

tar -xzf Python-3.7.3.tgz
rm -f Python-3.7.3.tgz

cd Python-3.7.3

opts=""
opts="$opts --without-dtrace"
opts="$opts --disable-profiling"
opts="$opts --disable-ipv6"
opts="$opts --without-pydebug"
opts="$opts --enable-optimizations"
opts="$opts --with-ensurepip=install"
opts="$opts --with-ssl-default-suites=openssl"

./configure $opts
make install

cd /tmp
rm -rf Python-3.7.3

pip3 install --upgrade pip
pip3 install psycopg2
pip3 install cftime
pip3 install python-dateutil
pip3 install pylint
