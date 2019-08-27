\c data

insert into sensors(name,units,match_key) values ('battery','V','storx/battery');
insert into sensors(name,units,match_key) values ('latitude','deg','gps/latitude');
insert into sensors(name,units,match_key) values ('longitude','deg','gps/longitude');
insert into sensors(name,units,match_key) values ('temperature','C','water/temperature');
insert into sensors(name,units,match_key) values ('conductivity','uS/cm','water/conductivity');
insert into sensors(name,units,match_key) values ('turbidity','NTU','water/turbidity');
insert into sensors(name,units,match_key) values ('chlorophyll','ug/L','water/chlorophyll');
insert into sensors(name,units,match_key) values ('oxygen_saturation','%','water/oxygen saturation');
insert into sensors(name,units,match_key) values ('oxygen','mg/L','water/oxygen');

update sensors set description='StorX battery' where name='battery';
update sensors set description='Latitude' where name='latitude';
update sensors set description='Longitude' where name='longitude';
update sensors set description='GPS HDOP' where name='hdop';
update sensors set description='Temperature' where name='temperature';
update sensors set description='Conductivity' where name='conductivity';
update sensors set description='Turbidity' where name='turbidity';
update sensors set description='Chlorophyll' where name='chlorophyll';
update sensors set description='Oxygen Saturation' where name='oxygen_saturation';
update sensors set description='Oxygen' where name='oxygen';
update sensors set description='Wind Speed' where name='wind_speed';
update sensors set description='Wind Direction' where name='wind_dir';
update sensors set description='Wind Direction stddev' where name='wind_dir_stddev';
update sensors set description='Air Temperature' where name='air_temperature';
update sensors set description='Relative Humidity' where name='relative_humidity';
update sensors set description='Barometric Pressure' where name='barometric_pressure';
update sensors set description='Rainfall 20min' where name='rainfall_20m';
update sensors set description='Wave Temperature' where name='wave_temperature';
update sensors set description='Wave Battery' where name='wave_battery';
update sensors set description='Wave Height' where name='wave_height';
update sensors set description='Max Wave Height' where name='max_wave_height';
update sensors set description='Wave Period' where name='wave_period';
update sensors set description='Wave Direction' where name='wave_dir';
update sensors set description='Wave Spread' where name='wave_spread';
update sensors set description='Wave Quality_code' where name='wave_quality_code';
update sensors set description='Current Velocity 1' where name='current_velocity1';
update sensors set description='Current Direction 1' where name='current_dir1';
update sensors set description='Current Velocity 2' where name='current_velocity2';
update sensors set description='Current Direction 2' where name='current_dir2';
update sensors set description='Current Velocity 3' where name='current_velocity3';
update sensors set description='Current Direction 3' where name='current_dir3';
update sensors set description='Current Velocity 4' where name='current_velocity4';
update sensors set description='Current Direction 4' where name='current_dir4';
update sensors set description='Current Velocity 5' where name='current_velocity5';
update sensors set description='Current Direction 5' where name='current_dir5';
update sensors set description='Current Velocity 6' where name='current_velocity6';
update sensors set description='Current Direction 6' where name='current_dir6';
update sensors set description='Current Velocity 7' where name='current_velocity7';
update sensors set description='Current Direction 7' where name='current_dir7';

update sensors set for_summary=true where name='temperature';
update sensors set for_summary=true where name='oxygen';
update sensors set for_summary=true where name='turbidity';

update sensors set for_public=true where name='temperature';
update sensors set for_public=true where name='oxygen';
update sensors set for_public=true where name='turbidity';

update sensors set cdf_units = 'Celsius' where units='C';
update sensors set cdf_units = 'degrees_north' where name='latitude';
update sensors set cdf_units = 'degrees_east'  where name='longitude';

update sensors set notify_limit_low = 8 where name='battery';

-- insert into settings( name,value) values ('website header','This is a test system');
-- insert into settings( name,value) values ('icon url',null);
