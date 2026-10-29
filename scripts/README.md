# GmatGroup: One-Command Code and Database Deployment

This directory contains the production deployment automation for the GmatGroup application ([https://www.gmatgroup.com](https://www.gmatgroup.com)).

The pipeline allows you to develop locally, modify code or update database records, and deploy everything to the live production server with a single command.

---

## Architecture Overview

```
 Local Machine (Dev)                          Production Server (Live)
┌───────────────────────────────┐            ┌────────────────────────────────┐
│ 1. Verify Git working tree    │            │ 5. Pull latest Git commit      │
│    (Stage & Push to GitHub)   │            │ 6. composer install --no-dev   │
│ 2. artisan db:export-for-deploy│   SSH/SCP  │ 7. Pre-deploy DB backup        │
│    - Dumps schema & data      │ ─────────> │ 8. Import database dump        │
│    - Gzips SQL archive        │            │ 9. php artisan migrate --force │
│ 3. Transfer dump via SCP      │            │ 10. Rebuild config/route caches│
│ 4. SSH Remote Execution       │            │ 11. Healthcheck (HTTP 200)     │
└───────────────────────────────┘            └────────────────────────────────┘
```

---

## 1. Quick Start / Normal Everyday Deployment

To deploy all local code changes, commit them to GitHub, export the local database, and import it into production:

```bash
bash scripts/deploy.sh
```

*(Optional: You can supply a custom commit message with `-m "Your message"`)*
```bash
bash scripts/deploy.sh -m "Updated MBA articles and study group styling"
```

The script automatically:
1. **Detects uncommitted changes** in your local project and commits them to Git.
2. **Pushes the commit to GitHub** (`origin/main`).
3. **Exports the local MySQL database** (`gmatgroup`) using `php artisan db:export-for-deploy` into a compressed `.sql.gz` archive.
4. **Uploads the archive to production** via secure SCP.
5. **Creates a safety backup** of the current production database (`mysqldump`).
6. **Pulls the latest code** on the server (`git pull origin main`) and installs production Composer dependencies.
7. **Imports the database dump** directly into MariaDB/MySQL on the production server.
8. **Runs migrations & rebuilds caches** (`config:cache`, `route:cache`, `view:cache`).
9. **Verifies live site health** by testing endpoints (`/`, `/login`, `/articles`).

---

## 2. Command-Line Options & Flags

| Flag | Purpose |
|---|---|
| `-m "message"` | Custom Git commit message. |
| `--dry-run` | Simulates deployment and bundle export without changing anything on production. |
| `--skip-db` | Deploys code only via Git and runs migrations without exporting or importing the database. |
| `--skip-code` | Uploads and imports the database dump only, skipping `git pull` and `composer install`. |
| `--no-git` | Skips automatic git add, commit, and push (requires clean working tree). |
| `--no-backup` | Skips creating the pre-deployment database backup on the production server. |
| `--config <path>` | Loads an alternative configuration file instead of `scripts/deploy.env`. |
| `-h`, `--help` | Displays usage instructions and available options. |

### Examples:

- **Deploy code changes only (skip database import)**:
  ```bash
  bash scripts/deploy.sh --skip-db
  ```
- **Deploy database only (skip code git pull)**:
  ```bash
  bash scripts/deploy.sh --skip-code
  ```
- **Simulate deployment with dry run**:
  ```bash
  bash scripts/deploy.sh --dry-run
  ```

---

## 3. Artisan Commands Reference

### Exporting Local Database
```bash
# Export local database to storage/app/deployments/exports/
php artisan db:export-for-deploy

# Export to a custom path
php artisan db:export-for-deploy --output=my_custom_backup.sql
```

### Importing Database
```bash
# Import an SQL or .sql.gz dump into the configured database
php artisan db:import-for-deploy /path/to/database_dump.sql.gz
```
