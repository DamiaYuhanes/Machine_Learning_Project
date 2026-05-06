# 🤖 Hand Gesture Recognition — ML Project

A real-time hand gesture recognition system built with Python, using **MediaPipe** for hand landmark detection and an **LSTM deep learning model** for gesture classification. The system is designed to control a **wheelchair** via hand gestures, making it a practical assistive technology application.

---

## ✨ Demo

> _Add a demo GIF or screenshot here — drag an image into GitHub_

---

## 🚀 Features

- 🖐️ Real-time hand gesture detection using a webcam
- 🧠 LSTM-based deep learning model for gesture classification
- 📸 Custom dataset creation pipeline from image capture
- ♿ Wheelchair control integration via ESP module
- 🗂️ Multi-class gesture support (Mati, Nan, Nyala, WheelChair)
- 📦 Pre-trained model weights included (`lstm_gesture_model.h5`)

---

## 🛠️ Tech stack

| Technology | Role |
|-----------|------|
| Python | Core language |
| MediaPipe | Hand landmark detection |
| TensorFlow / Keras | LSTM model training & inference |
| OpenCV | Webcam capture & image processing |
| ESP (via `ESP.py`) | Hardware/wheelchair control integration |
| NumPy | Data preprocessing |

---

## 📂 Project structure

```
Machine_Learning_Project/
├── ImageData/              ← Raw image dataset
├── datasetHands/           ← Processed hand landmark dataset
├── Mati/                   ← Gesture class: Mati
├── Nan/                    ← Gesture class: Nan
├── Nyala/                  ← Gesture class: Nyala
├── WheelChair/             ← Gesture class: WheelChair
│
├── create_dataset.py       ← Capture & build dataset from webcam
├── train_mediapipe.py      ← Train LSTM model on hand landmarks
├── test_mediapipe.py       ← Test model accuracy
├── main.py                 ← Main real-time gesture recognition
├── Mediapipe.py            ← MediaPipe hand detection logic
├── WheelChair.py           ← Wheelchair control logic
├── ESP.py                  ← ESP hardware communication
├── cam.py                  ← Camera utilities
│
├── lstm_gesture_model.h5   ← Trained LSTM model
├── model.h5                ← Alternative saved model
├── Weight.h5               ← Model weights
└── classes.txt             ← Gesture class labels
```

---

## ⚙️ How to run locally

### Prerequisites
```bash
pip install mediapipe tensorflow opencv-python numpy
```

### Steps

1. Clone the repo:
   ```bash
   git clone https://github.com/DamiaYuhanes/Machine_Learning_Project.git
   cd Machine_Learning_Project
   ```

2. (Optional) Collect your own gesture dataset:
   ```bash
   python create_dataset.py
   ```

3. (Optional) Retrain the model:
   ```bash
   python train_mediapipe.py
   ```

4. Run real-time gesture recognition:
   ```bash
   python main.py
   ```

> A webcam is required. The pre-trained model (`lstm_gesture_model.h5`) is included so you can run inference without retraining.

---

## 🧠 How it works

```
Webcam feed
    ↓
MediaPipe detects hand landmarks (21 keypoints)
    ↓
Landmark coordinates extracted as feature vectors
    ↓
LSTM model classifies the gesture sequence
    ↓
Predicted gesture → sends command to wheelchair / ESP module
```

---

## 💡 What I learned

- Building and training LSTM models for sequential gesture data
- Using MediaPipe for real-time hand tracking
- Dataset collection and preprocessing pipeline
- Integrating ML models with hardware (ESP/wheelchair control)
- End-to-end ML project from data collection to deployment

---

## 👩‍💻 Author

**Damia Yuhanes** — Software Engineering Student @ UniKL

[![GitHub](https://img.shields.io/badge/GitHub-DamiaYuhanes-181717?style=flat-square&logo=github)](https://github.com/DamiaYuhanes)

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
