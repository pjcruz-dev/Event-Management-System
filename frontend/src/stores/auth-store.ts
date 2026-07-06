import { create } from "zustand";
import { persist } from "zustand/middleware";
import { apiClient, setAuthToken } from "@/lib/api-client";
import type {
  AuthOrganization,
  AuthTokenResponse,
  AuthUser,
  MeResponse,
} from "@/types/auth";

interface AuthState {
  user: AuthUser | null;
  organizations: AuthOrganization[];
  activeOrganizationId: number | null;
  token: string | null;
  isHydrated: boolean;
  isLoading: boolean;
  setHydrated: (value: boolean) => void;
  setAuth: (payload: AuthTokenResponse) => void;
  setMe: (payload: MeResponse) => void;
  setActiveOrganization: (organizationId: number | null) => void;
  fetchMe: () => Promise<void>;
  logout: () => Promise<void>;
  clear: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      organizations: [],
      activeOrganizationId: null,
      token: null,
      isHydrated: false,
      isLoading: false,

      setHydrated: (value) => set({ isHydrated: value }),

      setAuth: ({ user, token }) => {
        setAuthToken(token);
        set({ user, token });
      },

      setMe: ({ user, organizations }) => {
        const currentActive = get().activeOrganizationId;
        const activeOrganizationId =
          currentActive &&
          organizations.some((org) => org.id === currentActive)
            ? currentActive
            : (organizations[0]?.id ?? null);

        set({ user, organizations, activeOrganizationId });
      },

      setActiveOrganization: (organizationId) =>
        set({ activeOrganizationId: organizationId }),

      fetchMe: async () => {
        const token = get().token;
        if (!token) {
          set({ isHydrated: true });
          return;
        }

        set({ isLoading: true });
        setAuthToken(token);

        try {
          const me = await apiClient.get<MeResponse>("/me");
          get().setMe(me);
        } catch {
          get().clear();
        } finally {
          set({ isLoading: false, isHydrated: true });
        }
      },

      logout: async () => {
        const token = get().token;
        if (token) {
          setAuthToken(token);
          try {
            await apiClient.post("/auth/logout");
          } catch {
            // Token may already be invalid — still clear local state.
          }
        }
        get().clear();
      },

      clear: () => {
        setAuthToken(null);
        set({
          user: null,
          organizations: [],
          activeOrganizationId: null,
          token: null,
          isLoading: false,
        });
      },
    }),
    {
      name: "event-saas-auth",
      partialize: (state) => ({
        token: state.token,
        activeOrganizationId: state.activeOrganizationId,
      }),
      onRehydrateStorage: () => (state) => {
        if (state?.token) {
          setAuthToken(state.token);
        }
        state?.setHydrated(true);
      },
    },
  ),
);
