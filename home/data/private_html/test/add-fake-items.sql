delete from framer_configs where
instrument_id=(select id from instruments where name='suna');
delete from framer_configs where
instrument_id=(select id from instruments where name='nothing');

insert into instruments (name) values ('suna');
insert into instruments (name) values ('nothing');
update instruments set is_usable=false where name='nothing';

select * from instruments;

insert into framer_configs (serial,config,instrument_id) values
('1','{"comment":"nothing"}',(select id from instruments where name='nothing'));

insert into framer_configs (serial,config,instrument_id) values
('11','{"comment":"sn 11 first"}',(select id from instruments where name='suna'));

insert into framer_configs (serial,config,instrument_id) values
('20','{"comment":"sn 20 first"}',(select id from instruments where name='suna'));

insert into framer_configs (serial,config,instrument_id,is_changed) values
('11','{"comment":"sn 11 unch"}',(select id from instruments where name='suna'),false);

insert into framer_configs (serial,config,instrument_id) values
('11','{"comment":"sn 11 second"}',(select id from instruments where name='suna'));
insert into framer_configs (serial,config,instrument_id) values
('20','{"comment":"sn 20 second"}',(select id from instruments where name='suna'));
