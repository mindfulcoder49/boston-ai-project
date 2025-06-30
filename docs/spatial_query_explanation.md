# Explanation: Why Swapping Latitude and Longitude Worked

You've stumbled upon a classic "gotcha" in spatial database queries. The key to understanding this lies in the difference between the **Well-Known Text (WKT) format** and the **Axis Order of a Spatial Reference System (SRS)**.

### 1. The WKT Standard: `POINT(x y)`

The standard format for representing a point in WKT is `POINT(x y)`. In a geographic context, we intuitively map this to:
-   `x` = Longitude (East/West)
-   `y` = Latitude (North/South)

So, when you created your `location` column with `POINT(lon, lat)`, you were following this standard perfectly. The database stored the data correctly, which you confirmed by seeing `POINT(-71.16426 42.25541)`.

### 2. The SRS Axis Order: `(latitude, longitude)`

This is the tricky part. While WKT is `(x y)`, the official definition for many geographic reference systems, including the one you're using (**EPSG:4326**), specifies an axis order of **(latitude, longitude)**.

Modern database systems (like MySQL 8+ and recent versions of MariaDB) are strict about this. When a function like `ST_GeomFromText()` sees that you've specified SRID 4326, it enforces the official axis order of that system.

### Putting It All Together: Why The Queries Behaved That Way

Let's look at your two attempts:

#### The "Correct" but Failing Query:

```sql
-- Your input: POINT(longitude, latitude)
ST_GeomFromText('POINT(-71.13 42.39)', 4326)
```

-   **Your Intent:** You provided the point as `(longitude, latitude)`.
-   **Database Interpretation:** Because of SRID 4326, the database expected the input to be in `(latitude, longitude)` order. It interpreted your input as:
    -   Latitude: -71.13
    -   Longitude: 42.39
-   **Result:** This point is near Antarctica. The query correctly found **0 crashes** because none of your Cambridge data is there.

#### The "Wrong" but Working Query:

```sql
-- Your input: POINT(latitude, longitude)
ST_GeomFromText('POINT(42.39 -71.13)', 4326)
```

-   **Your Intent:** You provided the point as `(latitude, longitude)`.
-   **Database Interpretation:** The database, expecting `(latitude, longitude)` for SRID 4326, correctly interpreted your input as:
    -   Latitude: 42.39
    -   Longitude: -71.13
-   **Result:** This point is in Cambridge, MA. The query correctly found crashes nearby because the location matched your data.

### Conclusion

Your initial migration that created the `location` column with `POINT(lon, lat)` was correct. The data is stored properly.

The confusion arose when **querying** that data. The `ST_GeomFromText()` function required you to provide the coordinates in the **(latitude, longitude)** order to match the formal definition of the EPSG:4326 reference system.

**In short: You were right that the data is stored as (lon, lat), but the function to create a temporary point for comparison needed it as (lat, lon).** This is a common source of frustration, and your detailed logging was the perfect way to uncover it.
