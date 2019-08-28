drop database data;
create database data;

\c data

create table sensors(
  name text not null,
  description text,
  units text,
  cdf_units text,
  match_key text,
  for_summary boolean,
  for_public boolean default true,
  for_private boolean default true,
  is_usable boolean default true,
  checked boolean,
  notify_on_failure boolean,
  notify_on_limit boolean default true,
  notify_limit_low float,
  notify_limit_high float,
  discard_limit_low float,
  discard_limit_high float,
  is_computed boolean,
  failure_count integer default 0,
  limit_count integer default 0,
  constraint notify_limit_order check(  notify_limit_low < notify_limit_high),
  constraint notify_discard_limit_order check( discard_limit_low < discard_limit_high)
);

comment on column sensors.match_key is 'Map raw data field to sensor';
comment on column sensors.for_summary is 'Show on map';
comment on column sensors.is_usable is 'Enabled for display and reporting';
comment on column sensors.for_public is 'Show on all pages';
comment on column sensors.for_private is 'Show on non-public pages';
comment on column sensors.checked is 'Checked by defaut in web selecton';
comment on column sensors.cdf_units is 'replaces regular units for CDL/NetCDF';
comment on column sensors.is_computed is 'Computed from another value';
comment on column sensors.failure_count is 'Number of times tests as failed';
comment on column sensors.limit_count is 'Number of times tests as limit exceeded';


create unique index sensor_name_unique
       on sensors (lower(trim(both from name)));
create unique index sensor_matchkey_unique
       on sensors (lower(trim(both from match_key)));

create table sites (
  id serial unique not null,
  web_key text,
  name text not null,
  description text,
  latitude float not null,
  longitude float not null,
  storx_serial integer unique not null,
  for_summary boolean default true,
  for_public boolean default true,
  for_private boolean default true,
  is_usable boolean default true,
  checked boolean default true,
  notify_on_missing_email boolean default true,
  notify_on_sensor_fail boolean default true,
  notify_on_sensor_range boolean default true,
  newest_arrival timestamp without time zone,
  reported_missing_at timestamp without time zone,
  hours_after_email_missing integer default 32,
  missing_email_count integer default 0,
  show_summary_comment boolean default false,
  summary_comment text
);

comment on column sites.web_key is 'Lookup via HTML forms';
comment on column sites.for_summary is 'Show on map';
comment on column sites.is_usable is 'Enabled for display and reporting';
comment on column sites.for_public is 'Show on all pages';
comment on column sites.for_private is 'Show on non-public pages';
comment on column sites.checked is 'Checked by defaut in web selecton';
comment on column sites.latitude is 'Fixed location for map';
comment on column sites.longitude is 'Fixed location for map';
comment on column sites.missing_email_count is 'Number of times tested as missing';
comment on column sites.hours_after_email_missing is 'Hours after which email marked missing';


create unique index site_name_unique
       on sites (lower(trim(both from name)));
create unique index web_key_unique
       on sites (lower(trim(both from web_key)));

-- the web key needs to be characters which are easily within a url
-- and can be used in a filename
-- lower case a-z, numbers, dash, period
-- must not allow comma because the multiple keys could be a comma delim list

alter table sites add constraint
  key_requires_limited_chars check( web_key ~ '^[a-z0-9\._-]+$' );


create table data (
  site_id integer not null references sites(id),
  data_time timestamp without time zone not null,
  is_usable boolean default true,
  data_file text,
  battery float,
  latitude float,
  longitude float,
  temperature float,
  conductivity float,
  turbidity float,
  chlorophyll float,
  oxygen_saturation float,
  oxygen float
);

create unique index data_time_key ON data USING btree (data_time,site_id);

comment on column data.is_usable is 'Display in output';

create role data_readable with nologin nosuperuser nocreatedb nocreaterole;
create role data_writable with nologin nosuperuser nocreatedb nocreaterole;

revoke all on table sensors from public;
revoke all on table sites   from public;
revoke all on table data    from public;
revoke all on sequence sites_id_seq from public;

grant all on table sensors to postgres;
grant select on table sensors to data_readable;
grant select,insert,update on table sensors to data_writable;

grant all on table sites to postgres;
grant select on table sites to data_readable;
grant select,insert,update on table sites to data_writable;

grant all on table data to postgres;
grant select on table data to data_readable;
grant select,insert,update on table data to data_writable;

grant all on sequence sites_id_seq to postgres;
grant all on sequence sites_id_seq to data_readable;
grant all on sequence sites_id_seq to data_writable;

create user data_read  with login password 'read';
create user data_write with login password 'write';

grant data_readable to data_read;
grant data_writable to data_write;

-- phpPhpAdmin style user

create role web_updatable with nologin nosuperuser nocreatedb nocreaterole;

grant select,insert,update on sensors      to web_updatable;
grant select,insert,update on sites        to web_updatable;
grant select               on data         to web_updatable;
grant select,update        on sites_id_seq to web_updatable;

grant update (is_usable) on table data to web_updatable;

create user data_manager with login password 'x.x';

grant web_updatable to data_manager;

create table notifications(
   created_at timestamp without time zone default now(),
   site_id integer not null references sites(id),
   -- repeat_count integer default 0,
   message_type text,
   message text
);

comment on table notifications is 'The event notifications';

revoke all on table notifications from public;
grant all on notifications to postgres;
grant select on notifications to web_updatable;
grant select on table notifications to data_readable;
grant select,insert on table notifications to data_writable;


create table config_notifications(
   created_at timestamp without time zone not null default now(),
   is_failure boolean
);

comment on table config_notifications is 'Events about framer configurations';

revoke all on table config_notifications from public;
grant all on config_notifications to postgres;
grant select on config_notifications to web_updatable;
grant select on table config_notifications to data_readable;
grant select,insert,delete on table config_notifications to data_writable;


create table discarded_data(
  data_time timestamp without time zone not null,
  site_id integer not null references sites(id),
  name text not null,
  value float,
  reason text
);

comment on table discarded_data is 'Each data item which was discarded and why';

revoke all on table discarded_data from public;
grant all on discarded_data to postgres;
grant select on discarded_data to web_updatable;
grant select on table discarded_data to data_readable;
grant select,insert on table discarded_data to data_writable;

create table notification_emails(
  site_checks boolean default true,
  sensor_checks boolean default true,
  config_checks boolean default true,
  address text not null
);

comment on table notification_emails is 'Addresses for delivering notifications';
comment on column notification_emails.site_checks is 'Notifications about sites';
comment on column notification_emails.sensor_checks is 'Notifications about sensors';
comment on column notification_emails.general_checks is 'Notifications about general issues';

revoke all on table notification_emails from public;
grant all on notification_emails to postgres;
grant select,insert,delete,update on notification_emails to web_updatable;
grant select on table notification_emails to data_readable;
grant select,insert,delete,update on table notification_emails to data_writable;

create table settings(
  name text unique not null,
  value text
);

comment on table settings is 'For the website and data collection';

revoke all on table settings from public;

grant all on table settings to postgres;
grant select on table settings to data_readable;
grant select,update on table settings to data_writable;
grant select,update on table settings to web_updatable;
