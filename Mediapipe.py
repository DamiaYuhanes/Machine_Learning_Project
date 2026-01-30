# -*- coding: utf-8 -*-
"""
Created on Wed Nov 26 15:37:44 2025

@author: ekomu
"""

# -*- coding: utf-8 -*-
"""
Body + Hand Feature Extraction (88-D) + Dataset Creator & Loader
+ LSTM Training + Pygame Menu + Editable Class List + Live Classification

Feature design per frame:
- HL' : normalized left-hand landmarks   (21 x 2 -> 42 values)
- HR' : normalized right-hand landmarks  (21 x 2 -> 42 values)
- DL  : vector (right shoulder -> right wrist) / H  (2 values)
- DR  : vector (left shoulder  -> left wrist)  / H  (2 values)

F = [flatten(HL'), DL, flatten(HR'), DR]  -> 88 dimensions

Rules:
- If the left hand is NOT detected   -> all HL' = 0
- If the right hand is NOT detected  -> all HR' = 0

Assumption for LSTM training:
- All sequences have the SAME length T (e.g. T = 60/90 frames),
  enforced when recording the dataset.
"""

import os
import time
from datetime import datetime
import threading  # untuk menjalankan training di thread terpisah

import cv2
import mediapipe as mp
import numpy as np
import pygame

# For LSTM training
import tensorflow as tf
from tensorflow.keras.models import Sequential, load_model
from tensorflow.keras.layers import LSTM, Dense, Dropout
from sklearn.model_selection import train_test_split

# -----------------------------------------------------------
# GLOBAL TRAINING STATUS (untuk ditampilkan di Pygame)
# -----------------------------------------------------------
TRAIN_STATUS = {
    "epoch": 0,
    "total_epochs": 0,
    "loss": 0.0,
    "acc": 0.0,
    "val_loss": 0.0,
    "val_acc": 0.0,
    "running": False,
    "done": False,
    "error": None,
}


class PygameTrainCallback(tf.keras.callbacks.Callback):
    """Callback Keras untuk update TRAIN_STATUS setiap akhir epoch."""
    def __init__(self, total_epochs):
        super().__init__()
        self.total_epochs = total_epochs

    def on_epoch_end(self, epoch, logs=None):
        logs = logs or {}
        TRAIN_STATUS["epoch"]        = epoch + 1
        TRAIN_STATUS["total_epochs"] = self.total_epochs
        TRAIN_STATUS["loss"]         = float(logs.get("loss", 0.0))
        TRAIN_STATUS["acc"]          = float(logs.get("accuracy", 0.0))
        TRAIN_STATUS["val_loss"]     = float(logs.get("val_loss", 0.0))
        TRAIN_STATUS["val_acc"]      = float(logs.get("val_accuracy", 0.0))


# -----------------------------------------------------------
# CONFIG: dataset & training
# -----------------------------------------------------------
DATASET_DIR       = "ImageData"   # root dataset dir

# DEFAULT CLASS LIST (used if classes.txt does not exist)
CLASS_LIST        = [
    "up",
    "down",
    "left",
    "right",
    "stop",
    "neutral",
]

SEQUENCE_LENGTH_T = 90     # frames per sequence (T)
BATCH_SIZE        = 8
EPOCHS            = 30
MODEL_PATH        = "lstm_gesture_model.h5"

CLASSES_FILE      = "classes.txt"  # file to save / load class list


# -----------------------------------------------------------
# Class list load/save
# -----------------------------------------------------------
def save_class_list_to_file():
    """Save global CLASS_LIST to CLASSES_FILE (one class per line)."""
    global CLASS_LIST
    try:
        with open(CLASSES_FILE, "w", encoding="utf-8") as f:
            for name in CLASS_LIST:
                name = name.strip()
                if name:
                    f.write(name + "\n")
        print(f"[INFO] Class list saved to {CLASSES_FILE}: {CLASS_LIST}")
    except Exception as e:
        print("[WARNING] Failed to save class list:", e)


def load_class_list_from_file():
    """
    If CLASSES_FILE exists:
        -> load class names from file into CLASS_LIST.
    If CLASSES_FILE does NOT exist:
        -> keep default CLASS_LIST, then save it to CLASSES_FILE.
    """
    global CLASS_LIST

    if os.path.exists(CLASSES_FILE):
        try:
            with open(CLASSES_FILE, "r", encoding="utf-8") as f:
                lines = [ln.strip() for ln in f.readlines()]
            lines = [ln for ln in lines if ln]

            if lines:
                CLASS_LIST = lines

            print(f"[INFO] Loaded classes from {CLASSES_FILE}: {CLASS_LIST}")
        except Exception as e:
            print("[WARNING] Failed to load class list, using default:", e)
    else:
        print(f"[INFO] {CLASSES_FILE} not found, using default CLASS_LIST and saving it.")
        save_class_list_to_file()


