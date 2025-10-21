import sys
import json
import joblib
import pandas as pd
import pymysql

# Load input from stdin
input_data = json.load(sys.stdin)

# Load trained model
model = joblib.load("team_recommender_model.pkl")

# Prepare DataFrame from input
df = pd.DataFrame([input_data])


db = pymysql.connect(host="localhost", user="root", password="", database="mbk_db")
cursor = db.cursor()

gender_pref = input_data["gender_preference"]  # 0: No preference, 1: Male, 2: Female

# Build gender-based filter
if gender_pref == 1:  # Male
    gender_sql = """
        SELECT t.team_id
        FROM teams t
        JOIN users m ON t.makeup_artist_id = m.user_id
        JOIN users h ON t.hairstylist_id = h.user_id
        WHERE m.sex = 'Male' AND h.sex = 'Male'
    """
elif gender_pref == 2:  # Female
    gender_sql = """
        SELECT t.team_id
        FROM teams t
        JOIN users m ON t.makeup_artist_id = m.user_id
        JOIN users h ON t.hairstylist_id = h.user_id
        WHERE m.sex = 'Female' AND h.sex = 'Female'
    """
else:
    gender_sql = "SELECT team_id FROM teams"  # No preference

cursor.execute(gender_sql)
valid_teams = set(row[0] for row in cursor.fetchall())

# Make prediction
prediction = model.predict(df)[0]

# If predicted team doesn't match gender preference, find closest valid one
if prediction in valid_teams:
    print(prediction)
else:
    # Use probabilities to find the next best match
    probs = model.predict_proba(df)[0]
    classes = model.classes_

    # Zip and sort by probability descending
    ranked = sorted(zip(classes, probs), key=lambda x: x[1], reverse=True)
    for team_id, _ in ranked:
        if team_id in valid_teams:
            print(team_id)
            break
    else:
        print(classes[0])  # fallback
