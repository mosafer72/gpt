"use client";

import { AIModel } from "@/types";
import styles from "./ModelSelector.module.css";

interface ModelSelectorProps {
  models: AIModel[];

  onSelect: (model: AIModel) => void;

  onClose: () => void;
}

export default function ModelSelector({
  models = [],

  onSelect,

  onClose,
}: ModelSelectorProps) {
  return (
    <div className={styles.overlay}>
      <div className={styles.modal}>
        {/* Header */}

        <div className={styles.header}>
          <div>
            <h2>انتخاب مدل هوش مصنوعی</h2>

            <p>مدل مناسب برای گفتگو را انتخاب کنید</p>
          </div>

          <button type="button" className={styles.close} onClick={onClose}>
            ✕
          </button>
        </div>

        {/* Models */}

        <div className={styles.models}>
          {models.length === 0 ? (
            <div className={styles.empty}>مدلی برای حساب شما فعال نیست</div>
          ) : (
            models.map((model) => (
              <button
                key={model.id}
                type="button"
                className={styles.modelCard}
                onClick={() => onSelect(model)}
              >
                <div className={styles.icon}>🤖</div>

                <div className={styles.info}>
                  <h3>{model.name}</h3>

                  <span>{String((model as any).provider || (model as any).slug || "")}</span>

                  {(model as any).description && <p>{(model as any).description}</p>}
                </div>

                <div className={styles.arrow}>›</div>
              </button>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