# -----------------------------------------------------------
# MediaPipe initialization (global)
# -----------------------------------------------------------
mp_pose    = mp.solutions.pose
mp_hands   = mp.solutions.hands
mp_drawing = mp.solutions.drawing_utils  # for drawing landmarks

pose = mp_pose.Pose(
    static_image_mode=False,
    model_complexity=1,
    enable_segmentation=False,
    min_detection_confidence=0.5,
    min_tracking_confidence=0.5,
)

hands = mp_hands.Hands(
    static_image_mode=False,
    max_num_hands=2,
    model_complexity=1,
    min_detection_confidence=0.5,
    min_tracking_confidence=0.5,
)

# Last MediaPipe results (global)
pose_results  = None
hands_results = None


# -----------------------------------------------------------
# Helper: body vectors DL, DR
# -----------------------------------------------------------
def extract_body_vectors(pose_results, img_height, img_width):
    """
    Compute:
      DL = (B16 - B12) / H  (right wrist - right shoulder)
      DR = (B15 - B11) / H  (left  wrist  - left  shoulder)

    If pose is incomplete -> DL and DR = (0,0).
    """
    DL = np.zeros(2, dtype=np.float32)
    DR = np.zeros(2, dtype=np.float32)

    if pose_results is None or pose_results.pose_landmarks is None:
        return DL, DR

    landmarks = pose_results.pose_landmarks.landmark

    def to_xy(idx):
        return np.array(
            [landmarks[idx].x * img_width, landmarks[idx].y * img_height],
            dtype=np.float32,
        )

    try:
        B11 = to_xy(11)  # left shoulder
        B12 = to_xy(12)  # right shoulder
        B15 = to_xy(15)  # left wrist
        B16 = to_xy(16)  # right wrist
    except (IndexError, ValueError):
        return DL, DR

      # panjang vektor |B12 - B11|
    v=B12-B11
    R = np.linalg.norm(v)  # skalar

    if R <= 0.00000001:
        return DL, DR


    DL = (B16 - B12) / (R)
    DR = (B15 - B11) / (R)



    return DL, DR


# -----------------------------------------------------------
# Helper: HL (Left) and HR (Right) 21x2
# -----------------------------------------------------------
def extract_hand_landmarks(hands_results, img_height, img_width):
    """
    Returns:
      HL : left-hand landmarks  of shape (21, 2) or None
      HR : right-hand landmarks of shape (21, 2) or None
    """
    HL = None
    HR = None

    if hands_results is None or hands_results.multi_hand_landmarks is None:
        return HL, HR

    multi_hands      = hands_results.multi_hand_landmarks
    multi_handedness = hands_results.multi_handedness

    for hand_landmarks, handedness in zip(multi_hands, multi_handedness):
        label = handedness.classification[0].label  # 'Left' or 'Right'

        coords = []
        for lm in hand_landmarks.landmark:
            x = lm.x * img_width
            y = lm.y * img_height
            coords.append([x, y])
        coords = np.array(coords, dtype=np.float32)  # (21, 2)

        if label == "Left":
            HL = coords
        elif label == "Right":
            HR = coords

    return HL, HR


# -----------------------------------------------------------
# Helper: hand normalization (HL' or HR')
# -----------------------------------------------------------
def normalize_hand(H):
    """
    1) Translation: H_trans[i] = H[i] - H[0]
    2) Scale:       H'[i]      = H_trans[i] / ||H[5] - H[0]||_2

    Rules:
      - If H is None (hand not detected)      -> all zeros (21x2)
      - If number of points < 6              -> zeros
      - If reference length is too small     -> zeros
    """
    if H is None:
        return np.zeros((21, 2), dtype=np.float32)

    if H.shape[0] < 6:
        return np.zeros((21, 2), dtype=np.float32)

    H0 = H[0]
    H_trans = -(H - H0)

    ref_vec = (H[5] - H0)
    L = np.linalg.norm(ref_vec)

    if L < 1e-6:
        return np.zeros((21, 2), dtype=np.float32)

    H_norm = H_trans / L

    if H_norm.shape != (21, 2):
        return np.zeros((21, 2), dtype=np.float32)

    return H_norm


# -----------------------------------------------------------
# Helper: build feature vector F (88-D)
# -----------------------------------------------------------
def build_feature_vector(HL_norm, DL, HR_norm, DR):
    """
    F = [flatten(HL'), DL, flatten(HR'), DR]  -> shape (88,)
    """
    F = np.concatenate(
        [
            HL_norm.flatten(),  # 42
            DL.flatten(),       # 2
            HR_norm.flatten(),  # 42
            DR.flatten(),       # 2
        ]
    ).astype(np.float32)

    if F.shape[0] != 88:
        F = np.zeros(88, dtype=np.float32)

    return F


