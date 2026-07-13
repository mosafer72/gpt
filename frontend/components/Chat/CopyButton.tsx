"use client";

import { useState } from "react";
import { Copy, Check } from "lucide-react";

import styles from "./CopyButton.module.css";


export default function CopyButton({
  text
}: {
  text: string;
}) {

  const [copied, setCopied] = useState(false);


  const copy = async () => {

    await navigator.clipboard.writeText(text);

    setCopied(true);


    setTimeout(() => {

      setCopied(false);

    }, 2000);

  };


  return (

    <button
      className={styles.copyButton}
      onClick={copy}
      title="Copy"
    >

      {
        copied
        ?
        <Check size={15}/>
        :
        <Copy size={15}/>
      }

    </button>

  );

}