"use client";

import { useState } from "react";

import { Prism as SyntaxHighlighter } from "react-syntax-highlighter";
import { oneLight } from "react-syntax-highlighter/dist/esm/styles/prism";
import styles from "./CodeBlock.module.css";

interface Props {
  language?: string;
  children: string;
}

export default function CodeBlock({ language, children }: Props) {
  const [copied, setCopied] = useState(false);

  const copyCode = async () => {
    await navigator.clipboard.writeText(children);

    setCopied(true);

    setTimeout(() => {
      setCopied(false);
    }, 2000);
  };

  return (
    <div className={styles.codeWrapper}>
      <div className={styles.header}>
        <span className={styles.language}>{language || "text"}</span>

        <button className={styles.copyButton} onClick={copyCode}>
          {copied ? "✓ Copied" : "Copy"}
        </button>
      </div>

      <SyntaxHighlighter
        language={language || "text"}
        style={oneLight}
        PreTag="div"
        className={styles.code}
      >
        {children}
      </SyntaxHighlighter>
    </div>
  );
}
