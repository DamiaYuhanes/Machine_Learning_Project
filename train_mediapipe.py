"""
Created on Sat Nov 22 08:17:57 2025
Author: Eko Mulyanto Yuniarno
Module: ImageClassificationCNNModule.py
"""
#Modul CNN
import os
import cv2
import numpy as np
from datetime import datetime
from numpy import expand_dims
from keras.models import Model, load_model
from keras.layers import Input, Dense, Conv2D, MaxPooling2D, Flatten
from keras.utils import load_img, img_to_array
from tensorflow.keras.preprocessing.image import ImageDataGenerator


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
# Classification
# --------------------------------------------------------
def classify_images(dataset_dir, target_folder, class_labels, model_cnn=None):

    if model_cnn is None:
        raise ValueError("ModelCNN must be provided.")

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
def load_saved_model(weight_file ="Weight.h5"):
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
                batch = next(iterator)  # FIXED HERE
                aug_image = batch[0].astype('uint8')

                counter += 1
                new_filename = datetime.now().strftime("%Y%m%d%H%M%S") + "_" + str(counter) + ".jpg"
                save_path = os.path.join(output_dir, new_filename)

                cv2.imwrite(save_path, cv2.cvtColor(aug_image, cv2.COLOR_RGB2BGR))


DatasetDir = "datasetHands"
class_labels = ["Left", "Right"]
train_cnn(10, DatasetDir, class_labels)