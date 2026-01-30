

import os
import time
from datetime import datetime

import cv2
import mediapipe as mp
import numpy as np
from numpy import expand_dims
from keras.models import Model
from keras.layers import Input, Dense, Conv2D, MaxPooling2D, Flatten
from keras.utils import load_img, img_to_array
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from tensorflow.keras.models import load_model  # used for loading saved models
import pygame
import socket

# =========================================================
# Root folder for dataset (each class will be a subfolder here)
DATASET_DIR = "WheelChair"  # change if needed

# Class labels (must match folder names and CNN output order)
CLASS_LABELS = ["Forward", "Backward", "Left", "Right", "Stop", "Neutral"]

# =========================================================
# Dataset creation
# =========================================================
def CreateDataset(DirPath, ClassName):
    # Ensure the target directory exists
    save_dir = os.path.join(DirPath, ClassName)
    os.makedirs(save_dir, exist_ok=True)

    # Initialize MediaPipe Hands and Drawing Utils
    mp_hands = mp.solutions.hands
    mp_draw = mp.solutions.drawing_utils

    hands = mp_hands.Hands(
        static_image_mode=False,
        max_num_hands=2,
        model_complexity=1,
        min_detection_confidence=0.5,
        min_tracking_confidence=0.5
    )

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)

    # State for countdown, capture, and finish
    phase = "countdown"       # "countdown" -> "capture" -> "finish"
    countdown_start = time.time()
    countdown_seconds = 10

    last_save_time = None
    saved_count = 0
    finish_start_time = None

    while True:
        success, frame = cap.read()
        if not success:
            break

        # >>> FLIP DULU SUPAYA MIRROR <<<
        frame = cv2.flip(frame, 1)

        # Convert frame color (BGR → RGB for MediaPipe)
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb_frame)

        # Create an empty black background with the same size as the frame
        black_canvas = np.zeros_like(frame)

        # Buffers for left and right hand crops (each resized to 128x128)
        left_hand_crop = None
        right_hand_crop = None

        if results.multi_hand_landmarks:
            for handedness, hand_landmarks in zip(results.multi_handedness,
                                                  results.multi_hand_landmarks):

                # Draw landmarks on the original frame
                mp_draw.draw_landmarks(
                    frame,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # Draw landmarks on the black background (same frame size)
                mp_draw.draw_landmarks(
                    black_canvas,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # ----------------- PER-HAND CROPPING -----------------
                label = handedness.classification[0].label  # 'Left' or 'Right'

                h, w, c = frame.shape
                xs = [lm.x * w for lm in hand_landmarks.landmark]
                ys = [lm.y * h for lm in hand_landmarks.landmark]

                min_x, max_x = int(min(xs)), int(max(xs))
                min_y, max_y = int(min(ys)), int(max(ys))

                margin = 20
                min_x = max(min_x - margin, 0)
                min_y = max(min_y - margin, 0)
                max_x = min(max_x + margin, w - 1)
                max_y = min(max_y + margin, h - 1)

                box_w = max_x - min_x
                box_h = max_y - min_y
                side = max(box_w, box_h)

                cx = (min_x + max_x) // 2
                cy = (min_y + max_y) // 2

                start_x = max(cx - side // 2, 0)
                start_y = max(cy - side // 2, 0)
                end_x = min(start_x + side, w)
                end_y = min(start_y + side, h)

                side = min(end_x - start_x, end_y - start_y)
                end_x = start_x + side
                end_y = start_y + side

                # Crop from black_canvas (hand over black background)
                hand_region = black_canvas[start_y:end_y, start_x:end_x]

                hand_h, hand_w = hand_region.shape[:2]
                square_side = max(hand_h, hand_w)
                hand_canvas = np.zeros((square_side, square_side, 3), dtype=np.uint8)

                y_off = (square_side - hand_h) // 2
                x_off = (square_side - hand_w) // 2
                hand_canvas[y_off:y_off + hand_h, x_off:x_off + hand_w] = hand_region

                # Resize to 128 x 128
                resized_hand = cv2.resize(hand_canvas, (128, 128), interpolation=cv2.INTER_AREA)

                if label == "Left":
                    left_hand_crop = resized_hand
                elif label == "Right":
                    right_hand_crop = resized_hand
                # ----------------- END CROP -----------------

        # ---------- ALWAYS SHOW CLASS NAME ON FRAME ----------
        cv2.putText(
            frame,
            f"Class: {ClassName}",
            (30, 90),
            cv2.FONT_HERSHEY_SIMPLEX,
            1.0,
            (255, 255, 0),
            2
        )
        # -----------------------------------------------------

        # Show original frame with landmarks (and class name)
        cv2.imshow("Original Video with Landmarks", frame)

        # -------------- MERGE LEFT-RIGHT INTO 256x256 --------------
        combined_256 = None

        if (left_hand_crop is not None) or (right_hand_crop is not None):
            # Base black image: 128 x 256
            combined_128x256 = np.zeros((128, 256, 3), dtype=np.uint8)

            # If left/right hand is not detected, that side remains black
            if left_hand_crop is not None:
                combined_128x256[0:128, 0:128] = left_hand_crop
            if right_hand_crop is not None:
                combined_128x256[0:128, 128:256] = right_hand_crop

            # Create final 256 x 256 canvas and center the 128 x 256 image vertically
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)
            y_start = (256 - 128) // 2  # 64
            combined_256[y_start:y_start + 128, 0:256] = combined_128x256
        else:
            # >>> NEW: if no hand detected, use a fully black 256x256 image <<<
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)

        # ===================== COUNTDOWN / CAPTURE / FINISH LOGIC =====================
        now = time.time()

        if phase == "countdown":
            elapsed = now - countdown_start
            remain = countdown_seconds - int(elapsed)

            if remain <= 0:
                phase = "capture"
                last_save_time = now
            else:
                # Show countdown text on the frame
                text = f"Starting in {remain}"
                cv2.putText(frame, text, (30, 50),
                            cv2.FONT_HERSHEY_SIMPLEX, 1.2, (0, 0, 255), 3)
                cv2.imshow("Original Video with Landmarks", frame)

        elif phase == "capture":
            # Show number of saved images
            cv2.putText(frame, f"Capturing... {saved_count}/20", (30, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 0), 2)
            cv2.imshow("Original Video with Landmarks", frame)

            # Save combined_256 every 0.5 seconds
            if combined_256 is not None:
                if (last_save_time is None) or ((now - last_save_time) >= 0.5):
                    # File name format: ClassNameYYMMDDhhmmssms.jpg
                    dt = datetime.now()
                    ms = int(dt.microsecond / 1000)
                    filename = f"{ClassName}{dt.strftime('%y%m%d%H%M%S')}{ms:03d}.jpg"
                    filepath = os.path.join(save_dir, filename)

                    cv2.imwrite(filepath, combined_256)
                    saved_count += 1
                    last_save_time = now
                    print("Saved:", filepath)

            # Check if 20 images have been saved
            if saved_count >= 20:
                phase = "finish"
                finish_start_time = now

        elif phase == "finish":
            # Show FINISH text
            cv2.putText(frame, "FINISH", (80, 200),
                        cv2.FONT_HERSHEY_SIMPLEX, 2.0, (0, 255, 255), 4)
            cv2.imshow("Original Video with Landmarks", frame)

            if now - finish_start_time >= 2.0:
                # After 2 seconds, exit the loop
                break
        # ============================================================================

        # Manual exit with ESC (kept as waitKey(1))
        if cv2.waitKey(1) & 0xFF == 27:
            break

    cap.release()
    cv2.destroyAllWindows()
    hands.close()


# --------------------------------------------------------
# Load Dataset
# --------------------------------------------------------
def load_training_images(dataset_dir, class_labels):

    num_classes = len(class_labels)
    one_hot_targets = np.eye(num_classes)

    data_images = []
    data_labels = []

    for class_index, label in enumerate(class_labels):

        class_path = os.path.join(dataset_dir, label)
        file_list = os.listdir(class_path)

        for file_name in file_list:
            lower_name = file_name.lower()
            print(file_name)

            if lower_name.endswith(('.jpg', '.jpeg', '.png')):

                img_path = os.path.join(class_path, file_name)
                img = cv2.imread(img_path)
                img = cv2.resize(img, (128, 128))
                img = img.astype('float32') / 255.0

                data_images.append(img)
                data_labels.append(one_hot_targets[class_index])

    data_images = np.array(data_images, dtype='float32')
    data_labels = np.array(data_labels, dtype='float32')
    return data_images, data_labels


# --------------------------------------------------------
# Build CNN Model
# --------------------------------------------------------
def build_cnn(num_classes):

    input_layer = Input(shape=(128, 128, 3))

    x = Conv2D(32, (3, 3), activation='relu', padding='same')(input_layer)
    x = MaxPooling2D((2, 2), padding='same')(x)
    x = Conv2D(32, (3, 3), activation='relu', padding='same')(x)
    x = MaxPooling2D((2, 2), padding='same')(x)
    x = Conv2D(32, (3, 3), activation='relu', padding='same')(x)

    x = Flatten()(x)
    x = Dense(100, activation='relu')(x)
    output_layer = Dense(num_classes, activation='softmax')(x)

    model_cnn = Model(input_layer, output_layer)
    model_cnn.compile(loss='categorical_crossentropy',
                      optimizer='adam',
                      metrics=['accuracy'])

    return model_cnn


# --------------------------------------------------------
# Train Model
# --------------------------------------------------------
def train_cnn(num_epochs, dataset_dir, class_labels, weight_file="Weight.h5"):

    X, Y = load_training_images(dataset_dir, class_labels)
    num_classes = len(class_labels)

    model_cnn = build_cnn(num_classes)

    history = model_cnn.fit(X, Y, epochs=num_epochs, shuffle=True)
    model_cnn.save(weight_file)

    return model_cnn, history


# --------------------------------------------------------
# Classification on folder
# --------------------------------------------------------
def classify_images(dataset_dir, target_folder, class_labels, model_cnn=None):

    if model_cnn is None:
        raise ValueError("model_cnn must be provided.")

    test_images = []
    file_list = []

    folder_path = os.path.join(dataset_dir, target_folder)
    print(folder_path)

    files = os.listdir(folder_path)

    for file_name in files:
        lower_name = file_name.lower()
        print(file_name)

        if lower_name.endswith(('.jpg', '.jpeg', '.png')):
            file_list.append(file_name)

            img_path = os.path.join(folder_path, file_name)
            img = cv2.imread(img_path)
            img = cv2.resize(img, (128, 128))
            img = img.astype('float32') / 255.0

            test_images.append(img)

    test_images = np.array(test_images, dtype='float32')

    predictions = model_cnn.predict(test_images)

    predicted_indices = []
    predicted_labels = []

    for vec in predictions:
        if vec.max() > 0.5:
            idx = np.argmax(vec)
            predicted_labels.append(class_labels[idx])
        else:
            idx = -1
            predicted_labels.append("Unknown")

        predicted_indices.append(idx)

    return file_list, predictions, predicted_labels


# --------------------------------------------------------
# Load Saved Model
# --------------------------------------------------------
def load_saved_model(weight_file="Weight.h5"):
    return load_model(weight_file)


# --------------------------------------------------------
# Data Augmentation
# --------------------------------------------------------
def image_augmentation(dataset_dir, class_label):

    output_folder_name = class_label + "_ext"
    output_dir = os.path.join(dataset_dir, output_folder_name)

    if not os.path.exists(output_dir):
        os.mkdir(output_dir)

    class_path = os.path.join(dataset_dir, class_label)
    file_list = os.listdir(class_path)

    counter = 0

    for file_name in file_list:
        lower_name = file_name.lower()

        if lower_name.endswith(('.jpg', '.jpeg', '.png')):

            print(file_name)

            src_path = os.path.join(class_path, file_name)
            img = load_img(src_path)
            img_array = np.array(img)

            # Save original
            dst_original = os.path.join(output_dir, file_name)
            cv2.imwrite(dst_original, cv2.cvtColor(img_array, cv2.COLOR_RGB2BGR))

            # Prepare augmentation data
            data = img_to_array(img)
            samples = expand_dims(data, 0)

            datagen = ImageDataGenerator(
                rotation_range=90,
                brightness_range=[0.2, 2.0],
                zoom_range=[0.5, 2.0],
                width_shift_range=0.2,
                height_shift_range=0.2
            )

            iterator = datagen.flow(samples, batch_size=1)

            for i in range(9):
                batch = next(iterator)
                aug_image = batch[0].astype('uint8')

                counter += 1
                new_filename = datetime.now().strftime("%Y%m%d%H%M%S") + "_" + str(counter) + ".jpg"
                save_path = os.path.join(output_dir, new_filename)

                cv2.imwrite(save_path, cv2.cvtColor(aug_image, cv2.COLOR_RGB2BGR))


# =========================================================
# Realtime Classification (view only)
# =========================================================
def RealtimeClassification(model, class_names):
    """
    model       : Loaded CNN model (input size 128x128x3, output = number of classes)
    class_names : List of class labels in the same order as the CNN output layer
    """

    mp_hands = mp.solutions.hands
    mp_draw = mp.solutions.drawing_utils

    hands = mp_hands.Hands(
        static_image_mode=False,
        max_num_hands=2,
        model_complexity=1,
        min_detection_confidence=0.5,
        min_tracking_confidence=0.5
    )

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)

    # lpr berisi 15 nilai awal -1 (tidak ada kelas)
    lpr = [-1] * 15

    while True:
        success, frame = cap.read()
        if not success:
            break

        # >>> FLIP DULU <<<
        frame = cv2.flip(frame, 1)

        # Convert frame format: OpenCV BGR → MediaPipe RGB
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb_frame)

        # Create an empty black canvas matching the frame size
        black_canvas = np.zeros_like(frame)

        # Buffers for left and right hand crops (resized to 128x128 later)
        left_hand_crop = None
        right_hand_crop = None

        if results.multi_hand_landmarks:
            for handedness, hand_landmarks in zip(results.multi_handedness,
                                                  results.multi_hand_landmarks):

                # Draw landmarks on the original video feed
                mp_draw.draw_landmarks(
                    frame,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # Draw landmarks on the black background
                mp_draw.draw_landmarks(
                    black_canvas,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # ----------------- HAND CROPPING -----------------
                label = handedness.classification[0].label  # 'Left' or 'Right'

                h, w, c = frame.shape
                xs = [lm.x * w for lm in hand_landmarks.landmark]
                ys = [lm.y * h for lm in hand_landmarks.landmark]

                min_x, max_x = int(min(xs)), int(max(xs))
                min_y, max_y = int(min(ys)), int(max(ys))

                # Add margin
                margin = 20
                min_x = max(min_x - margin, 0)
                min_y = max(min_y - margin, 0)
                max_x = min(max_x + margin, w - 1)
                max_y = min(max_y + margin, h - 1)

                box_w = max_x - min_x
                box_h = max_y - min_y
                side = max(box_w, box_h)

                cx = (min_x + max_x) // 2
                cy = (min_y + max_y) // 2

                start_x = max(cx - side // 2, 0)
                start_y = max(cy - side // 2, 0)
                end_x = min(start_x + side, w)
                end_y = min(start_y + side, h)

                # Adjust bounding box if touching frame border
                side = min(end_x - start_x, end_y - start_y)
                end_x = start_x + side
                end_y = start_y + side

                # Crop from black_canvas (only hand landmarks remain)
                hand_region = black_canvas[start_y:end_y, start_x:end_x]

                hand_h, hand_w = hand_region.shape[:2]
                square_side = max(hand_h, hand_w)
                hand_canvas = np.zeros((square_side, square_side, 3), dtype=np.uint8)

                # Center crop inside a square canvas
                y_off = (square_side - hand_h) // 2
                x_off = (square_side - hand_w) // 2
                hand_canvas[y_off:y_off + hand_h, x_off:x_off + hand_w] = hand_region

                # Resize to 128x128 for model input
                resized_hand = cv2.resize(hand_canvas, (128, 128), interpolation=cv2.INTER_AREA)

                if label == "Left":
                    left_hand_crop = resized_hand
                elif label == "Right":
                    right_hand_crop = resized_hand
                # ----------------- END CROP -----------------

        # -------------- COMBINE LEFT & RIGHT INTO 256x256 IMAGE --------------
        combined_256 = None

        if (left_hand_crop is not None) or (right_hand_crop is not None):
            combined_128x256 = np.zeros((128, 256, 3), dtype=np.uint8)

            if left_hand_crop is not None:
                combined_128x256[0:128, 0:128] = left_hand_crop

            if right_hand_crop is not None:
                combined_128x256[0:128, 128:256] = right_hand_crop

            # Final centered view (256x256)
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)
            y_start = (256 - 128) // 2
            combined_256[y_start:y_start + 128, 0:256] = combined_128x256
        else:
            # If no hand is detected, use a fully black 256x256 image
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)

        # ---------------- REAL-TIME INFERENCE ----------------
        if combined_256 is not None:

            # 1. Resize to model input size
            img_128 = cv2.resize(combined_256, (128, 128), interpolation=cv2.INTER_AREA)

            # 2. Normalize pixel values
            img_128 = img_128.astype(np.float32) / 255.0

            # 3. Expand dimensions → model expects shape (1,128,128,3)
            X = np.expand_dims(img_128, axis=0)

            # Model prediction
            preds = model.predict(X, verbose=0)
            class_id = int(np.argmax(preds[0]))
            prob = float(preds[0][class_id])

            # ========== SLIDING WINDOW & MAJORITY FILTER ==========
            # Tambah class_id ke lpr
            lpr.append(class_id)

            # Pastikan panjang lpr = 15 (hapus yang paling awal jika > 15)
            if len(lpr) > 15:
                lpr.pop(0)

            # Hitung distribusi tiap kelas (abaikan -1)
            class_counts = {}
            for cid in lpr:
                if cid < 0:
                    continue
                class_counts[cid] = class_counts.get(cid, 0) + 1

            # Kelas hasil filter (kelas yang dicari)
            filtered_class_id = -1

            # Jika distribusi class_id > 80% window
            if class_counts.get(class_id, 0) > 15 * 0.8:
                filtered_class_id = class_id
            else:
                filtered_class_id = -1
            # =======================================================

            if 0 <= filtered_class_id < len(class_names):
                final_label = class_names[filtered_class_id]
            else:
                # Jika tidak ada kelas yang stabil, bisa diberi label khusus
                final_label = "No Stable Class"

            # Display filtered label + probability (prob masih dari prediksi terakhir)
            text = f"{final_label}: {prob:.2f}"
            cv2.putText(frame, text, (30, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 0), 2)

            # Optional: show the processed model input image
            #cv2.imshow("Model Input 128x128", img_128)

        # Main display
        cv2.imshow("Original Video with Landmarks", frame)

        # Exit program using ESC key
        if cv2.waitKey(1) & 0xFF == 27:
            break

    cap.release()
    cv2.destroyAllWindows()
    hands.close()


# =========================================================
# Realtime Hand-Gesture Wheelchair Control
# =========================================================

# Urutan kelas HARUS sama dengan output layer model
CLASS_LABELS = ["Forward", "Backward", "Left", "Right", "Stop", "Neutral"]
MODEL_PATH = r"Weight.h5"   # contoh; sesuaikan


def send(msg: str):
    """
    Kirim satu karakter perintah ke ESP32.

    Mapping di firmware:
      A = KANAN
      B = MAJU
      C = BERHENTI
      D = MUNDUR
      E = KIRI
    """
    ESP_HOST = "192.168.4.1"
    ESP_PORT = 80

    data = (msg + "\n").encode("utf-8")
    try:
        with socket.create_connection((ESP_HOST, ESP_PORT), timeout=2) as s:
            s.sendall(data)
            print("Sent:", msg)
    except OSError as e:
        print("Socket error:", e)


def RealtimeClassificationWhellChairControl(model, class_names):
    """
    model       : CNN ter-load (input 128x128x3, output = len(class_names))
    class_names : list label sesuai urutan output (CLASS_LABELS)
    """

    mp_hands = mp.solutions.hands
    mp_draw = mp.solutions.drawing_utils

    hands = mp_hands.Hands(
        static_image_mode=False,
        max_num_hands=2,
        model_complexity=1,
        min_detection_confidence=0.5,
        min_tracking_confidence=0.5
    )

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)

    # Sliding window untuk majority vote (15 frame)
    lpr = [-1] * 15
    StatusBef = ""  # status kelas stabil sebelumnya

    while True:
        success, frame = cap.read()
        if not success:
            break

        # >>> FLIP DULU <<<
        frame = cv2.flip(frame, 1)

        # BGR → RGB untuk MediaPipe
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb_frame)

        # Canvas hitam
        black_canvas = np.zeros_like(frame)

        left_hand_crop = None
        right_hand_crop = None

        if results.multi_hand_landmarks:
            for handedness, hand_landmarks in zip(results.multi_handedness,
                                                  results.multi_hand_landmarks):

                # Gambar di frame asli
                mp_draw.draw_landmarks(
                    frame,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # Gambar di canvas hitam
                mp_draw.draw_landmarks(
                    black_canvas,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # ----------------- CROPPING PER TANGAN -----------------
                label_lr = handedness.classification[0].label  # 'Left' / 'Right'

                h, w, c = frame.shape
                xs = [lm.x * w for lm in hand_landmarks.landmark]
                ys = [lm.y * h for lm in hand_landmarks.landmark]

                min_x, max_x = int(min(xs)), int(max(xs))
                min_y, max_y = int(min(ys)), int(max(ys))

                margin = 20
                min_x = max(min_x - margin, 0)
                min_y = max(min_y - margin, 0)
                max_x = min(max_x + margin, w - 1)
                max_y = min(max_y + margin, h - 1)

                box_w = max_x - min_x
                box_h = max_y - min_y
                side = max(box_w, box_h)

                cx = (min_x + max_x) // 2
                cy = (min_y + max_y) // 2

                start_x = max(cx - side // 2, 0)
                start_y = max(cy - side // 2, 0)
                end_x = min(start_x + side, w)
                end_y = min(start_y + side, h)

                side = min(end_x - start_x, end_y - start_y)
                end_x = start_x + side
                end_y = start_y + side

                hand_region = black_canvas[start_y:end_y, start_x:end_x]

                hand_h, hand_w = hand_region.shape[:2]
                square_side = max(hand_h, hand_w)
                hand_canvas = np.zeros((square_side, square_side, 3), dtype=np.uint8)

                y_off = (square_side - hand_h) // 2
                x_off = (square_side - hand_w) // 2
                hand_canvas[y_off:y_off + hand_h, x_off:x_off + hand_w] = hand_region

                resized_hand = cv2.resize(hand_canvas, (128, 128), interpolation=cv2.INTER_AREA)

                if label_lr == "Left":
                    left_hand_crop = resized_hand
                elif label_lr == "Right":
                    right_hand_crop = resized_hand
                # ----------------- END CROP -----------------

        # -------------- KOMBINASI LEFT/RIGHT KE 256x256 --------------
        if (left_hand_crop is not None) or (right_hand_crop is not None):
            combined_128x256 = np.zeros((128, 256, 3), dtype=np.uint8)

            if left_hand_crop is not None:
                combined_128x256[0:128, 0:128] = left_hand_crop
            if right_hand_crop is not None:
                combined_128x256[0:128, 128:256] = right_hand_crop

            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)
            y_start = (256 - 128) // 2
            combined_256[y_start:y_start + 128, 0:256] = combined_128x256
        else:
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)

        # ---------------- REAL-TIME INFERENCE ----------------
        if combined_256 is not None:
            img_128 = cv2.resize(combined_256, (128, 128), interpolation=cv2.INTER_AREA)
            img_128 = img_128.astype(np.float32) / 255.0
            X = np.expand_dims(img_128, axis=0)

            preds = model.predict(X, verbose=0)
            class_id = int(np.argmax(preds[0]))
            prob = float(preds[0][class_id])

            # ========== SLIDING WINDOW & MAJORITY FILTER ==========
            lpr.append(class_id)
            if len(lpr) > 15:
                lpr.pop(0)

            class_counts = {}
            for cid in lpr:
                if cid < 0:
                    continue
                class_counts[cid] = class_counts.get(cid, 0) + 1

            filtered_class_id = -1
            if class_counts.get(class_id, 0) > 15 * 0.8:
                filtered_class_id = class_id
            # =======================================================
            ff = ""
            if 0 <= filtered_class_id < len(class_names):
                final_label = class_names[filtered_class_id]
                ff = final_label
            else:
                final_label = "No Stable Class"
                # ff dibiarkan "" kalau tidak ada kelas stabil

            # =============== KIRIM KE KURSI RODA (PAKAI send() INI SAJA) ===============
            # CLASS_LABELS = ["Forward", "Backward", "Left", "Right", "Stop", "Neutral"]
            # Mapping (firmware):
            #   A = KANAN
            #   B = MAJU
            #   C = BERHENTI
            #   D = MUNDUR
            #   E = KIRI

            # Jika kelas stabil berubah (StatusBef ≠ ff) dan ada kelas valid,
            # kirim STOP dulu supaya berganti arah harus berhenti dulu
            if StatusBef != ff and ff != "":
                send("C")
                StatusBef = final_label

            if final_label == "Forward":
                send("B")   # MAJU
            elif final_label == "Backward":
                send("D")   # MUNDUR
            elif final_label == "Right":
                send("A")   # KANAN
            elif final_label == "Left":
                send("E")   # KIRI
            elif final_label == "Stop":
                send("C")   # BERHENTI
            # "Neutral" atau "No Stable Class" → tidak kirim apa-apa
            # ==========================================================================

            # Tampilkan label + probabilitas
            text = f"{final_label}: {prob:.2f}"
            cv2.putText(frame, text, (30, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 0), 2)

            #cv2.imshow("Model Input 128x128", img_128)

        cv2.imshow("Original Video with Landmarks", frame)

        # ESC untuk keluar
        if cv2.waitKey(1) & 0xFF == 27:
            break

    cap.release()
    cv2.destroyAllWindows()
    hands.close()


# =========================================================
# PYGAME MENU
# =========================================================
def draw_text(text, font, color, surface, x, y):
    """Utility to draw text on a pygame surface."""
    obj = font.render(text, True, color)
    surface.blit(obj, (x, y))


def run_menu():
    """
    Pygame menu:

    1. Create Dataset Forward
    2. Create Dataset Backward
    3. Create Dataset Left
    4. Create Dataset Right
    5. Create Dataset Stop
    6. Create Dataset Neutral
    7. Training (train_cnn)
    8. Realtime Classification (tanpa kursi roda)
    9. Realtime Classification + Wheelchair Control
    """

    pygame.init()
    screen = pygame.display.set_mode((780, 500))
    pygame.display.set_caption("Gesture CNN Menu")

    font = pygame.font.Font(None, 32)

    running = True
    while running:
        screen.fill((255, 255, 255))

        draw_text("Main Menu", font, (0, 0, 0), screen, 320, 20)

        draw_text("1. Create Dataset - Forward",   font, (0, 0, 0), screen, 80, 70)
        draw_text("2. Create Dataset - Backward",  font, (0, 0, 0), screen, 80, 110)
        draw_text("3. Create Dataset - Left",      font, (0, 0, 0), screen, 80, 150)
        draw_text("4. Create Dataset - Right",     font, (0, 0, 0), screen, 80, 190)
        draw_text("5. Create Dataset - Stop",      font, (0, 0, 0), screen, 80, 230)
        draw_text("6. Create Dataset - Neutral",   font, (0, 0, 0), screen, 80, 270)

        draw_text("7. Training (train CNN)",                       font, (0, 0, 0), screen, 80, 320)
        draw_text("8. Realtime Classification (view only)",        font, (0, 0, 0), screen, 80, 360)
        draw_text("9. Realtime Classification + Wheelchair Control", font, (0, 0, 0), screen, 80, 400)

        draw_text("ESC: Exit",                     font, (150, 0, 0), screen, 80, 440)

        pygame.display.update()

        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                running = False

            if event.type == pygame.KEYDOWN:
                # Exit with ESC
                if event.key == pygame.K_ESCAPE:
                    running = False

                # 1–6: create dataset for each class
                elif event.key == pygame.K_1:
                    CreateDataset(DATASET_DIR, "Forward")
                elif event.key == pygame.K_2:
                    CreateDataset(DATASET_DIR, "Backward")
                elif event.key == pygame.K_3:
                    CreateDataset(DATASET_DIR, "Left")
                elif event.key == pygame.K_4:
                    CreateDataset(DATASET_DIR, "Right")
                elif event.key == pygame.K_5:
                    CreateDataset(DATASET_DIR, "Stop")
                elif event.key == pygame.K_6:
                    CreateDataset(DATASET_DIR, "Neutral")

                # 7: Training
                elif event.key == pygame.K_7:
                    print("[INFO] Start training CNN...")
                    model, history = train_cnn(
                        num_epochs=20,
                        dataset_dir=DATASET_DIR,
                        class_labels=CLASS_LABELS,
                        weight_file="Weight.h5"
                    )
                    print("[INFO] Training finished. Model saved as Weight.h5")

                # 8: Realtime classification (TANPA kirim ke kursi roda)
                elif event.key == pygame.K_8:
                    print("[INFO] Loading model and starting realtime classification (view only)...")
                    model = load_saved_model("Weight.h5")
                    RealtimeClassification(model, CLASS_LABELS)

                # 9: Realtime classification + kontrol kursi roda
                elif event.key == pygame.K_9:
                    print("[INFO] Loading model and starting wheelchair control...")
                    model = load_saved_model("Weight.h5")
                    RealtimeClassificationWhellChairControl(model, CLASS_LABELS)

    pygame.quit()


# =========================================================
# MAIN
# =========================================================
if __name__ == "__main__":
    run_menu()

