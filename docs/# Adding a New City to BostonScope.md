# Adding a New City to BostonScope

This document provides a comprehensive guide on how to integrate a new city into the BostonScope platform. The architecture is designed to be multi-city, with each city having its own isolated set of databases to ensure scalability and performance.

The core concept involves a two-tiered database system for each city:
1.  **Recent Data DB (`<cityname>_db`)**: Contains the last 6 months of data for all datasets in the city. This database powers the main map interface and is optimized for fast queries. It also contains the city's aggregated `data_points` table.
2.  **Full Data DB (`<cityname>_data_db`)**: Contains the complete historical data for each dataset. This is used for archival, historical analysis, or future features that require a deep data dive.

Let's use "Metropolis" as our example new city.

---

### Step 1: Database Setup

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

### Step 2: Data Acquisition

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

### Step 3: Create the Data Model & Migrations

Create an Eloquent model and the corresponding database table migrations for the new dataset.

1.  **Create Model**: Create a new model, e.g., `app/Models/MetropolisCrime.php`. This model **must** use the `Mappable` trait and implement all its required static methods. It should point to the full data DB connection by default.

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

### Step 4: Create the Data Seeder (ETL)

Create a seeder to process the downloaded CSV and populate both databases.

```bash
php artisan make:seeder MetropolisCrimeSeeder
```

This seeder will read the CSV from `storage/app/datasets/metropolis/`, transform the data, and perform `upsert` operations into both the `metropolis_db.metropolis_crimes` table (for recent data) and the `metropolis_data_db.metropolis_crimes` table (for all data). Refer to `ChicagoCrimeSeeder.php` or `PersonCrashDataSeeder.php` for a detailed implementation example.

---

### Step 5: Create the City's DataPoint Infrastructure

This is the aggregation layer that powers the map.

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

3.  **Create `DataPoint` Seeder**: Create a seeder, e.g., `MetropolisDataPointSeeder.php`, that reads from the recent data tables (like `metropolis_crimes`) and populates the `metropolis_data_points` table. Refer to `ChicagoDataPointSeeder.php` for an implementation example.

---

### Step 6: Integrate with the Application

Finally, make the `GenericMapController` aware of the new city.

1.  **Add Linkable Models**: In `app/Http/Controllers/GenericMapController.php`, add a new constant array for Metropolis's mappable models.

    ```php
    // app/Http/Controllers/GenericMapController.php
    private const METROPOLIS_LINKABLE_MODELS = [
        \App\Models\MetropolisCrime::class,
        // Add other Metropolis models here
    ];
    ```

2.  **Update City Context Logic**: Modify the `getCityContext` method to include Metropolis. Add its coordinates and return the correct context array when the request location is closest to it.

    ```php
    // app/Http/Controllers/GenericMapController.php
    protected function getCityContext(float $latitude, float $longitude): array
    {
        // ... coordinates for Boston, Chicago
        $metropolisLat = 29.7604; // Example: Houston
        $metropolisLon = -95.3698;

        // ... distance calculations for Boston, Chicago
        // ... calculate distance to Metropolis

        if ($distMetropolis < $distChicago && $distMetropolis < $distBoston) {
             return [
                'city' => 'metropolis',
                'data_points_table' => 'metropolis_data_points',
                'linkable_models' => self::METROPOLIS_LINKABLE_MODELS,
                'db_connection' => 'metropolis_db',
            ];
        }
        // ... other city checks
    }
    ```

---

### Step 7: Execution Workflow

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

Your new city, Metropolis, should now be fully integrated and accessible through the map interface when the map is centered on its location.