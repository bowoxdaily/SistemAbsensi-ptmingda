# Laravel Scheduler Helper untuk Development
# Jalankan script ini di PowerShell untuk testing scheduler

Write-Host "========================================" -ForegroundColor Green
Write-Host "  Laravel Scheduler - Development Mode" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Starting Laravel Scheduler..." -ForegroundColor Yellow
Write-Host "This will run scheduled tasks every minute." -ForegroundColor Cyan
Write-Host ""
Write-Host "Press Ctrl+C to stop the scheduler." -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Run the scheduler
php artisan schedule:work
