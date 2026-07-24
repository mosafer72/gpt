
export type DocumentStatus =
  | "uploaded"
  | "processing"
  | "completed"
  | "failed";


export interface DocumentMetadata {

  pages?: number;

  chunks?: number;

  words?: number;

}


export interface DocumentItem {

  id:number;

  workspace_id:number;

  user_id:number;

  name:string;

  path:string;

  mime_type:string;

  size:number;

  status:DocumentStatus;

  error?:string|null;


  metadata?:DocumentMetadata|null;


  created_at:string;

  updated_at:string;

}