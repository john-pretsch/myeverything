"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import { API_URL, apiFetch } from "./api";

export type User = {
  id: number;
  name: string;
  email: string;
  role: string;
};

type AuthStatus = "loading" | "authenticated" | "unauthenticated";

type AuthContextValue = {
  user: User | null;
  status: AuthStatus;
  login: () => void;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [status, setStatus] = useState<AuthStatus>("loading");

  const refresh = useCallback(async () => {
    try {
      const me = await apiFetch<User>("/api/user");
      setUser(me);
      setStatus("authenticated");
    } catch {
      setUser(null);
      setStatus("unauthenticated");
    }
  }, []);

  useEffect(() => {
    let ignore = false;

    apiFetch<User>("/api/user")
      .then((me) => {
        if (!ignore) {
          setUser(me);
          setStatus("authenticated");
        }
      })
      .catch(() => {
        if (!ignore) {
          setUser(null);
          setStatus("unauthenticated");
        }
      });

    return () => {
      ignore = true;
    };
  }, []);

  const login = useCallback(() => {
    window.location.href = `${API_URL}/api/auth/sso/redirect`;
  }, []);

  const logout = useCallback(async () => {
    const { redirect } = await apiFetch<{ redirect: string }>("/api/logout", { method: "POST" });
    setUser(null);
    setStatus("unauthenticated");
    window.location.href = redirect;
  }, []);

  return (
    <AuthContext.Provider
      value={{ user, status, login, logout, refresh }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return ctx;
}
