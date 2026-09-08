import json

import cv2
import mediapipe as mp
import numpy as np
from flask import Flask, jsonify, request

app = Flask(__name__)

from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision

BaseOptions = mp_python.BaseOptions
FaceLandmarker = vision.FaceLandmarker
FaceLandmarkerOptions = vision.FaceLandmarkerOptions
VisionRunningMode = vision.RunningMode

MODEL_PATH = "face_landmarker.task"

landmarker = FaceLandmarker.create_from_options(
    FaceLandmarkerOptions(
        base_options=BaseOptions(model_asset_path=MODEL_PATH),
        running_mode=VisionRunningMode.IMAGE,
        num_faces=1,
        min_face_detection_confidence=0.5,
    )
)

# Anchor landmarks used to build a pose-invariant transform
ANCHORS = [33, 133, 263, 362, 1, 4, 61, 291, 152, 10, 8, 6]
# Canonical targets for those anchors (consistent across all images)
ANCHOR_TARGETS = np.array(
    [
        [-1.0, 0.0],
        [-0.4, 0.0],
        [1.0, 0.0],
        [0.4, 0.0],
        [0.0, 1.1],
        [0.0, 1.45],
        [-0.9, 2.4],
        [0.9, 2.4],
        [0.0, 3.6],
        [0.0, -1.2],
        [-0.5, -0.7],
        [0.5, -0.7],
    ],
    dtype=np.float64,
)
# Stable sample landmarks used as the descriptor
SAMPLE = sorted(set(ANCHORS) | {i for i in range(10, 460, 5)})


def decode_image(stream):
    data = stream.read()
    arr = np.frombuffer(data, dtype=np.uint8)
    return cv2.imdecode(arr, cv2.IMREAD_COLOR)


def landmarks_for(img):
    rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb)
    result = landmarker.detect(mp_image)
    if not result.face_landmarks:
        return None
    return [(lm.x, lm.y) for lm in result.face_landmarks[0]]


def feature_vector(landmarks):
    pts = np.asarray(landmarks, dtype=np.float64)

    anchor_src = pts[ANCHORS]
    pad = np.hstack([anchor_src, np.ones((len(ANCHORS), 1))])
    transform, *_ = np.linalg.lstsq(pad, ANCHOR_TARGETS, rcond=None)

    aligned = np.hstack([pts, np.ones((len(pts), 1))]) @ transform
    selected = aligned[SAMPLE].ravel()

    norm = np.linalg.norm(selected)
    if norm == 0:
        return None
    return selected / norm


@app.get("/health")
def health():
    return jsonify({"status": "ok"})


@app.post("/enroll")
def enroll():
    stream = request.files.get("image")
    if stream is None:
        return jsonify({"detected": False, "error": "No image provided"}), 422

    img = decode_image(stream)
    if img is None:
        return jsonify({"detected": False, "error": "Invalid image"}), 422

    landmarks = landmarks_for(img)
    if landmarks is None:
        return jsonify({"detected": False, "error": "No face detected. Look straight at the camera."}), 422

    signature = feature_vector(landmarks)
    if signature is None:
        return jsonify({"detected": False, "error": "Could not build face signature."}), 422

    return jsonify({"detected": True, "signature": signature.tolist()})


@app.post("/verify")
def verify():
    stream = request.files.get("image")
    signature_raw = request.form.get("signature")

    if stream is None or not signature_raw:
        return jsonify({"matched": False, "error": "Missing image or signature"}), 422

    try:
        enrolled = np.asarray(json.loads(signature_raw), dtype=np.float64)
    except (ValueError, TypeError):
        return jsonify({"matched": False, "error": "Invalid signature"}), 422

    img = decode_image(stream)
    if img is None:
        return jsonify({"matched": False, "error": "Invalid image"}), 422

    landmarks = landmarks_for(img)
    if landmarks is None:
        return jsonify({"matched": False, "error": "No face detected. Look straight at the camera."}), 422

    current = feature_vector(landmarks)
    if current is None or enrolled.shape != current.shape:
        return jsonify({"matched": False, "error": "Could not compare faces."}), 422

    score = float(np.dot(current, enrolled))
    matched = bool(score >= 0.88)

    return jsonify({"matched": matched, "score": round(score, 4)})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8124, threaded=True)
