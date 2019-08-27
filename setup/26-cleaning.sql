drop table cleaned_data;

create table cleaned_data(
  cleaned_at timestamp without time zone default now(),
  data_time timestamp without time zone not null,
  site_id integer not null references sites(id),
  name text not null,
  value float,
  operation text,
  comment text
);

comment on table cleaned_data is 'Manually cleaned data';
comment on column cleaned_data.name is 'Sensor name, or null if whole row.';
comment on column cleaned_data.value is 'Value before cleannig, or null if whole row.';

revoke all on table cleaned_data from public;
grant all on cleaned_data to postgres;
grant select on cleaned_data to web_updatable;
grant select on table cleaned_data to data_readable;
grant select,insert on table cleaned_data to data_writable;
