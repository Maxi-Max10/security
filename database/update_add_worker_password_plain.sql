-- Add plaintext password column for workers (use with caution)
ALTER TABLE workers
    ADD COLUMN password_plain VARCHAR(255) NULL AFTER password;
