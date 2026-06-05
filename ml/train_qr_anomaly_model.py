"""
train_qr_anomaly_model.py
=========================
Huấn luyện Isolation Forest trên file CSV đặc trưng QR scan.
Chạy sau khi đã export CSV bằng: php artisan scan:export-features

Cách dùng:
    python3 ml/train_qr_anomaly_model.py
    python3 ml/train_qr_anomaly_model.py --data ml/qr_scan_features.csv --model ml/qr_anomaly_model.joblib
"""

import argparse
import datetime
import json
import sys

import joblib
import pandas as pd
from sklearn.ensemble import IsolationForest
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler

# ── Cấu hình mặc định ────────────────────────────────────────────────────────
DEFAULT_DATA  = "ml/qr_scan_features.csv"
DEFAULT_MODEL = "ml/qr_anomaly_model.joblib"
MIN_ROWS      = 200   # cold-start guard: ít hơn thì không train

FEATURE_COLUMNS = [
    "scan_count",
    "distinct_tables",
    "distinct_branches",
    "unique_user_agents",
    "avg_seconds_between_scans",
    "min_seconds_between_scans",
    "max_seconds_between_scans",
    "std_seconds_between_scans",
    "created_orders",
    "conversion_rate",
    "suspicious_user_agent_flag",
    "night_scan_flag",
]


def main():
    parser = argparse.ArgumentParser(description="Train QR Anomaly Isolation Forest")
    parser.add_argument("--data",     default=DEFAULT_DATA,  help="Path to feature CSV")
    parser.add_argument("--model",    default=DEFAULT_MODEL, help="Output model path (.joblib)")
    parser.add_argument("--min-rows", type=int, default=MIN_ROWS, help="Minimum rows required to train")
    args = parser.parse_args()

    # ── Đọc dữ liệu ──────────────────────────────────────────────────────────
    try:
        df = pd.read_csv(args.data)
    except FileNotFoundError:
        print(json.dumps({"trained": False, "reason": f"File not found: {args.data}"}))
        sys.exit(0)

    min_rows = args.min_rows
    if len(df) < min_rows:
        print(json.dumps({
            "trained": False,
            "reason": f"Not enough data ({len(df)} rows < minimum {min_rows}). "
                      "Run 'php artisan scan:export-features' with more historical data.",
        }))
        sys.exit(0)

    # Chỉ lấy các cột feature, điền 0 cho ô thiếu
    missing = [c for c in FEATURE_COLUMNS if c not in df.columns]
    if missing:
        print(json.dumps({"trained": False, "reason": f"Missing columns: {missing}"}))
        sys.exit(1)

    X = df[FEATURE_COLUMNS].fillna(0)

    # ── Xây pipeline: chuẩn hoá → Isolation Forest ───────────────────────────
    # contamination='auto' — tránh ép cứng 5% là outlier khi dữ liệu sạch
    model = Pipeline([
        ("scaler", StandardScaler()),
        ("iforest", IsolationForest(
            n_estimators=200,
            contamination="auto",
            random_state=42,
        )),
    ])
    model.fit(X)

    # ── Lưu model kèm metadata ────────────────────────────────────────────────
    version    = datetime.datetime.now().strftime("%Y%m%d%H%M%S")
    trained_at = datetime.datetime.now().isoformat()

    bundle = {
        "model":      model,
        "features":   FEATURE_COLUMNS,
        "version":    version,
        "trained_at": trained_at,
        "train_rows": len(df),
    }
    joblib.dump(bundle, args.model)

    result = {"trained": True, "rows": len(df), "version": version, "model_path": args.model}
    print(json.dumps(result))


if __name__ == "__main__":
    main()
