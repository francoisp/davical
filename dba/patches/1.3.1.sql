
-- Notable enhancement:	Add/Alter tables for dealing with remote attendee handling

BEGIN;
SELECT check_db_revision(1,2,12);


CREATE TABLE calendar_attendee_email_status (
  email_status_id INT8 PRIMARY KEY,
  description TEXT NOT NULL
);

INSERT INTO calendar_attendee_email_status (email_status_id, description)
  VALUES
  (1, 'waiting for invitation to be sent'),
  (2, 'invitation has been sent'),
  (3, 'waiting for schedule change to be sent'),
  (4, 'schedule change has been sent'),
  (11, 'attendee has accepted'),
  (12, 'attendee indicated maybe'),
  (13, 'attendee has refused')
;


ALTER TABLE calendar_attendee_dav
  ADD COLUMN email_status INT REFERENCES calendar_attendee_email_status(email_status_id) DEFAULT 1 NOT NULL ;

ALTER TABLE calendar_attendee_dav
  ADD COLUMN is_remote BOOLEAN DEFAULT FALSE;

-- A new year!  http://blogs.transparent.com/polish/names-of-the-months-and-their-meaning/
SELECT new_db_revision(1,3,1, 'Styczeń' );

COMMIT;
ROLLBACK;

