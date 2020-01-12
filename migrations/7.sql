ALTER TABLE opt_in
    ADD status varchar(10) not null default 'open';

ALTER TABLE opt_in
    ADD CONSTRAINT valid_status CHECK (status in ('open', 'cancelled', 'accepted', 'rejected'));
