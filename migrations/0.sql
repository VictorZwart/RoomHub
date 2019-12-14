# assuming ddwt19_fp with right user privileges exist

CREATE TABLE user
(
    user_id      int auto_increment not null primary key,
    username     varchar(255)       not null unique,
    password     varchar(255)       not null,
    phone_number varchar(10)        not null,
    email        varchar(255)       not null,
    language     varchar(255)       not null,
    birthdate    date               not null,
    biography    text,
    first_name   varchar(255)       not null,
    last_name    varchar(255)       not null,
    occupation   varchar(255)       not null,
    role         varchar(10)        not null,

    CHECK (role IN ('tennant', 'owner'))
);

CREATE TABLE room
(
    room_id     int auto_increment not null primary key,
    owner_id    int                not null,
    description text               not null,
    price       float              not null,
    size        varchar(20)        not null,
    type        varchar(100)       not null,
    /* check type in ... */
    city        varchar(255)       not null,
    zipcode     char(6)            not null,
    street_name varchar(255)       not null,
    number      varchar(10)        not null,

    FOREIGN KEY (owner_id) REFERENCES user (user_id)
    /* check if user is owner */
);

CREATE TABLE attribute
(
    attr_id int auto_increment not null primary key,
    attr    varchar(255)       not null,
    room_id int                not null,

    FOREIGN KEY (room_id) REFERENCES room (room_id)
);



CREATE TABLE listing
(
    listing_id     int auto_increment not null primary key,
    status         varchar(10)        not null,
    available_from date               not null,
    available_to   date               not null,
    room_id        int                not null,

    CHECK (status in ('open', 'cancelled', 'closed', 'other')),
    FOREIGN KEY (room_id) REFERENCES room (room_id)
);

CREATE TABLE opt_in
(
    opt_in_id  int auto_increment not null primary key,
    listing_id int                not null,
    user_id    int                not null,
    message    text               not null,
    date       datetime default LOCALTIME(),

    FOREIGN KEY (listing_id) REFERENCES listing (listing_id),
    FOREIGN KEY (user_id) REFERENCES user (user_id)

);

CREATE TABLE migration
(
    migration_id   int primary key auto_increment,
    migration_date datetime default LOCALTIME(),
    migration_file varchar(255) not null
);

