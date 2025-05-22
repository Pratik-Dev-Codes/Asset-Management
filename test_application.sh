#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting Laravel Application Tests...${NC}\n"

# Check if PHP is installed
php -v > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}PHP is not installed or not in PATH${NC}"
    exit 1
fi

# Check if Composer is installed
composer -V > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}Composer is not installed or not in PATH${NC}"
    exit 1
fi

# Check if Node.js is installed
node -v > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}Node.js is not installed or not in PATH${NC}"
fi

# Check if NPM is installed
npm -v > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}NPM is not installed or not in PATH${NC}"
fi

# Install dependencies
echo -e "\n${YELLOW}Installing PHP dependencies...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install NPM dependencies if package.json exists
if [ -f "package.json" ]; then
    echo -e "\n${YELLOW}Installing NPM dependencies...${NC}"
    npm install
    
    echo -e "\n${YELLOW}Building assets...${NC}"
    npm run dev
fi

# Create environment file if it doesn't exist
if [ ! -f ".env" ]; then
    echo -e "\n${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    php artisan key:generate
fi

# Set application key if not set
if grep -q '^APP_KEY=$' .env; then
    echo -e "\n${YELLOW}Generating application key...${NC}"
    php artisan key:generate
fi

# Create storage link if it doesn't exist
if [ ! -L "public/storage" ]; then
    echo -e "\n${YELLOW}Creating storage link...${NC}"
    php artisan storage:link
fi

# Set directory permissions
echo -e "\n${YELLOW}Setting directory permissions...${NC}"
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/framework/sessions

# Clear caches
echo -e "\n${YELLOW}Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run database migrations
echo -e "\n${YELLOW}Running database migrations...${NC}
php artisan migrate:fresh --seed

# Run tests
echo -e "\n${YELLOW}Running PHPUnit tests...${NC}
php artisan test --stop-on-failure

# Check for PHP syntax errors
echo -e "\n${YELLOW}Checking for PHP syntax errors...${NC}" 
find app -type f -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# Check for JavaScript errors
if [ -f "webpack.mix.js" ]; then
    echo -e "\n${YELLOW}Checking for JavaScript errors...${NC}"
    npx eslint resources/js/
fi

# Check for security vulnerabilities
echo -e "\n${YELLOW}Checking for security vulnerabilities...${NC}"
php artisan security:check

# Run static analysis
if [ -f "vendor/bin/phpstan" ]; then
    echo -e "\n${YELLOW}Running static analysis...${NC}"
    vendor/bin/phpstan analyse
fi

# Check code style
if [ -f "vendor/bin/phpcs" ]; then
    echo -e "\n${YELLOW}Checking code style...${NC}"
    vendor/bin/phpcs --standard=PSR12 app/
fi

echo -e "\n${GREEN}Test script completed!${NC}\n"

# Display any remaining issues
if [ -f "storage/logs/laravel.log" ]; then
    echo -e "${YELLOW}Recent errors from laravel.log:${NC}"
    tail -n 20 storage/logs/laravel.log
fi

echo -e "\n${GREEN}Application is ready for testing!${NC}"
echo -e "Run: ${YELLOW}php artisan serve${NC} to start the development server"
echo -e "Then visit: ${YELLOW}http://localhost:8000${NC} in your browser\n"
