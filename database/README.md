# Wedding Za Database Setup

1. Create a MySQL database and database user.
2. Import `database/schema.sql`.
3. Copy `config.example.php` to `config.local.php`.
4. Fill in the database credentials and production app URL.
5. Keep `config.local.php` private. It is ignored by Git.

The application automatically uses MySQL when the configuration is valid.

If MySQL is not configured, the public discovery experience still works and lead forms fall back to the local CSV storage used for development.
