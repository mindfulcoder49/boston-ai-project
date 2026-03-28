# Adding a New City to PublicDataWatch

This document provides the current guide for integrating a new city or region into PublicDataWatch. The architecture is multi-city, but the product is now also address-first, so onboarding a city means more than seeding a table and hoping the map finds it.

The current onboarding model has four parts:

1. **Data plumbing**: database connections, models, migrations, download config, and seeders.
2. **Map plumbing**: aggregated `data_points` tables and model wiring for the radial/full map surfaces.
3. **Product surfacing**: serviceability rules, city landing page copy, homepage coverage, and navigation exposure.
4. **Verification**: backend tests, browser checks, and production validation.

Use "Metropolis" as the example new city below.

---

## Step 1: Decide The Product Shape First

Before writing migrations, decide what this new city actually is in the product:

- crime-first preview city
- multi-dataset city
- 311-first or other dataset-specific city
- standalone city vs county/region landing page

At minimum, define:

- landing-page slug
- public-facing city name
- supported localities
- city center coordinates
- which datasets will be public on day one
- whether `/crime-address` should support the area

That product decision is reflected in `config/cities.php`, the city landing controller content, homepage coverage copy, and tests.

## Step 2: Database Setup

First, you need to create two new databases for Metropolis and configure Laravel to connect to them.

1.  **Create Databases**: In your database server, create two empty databases:
    *   `metropolis_db`
    *   `metropolis_data_db`

2.  **Configure Laravel**: Open `config/database.php` and add two new connection configurations.

    ```php
    // config/database.php
    // ... existing connections
    'metropolis_db' => [
        'driver' => 'mysql',
        'url' => env('METROPOLIS_DATABASE_URL'),
        'host' => env('METROPOLIS_DB_HOST', '127.0.0.1'),
        'port' => env('METROPOLIS_DB_PORT', '3306'),
        'database' => env('METROPOLIS_DB_DATABASE', 'metropolis_db'),
        'username' => env('METROPOLIS_DB_USERNAME', 'forge'),
        'password' => env('METROPOLIS_DB_PASSWORD', ''),
        // ... other settings copied from the main mysql connection
    ],

    'metropolis_data_db' => [
        'driver' => 'mysql',
        'url' => env('METROPOLIS_DATA_DATABASE_URL'),
        'host' => env('METROPOLIS_DATA_DB_HOST', '127.0.0.1'),
        'port' => env('METROPOLIS_DATA_DB_PORT', '3306'),
        'database' => env('METROPOLIS_DATA_DB_DATABASE', 'metropolis_data_db'),
        'username' => env('METROPOLIS_DATA_DB_USERNAME', 'forge'),
        'password' => env('METROPOLIS_DATA_DB_PASSWORD', ''),
        // ... other settings
    ],
    ```

3.  **Update Environment**: Add the corresponding credentials to your `.env` file.

---

## Step 3: Data Acquisition

Configure the system to download the raw data for the new city.

1.  **Add to Config**: Open `config/datasets.php` and add an entry for each dataset you want to download for Metropolis.

    ```php
    // config/datasets.php
    'datasets' => [
        // ... existing datasets
        [
            'name' => 'metropolis-crimes',
            'city' => 'metropolis',
            'base_url' => 'https://data.metropolis.gov/resource',
            'resource_id' => 'x1y2-z3a4',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 50000,
            'order_by_field' => 'date',
            'order_by_direction' => 'DESC',
        ],
    ],
    ```

2.  **Download Data**: Run the download command. This will fetch the CSV file and place it in `storage/app/datasets/metropolis/`.

    ```bash
    php artisan app:download-city-dataset
    ```

---

## Step 4: Create The Models And Migrations

Create an Eloquent model and the corresponding database table migrations for the new dataset.

1.  **Create Model**: Create a new model, e.g. `app/Models/MetropolisCrime.php`. This model should use the `Mappable` trait if it needs to appear on the map surfaces. Point it at the full-history DB connection by default.

2.  **Create Migrations**: Create two migration files.
    *   **Full Data Table**: This migration creates the table in the `metropolis_data_db`.
    *   **Recent Data Table**: This migration creates an identical table in the `metropolis_db`.

    **Example Full Data Migration:**
    ```bash
    php artisan make:migration create_metropolis_crimes_table_full
    ```
    ```php
    // database/migrations/YYYY_MM_DD_HHMMSS_create_metropolis_crimes_table_full.php
    class CreateMetropolisCrimesTableFull extends Migration
    {
        protected $connection = 'metropolis_data_db';

        public function up()
        {
            Schema::connection($this->connection)->create('metropolis_crimes', function (Blueprint $table) {
                $table->bigInteger('id')->primary();
                // ... define all other columns
                $table->point('location', '4326')->nullable()->spatialIndex();
            });
        }
        // ... down() method
    }
    ```

    **Example Recent Data Migration:**
    ```bash
    php artisan make:migration create_metropolis_crimes_table_recent
    ```
    ```php
    // database/migrations/YYYY_MM_DD_HHMMSS_create_metropolis_crimes_table_recent.php
    class CreateMetropolisCrimesTableRecent extends Migration
    {
        protected $connection = 'metropolis_db';

        public function up()
        {
            // Schema is identical to the full data table
            Schema::connection($this->connection)->create('metropolis_crimes', function (Blueprint $table) {
                $table->bigInteger('id')->primary();
                // ... define all other columns
                $table->point('location', '4326')->nullable()->spatialIndex();
            });
        }
        // ... down() method
    }
    ```

