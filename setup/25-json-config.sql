drop table framer_configs;
drop index instrument_name_unique;
drop table instruments;

create table instruments (
  id serial unique not null,
  name text not null,
  comment text,
  is_usable boolean default true
);

comment on column instruments.is_usable is 'Otherwise ignore this type';

create unique index instrument_name_unique
       on instruments (lower(trim(both from name)));

create table framer_configs(
   id serial unique not null,
   created_at timestamp without time zone not null default now(),
   instrument_id integer not null references instruments(id),
   serial text not null,
   comment text,
   config json not null,
   is_usable boolean default true,
   is_changed boolean default true
);

comment on column framer_configs.created_at is 'Date of upload';
comment on column framer_configs.is_usable is 'Otherwise ignore this instance';
comment on column framer_configs.is_active is 'Has been placed into the package';
comment on column framer_configs.passed_test is 'Is a valid configuration';
comment on column framer_configs.is_changed is 'Needs handling';

grant all on table instruments to postgres;
grant select on table instruments to data_readable;
grant select,insert,update on table instruments to data_writable;
grant select,insert,update on table instruments to web_updatable;

grant all on sequence instruments_id_seq to postgres;
grant all on sequence instruments_id_seq to data_readable;
grant all on sequence instruments_id_seq to data_writable;
grant all on sequence instruments_id_seq to web_updatable;

grant all on table framer_configs to postgres;
grant select on table framer_configs to data_readable;
grant select,insert,update on table framer_configs to data_writable;
grant select,insert,update on table framer_configs to web_updatable;

grant all on sequence framer_configs_id_seq to postgres;
grant all on sequence framer_configs_id_seq to data_readable;
grant all on sequence framer_configs_id_seq to data_writable;
grant all on sequence framer_configs_id_seq to web_updatable;
