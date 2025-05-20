# Database Schema Documentation

## Tables

### users
- `id` - Primary key
- `name` - User's full name
- `email` - User's email (unique)
- `email_verified_at` - Timestamp when email was verified
- `password` - Hashed password
- `remember_token` - For "remember me" functionality
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

### assets
- `id` - Primary key
- `name` - Asset name
- `asset_tag` - Unique identifier for the asset
- `model_id` - Foreign key to asset_models
- `status_id` - Foreign key to asset_statuses
- `assigned_to` - Foreign key to users (nullable)
- `purchase_date` - When the asset was purchased
- `purchase_cost` - Purchase cost
- `warranty_months` - Warranty period in months
- `notes` - Additional notes
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

### asset_models
- `id` - Primary key
- `name` - Model name (e.g., "MacBook Pro 16")
- `manufacturer_id` - Foreign key to manufacturers
- `category_id` - Foreign key to categories
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

### asset_statuses
- `id` - Primary key
- `name` - Status name (e.g., "Available", "Deployed", "In Maintenance")
- `type` - Status type (e.g., "deployable", "undeployable")
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

### maintenance_records
- `id` - Primary key
- `asset_id` - Foreign key to assets
- `user_id` - Foreign key to users (who performed the maintenance)
- `title` - Maintenance title
- `notes` - Maintenance notes
- `cost` - Maintenance cost
- `completed_at` - When maintenance was completed
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

## Relationships

- A user can be assigned many assets
- An asset belongs to one model
- An asset has one status
- An asset can have many maintenance records
- A maintenance record belongs to one asset and one user

## Indexes

- Primary keys on all tables
- Foreign key indexes
- Unique constraint on `users.email`
- Unique constraint on `assets.asset_tag`
- Index on frequently queried columns (status_id, model_id, etc.)

## Migrations

Database changes are managed through Laravel migrations in `database/migrations/`. Each migration file includes a timestamp and a description of the changes.
