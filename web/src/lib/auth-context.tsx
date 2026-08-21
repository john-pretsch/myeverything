"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import { apiFetch } from "./api";

export type User = {
  id: number;
  name: string;
  email: string;
  role: string;
  two_factor_enabled: boolean;
};

type AuthStatus = "loading" | "authenticated" | "unauthenticated";

type LoginResult =
  | { twoFactorRequired: false }
  | { twoFactorRequired: true; loginToken: string };

type AuthContextValue = {
  user: User | null;
  status: AuthStatus;
  login: (email: string, password: string) => Promise<LoginResult>;
  verifyTwoFactorLogin: (
    loginToken: string,
    input: { code?: string; recoveryCode?: string },
  ) => Promise<void>;
  register: (
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ) => Promise<void>;
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

  const login = useCallback(
    async (email: string, password: string): Promise<LoginResult> => {
      const res = await apiFetch<{ two_factor_required?: boolean; login_token?: string }>(
        "/api/login",
        { method: "POST", body: { email, password } },
      );

      if (res.two_factor_required && res.login_token) {
        return { twoFactorRequired: true, loginToken: res.login_token };
      }

      await refresh();
      return { twoFactorRequired: false };
    },
    [refresh],
  );

  const verifyTwoFactorLogin = useCallback(
    async (loginToken: string, input: { code?: string; recoveryCode?: string }) => {
      await apiFetch("/api/two-factor-challenge", {
        method: "POST",
        body: {
          login_token: loginToken,
          code: input.code,
          recovery_code: input.recoveryCode,
        },
      });
      await refresh();
    },
    [refresh],
  );

  const register = useCallback(
    async (
      name: string,
      email: string,
      password: string,
      passwordConfirmation: string,
    ) => {
      await apiFetch("/api/register", {
        method: "POST",
        body: {
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        },
      });
      await refresh();
    },
    [refresh],
  );

  const logout = useCallback(async () => {
    await apiFetch("/api/logout", { method: "POST" });
    setUser(null);
    setStatus("unauthenticated");
  }, []);

  return (
    <AuthContext.Provider
      value={{ user, status, login, verifyTwoFactorLogin, register, logout, refresh }}
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