# -----------------------------------------------------------
# FUNCTION: draw pose + hand landmarks (using global results)
# -----------------------------------------------------------
def draw_landmarks(image_bgr):
    """
    Draw pose + hand landmarks on top of image_bgr.
    Updates global pose_results and hands_results.
    """
    global pose, hands, pose_results, hands_results

    image_draw = image_bgr.copy()

    # Run MediaPipe
    rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
    rgb.flags.writeable = False
    pose_results  = pose.process(rgb)
    hands_results = hands.process(rgb)
    rgb.flags.writeable = True

    # Draw pose
    if pose_results is not None and pose_results.pose_landmarks:
        mp_drawing.draw_landmarks(
            image_draw,
            pose_results.pose_landmarks,
            mp_pose.POSE_CONNECTIONS,
        )

    # Draw hands
    if hands_results is not None and hands_results.multi_hand_landmarks:
        for hand_landmarks in hands_results.multi_hand_landmarks:
            mp_drawing.draw_landmarks(
                image_draw,
                hand_landmarks,
                mp_hands.HAND_CONNECTIONS,
            )

    return image_draw


# -----------------------------------------------------------
# MAIN FEATURE EXTRACTION: from a single frame
# -----------------------------------------------------------
def extract_features(image_bgr):
    """
    Extract body+hand feature vector F (88-D) from one BGR image (OpenCV).
    Uses global pose_results and hands_results (last output of draw_landmarks).
    """
    global pose, hands, pose_results, hands_results

    img_height, img_width = image_bgr.shape[:2]

    # If no results yet, run MediaPipe once
    if pose_results is None or hands_results is None:
        rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
        rgb.flags.writeable = False
        pose_results  = pose.process(rgb)
        hands_results = hands.process(rgb)
        rgb.flags.writeable = True

    DL, DR = extract_body_vectors(pose_results, img_height, img_width)
    HL, HR = extract_hand_landmarks(hands_results, img_height, img_width)

    HL_norm = normalize_hand(HL)
    HR_norm = normalize_hand(HR)

    F = build_feature_vector(HL_norm, DL, HR_norm, DR)

    return F


# -----------------------------------------------------------
# FUNCTION: create image dataset from camera
#   (SIMPAN .JPG + FITUR .CSV PER FRAME)
# -----------------------------------------------------------
def create_image_dataset(dataset_dir, class_name, num_files=100):
    """
    Create an image dataset for one class:

    dataset_dir/
      class_name/
        YYMMDDhhmmssms/
          <timestamp>.jpg   -> frame BGR
          <timestamp>.csv   -> feature vector F (1 x 88, comma-separated)

    num_files == sequence length T.
    """
    class_dir = os.path.join(dataset_dir, class_name)
    os.makedirs(class_dir, exist_ok=True)

    session_stamp = datetime.now().strftime("%y%m%d%H%M%S%f")[:-3]
    save_dir = os.path.join(class_dir, session_stamp)
    os.makedirs(save_dir, exist_ok=True)

    print(f"[INFO] Save directory: {save_dir}")

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
    if not cap.isOpened():
        print("[ERROR] Cannot open camera.")
        return

    phase              = "countdown"
    countdown_start    = time.time()
    countdown_duration = 5
    finish_duration    = 3
    i                  = 0
    finish_start       = None

    while True:
        ret, frame = cap.read()
        if not ret:
            print("[ERROR] Failed to grab frame from camera.")
            break

        # mirror view
        frame = cv2.flip(frame, 1)
        frame_to_save = frame.copy()

        # update pose_results & hands_results + overlay
        frame_draw = draw_landmarks(frame)

        now = time.time()
        text = ""

        if phase == "countdown":
            remaining = int(countdown_duration - (now - countdown_start))
            if remaining <= 0:
                phase = "capture"
                text = f"Start CAPTURE for class '{class_name}'"
            else:
                text = f"Capture starts in: {remaining} s"

        elif phase == "capture":
            if i < num_files:
                # timestamp base name
                ts_file   = datetime.now().strftime("%y%m%d%H%M%S%f")[:-3]
                base_name = ts_file

                # --- path untuk gambar & csv ---
                img_filename = f"{base_name}.jpg"
                csv_filename = f"{base_name}.csv"

                img_path = os.path.join(save_dir, img_filename)
                csv_path = os.path.join(save_dir, csv_filename)

                # --- EKSTRAK FITUR F (88-D) ---
                F = extract_features(frame_to_save)
                if F is None or F.shape[0] != 88:
                    print(f"[WARNING] Invalid feature shape for frame {i}, using zeros.")
                    F = np.zeros(88, dtype=np.float32)

                # Simpan fitur ke CSV: 1 baris x 88 kolom
                try:
                    np.savetxt(
                        csv_path,
                        F.reshape(1, -1),
                        delimiter=",",
                        fmt="%.6f"
                    )
                except Exception as e:
                    print(f"[WARNING] Failed to save CSV {csv_path}: {e}")

                # Simpan gambar
                cv2.imwrite(img_path, frame_to_save)

                i += 1
                print(f"[SAVE] {img_path} & {csv_path}  ({i}/{num_files})")
                text = f"Class: {class_name} | Samples: {i}/{num_files}"
            else:
                phase = "finish"
                finish_start = now
                text = "Capture finished. Exit countdown..."

        elif phase == "finish":
            remaining = int(finish_duration - (now - finish_start))
            if remaining <= 0:
                print("[INFO] Exit countdown finished. Exiting.")
                break
            else:
                text = f"Done. Exit in: {remaining} s"

        cv2.putText(
            frame_draw,
            text,
            (10, 30),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.7,
            (0, 255, 0),
            2,
        )

        cv2.imshow(f"Capture Dataset - {class_name}", frame_draw)

        key = cv2.waitKey(1) & 0xFF
        if key == 27:  # ESC
            print("[INFO] Stopped by user (ESC).")
            break

    cap.release()
    cv2.destroyAllWindows()
    print(f"[DONE] Image dataset for class '{class_name}' finished, total files: {i}")

