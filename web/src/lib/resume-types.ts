export type Resume = {
  id: number;
  filename: string;
  mime_type: string;
  size: number;
  content: string | null;
  organization: string | null;
  gig_lead_id: number | null;
  is_primary: boolean;
  profile_image_url: string | null;
  view_url: string;
  uploaded_at: string;
};
