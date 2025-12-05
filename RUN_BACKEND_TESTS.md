# How to Run Backend Tests Yourself

## Quick Start Guide

### Step 1: Make sure Docker is running
```powershell
# Check if containers are running
docker ps
```

You should see `wordpress-sqe` container running.

### Step 2: Run tests from your computer (PowerShell)

**Option A: Run all tests**
```powershell
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/ --bootstrap tests/bootstrap.php --no-configuration --testdox"
```

**Option B: Run specific test suite**
```powershell
# Unit tests only
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/unit/ --bootstrap tests/bootstrap.php --no-configuration --testdox"

# Integration tests only
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/integration/ --bootstrap tests/bootstrap.php --no-configuration --testdox"
```

**Option C: Run a specific test file**
```powershell
# User tests
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/unit/UserTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox"

# Post tests
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/unit/PostTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox"

# API tests
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/integration/APITest.php --bootstrap tests/bootstrap.php --no-configuration --testdox"
```

### Step 3: Enter container interactively (Alternative Method)

If you prefer to work inside the container:

```powershell
# Enter the container
docker exec -it wordpress-sqe bash

# Once inside, navigate to project
cd /var/www/html/project

# Run tests
./vendor/bin/phpunit tests/ --bootstrap tests/bootstrap.php --no-configuration --testdox
```

## Expected Output

You should see output like:

```
PHPUnit 9.6.30 by Sebastian Bergmann and contributors.

API (Tests\Integration\API)
 ✔ Get posts endpoint
 ✔ Create post via api
 ...

Authentication (Tests\Integration\Authentication)
 ✔ Complete login flow
 ...

Time: 00:35.901, Memory: 42.00 MB

OK, but incomplete, skipped, or risky tests!
Tests: 40, Assertions: 179, Skipped: 1.
```

## Troubleshooting

### If tests don't run:

1. **Check if container is running:**
   ```powershell
   docker ps | findstr wordpress-sqe
   ```

2. **Check if PHPUnit is installed:**
   ```powershell
   docker exec wordpress-sqe bash -c "cd /var/www/html/project && ls -la vendor/bin/phpunit"
   ```

3. **If PHPUnit is missing, install dependencies:**
   ```powershell
   docker exec wordpress-sqe bash -c "cd /var/www/html/project && composer install"
   ```

4. **Check if WordPress is loaded:**
   ```powershell
   docker exec wordpress-sqe bash -c "cd /var/www/html/project && php -r \"require 'tests/bootstrap.php'; echo 'WordPress loaded: ' . (function_exists('wp_insert_post') ? 'YES' : 'NO');\""
   ```

### Common Issues:

- **"Cannot find phpunit"**: Run `composer install` inside container
- **"WordPress functions not available"**: Check `tests/bootstrap.php` is loading WordPress correctly
- **"Database connection failed"**: Make sure database container is running (`docker ps`)

## Test Files Location

- Unit Tests: `tests/unit/`
  - `UserTest.php`
  - `PostTest.php`
  - `DatabaseTest.php`
  
- Integration Tests: `tests/integration/`
  - `APITest.php`
  - `AuthenticationTest.php`

