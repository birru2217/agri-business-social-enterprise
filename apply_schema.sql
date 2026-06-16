-- Apply these SQL commands to your existing database to add missing columns

-- Add profile_photo column to users table
ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) AFTER location;

-- Add bio column to users table  
ALTER TABLE users ADD COLUMN bio TEXT AFTER profile_photo;
