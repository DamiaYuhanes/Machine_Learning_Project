import cv2
import mediapipe as mp
import os
from datetime import datetime
import time
import sys
import numpy as np
from tensorflow.keras.models import load_model

import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Conv2D, MaxPooling2D, Flatten, Dense, Dropout
from sklearn.model_selection import train_test_split
import pygame

import requests


# --- DATA CAPTURE FUNCTION ---
def CreateData(ClassName, Camera=0, capture_interval=1, max_images=20):
    """Function for capturing hand gesture dataset"""

    # Initialize MediaPipe Hands
    mp_hands = mp.solutions.hands
    hands = mp_hands.Hands(static_image_mode=False, max_num_hands=2, min_detection_confidence=0.5)
    mp_drawing = mp.solutions.drawing_utils

    # Initialize webcam
    cap = cv2.VideoCapture(Camera, cv2.CAP_DSHOW)

    # Create directory if it does not exist
    if not os.path.exists(ClassName):
        os.makedirs(ClassName)

    # Control variables for image capture
    start_time = time.time()
    time_before = start_time
    image_count = 0

    initial_delay = 10  # Delay before starting to save dataset

    while cap.isOpened():
        success, image = cap.read()
        if not success:
            print("Ignoring empty camera frame.")
            continue

        # Flip the image for a mirror view
        image = cv2.flip(image, 1)

        # Convert BGR to RGB
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

        # Process hand detection
        results = hands.process(image_rgb)

        # Countdown before recording starts
        timenow = time.time()
        elapsed_time = timenow - start_time
        if elapsed_time < initial_delay:
            countdown = int(initial_delay - elapsed_time)
            cv2.putText(image, f"Starting in: {countdown}", (50, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)

        # Detect and draw bounding box
        if results.multi_hand_landmarks:
            for hand_landmarks, handedness in zip(results.multi_hand_landmarks, results.multi_handedness):
                if handedness.classification[0].label == 'Right':
                    h, w, c = image.shape
                    landmark_points = [(int(lm.x * w), int(lm.y * h)) for lm in hand_landmarks.landmark]

                    x_min = min(x for x, _ in landmark_points)
                    y_min = min(y for _, y in landmark_points)
                    x_max = max(x for x, _ in landmark_points)
                    y_max = max(y for _, y in landmark_points)

                    cv2.rectangle(image, (x_min, y_min), (x_max, y_max), (0, 255, 0), 2)

                    mp_drawing.draw_landmarks(
                        image, hand_landmarks, mp_hands.HAND_CONNECTIONS,
                        mp_drawing.DrawingSpec(color=(0, 0, 255), thickness=2),
                        mp_drawing.DrawingSpec(color=(0, 0, 255), thickness=2)
                    )

                    # Extract ROI
                    hand_roi = image[y_min:y_max, x_min:x_max]
                    if hand_roi.size != 0:
                        if elapsed_time > initial_delay and (timenow - time_before) > capture_interval:
                            timestamp = datetime.now().strftime("%y%m%d%H%M%S")
                            filename = os.path.join(ClassName, f"{timestamp}.jpg")
                            cv2.imwrite(filename, hand_roi)
                            image_count += 1
                            print(f"Image {image_count} saved: {filename}")
                            time_before = timenow

        cv2.putText(image, "Press Q to exit", (1, 20),
                    cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

        cv2.putText(image, f"Images saved: {image_count}", (50, 80),
                    cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

        cv2.imshow('MediaPipe Hands', image)

        key = cv2.waitKey(5) & 0xFF
        if key in [27, ord('q'), ord('Q')] or image_count >= max_images:
            break

    cap.release()
    cv2.destroyAllWindows()


# --- DATA LOADING FUNCTION ---
def LoadImage(directory, size=(64, 64)):
    """Load and resize image dataset"""
    X = []
    for filename in os.listdir(directory):
        if filename.endswith(".jpg"):
            img = cv2.imread(os.path.join(directory, filename))
            if img is not None:
                X.append(cv2.resize(img, size).astype(float) / 255)
    print(f"Total images loaded: {len(X)}")
    return X


def LoadData(ClassList):
    """Assign label to image dataset"""
    Label = np.eye(len(ClassList))
    y, X = [], []
    for index, ClassName in enumerate(ClassList):
        images = LoadImage(ClassName)
        for img in images:
            X.append(img)
            y.append(Label[index])
    return np.array(X), np.array(y)


# --- TRAINING FUNCTION ---
def TrainingData(X, y, save_path="model.h5", epoh=20):
    """Train CNN model using dataset"""

    X_train, X_val, y_train, y_val = train_test_split(X, y, test_size=0.2, random_state=42)

    def create_cnn_model(input_shape, num_classes):
        model = Sequential([
            Conv2D(32, (3, 3), activation='relu', input_shape=input_shape),
            MaxPooling2D((2, 2)),
            Conv2D(64, (3, 3), activation='relu'),
            MaxPooling2D((2, 2)),
            Conv2D(128, (3, 3), activation='relu'),
            MaxPooling2D((2, 2)),
            Flatten(),
            Dense(128, activation='relu'),
            Dropout(0.5),
            Dense(num_classes, activation='softmax')
        ])
        return model

    model = create_cnn_model(X_train.shape[1:], y_train.shape[1])
    model.compile(optimizer='adam', loss='categorical_crossentropy', metrics=['accuracy'])

    model.fit(X_train, y_train, validation_data=(X_val, y_val), epochs=epoh, batch_size=1, shuffle=True)

    model.save(save_path)
    print(f"Model saved at: {save_path}")

    return model


# --- CLASSIFICATION FUNCTION ---
def Klasifikasi(ClassList, Camera=0, model_path="model.h5"):
    """Real-time gesture classification"""

    mp_hands = mp.solutions.hands
    hands = mp_hands.Hands()
    mp_drawing = mp.solutions.drawing_utils
    model = load_model(model_path)

    cap = cv2.VideoCapture(Camera, cv2.CAP_DSHOW)

    while cap.isOpened():
        ret, image = cap.read()
        if not ret:
            continue

        image = cv2.flip(image, 1)
        rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb)

        if results.multi_hand_landmarks:
            for hand_landmarks in results.multi_hand_landmarks:
                h, w, _ = image.shape
                pts = [(int(lm.x * w), int(lm.y * h)) for lm in hand_landmarks.landmark]
                x_min, y_min = min([p[0] for p in pts]), min([p[1] for p in pts])
                x_max, y_max = max([p[0] for p in pts]), max([p[1] for p in pts])

                roi = image[y_min:y_max, x_min:x_max]
                if roi.size > 0:
                    roi = cv2.resize(roi, (64, 64)).astype(float) / 255
                    pred = np.argmax(model.predict(np.expand_dims(roi, 0), verbose=0))
                    label = ClassList[pred]
                    cv2.putText(image, f"Prediction: {label}", (x_min, y_min - 10),
                                cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

        cv2.imshow("Gesture Recognition", image)

        if cv2.waitKey(5) & 0xFF in [27, ord('q')]:
            break

    cap.release()
    cv2.destroyAllWindows()


# --- SEND COMMAND TO ESP8266 ---
def send_command(command):
    esp8266_ip = "http://192.168.4.1"

    try:
        response = requests.get(f"{esp8266_ip}/{command}")
        print(f"ESP Response: {response.text}")
    except Exception as e:
        print(f"Error communicating with ESP8266: {e}")


# --- CLASSIFICATION + ESP8266 CONTROL ---
def KlasifikasiKeESP8266(ClassList, Camera=0, model_path="model.h5"):
    """Run classification and send control signals to ESP8266"""

    mp_hands = mp.solutions.hands
    hands = mp_hands.Hands()
    mp_drawing = mp.solutions.drawing_utils
    model = load_model(model_path)

    cap = cv2.VideoCapture(Camera, cv2.CAP_DSHOW)

    while cap.isOpened():
        ret, image = cap.read()
        if not ret:
            continue

        image = cv2.flip(image, 1)
        rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb)

        if results.multi_hand_landmarks:
            for hand_landmarks in results.multi_hand_landmarks:
                h, w, _ = image.shape
                pts = [(int(lm.x * w), int(lm.y * h)) for lm in hand_landmarks.landmark]
                x_min, y_min = min([p[0] for p in pts]), min([p[1] for p in pts])
                x_max, y_max = max([p[0] for p in pts]), max([p[1] for p in pts])

                roi = image[y_min:y_max, x_min:x_max]
                if roi.size > 0:
                    roi = cv2.resize(roi, (64, 64)).astype(float) / 255
                    pred = np.argmax(model.predict(np.expand_dims(roi, 0), verbose=0))
                    label = ClassList[pred]

                    if label == "Nyala":
                        send_command("A")
                    else:
                        send_command("B")

                    cv2.putText(image, f"Prediction: {label}", (x_min, y_min - 10),
                                cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

        cv2.imshow("ESP8266 Gesture Control", image)

        if cv2.waitKey(5) & 0xFF in [27, ord('q')]:
            break

    cap.release()
    cv2.destroyAllWindows()


# --- PYGAME MENU ---
def draw_text(text, font, color, surface, x, y):
    obj = font.render(text, True, color)
    surface.blit(obj, (x, y))


def menu():
    pygame.init()
    screen = pygame.display.set_mode((600, 400))
    pygame.display.set_caption("Classification Menu")

    font = pygame.font.Font(None, 36)

    while True:
        screen.fill((255, 255, 255))
        draw_text("Main Menu", font, (0, 0, 0), screen, 250, 20)
        draw_text("1. Create Dataset - Light ON", font, (0, 0, 0), screen, 150, 80)
        draw_text("2. Create Dataset - Light OFF", font, (0, 0, 0), screen, 150, 120)
        draw_text("3. Create Dataset - Others", font, (0, 0, 0), screen, 150, 160)
        draw_text("4. Train Model", font, (0, 0, 0), screen, 150, 200)
        draw_text("5. Classification", font, (0, 0, 0), screen, 150, 240)
        draw_text("6. ESP8266 Control Mode", font, (0, 0, 0), screen, 150, 280)
        draw_text("7. Exit", font, (0, 0, 0), screen, 150, 320)

        pygame.display.update()

        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                pygame.quit()
                sys.exit()

            if event.type == pygame.KEYDOWN:
                if event.key == pygame.K_1:
                    CreateData("Nyala")
                elif event.key == pygame.K_2:
                    CreateData("Mati")
                elif event.key == pygame.K_3:
                    CreateData("Nan")
                elif event.key == pygame.K_4:
                    X, y = LoadData(["Nyala", "Mati", "Nan"])
                    TrainingData(X, y)
                elif event.key == pygame.K_5:
                    Klasifikasi(["Nyala", "Mati", "Nan"])
                elif event.key == pygame.K_6:
                    KlasifikasiKeESP8266(["Nyala", "Mati", "Nan"])
                elif event.key == pygame.K_7:
                    pygame.quit()
                    sys.exit()


menu()
