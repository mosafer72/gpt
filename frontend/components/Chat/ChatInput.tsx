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

type ScrollBehavior = "auto" | "smooth";

interface ChatInputProps {
  value: string;
  onChange: (val: string) => void;
  onSubmit: (e: FormEvent) => void;
  onImageUpload: (file: File) => void;
  onImageRemove: () => void;
  imageFile: File | null;

  onFileUpload: (file: File) => void;
  onFileRemove: () => void;
  attachedFile: File | null;

  disabled?: boolean;
  isStreaming?: boolean;
  onCancel?: () => void;
  inputRef?: RefObject<HTMLTextAreaElement | null>;
  onScrollToBottom: (behavior?: ScrollBehavior) => void;
  
  // جدید: درصد پیشرفت آپلود (بین 0 تا 100)؛ اگر null یا undefined باشد یعنی آپلودی در جریان نیست
  uploadProgress?: number | null;
}

export default function ChatInput({
  value,
  onChange,
  onSubmit,
  onImageUpload,
  onImageRemove,
  imageFile,
  onFileUpload,
  onFileRemove,
  attachedFile,
  disabled,
  isStreaming = false,
  onCancel,
  inputRef,
  onScrollToBottom,
  uploadProgress = null,
}: ChatInputProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);

  const setTextareaRef = useCallback(
    (element: HTMLTextAreaElement | null) => {
      textareaRef.current = element;
      if (inputRef) {
        // @ts-ignore
        inputRef.current = element;
      }
    },
    [inputRef]
  );

  const autoResize = useCallback(() => {
    const textarea = textareaRef.current;
    if (!textarea) return;

    textarea.style.height = "auto";
    const newHeight = Math.min(textarea.scrollHeight, 400);
    textarea.style.height = `${newHeight}px`;
  }, []);

  useEffect(() => {
    autoResize();
  }, [value, autoResize]);

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
              new File([blob], file.name.replace(/\.(png|jpg|jpeg)$/i, ".jpg"), {
                type: "image/jpeg",
              })
            );
          },
          "image/jpeg",
          0.8
        );
      };

      reader.readAsDataURL(file);
    });
  };

  const handleFileChange = async (
    e: React.ChangeEvent<HTMLInputElement>
  ) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.type.startsWith("image/")) {
      const compressed = await compressImage(file);
      onImageUpload(compressed);
    } else {
      onFileUpload(file);
    }

    e.target.value = "";
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey && !isComposing) {
      e.preventDefault();
      onSubmit(e);
    }
  };

  return (
    <div className={styles.inputArea}>
      {/* 📊 نوار پیشرفت آپلود فایل/تصویر */}
      {uploadProgress !== null && uploadProgress !== undefined && (
        <div style={{ width: "100%", marginBottom: "8px" }}>
          <div style={{ display: "flex", justifyContent: "space-between", fontSize: "12px", marginBottom: "4px", color: "var(--text-secondary, #888)" }}>
            <span>در حال آپلود فایل...</span>
            <span>{Math.round(uploadProgress)}%</span>
          </div>
          <div style={{ width: "100%", height: "4px", backgroundColor: "rgba(255,255,255,0.1)", borderRadius: "2px", overflow: "hidden" }}>
            <div 
              style={{ 
                width: `${uploadProgress}%`, 
                height: "100%", 
                backgroundColor: "var(--primary-color, #3b82f6)", 
                transition: "width 0.2s ease" 
              }} 
            />
          </div>
        </div>
      )}

      {imageFile && (
        <div className={styles.preview}>
          <img src={previewUrl ?? undefined} alt="preview" />
          <button type="button" className={styles.removeBtn} onClick={onImageRemove}>
            ✕
          </button>
        </div>
      )}

      {attachedFile && (
        <div className={styles.filePreview}>
          <span>📄 {attachedFile.name}</span>
          <button type="button" onClick={onFileRemove}>✕</button>
        </div>
      )}

      <form className={styles.form} onSubmit={onSubmit}>
        <button
          type="button"
          className={styles.uploadBtn}
          onClick={() => fileInputRef.current?.click()}
          title="آپلود فایل یا تصویر"
        >
          📎
        </button>

        {/* بدون محدودیت accept تا کاربر بتواند هر فایلی را انتخاب کند */}
        <input
          type="file"
          ref={fileInputRef}
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
          placeholder="پیام خود را بنویسید..."
          rows={1}
          disabled={disabled}
          onKeyDown={handleKeyDown}
          style={{ resize: "none", overflow: "auto", maxHeight: "400px" }}
        />

        {isStreaming ? (
          <button type="button" className={styles.cancelBtn} onClick={onCancel}>
            ■
          </button>
        ) : (
          <button
            type="submit"
            className={styles.sendBtn}
            disabled={(!value.trim() && !imageFile && !attachedFile) || disabled}
          >
            ➤
          </button>
        )}
      </form>
    </div>
  );
}