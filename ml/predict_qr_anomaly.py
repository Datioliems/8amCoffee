"""
predict_qr_anomaly.py
=====================
Nhận JSON array feature dicts từ stdin, trả về JSON array kết quả ra stdout.
Laravel gọi script này qua Symfony Process.

Input  (stdin):  JSON array các feature dict — cùng schema với FEATURE_COLUMNS bên dưới
Output (stdout): JSON array cùng độ dài, mỗi phần tử:
    {'trained': true,  'is_anomaly': bool, 'anomaly_score': float,
     'risk_score': int, 'risk_level': str, 'model_version': str}
Khi model chưa có (cold-start):
    {'trained': false}

Cách dùng thủ công (debug):
    echo '[{"scan_count":35,"distinct_tables":12,...}]' | python3 ml/predict_qr_anomaly.py
"""

import json
import sys

import joblib
import pandas as pd

MODEL_PATH = "ml/qr_anomaly_model.joblib"

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


def risk_from_decision_score(score: float) -> tuple:
    """
    decision_function() của IsolationForest: âm = bất thường, dương = bình thường.
    Quy đổi về risk_score 0–100 (cao = nguy hiểm).

    Công thức: risk_score = clip((0.2 - score) * 250, 0, 100)
    score =  0.2  → risk_score = 0   (bình thường)
    score =  0.0  → risk_score = 50  (ngưỡng giữa)
    score = -0.2  → risk_score = 100 (rất bất thường)
    """
    risk_score = int(max(0, min(100, (0.2 - score) * 250)))

    if risk_score >= 80:
        level = "critical"
    elif risk_score >= 60:
        level = "high"
    elif risk_score >= 40:
        level = "medium"
    else:
        level = "low"

    return risk_score, level


def main():
    # ── Đọc input từ stdin ────────────────────────────────────────────────────
    try:
        raw = sys.stdin.read()
        payload = json.loads(raw)
    except Exception as e:
        print(json.dumps([{"trained": False, "error": f"Invalid input JSON: {e}"}]))
        return

    rows = payload if isinstance(payload, list) else [payload]

    # ── Load model ────────────────────────────────────────────────────────────
    try:
        bundle  = joblib.load(MODEL_PATH)
        model   = bundle["model"]
        version = bundle.get("version", "unknown")
    except FileNotFoundError:
        # Cold-start: model chưa được train — Laravel fallback sang rule-based
        print(json.dumps([{"trained": False} for _ in rows], ensure_ascii=False))
        return
    except Exception as e:
        print(json.dumps([{"trained": False, "error": str(e)} for _ in rows], ensure_ascii=False))
        return

    # ── Predict batch ─────────────────────────────────────────────────────────
    df     = pd.DataFrame(rows)
    X      = df.reindex(columns=FEATURE_COLUMNS).fillna(0)

    preds  = model.predict(X)          # 1 = normal, -1 = anomaly
    scores = model.decision_function(X)  # âm hơn = bất thường hơn

    results = []
    for pred, score in zip(preds, scores):
        r_score, r_level = risk_from_decision_score(float(score))
        results.append({
            "trained":       True,
            "is_anomaly":    bool(pred == -1),
            "anomaly_score": round(float(score), 6),
            "risk_score":    r_score,
            "risk_level":    r_level,
            "model_version": version,
        })

    print(json.dumps(results, ensure_ascii=False))


if __name__ == "__main__":
    main()