---

## Step 5: Create The Data Seeder (ETL)

Create a seeder to process the downloaded CSV and populate both databases.

```bash
php artisan make:seeder MetropolisCrimeSeeder
```

This seeder should read the CSV from `storage/app/datasets/metropolis/`, transform the source schema into the app schema, and upsert into both the recent and historical tables where applicable.

Current useful examples:

- `database/seeders/ChicagoCrimeSeeder.php`
- `database/seeders/SanFranciscoCrimeSeeder.php`
- `database/seeders/SeattleCrimeSeeder.php`
- `database/seeders/MontgomeryCountyMdCrimeSeeder.php`
- `database/seeders/NewYork311Seeder.php`

---

## Step 6: Create The City DataPoint Infrastructure

This is the aggregation layer that powers the radial map and several shared spatial queries.

1.  **Create `DataPoint` Model**: Create a model for the city's aggregated data points, e.g., `app/Models/MetropolisDataPoint.php`.

2.  **Create `DataPoint` Migration**: Create a migration for the `metropolis_data_points` table. This table lives in the **recent data DB** (`metropolis_db`).

    ```bash
    php artisan make:migration create_metropolis_data_points_table
    ```
    ```php
    // database/migrations/YYYY_MM_DD_HHMMSS_create_metropolis_data_points_table.php
    class CreateMetropolisDataPointsTable extends Migration
    {
        protected $connection = 'metropolis_db';

        public function up()
        {
            Schema::connection($this->connection)->create('metropolis_data_points', function (Blueprint $table) {
                $table->id();
                $table->string('type')->index();
                $table->point('location', '4326')->spatialIndex();
                $table->string('generic_foreign_id');
                $table->dateTime('alcivartech_date')->index();
                
                // Foreign key to the recent data table
                $table->unsignedBigInteger('metropolis_crime_id')->nullable();
                $table->foreign('metropolis_crime_id')->references('id')->on('metropolis_crimes')->onDelete('cascade');
                
                $table->timestamps();
                $table->unique(['type', 'generic_foreign_id']);
            });
        }
        // ... down() method
    }
    ```

3.  **Create `DataPoint` Seeder**: Create a seeder, e.g. `MetropolisDataPointSeeder.php`, that reads from the recent-data tables and populates the `metropolis_data_points` table. Refer to `ChicagoDataPointSeeder.php` or `NewYorkDataPointSeeder.php` for concrete examples.

---

## Step 7: Register The City In App Config

Register the city in `config/cities.php`.

At minimum, define:

- `display_name`
- `slug`
- `center_lat`
- `center_lng`
- `supported_localities`
- `data_points_table`
- `db_connection`
- `linkable_models`
- `data_types`

This config now feeds:

- map-city context
- address serviceability decisions
- city landing routing
- homepage and footer coverage links

If the new city should appear in the public city nav/footer, also update `resources/js/Utils/publicNavigation.js`.

---

## Step 8: Add Product Surfaces

Do not stop at the DB layer. Add the city to the actual user-facing product.

1. **City landing page**
   - add copy in `app/Http/Controllers/CityLandingController.php`
   - confirm dataset labels and related links look right

2. **Homepage coverage**
   - update `resources/js/Pages/Home.vue` or the backing controller payload if the new city should appear in the homepage coverage block

3. **Crime preview support**
   - if the area should be supported in `/crime-address`, update `config/cities.php` and confirm the serviceability logic recognizes the localities correctly
   - if the area should not be supported yet, leave it out of the funnel so unsupported-address behavior stays honest

4. **SEO and sitemap**
   - confirm titles/descriptions are sensible
   - confirm the city landing page should be indexable

---

## Step 9: Add Tests

Minimum expected coverage for a new city:

- feature tests for city landing resolution and metadata
- serviceability tests if the city participates in `/crime-address`
- seeder or parsing tests for any nontrivial source transformation
- Playwright regression coverage if the city has a public landing page or preview flow

Useful existing references:

- `tests/Feature/CityLandingTest.php`
- `tests/Feature/CrimeAddressFunnel/*`
- `tests/e2e/public-surface-regressions.spec.ts`
- `tests/e2e/crime-address-production.spec.ts`

---

## Step 10: Execution Workflow

Run the following commands in order to bring the new city online.

1.  **Download the data**:
    ```bash
    php artisan app:download-city-dataset
    ```

2.  **Run the migrations for the new city**:
    ```bash
    php artisan migrate --database=metropolis_data_db
    php artisan migrate --database=metropolis_db
    ```

3.  **Seed the data into the tables**:
    ```bash
    php artisan db:seed --class=MetropolisCrimeSeeder
    php artisan db:seed --class=MetropolisDataPointSeeder
    ```

4. **Build and verify**:
   ```bash
   ./vendor/bin/sail test
   npx playwright test tests/e2e/public-surface-regressions.spec.ts
   npm run build
   ```

5. **Deploy and verify production**:
   - push `main`
   - SSH using the alias from local `~/.ssh/config`
   - run `~/publicdatawatchdeploy.sh`
   - verify the city landing page, relevant map surface, and preview flow if applicable

Metropolis should now be integrated at the data, map, and product layers, not just present in one database table.