# -----------------------------------------------------------
# FUNCTION: Load dataset images/features -> feature sequences
# -----------------------------------------------------------
def load_dataset(dataset_dir, class_list):
    """
    dataset_dir/
      Class1/
        seq1/
          *.csv (preferred, features)  OR
          *.jpg (fallback, compute features)
        seq2/
          ...
      Class2/
        ...

    X_seq: list of (T, 88)
    y_seq: list of one-hot labels
    """
    X_seq = []
    y_seq = []

    M  = len(class_list)
    I  = np.eye(M, dtype=np.float32)

    for class_idx, class_name in enumerate(class_list):
        class_dir = os.path.join(dataset_dir, class_name)
        if not os.path.isdir(class_dir):
            print(f"[WARNING] Class directory not found: {class_dir}")
            continue

        label_one_hot = I[class_idx, :]

        print(f"[INFO] Processing class '{class_name}' in {class_dir}")

        for seq_name in sorted(os.listdir(class_dir)):
            seq_dir = os.path.join(class_dir, seq_name)
            if not os.path.isdir(seq_dir):
                continue

            print(f"  [SEQ] Directory: {seq_dir}")

            F_list = []

            # --- 1) Coba baca fitur dari CSV jika ada ---
            csv_files = sorted(
                fn for fn in os.listdir(seq_dir)
                if fn.lower().endswith(".csv")
            )

            if csv_files:
                # Langsung load dari CSV
                for csv_name in csv_files:
                    csv_path = os.path.join(seq_dir, csv_name)
                    try:
                        F = np.loadtxt(csv_path, delimiter=",", dtype=np.float32)
                    except Exception as e:
                        print(f"    [WARNING] Failed to load CSV {csv_path}: {e}")
                        continue

                    F = np.array(F, dtype=np.float32).reshape(-1)
                    if F.shape[0] != 88:
                        print(f"    [WARNING] Invalid CSV feature length in {csv_path}: {F.shape}")
                        continue

                    F_list.append(F)
            else:
                # --- 2) Jika tidak ada CSV, fallback: hitung fitur dari gambar ---
                for file_name in sorted(os.listdir(seq_dir)):
                    if not file_name.lower().endswith((".jpg", ".jpeg", ".png", ".bmp")):
                        continue

                    img_path = os.path.join(seq_dir, file_name)
                    img = cv2.imread(img_path)

                    if img is None:
                        print(f"    [WARNING] Failed to load image: {img_path}")
                        continue

                    # update pose/hands
                    _ = draw_landmarks(img)
                    F = extract_features(img)

                    if F is None or F.shape[0] != 88:
                        print(f"    [WARNING] Invalid feature for {img_path}")
                        continue

                    # simpan fitur ke CSV supaya next time bisa langsung load
                    base_name, _ = os.path.splitext(file_name)
                    csv_path = os.path.join(seq_dir, base_name + ".csv")
                    try:
                        np.savetxt(
                            csv_path,
                            F.reshape(1, -1),
                            delimiter=",",
                            fmt="%.6f"
                        )
                    except Exception as e:
                        print(f"    [WARNING] Failed to save CSV {csv_path}: {e}")

                    F_list.append(F)

            if len(F_list) == 0:
                print(f"  [WARNING] No valid features in {seq_dir}")
                continue

            seq_F = np.vstack(F_list)  # (T, 88)
            X_seq.append(seq_F)
            y_seq.append(label_one_hot)

    X_seq = np.array(X_seq, dtype=object)
    y_seq = np.array(y_seq, dtype=np.float32)

    print(f"[DONE] Total sequences: {len(X_seq)}, label shape: {y_seq.shape}")
    return X_seq, y_seq



