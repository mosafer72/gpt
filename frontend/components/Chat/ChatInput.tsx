"use client";

import { FormEvent, RefObject, useRef } from "react";
import styles from "./ChatInput.module.css";

interface ChatInputProps {
  value: string;
  onChange: (val: string) => void;
  onSubmit: (e: FormEvent) => void;
  onImageUpload: (file: File) => void;
  onImageRemove: () => void;
  imageFile: File | null;
  disabled?: boolean;
  inputRef?: RefObject<HTMLTextAreaElement | null>;
}

export default function ChatInput({
  value,
  onChange,
  onSubmit,
  onImageUpload,
  onImageRemove,
  imageFile,
  disabled,
  inputRef,
}: ChatInputProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const compressed = await compressImage(file);
      onImageUpload(compressed);
    }
    e.target.value = "";
  };

  const compressImage = (file: File): Promise<File> => {
    return new Promise((resolve) => {
      const img = new Image();
      const reader = new FileReader();

      reader.onload = (e) => {
        img.src = e.target?.result as string;
      };

      img.onload = () => {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d")!;

        const maxWidth = 1280;
        const scale = Math.min(1, maxWidth / img.width);

        canvas.width = img.width * scale;
        canvas.height = img.height * scale;

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(
          (blob) => {
            if (!blob) return resolve(file);

            resolve(
              new File([blob], file.name.replace(/.(png|jpg|jpeg)$/i, ".jpg"), {
                type: "image/jpeg",
              }),
            );
          },
          "image/jpeg",
          0.8,
        );
      };

      reader.readAsDataURL(file);
    });
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      onSubmit(e);
    }
  };

  return (
    <div className={styles.inputArea}>
      {imageFile && (
        <div className={styles.preview}>
          <img src={URL.createObjectURL(imageFile)} alt="preview" />
          <button
            type="button"
            className={styles.removeBtn}
            onClick={onImageRemove}
          >
            ✕
          </button>
        </div>
      )}
      <form className={styles.form} onSubmit={onSubmit}>
        <button
          type="button"
          className={styles.uploadBtn}
          onClick={() => fileInputRef.current?.click()}
        >
          📎
        </button>
        <input
          type="file"
          ref={fileInputRef}
          accept="image/*"
          onChange={handleFileChange}
          hidden
        />
        <textarea
          ref={inputRef}
          className={styles.input}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={
            imageFile ? "توضیح عکس (اختیاری)..." : "پیام خود را بنویسید..."
          }
          rows={1}
          disabled={disabled}
          onKeyDown={handleKeyDown}
        />
        <button
          type="submit"
          className={styles.sendBtn}
          disabled={(!value.trim() && !imageFile) || disabled}
        >
          ➤
        </button>
      </form>
    </div>
  );
}
