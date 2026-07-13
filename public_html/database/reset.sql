-- WARNING: this permanently deletes every Uploady table and all data in them
-- (users, connected accounts, videos, invoices — everything). Only run this if you're
-- recovering from a broken/partial install and don't have real client data yet.
--
-- Use this to fix "Duplicate key on write or update" (errno 121) or similar errors when
-- (re-)running schema.sql — that error means a leftover table/constraint from a previous
-- partial run is conflicting with the new one. Dropping everything and running schema.sql
-- fresh, exactly once, clears it.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS oauth_states;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS post_targets;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS social_accounts;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS plans;
SET FOREIGN_KEY_CHECKS = 1;

-- After this runs successfully, execute database/schema.sql once (and nothing else —
-- don't also run files under database/migrations/, those are only for upgrading an
-- already-populated older database).
