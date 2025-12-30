# E-Commerce Shopping Cart Application

A modern Laravel-based e-commerce shopping cart system featuring product catalog browsing, cart management, user authentication, automated inventory notifications, and sales reporting.

[![Tests](https://img.shields.io/badge/tests-73%20passed-success)](tests)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://php.net)

## Features

### 🛒 Shopping Cart
- Browse products with pagination
- Add products to cart with quantity selection
- Update cart item quantities
- Remove items from cart
- Real-time cart item count badge
- Cart total calculation
- Stock validation on all cart operations
- User-isolated carts with authorization

### 👤 User Authentication
- User registration and login (Laravel Fortify)
- Email verification
- Password reset functionality
- Secure session management
- Cart persistence across sessions

### 📦 Product Management
- Product catalog with details (name, price, stock, description)
- Stock level tracking
- Low stock threshold monitoring
- Product factory for testing and seeding

### 🔔 Automated Notifications
- **Low Stock Alerts**: Automated email notifications when products reach low stock threshold
- **Daily Sales Reports**: Scheduled daily sales summary sent to admin
- Duplicate notification prevention
- Database tracking of sent notifications
- Manual notification triggers via Artisan commands

### 📊 Reporting & Analytics
- Daily sales metrics (total sales, items sold, revenue)
- Top-selling products tracking
- Sales aggregation by date
- Historical report data

## Tech Stack

- **PHP**: 8.3
- **Laravel**: 11.x
- **Database**: MySQL/SQLite
- **Frontend**: Livewire 3.x, Tailwind CSS
- **Authentication**: Laravel Fortify
- **Queue**: Database driver
- **Testing**: PHPUnit/Pest

## Architecture Patterns

This application demonstrates clean architecture principles:

✅ **Service Layer** - Business logic separation (`CartService`, `ProductService`)
✅ **Action Classes** - Discrete operations (`AddProductToCart`, `RemoveProductFromCart`, `UpdateCartItemQuantity`)
✅ **Model Accessors** - Computed properties and formatting (no DTOs for simplicity)
✅ **Livewire Components** - Reactive UI without full SPA complexity
✅ **Job/Queue System** - Background processing for notifications
✅ **Policy-Based Authorization** - Secure resource access control

### Model Accessor Pattern

Instead of DTOs, this project uses Laravel's native model accessors:

**Product Model**:
```php
$product->isInStock()           // Check stock availability
$product->getFormattedPrice()   // Formatted price ($XX.XX)
```

**Cart Model**:
```php
$cart->getItemCount()           // Total items count
$cart->getTotal()               // Cart total (float)
$cart->getFormattedTotal()      // Formatted total ($XX.XX)
$cart->isEmpty()                // Empty cart check
```

**CartItem Model**:
```php
$item->product_name             // Product name accessor
$item->getSubtotal()            // Line item subtotal
$item->getFormattedPrice()      // Formatted product price
$item->getFormattedSubtotal()   // Formatted subtotal
```

## Installation & Setup

### Prerequisites
- PHP 8.3 or higher
- Composer
- MySQL or SQLite
- Node.js & NPM (for asset compilation)

### 1. Clone the Repository
```bash
git clone https://github.com/kingsleyudenewu/trustfactory-commerce.git
cd tf-ecommerce
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment Variables
Edit `.env` and set:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tf_ecommerce
DB_USERNAME=root
DB_PASSWORD=

# Admin email for notifications
ADMIN_EMAIL=admin@example.com

# Low stock threshold (default: 5)
LOW_STOCK_THRESHOLD=5

# Mail configuration for notifications
MAIL_MAILER=log  # Use 'smtp' for production
```

### 5. Database Setup
```bash
php artisan migrate --seed
```

This creates:
- Database tables (products, carts, cart_items, users, etc.)
- Test user: `test@example.com` / `password`
- 24 sample products (20 normal stock + 4 low stock)

### 6. Compile Assets
```bash
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit: http://localhost:8000

### 8. Queue Worker (for background jobs)
In a separate terminal:
```bash
php artisan queue:work
```

### 9. Task Scheduler (for automated reports)
In a separate terminal:
```bash
php artisan schedule:work
```

Or configure cron:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Quick Start

### Access the Application
**Home Page**: http://localhost:8000/
- Login/Register links
- "Shop" link for authenticated users

**Test User Credentials**:
```
Email: test@example.com
Password: password
```

**Shopping Routes**:
- Browse Products: http://localhost:8000/products
- Shopping Cart: http://localhost:8000/cart
- Dashboard: http://localhost:8000/dashboard

## Database Schema

### Core Tables

**products**
- `id`, `name`, `price`, `stock_quantity`, `description`
- Tracks product inventory

**carts**
- `id`, `user_id`
- One cart per authenticated user

**cart_items**
- `id`, `cart_id`, `product_id`, `quantity`
- Items in each cart with quantities

**users**
- Standard Laravel user table with authentication fields

### Notification & Reporting Tables

**product_low_stock_notifications**
- Tracks sent low stock alerts to prevent duplicates

**daily_sales_reports**
- Stores daily sales metrics and aggregated data

## Artisan Commands

### Send low stock notifications
```bash
# Check all products
php artisan notification:low-stock

# Check specific product
php artisan notification:low-stock 5
```

### Generate daily sales report
```bash
# Today's report
php artisan report:daily-sales

# Specific date
php artisan report:daily-sales --date=2025-01-15
```

### Clean old notifications
```bash
php artisan notification:cleanup
```

## Scheduled Tasks

Configured in `routes/console.php`:

- **Daily Sales Report**: 6:00 PM daily
- **Notification Cleanup**: Midnight daily (removes 90+ day old notifications)

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suites
```bash
# Authentication tests
php artisan test --filter=AuthenticationTest

# Cart functionality tests
php artisan test --filter=UserAuthenticatedCartTest

# Notification tests
php artisan test --filter=LowStockNotificationTest

# Sales report tests
php artisan test --filter=DailySalesReportTest
```

### Test Coverage
- ✅ 73 tests covering all features
- ✅ 135 assertions
- Feature tests for auth, cart, notifications, reports
- Integration tests for end-to-end workflows
- Policy and authorization tests

## Performance Optimizations

### Implemented
1. **Eager Loading**: Relationships loaded efficiently (`items.product`)
2. **Pagination**: Product catalog paginated (12 per page)
3. **Query Optimization**: Using `cursor()` for large datasets in commands
4. **Database Indexes**: Foreign keys and commonly queried fields

### Recommendations for Production
- Enable query caching for product listings
- Add Redis for session/cache storage
- Implement CDN for static assets
- Add database read replicas for scaling
- Enable opcache and JIT compilation

## Project Structure

```
app/
├── Actions/Cart/           # Discrete cart operations
│   ├── AddProductToCart.php
│   ├── RemoveProductFromCart.php
│   └── UpdateCartItemQuantity.php
├── Console/Commands/       # Artisan commands
│   ├── CleanupNotificationsCommand.php
│   ├── SendLowStockNotificationCommand.php
│   └── GenerateDailySalesReportCommand.php
├── Jobs/                   # Queue jobs
│   ├── GenerateDailySalesReportJob.php
│   └── SendLowStockNotificationJob.php
├── Livewire/              # Livewire components
│   ├── CartBadge.php
│   ├── ProductCatalog.php
│   └── ShoppingCart.php
├── Mail/                  # Email templates
│   ├── DailySalesReportMail.php
│   └── LowStockNotificationMail.php
├── Models/                # Eloquent models
│   ├── Cart.php
│   ├── CartItem.php
│   ├── Product.php
│   ├── DailySalesReport.php
│   └── ProductLowStockNotification.php
├── Policies/              # Authorization policies
│   └── CartPolicy.php
└── Services/              # Business logic services
    ├── CartService.php
    └── ProductService.php

database/
├── factories/             # Model factories
│   ├── ProductFactory.php
│   └── UserFactory.php
├── migrations/            # Database migrations
└── seeders/              # Database seeders
    ├── DatabaseSeeder.php
    └── ProductSeeder.php

resources/
└── views/
    ├── livewire/         # Livewire component views
    │   ├── cart-badge.blade.php
    │   ├── product-catalog.blade.php
    │   └── shopping-cart.blade.php
    └── mails/            # Email templates
        ├── daily-sales-report.blade.php
        └── low-stock-notification.blade.php

routes/
├── web.php              # Web routes
└── console.php          # Scheduled tasks

tests/
├── Feature/              # Feature tests
│   ├── Auth/
│   ├── Notifications/
│   └── Reports/
└── Unit/                # Unit tests
```

## Security Features

- ✅ CSRF protection on all forms
- ✅ Password hashing (Bcrypt)
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS protection (Blade escaping)
- ✅ Policy-based authorization
- ✅ Cart isolation per user
- ✅ Email verification support
- ✅ Rate limiting on routes

## Troubleshooting

### Common Issues

**Cart not updating**
- Check browser console for Livewire errors
- Clear browser cache
- Verify wire:click directives in views

**Queue jobs not processing**
- Ensure `queue:work` is running
- Check `failed_jobs` table for errors
- Verify database connection

**Notifications not sending**
- Check mail configuration in `.env`
- Verify `ADMIN_EMAIL` is set
- Check `product_low_stock_notifications` table

**Database issues**
```bash
# Reset and reseed
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

## Future Enhancements

- [ ] Order checkout and payment processing (Stripe/PayPal)
- [ ] Order history and tracking
- [ ] Product categories and filtering
- [ ] Product search functionality
- [ ] Product reviews and ratings
- [ ] Wishlist functionality
- [ ] Admin panel for product management
- [ ] Multi-currency support
- [ ] Inventory management dashboard
- [ ] Advanced analytics and reporting
- [ ] REST API for mobile apps
- [ ] Product image uploads

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Use meaningful commit messages

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues and feature requests, please use the GitHub issue tracker.

---

**Built with ❤️ using Laravel**
