#Membuat dataset
import cv2
import mediapipe as mp
import numpy as np
import os
import time
from datetime import datetime


def CreateDataset(DirPath, Kelas):
    # Pastikan direktori tujuan ada
    save_dir = os.path.join(DirPath, Kelas)
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

    cap = cv2.VideoCapture(0)

    # State untuk countdown, capture, dan finish
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

        # Convert frame color
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = hands.process(rgb_frame)

        # Create empty black background with the same size as frame
        black_canvas = np.zeros_like(frame)

        # Canvas untuk crop tangan kiri & kanan (sudah di-resize 128x128)
        left_hand_crop = None
        right_hand_crop = None

        if results.multi_hand_landmarks:
            for handedness, hand_landmarks in zip(results.multi_handedness,
                                                  results.multi_hand_landmarks):

                # Gambar landmark di frame asli
                mp_draw.draw_landmarks(
                    frame,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # Gambar landmark di background hitam ukuran frame
                mp_draw.draw_landmarks(
                    black_canvas,
                    hand_landmarks,
                    mp_hands.HAND_CONNECTIONS
                )

                # ----------------- CROP TANGAN PER-HAND -----------------
                label = handedness.classification[0].label  # 'Left' atau 'Right'

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

                # Crop dari black_canvas (tangan di atas background hitam)
                hand_region = black_canvas[start_y:end_y, start_x:end_x]

                hand_h, hand_w = hand_region.shape[:2]
                square_side = max(hand_h, hand_w)
                hand_canvas = np.zeros((square_side, square_side, 3), dtype=np.uint8)

                y_off = (square_side - hand_h) // 2
                x_off = (square_side - hand_w) // 2
                hand_canvas[y_off:y_off + hand_h, x_off:x_off + hand_w] = hand_region

                # Resize ke 128 x 128
                resized_hand = cv2.resize(hand_canvas, (128, 128), interpolation=cv2.INTER_AREA)

                if label == "Left":
                    left_hand_crop = resized_hand
                elif label == "Right":
                    right_hand_crop = resized_hand
                # ----------------- END CROP -----------------

        # Tampilkan jendela lama
        cv2.imshow("Original Video with Landmarks", frame)



        # -------------- GABUNG KIRI-KANAN MENJADI 256x256 --------------
        combined_256 = None

        if (left_hand_crop is not None) or (right_hand_crop is not None):
            # base 128 x 256 hitam
            combined_128x256 = np.zeros((128, 256, 3), dtype=np.uint8)

            # Jika tidak ada tangan kiri/kanan, tetap hitam di sisi tersebut
            if left_hand_crop is not None:
                combined_128x256[0:128, 0:128] = left_hand_crop
            if right_hand_crop is not None:
                combined_128x256[0:128, 128:256] = right_hand_crop

            # Sekarang buat canvas 256 x 256, isi tengahnya dengan combined_128x256
            combined_256 = np.zeros((256, 256, 3), dtype=np.uint8)
            y_start = (256 - 128) // 2  # 64
            combined_256[y_start:y_start + 128, 0:256] = combined_128x256


        # -------------------------------------------------

        # ===================== LOGIKA COUNTDOWN / CAPTURE / FINISH =====================
        now = time.time()

        if phase == "countdown":
            elapsed = now - countdown_start
            remain = countdown_seconds - int(elapsed)

            if remain <= 0:
                phase = "capture"
                last_save_time = now
            else:
                # Tampilkan teks countdown di frame
                text = f"Starting in {remain}"
                cv2.putText(frame, text, (30, 50),
                            cv2.FONT_HERSHEY_SIMPLEX, 1.2, (0, 0, 255), 3)
                cv2.imshow("Original Video with Landmarks", frame)

        elif phase == "capture":
            # Tampilkan info jumlah citra tersimpan
            cv2.putText(frame, f"Capturing... {saved_count}/20", (30, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 0), 2)
            cv2.imshow("Original Video with Landmarks", frame)

            # Simpan combined_256 setiap 0.5 detik
            if combined_256 is not None:
                if (last_save_time is None) or ((now - last_save_time) >= 0.5):
                    # Format nama file: KelasYYMMDDhhmmssms.jpg
                    dt = datetime.now()
                    ms = int(dt.microsecond / 1000)
                    filename = f"{Kelas}{dt.strftime('%y%m%d%H%M%S')}{ms:03d}.jpg"
                    filepath = os.path.join(save_dir, filename)

                    cv2.imwrite(filepath, combined_256)
                    saved_count += 1
                    last_save_time = now
                    print("Saved:", filepath)

            # Cek apakah sudah 20 citra
            if saved_count >= 20:
                phase = "finish"
                finish_start_time = now

        elif phase == "finish":
            # Tampilkan tulisan FINISH
            cv2.putText(frame, "FINISH", (80, 200),
                        cv2.FONT_HERSHEY_SIMPLEX, 2.0, (0, 255, 255), 4)
            cv2.imshow("Original Video with Landmarks", frame)

            if now - finish_start_time >= 2.0:
                # Setelah 2 detik, keluar loop
                break
        # ============================================================================

        # Exit manual dengan ESC (tanpa diubah: tetap waitKey(1))
        if cv2.waitKey(1) & 0xFF == 27:
            break

    cap.release()
    cv2.destroyAllWindows()
    hands.close()




# CreateDataset("datasetHands", "Right")

CreateDataset("datasetHands", "Left")
