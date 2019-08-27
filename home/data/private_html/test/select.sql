select c.id,i.name, c.serial, c.created_at,substring(cast(config as text),12,15)
from instruments i, framer_configs c
where i.id = c.instrument_id
and c.is_usable
and c.is_changed
and i.id in (select id from instruments where is_usable)
order by c.created_at
;
