"use client";

import { useMutation } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { ExhibitorGuard } from "@/components/exhibitor-portal/exhibitor-guard";
import { apiClient } from "@/lib/api-client";
import {
  exhibitorApiOptions,
  useExhibitorAuthStore,
} from "@/stores/exhibitor-auth-store";
import type { ExhibitorMaterial, ExhibitorRecord } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function ExhibitorProfilePage() {
  const token = useExhibitorAuthStore((s) => s.token);
  const exhibitor = useExhibitorAuthStore((s) => s.exhibitor);
  const setMe = useExhibitorAuthStore((s) => s.setMe);
  const options = exhibitorApiOptions(token);
  const [formError, setFormError] = useState<string | null>(null);
  const [description, setDescription] = useState(exhibitor?.description ?? "");
  const [websiteUrl, setWebsiteUrl] = useState(exhibitor?.website_url ?? "");
  const [materials, setMaterials] = useState<ExhibitorMaterial[]>(
    exhibitor?.materials ?? [],
  );
  const [materialName, setMaterialName] = useState("");
  const [materialUrl, setMaterialUrl] = useState("");

  useEffect(() => {
    if (!exhibitor) return;
    setDescription(exhibitor.description ?? "");
    setWebsiteUrl(exhibitor.website_url ?? "");
    setMaterials(exhibitor.materials ?? []);
  }, [exhibitor]);

  const saveMutation = useMutation({
    mutationFn: () =>
      apiClient.put<ExhibitorRecord>(
        "/exhibitor-portal/profile",
        {
          description: description || null,
          website_url: websiteUrl || null,
          materials,
        },
        options,
      ),
    onSuccess: (updated) => {
      setFormError(null);
      const contact = useExhibitorAuthStore.getState().contact;
      if (contact) {
        setMe({ contact, exhibitor: updated });
      }
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not save profile."),
  });

  return (
    <ExhibitorGuard>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold">Booth profile</h1>
          <p className="text-sm text-muted-foreground">
            Update your company description and downloadable materials.
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>{exhibitor?.name ?? "Your booth"}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {formError ? <FormErrorBanner message={formError} /> : null}
            <Textarea
              placeholder="Company description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
            <Input
              placeholder="Website URL"
              value={websiteUrl}
              onChange={(e) => setWebsiteUrl(e.target.value)}
            />

            <div className="space-y-2">
              <p className="text-sm font-medium">Materials</p>
              {materials.map((material, index) => (
                <div
                  key={`${material.url}-${index}`}
                  className="flex items-center justify-between gap-2 rounded-md border border-border p-2 text-sm"
                >
                  <span>
                    {material.name} — {material.url}
                  </span>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      setMaterials((list) => list.filter((_, i) => i !== index))
                    }
                  >
                    Remove
                  </Button>
                </div>
              ))}
              <div className="grid gap-2 sm:grid-cols-3">
                <Input
                  placeholder="Material name"
                  value={materialName}
                  onChange={(e) => setMaterialName(e.target.value)}
                />
                <Input
                  placeholder="Material URL"
                  value={materialUrl}
                  onChange={(e) => setMaterialUrl(e.target.value)}
                />
                <Button
                  type="button"
                  variant="secondary"
                  disabled={!materialName.trim() || !materialUrl.trim()}
                  onClick={() => {
                    setMaterials((list) => [
                      ...list,
                      { name: materialName.trim(), url: materialUrl.trim() },
                    ]);
                    setMaterialName("");
                    setMaterialUrl("");
                  }}
                >
                  Add material
                </Button>
              </div>
            </div>

            <Button
              type="button"
              disabled={saveMutation.isPending}
              onClick={() => saveMutation.mutate()}
            >
              Save profile
            </Button>
          </CardContent>
        </Card>
      </div>
    </ExhibitorGuard>
  );
}
