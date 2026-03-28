# Chicago Integration Guide

This document outlines how Chicago is integrated into the current PublicDataWatch product. It is both a historical example of the multi-city architecture and a practical reference for how a crime-first city should connect to the address-first funnel and city landing surfaces.

## 1. Overview

Chicago is a crime-first city in the product:

- public landing page: `/chicago`
- public funnel eligibility: supported in `/crime-address`
- recent-data connection: `chicago_db`
- full-history connection: `chicago_crime_db`
- aggregated map table: `chicago_data_points`

The goal was to add Chicago while keeping its data isolated from the Boston-area databases and still letting the shared map, landing-page, and preview systems work correctly.

---

## 2. File-by-File Implementation Details

### Configuration And Product Registration

1.  **Database Connections (`config/database.php`)**: Two new database connections were defined to handle Chicago's data, pointing to `chicago_db` and `chicago_crime_db`.

2.  **Dataset Definition (`config/datasets.php`)**: The Chicago Crimes dataset was added to the configuration to enable downloading via the `app:download-city-dataset` command.

3.  **City Registration (`config/cities.php`)**: Chicago is registered with its display name, supported localities, `chicago_data_points` table, `chicago_db` connection, and linkable models so the map and serviceability layers can resolve it consistently.

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

### Controller And Public-Surface Integration

Chicago is now wired into more than the old multi-city map controller.

1.  **Generic map context**: `app/Http/Controllers/GenericMapController.php` serves Chicago through its city context and linkable models when the shared map is centered on Chicago.

2.  **Address-first funnel**: `config/cities.php` and the serviceability layer allow supported Chicago addresses to enter `/crime-address` without pretending unsupported areas are covered.

3.  **City landing page**: `app/Http/Controllers/CityLandingController.php` and `resources/js/Pages/CityMapLite.vue` give Chicago a city-specific landing page rather than relying on a generic shell.

4.  **Navigation and discovery**: `resources/js/Utils/publicNavigation.js`, the homepage coverage section, the footer, and the sitemap all help Chicago behave like a real public entry point.

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

4.  **Verify the public surfaces**:
    ```bash
    ./vendor/bin/sail test tests/Feature/CityLandingTest.php
    npx playwright test tests/e2e/public-surface-regressions.spec.ts
    ```

After these steps, Chicago is fully integrated across the data layer, shared maps, city landing page, and address-preview funnel.
