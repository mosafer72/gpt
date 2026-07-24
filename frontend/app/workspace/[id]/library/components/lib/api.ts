const API_BASE = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

// ============================
// Auth
// ============================

function getToken() {
  if (typeof window === "undefined") {
    return null;
  }

  return localStorage.getItem("auth_token");
}

// ============================
// Documents
// ============================
export async function getDocuments(workspaceId: string) {
  const token = getToken();

  const response = await fetch(
    `${process.env.NEXT_PUBLIC_API_URL}/workspaces/${workspaceId}/documents`,
    {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    },
  );

  if (!response.ok) {
    throw new Error("Failed to load documents");
  }

  const json = await response.json();
  console.log("dsffsddfs", json);
  return json.documents ?? [];
}

export async function getDocument(workspaceId: string, documentId: string) {
  const token = getToken();

  const response = await fetch(
    `${process.env.NEXT_PUBLIC_API_URL}/workspaces/${workspaceId}/documents/${documentId}`,
    {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    },
  );

  if (!response.ok) {
    throw new Error("Failed to load document");
  }

  const json = await response.json();

  console.log("DOCUMENT DETAIL:", json);

  return json;
}

export function formatPersianDate(date: string) {
  return new Intl.DateTimeFormat("fa-IR", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(new Date(date));
}
