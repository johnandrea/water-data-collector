#!/bin/bash

mkdir /home/info
rpm -q -a | sort > /home/info/rpm.00

yum -y update

rpm -q -a | sort > /home/info/rpm.01

rpm --erase alsa-firmware alsa-tools-firmware
rpm --erase audit
rpm --erase selinux-policy selinux-policy-targeted

rpm -q -a | sort > /home/info/rpm.02

yum -y install firewalld tcp_wrappers
yum -y install bindutils nano nmap lsof telnet ftp tar findutils file
yum -y install dos2unix unix2dos zip unzip zlib wget man-pages logrotate
yum -y install bc ed set patch rsync vim

yum -y install postfix procmail mailx
yum -y install expat

rpm -q -a | sort > /home/info/rpm.03

yum -y install postgresql-server postgresql-devel postgresql-contrib
yum -y install httpd php php-devel php-pdo php-pgsql

rpm -q -a | sort > /home/info/rpm.04

# to get python 37 it needs to be built from source
#yum -y install epel-release
#yum -y install python36

systemctl enable firewalld
systemctl start  firewalld

firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --reload
