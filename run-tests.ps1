# PowerShell script to run backend tests easily

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "WordPress Backend Tests Runner" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if Docker is running
Write-Host "Checking Docker container..." -ForegroundColor Yellow
$containerStatus = docker ps --filter "name=wordpress-sqe" --format "{{.Status}}" 2>&1

if ($LASTEXITCODE -ne 0 -or $containerStatus -eq "") {
    Write-Host "ERROR: wordpress-sqe container is not running!" -ForegroundColor Red
    Write-Host "Please start Docker and run: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}

Write-Host "✓ Container is running" -ForegroundColor Green
Write-Host ""

# Menu
Write-Host "Select test suite to run:" -ForegroundColor Cyan
Write-Host "1. All tests"
Write-Host "2. Unit tests only"
Write-Host "3. Integration tests only"
Write-Host "4. User tests only"
Write-Host "5. Post tests only"
Write-Host "6. API tests only"
Write-Host "7. Authentication tests only"
Write-Host "8. Database tests only"
Write-Host ""

$choice = Read-Host "Enter your choice (1-8)"

$testCommand = switch ($choice) {
    "1" { "tests/ --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "2" { "tests/unit/ --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "3" { "tests/integration/ --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "4" { "tests/unit/UserTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "5" { "tests/unit/PostTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "6" { "tests/integration/APITest.php --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "7" { "tests/integration/AuthenticationTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    "8" { "tests/unit/DatabaseTest.php --bootstrap tests/bootstrap.php --no-configuration --testdox" }
    default { 
        Write-Host "Invalid choice. Running all tests..." -ForegroundColor Yellow
        "tests/ --bootstrap tests/bootstrap.php --no-configuration --testdox"
    }
}

Write-Host ""
Write-Host "Running tests..." -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Run the tests
docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit $testCommand"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Tests completed!" -ForegroundColor Green