# -----------------------------------------------------------
# LSTM DATA PREPARATION (fixed sequence length T)
# -----------------------------------------------------------
def prepare_lstm_data_fixedT(X_seq, y_seq, T=None, test_size=0.2, random_state=42):
    """
    All sequences must have same length T.
    Output:
      X_train : (Ntrain, T, 88)
      y_train : (Ntrain, T, NClass)  -> label diulang di setiap frame
    """
    Ndata = len(X_seq)
    if Ndata == 0:
        raise ValueError("X_seq is empty. Please check your dataset.")

    if T is None:
        T = X_seq[0].shape[0]

    valid_indices = []
    for i, seq in enumerate(X_seq):
        if seq.shape[0] != T:
            print(f"[WARNING] Sequence {i} has length {seq.shape[0]}, expected {T}. Skipped.")
        else:
            valid_indices.append(i)

    if len(valid_indices) == 0:
        raise ValueError("No sequence has the expected length T.")

    X_seq_fixed = [X_seq[i] for i in valid_indices]
    y_seq_fixed = y_seq[valid_indices]

    X = np.stack(X_seq_fixed, axis=0).astype(np.float32)
    NumFrames   = X.shape[1]
    NumFeatures = X.shape[2]
    NClass      = y_seq_fixed.shape[1]
    Nvalid      = X.shape[0]

    print(f"[INFO] Ndata (valid) : {Nvalid}")
    print(f"[INFO] NumFrames    : {NumFrames}")
    print(f"[INFO] NumFeatures  : {NumFeatures}")
    print(f"[INFO] NClass       : {NClass}")

    use_full = False

    if Nvalid < 10:
        print("[INFO] Ndata_valid < 10 → using ALL data for both train and validation.")
        use_full = True
    else:
        if isinstance(test_size, float):
            n_test = int(max(1, np.floor(test_size * Nvalid)))
        else:
            n_test = int(test_size)

        if n_test < NClass:
            print(
                f"[INFO] Computed test_size ({n_test}) < number of classes ({NClass}).\n"
                "       Using ALL data for both train and validation."
            )
            use_full = True

    if use_full:
        X_train_seq = X
        y_train_seq = y_seq_fixed
        X_val_seq   = X
        y_val_seq   = y_seq_fixed
    else:
        X_train_seq, X_val_seq, y_train_seq, y_val_seq = train_test_split(
            X,
            y_seq_fixed,
            test_size=test_size,
            random_state=random_state,
            stratify=y_seq_fixed,
        )

    # Convert labels to 3D: repeat over time axis
    X_train = X_train_seq
    X_val   = X_val_seq

    y_train = np.repeat(y_train_seq[:, np.newaxis, :], NumFrames, axis=1)
    y_val   = np.repeat(y_val_seq[:,   np.newaxis, :], NumFrames, axis=1)

    print(f"[INFO] X_train shape : {X_train.shape}")   # (Ntrain, T, 88)
    print(f"[INFO] X_val   shape : {X_val.shape}")     # (Nval,   T, 88)
    print(f"[INFO] y_train shape : {y_train.shape}")   # (Ntrain, T, NClass)
    print(f"[INFO] y_val   shape : {y_val.shape}")     # (Nval,   T, NClass)

    return X_train, X_val, y_train, y_val, NumFrames, NumFeatures, NClass


# -----------------------------------------------------------
# BUILD LSTM MODEL (per-frame output)
# -----------------------------------------------------------
def build_lstm_model(NumFrames, NumFeatures, NClass):
    model = Sequential()

    model.add(LSTM(
        128,
        return_sequences=True,
        input_shape=(NumFrames, NumFeatures)
    ))
    model.add(Dropout(0.3))

    model.add(LSTM(
        64,
        return_sequences=True
    ))
    model.add(Dropout(0.3))

    # Dense di sini otomatis diaplikasikan ke setiap time-step
    model.add(Dense(NClass, activation="softmax"))

    model.compile(
        optimizer=tf.keras.optimizers.Adam(learning_rate=1e-3),
        loss="categorical_crossentropy",
        metrics=["accuracy"],
    )

    model.summary()
    return model


