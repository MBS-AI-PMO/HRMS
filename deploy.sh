#!/bin/bash

# Hostinger Deployment Script
# Run this after git pull to ensure proper build

echo "=== HRMS Deployment Script ==="
echo "Installing Node dependencies..."
npm install --legacy-peer-deps --production=false

echo "Building production assets..."
npm run production

echo "Clearing Laravel cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Deployment Complete ==="
echo "Visit https://ems.solochoicezz.com to verify"
