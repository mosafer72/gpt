"use client";

import {
  FormEvent,
  RefObject,
  useRef,
  useMemo,
  useEffect,
  useState,
  useCallback,
} from "react";

import styles from "./ChatInput.module.css";

type ScrollBehavior = 'auto' | 'smooth';

interface ChatInputProps {
  value: string;
  onChange: (val: string) => void;
  onSubmit: (e: FormEvent) => void;
  onImageUpload: (file: File) => void;
  onImageRemove: () => void;
  imageFile: File | null;
  disabled?: boolean;
  isStreaming?: boolean;
  onCancel?: () => void;
  inputRef?: RefObject<HTMLTextAreaElement | null>;
  onScrollToBottom: (behavior?: ScrollBehavior) => void;
}

export default function ChatInput({
  value,
  onChange,
  onSubmit,
  onImageUpload,
  onImageRemove,
  imageFile,
  disabled,
  isStreaming = false,
  onCancel,
  inputRef,
  onScrollToBottom,
}: ChatInputProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);

  // ترکیب refهای داخلی و خارجی
  const setTextareaRef = useCallback(
    (element: HTMLTextAreaElement | null) => {
      textareaRef.current = element;
      if (inputRef) {
        // @ts-ignore - برای سازگاری با RefObject
        inputRef.current = element;
      }
    },
    [inputRef]
  );

  // تابع تنظیم ارتفاع خودکار
  const autoResize = useCallback(() => {
    const textarea = textareaRef.current;
    if (!textarea) return;

    // ریست کردن ارتفاع برای محاسبه صحیح scrollHeight
    textarea.style.height = 'auto';
    // تنظیم ارتفاع جدید با محدودیت 400px
    const newHeight = Math.min(textarea.scrollHeight, 400);
    textarea.style.height = `${newHeight}px`;
    textarea.style.maxHeight = '400px';
  }, []);

  // هر بار value تغییر کند، ارتفاع را تنظیم کن
  useEffect(() => {
    autoResize();
  }, [value, autoResize]);

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const compressed = await compressImage(file);
      onImageUpload(compressed);
    }
    e.target.value = "";
  };

  const [isComposing, setIsComposing] = useState(false);
  const previewUrl = useMemo(() => {
    return imageFile ? URL.createObjectURL(imageFile) : null;
  }, [imageFile]);

  useEffect(() => {
    return () => {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
    };
  }, [previewUrl]);

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
    if (e.key === "Enter" && !e.shiftKey && !isComposing) {
      e.preventDefault();
      onSubmit(e);
    }
  };

  const handleScrollDown = useCallback(() => {
    onScrollToBottom('smooth');
  }, [onScrollToBottom]);

  return (
    <div className={styles.inputArea}>
      <button
        type="button"
        className={styles.scrollDownBtn}
        onClick={handleScrollDown}
        aria-label="رفتن به انتهای گفتگو"
      >
        <span>↓</span>
        رفتن به آخر گفتگو
      </button>

      {imageFile && (
        <div className={styles.preview}>
          <img src={previewUrl ?? undefined} alt="preview" />
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
          ref={setTextareaRef}
          className={styles.input}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          onCompositionStart={() => setIsComposing(true)}
          onCompositionEnd={() => setIsComposing(false)}
          placeholder={
            imageFile ? "توضیح عکس (اختیاری)..." : "پیام خود را بنویسید..."
          }
          rows={1}
          disabled={disabled}
          onKeyDown={handleKeyDown}
          style={{
            resize: 'none',
            overflow: 'auto',
            maxHeight: '400px',
            minHeight: '44px',
          }}
        />
        {isStreaming ? (
          <button type="button" className={styles.cancelBtn} onClick={onCancel}>
            ■
          </button>
        ) : (
          <button
            type="submit"
            className={styles.sendBtn}
            disabled={(!value.trim() && !imageFile) || disabled}
          >
            ➤
          </button>
        )}
      </form>
    </div>
  );
}