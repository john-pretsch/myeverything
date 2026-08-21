export type GigLeadCountry = "usa" | "canada";

export type GigLeadJobType =
  | "full_time"
  | "part_time"
  | "short_term_contract"
  | "long_term_contract";

export type GigLeadOrigin = "linkedin" | "arc" | "indeed" | "gunio" | "other";

export type GigLeadStatus = "new" | "reviewed" | "dismissed";

export type GigLead = {
  id: number;
  url: string;
  country: GigLeadCountry;
  job_type: GigLeadJobType;
  origin: GigLeadOrigin;
  status: GigLeadStatus;
  title: string | null;
  company: string | null;
  description: string | null;
  added_at: string;
};

export type GigLeadInput = {
  url: string;
  country: GigLeadCountry;
  job_type: GigLeadJobType;
  origin: GigLeadOrigin;
};

export type GigLeadDetailsInput = {
  title: string;
  company: string;
  description: string;
};