# -----------------------------------------------------------
# FULL TRAINING PIPELINE
# -----------------------------------------------------------
def train_lstm_model(
    dataset_dir,
    class_list,
    T=None,
    test_size=0.2,
    random_state=42,
    batch_size=16,
    epochs=50,
    save_model_path=None,
    callbacks=None,
):
    X_seq, y_seq = load_dataset(dataset_dir, class_list)

    if len(X_seq) == 0:
        raise ValueError("No sequences loaded. Please check dataset_dir / class_list.")

    (X_train, X_val, y_train, y_val,
     NumFrames, NumFeatures, NClass) = prepare_lstm_data_fixedT(
        X_seq,
        y_seq,
        T=T,
        test_size=test_size,
        random_state=random_state,
    )

    model = build_lstm_model(NumFrames, NumFeatures, NClass)

    history = model.fit(
        X_train,
        y_train,
        validation_data=(X_val, y_val),
        batch_size=batch_size,
        epochs=epochs,
        verbose=1,
        callbacks=callbacks,
    )

    if save_model_path is not None:
        model.save(save_model_path)
        print(f"[INFO] Model saved to {save_model_path}")

    return model, history, NumFrames


# -----------------------------------------------------------
# LIVE CLASSIFICATION (OpenCV window)
# -----------------------------------------------------------
def live_classification(
    model_path=MODEL_PATH,
    seq_length=SEQUENCE_LENGTH_T,
    infer_interval=5,
):
    """
    Live classification with sliding window of length seq_length.
    Model output: (1, T, NClass). Kita ambil rata-rata probabilitas
    sepanjang urutan untuk menentukan kelas.
    """
    if not os.path.exists(model_path):
        print(f"[ERROR] Model file not found: {model_path}")
        return

    print(f"[INFO] Loading model from {model_path} ...")
    model = load_model(model_path)

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
    if not cap.isOpened():
        print("[ERROR] Cannot open camera for live classification.")
        return

    feature_buffer = []
    counter        = 0
    current_label  = ""

    print("[INFO] Live classification started. Press ESC to exit.")

    while True:
        ret, frame = cap.read()
        if not ret:
            print("[ERROR] Failed to grab frame from camera.")
            break

        frame = cv2.flip(frame, 1)
        frame_bgr  = frame.copy()

        frame_draw = draw_landmarks(frame_bgr)

        F = extract_features(frame_bgr)
        feature_buffer.append(F)

        if len(feature_buffer) > seq_length:
            feature_buffer = feature_buffer[-seq_length:]

        if len(feature_buffer) == seq_length:
            counter += 1
            if counter >= infer_interval:
                counter = 0

                lx = np.array(feature_buffer, dtype=np.float32)
                lx = lx.reshape(1, seq_length, -1)

                preds = model.predict(lx, verbose=0)   # (1, T, NClass)
                prob_seq = preds[0]                    # (T, NClass)
                prob_mean = prob_seq.mean(axis=0)      # rata2 sepanjang waktu

                idx = int(np.argmax(prob_mean))

                if 0 <= idx < len(CLASS_LIST):
                    current_label = CLASS_LIST[idx]
                else:
                    current_label = f"Class {idx}"

                print(f"[PRED] {current_label}")

        text_label = "Predicting..." if current_label == "" else f"Gesture: {current_label}"

        cv2.putText(
            frame_draw,
            text_label,
            (10, 30),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.8,
            (255, 0, 0),
            2,
        )

        cv2.imshow("Live Classification", frame_draw)

        key = cv2.waitKey(1) & 0xFF
        if key == 27:  # ESC
            print("[INFO] Live classification stopped by user.")
            break

    cap.release()
    cv2.destroyAllWindows()


