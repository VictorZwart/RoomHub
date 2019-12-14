-- assuming ddwt19_fp with right user privileges exist

CREATE TABLE siteuser
(
    user_id      serial primary key,
    username     varchar(255) not null unique,
    password     varchar(255) not null,
    phone_number varchar(10)  not null,
    email        varchar(255) not null,
    language     varchar(255) not null,
    birthdate    date         not null,
    biography    text,
    first_name   varchar(255) not null,
    last_name    varchar(255) not null,
    occupation   varchar(255) not null,
    role         varchar(10)  not null,

    CHECK (role IN ('tennant', 'owner'))
);

CREATE TABLE room
(
    room_id     serial primary key,
    owner_id    bigint unsigned not null,
    description text            not null,
    price       float           not null,
    size        varchar(20)     not null,
    type        varchar(100)    not null,
    /* check type in ... */
    city        varchar(255)    not null,
    zipcode     char(6)         not null,
    street_name varchar(255)    not null,
    number      varchar(10)     not null,

    FOREIGN KEY (owner_id) REFERENCES siteuser (user_id)
    /* check if user is owner */
);

CREATE TABLE attribute
(
    attr_id serial primary key,
    attr    varchar(255)    not null,
    room_id bigint unsigned not null,

    FOREIGN KEY (room_id) REFERENCES room (room_id)
);



CREATE TABLE listing
(
    listing_id     serial primary key,
    status         varchar(10)     not null,
    available_from date            not null,
    available_to   date            not null,
    room_id        bigint unsigned not null,

    CHECK (status in ('open', 'cancelled', 'closed', 'other')),
    FOREIGN KEY (room_id) REFERENCES room (room_id)
);

CREATE TABLE opt_in
(
    opt_in_id  serial primary key,
    listing_id bigint unsigned not null,
    user_id    bigint unsigned not null,
    message    text            not null,
    date       datetime default LOCALTIME(),

    FOREIGN KEY (listing_id) REFERENCES listing (listing_id),
    FOREIGN KEY (user_id) REFERENCES siteuser (user_id)

);

CREATE TABLE migration
(
    migration_id   serial primary key,
    migration_date datetime default LOCALTIME(),
    migration_file varchar(255) not null
);
