# Quick test script - runs all backend tests

Write-Host "Running all backend tests..." -ForegroundColor Cyan
Write-Host ""

docker exec wordpress-sqe bash -c "cd /var/www/html/project && ./vendor/bin/phpunit tests/ --bootstrap tests/bootstrap.php --no-configuration --testdox"

Write-Host ""
Write-Host "Done!" -ForegroundColor Green

