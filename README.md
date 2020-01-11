# RoomHub
Roomhub is an interactive fun website where you can find a room in groningen!

This project is made by Erik, Robin, Lars and Victor!

## how to install (from source)
```
git clone git@github.com:VictorZwart/RoomHub.git
cd RoomHub
composer install
# execute db.sql in phpmyadmin
# it should work now!
```

## Help, I get an CONSTRAINT_1 error!
Mysql (/mariadb) is really weird with CHECKs, and some engines do validate them, and some dont.
If you get an error like constraint_1 failed on user, try `ALTER TABLE user DROP CONSTRAINT CONSTRAINT_1;`
The constraints are not really needed, because we have validators in our PHP.