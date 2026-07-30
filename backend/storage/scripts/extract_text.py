import sys
import fitz  # PyMuPDF

def extract_clean_text(pdf_path):
    try:
        doc = fitz.open(pdf_path)
        full_text = []

        for page in doc:
            # پارامتر sort=True ترتیب خواندن مقالات ۲ ستونه را درست رعایت می‌کند
            text = page.get_text("text", sort=True)
            if text.strip():
                full_text.append(text.strip())

        return "\n\n".join(full_text)
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.exit(1)

    file_path = sys.argv[1]
    extracted_text = extract_clean_text(file_path)
    print(extracted_text)
