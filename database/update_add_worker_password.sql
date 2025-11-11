-- Add password column for workers so admins can assign credentials
ALTER TABLE workers
    ADD COLUMN password VARCHAR(255) NULL AFTER email;
