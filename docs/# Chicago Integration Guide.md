# Chicago Integration Guide

This document outlines the specific steps and files created to integrate the City of Chicago's data into the BostonScope platform. It serves as a concrete example of the multi-city architecture described in `adding_a_new_city.md`.

## 1. Overview

The goal was to add Chicago crime data while keeping it isolated from the existing Boston-area data. This was achieved by creating two new database connections (`chicago_db` for recent data, `chicago_crime_db` for all data) and a set of models, migrations, and seeders specific to Chicago. The `GenericMapController` was then updated to be "city-aware," dynamically serving data based on the map's location.

---

## 2. File-by-File Implementation Details

### Configuration

1.  **Database Connections (`config/database.php`)**: Two new database connections were defined to handle Chicago's data, pointing to `chicago_db` and `chicago_crime_db`.

2.  **Dataset Definition (`config/datasets.php`)**: The Chicago Crimes dataset was added to the configuration to enable downloading via the `app:download-city-dataset` command.

### Models

Two new Eloquent models were created.

1.  **`app/Models/ChicagoCrime.php`**: Represents a single crime record.
    *   Implements the `Mappable` trait for integration with the map controller.
    *   Defaults to the `chicago_crime_db` connection, which holds the complete historical dataset.
    *   Defines field casting, popup configuration, and human-readable labels.

2.  **`app/Models/ChicagoDataPoint.php`**: Represents an aggregated, map-ready data point for Chicago.
    *   Uses the `chicago_db` connection.
    *   Points to the `chicago_data_points` table.
    *   Defines the `belongsTo` relationship with `ChicagoCrime`.

An update was also made to `app/Models/DataPoint.php` to add the relationship for `PersonCrashData`, which was part of the same development effort.

### Migrations

Three new migration files were created to set up the necessary tables for Chicago.

1.  **`..._create_chicago_crimes_table_full.php`**:
    *   Runs on the `chicago_crime_db` connection.
    *   Creates the `chicago_crimes` table to store the entire history of crime data.

2.  **`..._create_chicago_crimes_table_recent.php`**:
    *   Runs on the `chicago_db` connection.
    *   Creates an identical `chicago_crimes` table to store the most recent six months of data for faster map queries.

3.  **`..._create_chicago_data_points_table.php`**:
    *   Runs on the `chicago_db` connection.
    *   Creates the `chicago_data_points` table, which aggregates all mappable Chicago data types into a single, indexed table for the map controller.
    *   Includes a foreign key to the `chicago_crimes` table.

### Seeders (ETL)

Two new seeders were created to handle the Extract, Transform, Load (ETL) process for Chicago data.

1.  **`database/seeders/ChicagoCrimeSeeder.php`**:
    *   Reads the downloaded `chicago-crimes-....csv` file.
    *   Parses and transforms each row, handling data types and the complex `location` field.
    *   Upserts the complete dataset into the `chicago_crime_db.chicago_crimes` table.
    *   Upserts the last six months of data into the `chicago_db.chicago_crimes` table.

2.  **`database/seeders/ChicagoDataPointSeeder.php`**:
    *   Reads from the recent data table (`chicago_db.chicago_crimes`).
    *   Transforms each record into a standardized `DataPoint` format.
    *   Upserts the transformed records into the `chicago_data_points` table.

### Controller Modifications

The `app/Http/Controllers/GenericMapController.php` was significantly updated to support multiple cities.

1.  **Linkable Models**: A new `CHICAGO_LINKABLE_MODELS` constant was added to define which models are available for the Chicago context.

2.  **City Context Detection (`getCityContext` method)**: A new helper method was created to determine the closest city based on the latitude and longitude of the user's request. It returns a context array containing the appropriate database connection, `data_points` table name, and list of linkable models.

3.  **Dynamic Querying**: The `getRadialMapData` method now calls `getCityContext` at the beginning of a request. It uses the returned context to dynamically set the DB connection and table names for its queries, ensuring it pulls data from the correct city's database.

---

## 3. Execution Workflow

To deploy the Chicago integration from scratch, the following Artisan commands must be run in order:

1.  **Download the data**:
    ```bash
    php artisan app:download-city-dataset
    ```

2.  **Run migrations for both Chicago databases**:
    ```bash
    php artisan migrate --database=chicago_crime_db
    php artisan migrate --database=chicago_db
    ```

3.  **Seed the data, starting with the raw crime data, then the aggregated data points**:
    ```bash
    php artisan db:seed --class=ChicagoCrimeSeeder
    php artisan db:seed --class=ChicagoDataPointSeeder
    ```

After these steps, the Chicago data is fully integrated and will be served automatically when the map is used in the Chicago area.