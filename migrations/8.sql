ALTER TABLE room
ADD CONSTRAINT owner_id
    FOREIGN KEY (owner_id)
    REFERENCES user (user_id)
    ON DELETE CASCADE;

ALTER TABLE listing
DROP FOREIGN KEY listing_ibfk_1;

ALTER TABLE listing
ADD CONSTRAINT room_id
    FOREIGN KEY (room_id)
    REFERENCES room (room_id)
    ON DELETE CASCADE;

ALTER TABLE opt_in
ADD CONSTRAINT user_id
    FOREIGN KEY (user_id)
    REFERENCES user (user_id)
    ON DELETE CASCADE;

ALTER TABLE opt_in
ADD CONSTRAINT listing_id
    FOREIGN KEY (listing_id)
    REFERENCES listing (listing_id)
    ON DELETE CASCADE;