# Realtime Classification
import cv2
import mediapipe as mp
import numpy as np
import os
import time
from datetime import datetime
from tensorflow.keras.models import load_model  # if the model is not loaded externally


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

    cap = cv2.VideoCapture(0)

    while True:
        success, frame = cap.read()
        if not success:
            break

        # Convert frame format: OpenCV BGR → MediaPipe RGB
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb_frame)

        # Create an empty black canvas matching the frame size
        black_canvas = np.zeros_like(frame)

        # Crop buffers for left and right hands (resized to 128x128 later)
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

            # Assign class label
            class_label = class_names[class_id] if class_id < len(class_names) else f"Class {class_id}"

            # Display label + probability
            text = f"{class_label}: {prob:.2f}"
            cv2.putText(frame, text, (30, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 0), 2)

            # Optional: show the processed model input image
            cv2.imshow("Model Input 128x128", img_128)

        # Main display
        cv2.imshow("Original Video with Landmarks", frame)

        # Exit program using ESC key
        if cv2.waitKey(1) & 0xFF == 27:
            break

    cap.release()
    cv2.destroyAllWindows()
    hands.close()


# ----------------- EXAMPLE USAGE -----------------
if __name__ == "__main__":
    # Load trained model
    model_path = "Weight.h5"
    model = load_model(model_path)

    # Order of class names must match CNN output order
    class_names = ["Right", "Left"]

    RealtimeClassification(model, class_names)
