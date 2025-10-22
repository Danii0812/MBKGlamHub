import sys
import json
import joblib
import pandas as pd
import pymysql
import os

# -------- CONFIG --------
DB_HOST = os.getenv("MBK_DB_HOST", "localhost")
DB_USER = os.getenv("MBK_DB_USER", "root")
DB_PASS = os.getenv("MBK_DB_PASS", "")
DB_NAME = os.getenv("MBK_DB_NAME", "mbk_db")
MODEL_PATH = os.getenv("MBK_MODEL_PATH", "team_recommender_model.pkl")
TOP_K = int(os.getenv("MBK_TOP_K", "3"))  # diversify among top-K
# ------------------------

def get_connection():
    return pymysql.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME, cursorclass=pymysql.cursors.DictCursor
    )

def safe_int(x, default=None):
    try:
        return int(x)
    except Exception:
        return default

def read_stdin_json():
    data = json.load(sys.stdin)
    # Ensure all features exist; default to None (the model can handle via training or imputer; else fail early)
    required = ["hair_style","makeup_style","price_range","event_type","skin_tone",
                "face_shape","gender_preference","hair_length",
                "booking_date","booking_time"]
    for k in required:
        if k not in data:
            data[k] = None
    return data

def fetch_valid_teams(booking_date, booking_time):
    """
    Returns a set of team_ids that are NOT already booked at the exact date&time.
    A team is considered 'booked' if it appears in booking_clients joined to a booking
    with same date/time (any status other than canceled, if you later add a 'canceled' flag).
    """
    sql = """
        SELECT t.team_id,
               SUM(CASE WHEN b.booking_id IS NULL THEN 0 ELSE 1 END) AS conflicts
        FROM teams t
        LEFT JOIN booking_clients bc ON bc.team_id = t.team_id
        LEFT JOIN bookings b
               ON b.booking_id = bc.booking_id
              AND b.booking_date = %s
              AND b.booking_time = %s
        GROUP BY t.team_id
    """
    with get_connection() as conn, conn.cursor() as cur:
        cur.execute(sql, (booking_date, booking_time))
        rows = cur.fetchall()
    # Valid = no conflicts at that timeslot
    return {r["team_id"] for r in rows if safe_int(r.get("conflicts", 0), 0) == 0}


def filter_teams_by_gender(valid_teams, gender_pref):
    """gender_pref: 0=None, 1=Male, 2=Female"""
    if not valid_teams or not gender_pref or gender_pref == 0:
        return valid_teams
    want = "Male" if gender_pref == 1 else "Female"
    sql = """
        SELECT t.team_id, ma.sex AS ma_sex, hs.sex AS hs_sex
        FROM teams t
        LEFT JOIN users ma ON t.makeup_artist_id = ma.user_id
        LEFT JOIN users hs ON t.hairstylist_id = hs.user_id
        WHERE t.team_id IN ({})
    """.format(",".join(["%s"]*len(valid_teams)))
    with get_connection() as conn, conn.cursor() as cur:
        cur.execute(sql, tuple(valid_teams))
        rows = cur.fetchall()
    filtered = {r["team_id"] for r in rows if r.get("ma_sex")==want and r.get("hs_sex")==want}
    return (valid_teams & filtered) if filtered else valid_teams


def choose_from_topk_with_diversity(probs, classes, valid_teams, k):
    ranked = sorted(zip(classes, probs), key=lambda x: x[1], reverse=True)
    # Filter to valid teams first
    ranked_valid = [t for t in ranked if t[0] in valid_teams] or ranked
    top = ranked_valid[:max(1, k)]
    # Simple diversity trick: pick the highest with a small chance to pick #2/#3
    # (If you want strict determinism, just return top[0][0])
    import random
    # Weight by probability but ensure all top items get some chance
    weights = [max(0.0001, p) for _, p in top]
    s = sum(weights)
    weights = [w / s for w in weights]
    choice = random.choices([t[0] for t in top], weights=weights, k=1)[0]
    return choice

def main():
    # Load input
    input_data = read_stdin_json()
    booking_date = input_data["booking_date"]
    booking_time = input_data["booking_time"]

    # Load model
    model = joblib.load(MODEL_PATH)

    # Build DF for the model (only the 8 numeric features)
    features = [
        "hair_style", "makeup_style", "price_range", "event_type",
        "skin_tone", "face_shape", "gender_preference", "hair_length"
    ]
    X = pd.DataFrame([ {f: input_data.get(f) for f in features} ])

    # Predict class probabilities
    probs = model.predict_proba(X)[0]
    classes = model.classes_

    # Availability set
    valid_teams = fetch_valid_teams(booking_date, booking_time)
    # Honor gender preference if provided
    gender_pref = safe_int(input_data.get('gender_preference'), 0)
    valid_teams = filter_teams_by_gender(valid_teams, gender_pref)
    if not valid_teams:
        # If nothing is available, just fall back to the top probability class
        # (Still better than printing nothing.)
        print(int(classes[probs.argmax()]))
        return

    # Diversified pick among the top K valid teams
    team_id = choose_from_topk_with_diversity(probs, classes, valid_teams, TOP_K)
    print(int(team_id))

if __name__ == "__main__":
    main()