# -----------------------------------------------------------
# SIMPLE PYGAME MENU
# -----------------------------------------------------------
def draw_text_center(surface, text, font, color, y):
    text_surf = font.render(text, True, color)
    text_rect = text_surf.get_rect(center=(surface.get_width() // 2, y))
    surface.blit(text_surf, text_rect)


def pygame_main_menu():
    """
    1. Capture dataset
    2. Train LSTM model
    3. Edit classes
    4. Live classification
    5. Quit
    """
    pygame.init()
    screen = pygame.display.set_mode((800, 600))
    pygame.display.set_caption("Gesture LSTM Pipeline - Menu")

    font_title = pygame.font.SysFont(None, 48)
    font_menu  = pygame.font.SysFont(None, 32)
    clock      = pygame.time.Clock()

    running = True

    while running:
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                running = False

            if event.type == pygame.KEYDOWN:
                if event.key == pygame.K_ESCAPE:
                    running = False
                elif event.key == pygame.K_1:
                    pygame_capture_menu(screen)
                elif event.key == pygame.K_2:
                    pygame_train_lstm(screen)
                elif event.key == pygame.K_3:
                    pygame_edit_classes(screen)
                elif event.key == pygame.K_4:
                    live_classification()
                elif event.key == pygame.K_5:
                    running = False

        screen.fill((0, 0, 0))

        draw_text_center(screen, "Gesture LSTM Pipeline", font_title, (0, 255, 0), 80)
        draw_text_center(screen, "Press:", font_menu, (255, 255, 255), 180)
        draw_text_center(screen, "[1] Capture dataset",      font_menu, (255, 255, 0), 230)
        draw_text_center(screen, "[2] Train LSTM model",     font_menu, (255, 255, 0), 270)
        draw_text_center(screen, "[3] Edit classes",         font_menu, (255, 255, 0), 310)
        draw_text_center(screen, "[4] Live classification",  font_menu, (255, 255, 0), 350)
        draw_text_center(screen, "[5] Quit  (or ESC)",       font_menu, (255, 255, 0), 390)

        pygame.display.flip()
        clock.tick(30)

    pygame.quit()
    print("[INFO] Exiting Pygame menu.")


def pygame_capture_menu(screen):
    """
    Submenu Capture Dataset:
    - Setelah selesai capture, tetap di submenu ini.
    - Kembali ke main menu hanya jika menekan ESC.
    """
    font_title = pygame.font.SysFont(None, 40)
    font_menu  = pygame.font.SysFont(None, 30)
    clock      = pygame.time.Clock()

    selecting = True

    while selecting:
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                selecting = False

            if event.type == pygame.KEYDOWN:
                # ESC → kembali ke main menu
                if event.key == pygame.K_ESCAPE:
                    selecting = False

                # Pilih kelas dengan tombol angka
                if pygame.K_1 <= event.key <= pygame.K_9:
                    idx = event.key - pygame.K_1
                    if 0 <= idx < len(CLASS_LIST):
                        class_name = CLASS_LIST[idx]
                        print(f"[MENU] Capture dataset for class: {class_name}")
                        create_image_dataset(
                            dataset_dir=DATASET_DIR,
                            class_name=class_name,
                            num_files=SEQUENCE_LENGTH_T,
                        )
                        # tetap di submenu, bisa lanjut ambil kelas lain

        screen.fill((30, 30, 30))
        draw_text_center(screen, "Capture Dataset", font_title, (0, 255, 255), 60)
        draw_text_center(screen, "Press number key to select class:", font_menu, (255, 255, 255), 120)

        y = 170
        for i, class_name in enumerate(CLASS_LIST):
            text = f"[{i+1}] {class_name}"
            draw_text_center(screen, text, font_menu, (255, 255, 0), y)
            y += 40

        draw_text_center(screen, "[ESC] Back to main menu", font_menu, (200, 200, 200), y + 40)

        pygame.display.flip()
        clock.tick(30)


def pygame_train_lstm(screen):
    """
    Training LSTM dengan progress yang ditampilkan di Pygame.
    Training dijalankan di thread terpisah.
    """
    global TRAIN_STATUS

    font_title = pygame.font.SysFont(None, 40)
    font_menu  = pygame.font.SysFont(None, 26)
    clock      = pygame.time.Clock()

    # Reset status training
    TRAIN_STATUS.update({
        "epoch": 0,
        "total_epochs": EPOCHS,
        "loss": 0.0,
        "acc": 0.0,
        "val_loss": 0.0,
        "val_acc": 0.0,
        "running": True,
        "done": False,
        "error": None,
    })

    # Fungsi yang akan dijalankan di thread terpisah
    def train_thread_func():
        try:
            cb = PygameTrainCallback(EPOCHS)
            _model, _history, _NumFrames = train_lstm_model(
                dataset_dir=DATASET_DIR,
                class_list=CLASS_LIST,
                T=SEQUENCE_LENGTH_T,
                test_size=0.2,
                random_state=42,
                batch_size=BATCH_SIZE,
                epochs=EPOCHS,
                save_model_path=MODEL_PATH,
                callbacks=[cb],
            )
            TRAIN_STATUS["done"] = True
        except Exception as e:
            print("[ERROR] Training failed:", e)
            TRAIN_STATUS["error"] = str(e)
            TRAIN_STATUS["done"]  = True
        finally:
            TRAIN_STATUS["running"] = False

    # Jalankan thread training
    train_thread = threading.Thread(target=train_thread_func, daemon=True)
    train_thread.start()

    running = True
    while running:
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                # biarkan training lanjut di background, keluar ke main menu
                running = False
            if event.type == pygame.KEYDOWN:
                # Hanya boleh keluar kalau training sudah selesai (done)
                if TRAIN_STATUS["done"]:
                    running = False

        screen.fill((0, 0, 50))
        draw_text_center(screen, "Training LSTM Model", font_title, (0, 255, 255), 60)

        if TRAIN_STATUS["error"] is not None:
            # Ada error training
            draw_text_center(screen, "Training FAILED", font_menu, (255, 0, 0), 120)
            msg = TRAIN_STATUS["error"][:60] + ("..." if len(TRAIN_STATUS["error"]) > 60 else "")
            draw_text_center(screen, msg, font_menu, (255, 255, 255), 150)
            draw_text_center(screen, "Press any key to return to main menu.", font_menu, (255, 255, 0), 210)
        elif not TRAIN_STATUS["done"]:
            # Sedang training
            e   = TRAIN_STATUS["epoch"]
            te  = TRAIN_STATUS["total_epochs"]
            ls  = TRAIN_STATUS["loss"]
            ac  = TRAIN_STATUS["acc"]
            vls = TRAIN_STATUS["val_loss"]
            vac = TRAIN_STATUS["val_acc"]

            draw_text_center(screen, f"Epoch: {e}/{te}", font_menu, (255, 255, 0), 120)
            draw_text_center(screen, f"loss     : {ls:.4f}", font_menu, (255, 255, 255), 160)
            draw_text_center(screen, f"accuracy : {ac:.4f}", font_menu, (255, 255, 255), 190)
            draw_text_center(screen, f"val_loss : {vls:.4f}", font_menu, (200, 255, 255), 220)
            draw_text_center(screen, f"val_acc  : {vac:.4f}", font_menu, (200, 255, 255), 250)
            draw_text_center(screen, "(Training... do not close window)", font_menu, (255, 255, 0), 300)
        else:
            # Training selesai sukses
            draw_text_center(screen, "Training finished successfully.", font_menu, (0, 255, 0), 140)
            draw_text_center(screen, "Press any key to return to main menu.", font_menu, (255, 255, 0), 200)

        pygame.display.flip()
        clock.tick(15)


def pygame_edit_classes(screen):
    global CLASS_LIST

    font_title = pygame.font.SysFont(None, 40)
    font_menu  = pygame.font.SysFont(None, 26)
    font_small = pygame.font.SysFont(None, 22)
    clock      = pygame.time.Clock()

    current_name = ""
    editing = True

    while editing:
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                editing = False

            if event.type == pygame.KEYDOWN:
                if event.key == pygame.K_ESCAPE:
                    editing = False

                elif event.key in (pygame.K_PLUS, pygame.K_KP_PLUS, pygame.K_EQUALS):
                    name = current_name.strip()
                    if name and name not in CLASS_LIST:
                        CLASS_LIST.append(name)
                        save_class_list_to_file()
                        print(f"[EDIT] Added class: {name}")
                    current_name = ""

                elif event.key == pygame.K_BACKSPACE:
                    current_name = current_name[:-1]

                elif event.key == pygame.K_DELETE:
                    if CLASS_LIST:
                        removed = CLASS_LIST.pop()
                        save_class_list_to_file()
                        print(f"[EDIT] Removed last class: {removed}")

                else:
                    ch = event.unicode
                    if ch and (ch.isalnum() or ch in ["_", "-"]):
                        if len(current_name) < 20:
                            current_name += ch

        screen.fill((20, 20, 20))
        draw_text_center(screen, "Edit Classes", font_title, (0, 255, 255), 50)

        y_inst = 100
        draw_text_center(screen, "Type class name, press '+' to add.", font_menu, (255, 255, 255), y_inst)
        draw_text_center(screen, "Press 'DEL' to remove the LAST class.", font_menu, (255, 255, 255), y_inst + 30)
        draw_text_center(screen, "Press ESC to return to main menu.", font_menu, (200, 200, 200), y_inst + 60)

        draw_text_center(
            screen,
            f"New class: {current_name}",
            font_menu,
            (255, 255, 0),
            y_inst + 110,
        )

        y = y_inst + 160
        draw_text_center(screen, "Current classes:", font_menu, (0, 255, 0), y)
        y += 30

        if CLASS_LIST:
            for i, cname in enumerate(CLASS_LIST):
                text = f"{i+1}. {cname}"
                draw_text_center(screen, text, font_small, (255, 255, 255), y)
                y += 25
        else:
            draw_text_center(screen, "(no classes defined)", font_small, (150, 150, 150), y)

        pygame.display.flip()
        clock.tick(30)


# -----------------------------------------------------------
# MAIN ENTRY
# -----------------------------------------------------------
if __name__ == "__main__":
    load_class_list_from_file()
    pygame_main_menu()
