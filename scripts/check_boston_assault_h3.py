import pandas as pd
import h3
from math import radians, cos, sin, asin, sqrt


CSV_PATH = "/home/briarmoss/Documents/boston-ai-project/storage/app/datasets/crime-incident-reports_20260320_132817.csv"
RESOLUTION = 8
RADIUS_MILES = 0.3
WEEKS = 52

POINTS = [
    {
        "label": "82 Christopher St, Dorchester",
        "lat": 42.29917,
        "lng": -71.05684,
    },
    {
        "label": "Mass & Cass",
        "lat": 42.33272,
        "lng": -71.07218,
    },
]

ASSAULT_CODES = {
    "401", "402", "403", "404",
    "411", "412", "413",
    "421", "422", "423", "424",
    "431", "432", "433",
    "801", "802", "803", "804",
    "2647",
}


def haversine(lat1, lon1, lat2, lon2):
    lon1, lat1, lon2, lat2 = map(radians, [lon1, lat1, lon2, lat2])
    dlon = lon2 - lon1
    dlat = lat2 - lat1
    a = sin(dlat / 2) ** 2 + cos(lat1) * cos(lat2) * sin(dlon / 2) ** 2
    c = 2 * asin(sqrt(a))
    r = 3956
    return c * r


def format_series(series):
    if series.empty:
        return "  <none>"
    return series.to_string()


def summarize(label, subset, method_name):
    subset = subset.copy()
    subset["is_assault_group"] = subset["OFFENSE_CODE"].isin(ASSAULT_CODES)

    assault_count = int(subset["is_assault_group"].sum())
    simple_assault_count = int((subset["OFFENSE_DESCRIPTION"] == "ASSAULT - SIMPLE").sum())

    missed = (
        subset[subset["is_assault_group"] & (subset["OFFENSE_DESCRIPTION"] != "ASSAULT - SIMPLE")]
        .groupby(["OFFENSE_CODE", "OFFENSE_DESCRIPTION"])
        .size()
        .sort_values(ascending=False)
    )

    included = (
        subset[subset["is_assault_group"]]
        .groupby(["OFFENSE_CODE", "OFFENSE_DESCRIPTION"])
        .size()
        .sort_values(ascending=False)
    )

    print("=" * 80)
    print(label)
    print(method_name)
    print(f"rows in window: {len(subset)}")
    print(f"assault group count: {assault_count}")
    print(f"assault group weekly average: {assault_count / WEEKS}")
    print(f"ASSAULT - SIMPLE count: {simple_assault_count}")
    print(f"ASSAULT - SIMPLE weekly average: {simple_assault_count / WEEKS}")
    print()
    print("assault-group records by offense code / description:")
    print(format_series(included))
    print()
    print("categories missed if someone only counts ASSAULT - SIMPLE:")
    print(format_series(missed))
    print()


def main():
    df = pd.read_csv(CSV_PATH, index_col=0, low_memory=False)
    df["date"] = pd.to_datetime(df["OCCURRED_ON_DATE"], utc=True, errors="coerce")
    df = df.dropna(subset=["Lat", "Long", "date", "OFFENSE_CODE"]).copy()

    df["OFFENSE_CODE"] = df["OFFENSE_CODE"].astype(int).astype(str)

    latest_date = df["date"].max()
    start_date = latest_date - pd.Timedelta(weeks=WEEKS)
    window_df = df[df["date"] >= start_date].copy()

    print("latest_date:", latest_date)
    print("start_date:", start_date)
    print()

    window_df["h3_8"] = window_df.apply(
        lambda row: h3.latlng_to_cell(row["Lat"], row["Long"], RESOLUTION),
        axis=1,
    )

    for point in POINTS:
        lat = point["lat"]
        lng = point["lng"]
        label = point["label"]

        target_h3 = h3.latlng_to_cell(lat, lng, RESOLUTION)
        h3_subset = window_df[window_df["h3_8"] == target_h3].copy()

        radial_subset = window_df[
            window_df.apply(
                lambda row: haversine(lat, lng, row["Lat"], row["Long"]) < RADIUS_MILES,
                axis=1,
            )
        ].copy()

        print(f"target_h3 for {label}: {target_h3}")
        summarize(label, h3_subset, f"H3 method (resolution {RESOLUTION})")
        summarize(label, radial_subset, f"Radial method ({RADIUS_MILES} mile)")


if __name__ == "__main__":
    main()
