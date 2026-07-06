import { useAuthStore } from "@/stores/auth-store";
import { apiClient, apiUpload } from "@/lib/api-client";

export function useOrganizationId(): number | null {
  return useAuthStore((s) => s.activeOrganizationId);
}

export function orgRequestOptions(organizationId: number | null) {
  return organizationId ? { organizationId } : {};
}

export async function uploadAvatar(file: File) {
  const formData = new FormData();
  formData.append("avatar", file);
  return apiUpload("/profile/avatar", formData);
}

export { apiClient };
