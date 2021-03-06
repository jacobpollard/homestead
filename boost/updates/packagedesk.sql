CREATE TABLE hms_package_desk (
    id          integer NOT NULL,
    name        character varying,
    location    character varying,
    street      character varying,
    city        character varying,
    state       character varying,
    zip         character varying,
    PRIMARY KEY(id)
);

create sequence hms_package_desk_seq;

insert into hms_package_desk VALUES (nextval('hms_package_desk_seq'), 'East Service Desk', 'East Service Desk', '214 Locust Street', 'Boone', 'NC', '28608');
insert into hms_package_desk VALUES (nextval('hms_package_desk_seq'), 'West Service Desk', 'West Service Desk', '193 Stadium Drive', 'Boone', 'NC', '28608');

ALTER TABLE hms_residence_hall ADD COLUMN package_desk integer REFERENCES hms_package_desk(id);
UPDATE hms_residence_hall SET package_desk = 1;
ALTER TABLE hms_residence_hall ALTER COLUMN package_desk SET NOT NULL;

CREATE TABLE hms_package (
    id                  integer NOT NULL,
    carrier             character varying,
    tacking_number      character varying,
    addressed_to        character varying,
    addressed_phone     character varying,
    recipient_banner_id integer NOT NULL,
    received_on         integer NOT NULL,
    received_by         character varying,
    package_desk        integer NOT NULL references hms_package_desk(id),
    pickedup_on         integer,
    released_by         character varying,
    PRIMARY KEY(id)
);