"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Copy, Trash2 } from "lucide-react";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import {
  REGISTRATION_FIELD_TYPES,
  normalizeRegistrationFields,
  slugifyFieldKey,
  validateRegistrationFields,
} from "@/lib/registration-form-utils";
import type { RegistrationField, RegistrationFormRecord } from "@/types/ticketing";
import { ApiError } from "@/types/api";

function createDefaultField(index: number): RegistrationField {
  return {
    key: `field_${index}`,
    type: "text",
    label: "New field",
    required: false,
    options: [],
  };
}

function RegistrationFieldPreview({ field }: { field: RegistrationField }) {
  const id = `preview-${field.key}`;

  switch (field.type) {
    case "textarea":
      return <Textarea id={id} disabled placeholder="Long answer" />;
    case "select":
      return (
        <select
          id={id}
          disabled
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        >
          <option value="">Select an option</option>
          {field.options.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      );
    case "checkbox":
      return (
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" disabled />
          {field.label}
        </label>
      );
    case "email":
      return <Input id={id} type="email" disabled placeholder="name@example.com" />;
    case "phone":
      return <Input id={id} type="tel" disabled placeholder="+1 (555) 000-0000" />;
    default:
      return <Input id={id} disabled placeholder="Short answer" />;
  }
}

export default function EventFormBuilderPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [fields, setFields] = useState<RegistrationField[]>([]);
  const [formError, setFormError] = useState<string | null>(null);
  const [keysManuallyEdited, setKeysManuallyEdited] = useState<Set<number>>(new Set());

  const formQuery = useQuery({
    queryKey: ["registration-form", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<RegistrationFormRecord>(`/events/${eventId}/registration-form`, orgOptions),
  });

  useEffect(() => {
    if (formQuery.data?.fields) {
      setFields(formQuery.data.fields);
      const allEdited = new Set<number>();
      formQuery.data.fields.forEach((_, i) => allEdited.add(i));
      setKeysManuallyEdited(allEdited);
    }
  }, [formQuery.data]);

  const updateField = (index: number, patch: Partial<RegistrationField>) => {
    setFields((current) =>
      current.map((field, i) => (i === index ? { ...field, ...patch } : field)),
    );
  };

  const saveMutation = useMutation({
    mutationFn: () => {
      const normalized = normalizeRegistrationFields(fields);
      const validation = validateRegistrationFields(normalized);
      if (!validation.valid) {
        throw new Error(validation.message);
      }
      return apiClient.put<RegistrationFormRecord>(
        `/events/${eventId}/registration-form`,
        { fields: normalized },
        orgOptions,
      );
    },
    onSuccess: (data) => {
      setFields(data.fields);
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["registration-form", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : error.message || "Could not save form."),
  });

  const addField = () => {
    setFields((current) => [...current, createDefaultField(current.length + 1)]);
  };

  const duplicateField = (index: number) => {
    const source = fields[index];
    const copy: RegistrationField = {
      ...source,
      key: `${source.key}_copy`,
      label: `${source.label} (copy)`,
      options: [...source.options],
    };
    const next = [...fields];
    next.splice(index + 1, 0, copy);
    setFields(next);
  };

  const removeField = (index: number) => {
    setFields((current) => current.filter((_, i) => i !== index));
  };

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold">Registration form builder</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Customize attendee questions shown during checkout.
          </p>
        </div>
        <Button asChild variant="outline">
          <Link href={`/events/${eventId}/edit`}>Back to event</Link>
        </Button>
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      <div className="grid gap-6 xl:grid-cols-2">
        <Card>
          <CardHeader className="flex flex-row items-start justify-between gap-4">
            <div>
              <CardTitle>Fields</CardTitle>
              <CardDescription>Drag order with Up/Down. Keys must be unique snake_case identifiers.</CardDescription>
            </div>
            <Button type="button" variant="outline" size="sm" onClick={addField}>
              Add field
            </Button>
          </CardHeader>
          <CardContent className="space-y-4">
            {fields.length === 0 ? (
              <p className="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                No custom fields yet. Add fields for dietary preferences, company name, t-shirt size, and more.
              </p>
            ) : null}

            {fields.map((field, index) => (
              <div key={`${field.key}-${index}`} className="space-y-3 rounded-lg border border-border p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="text-sm font-medium">Field {index + 1}</p>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={() => duplicateField(index)}>
                      <Copy className="mr-1 h-3.5 w-3.5" aria-hidden />
                      Duplicate
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => removeField(index)}
                      disabled={fields.length === 1}
                    >
                      <Trash2 className="mr-1 h-3.5 w-3.5" aria-hidden />
                      Remove
                    </Button>
                  </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor={`field-label-${index}`}>Label</Label>
                    <Input
                      id={`field-label-${index}`}
                      value={field.label}
                      onChange={(e) => {
                        const label = e.target.value;
                        const patch: Partial<RegistrationField> = { label };
                        if (!keysManuallyEdited.has(index)) {
                          patch.key = slugifyFieldKey(label, index + 1);
                        }
                        updateField(index, patch);
                      }}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor={`field-key-${index}`}>Field key</Label>
                    <Input
                      id={`field-key-${index}`}
                      value={field.key}
                      onChange={(e) => {
                        setKeysManuallyEdited((prev) => new Set(prev).add(index));
                        updateField(index, { key: e.target.value.toLowerCase().replace(/\s+/g, "_") });
                      }}
                      placeholder="company_name"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor={`field-type-${index}`}>Field type</Label>
                  <select
                    id={`field-type-${index}`}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={field.type}
                    onChange={(e) => {
                      const type = e.target.value as RegistrationField["type"];
                      updateField(index, {
                        type,
                        options: type === "select" ? field.options.length ? field.options : ["Option 1", "Option 2"] : [],
                      });
                    }}
                  >
                    {REGISTRATION_FIELD_TYPES.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                  <p className="text-xs text-muted-foreground">
                    {REGISTRATION_FIELD_TYPES.find((option) => option.value === field.type)?.description}
                  </p>
                </div>

                {field.type === "select" ? (
                  <div className="space-y-2">
                    <Label htmlFor={`field-options-${index}`}>Options (one per line)</Label>
                    <Textarea
                      id={`field-options-${index}`}
                      rows={4}
                      value={field.options.join("\n")}
                      onChange={(e) =>
                        updateField(index, {
                          options: e.target.value.split("\n"),
                        })
                      }
                      placeholder={"Vegetarian\nVegan\nGluten-free"}
                    />
                  </div>
                ) : null}

                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={field.required}
                    onChange={(e) => updateField(index, { required: e.target.checked })}
                  />
                  Required field
                </label>

                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={index === 0}
                    onClick={() => {
                      const next = [...fields];
                      [next[index - 1], next[index]] = [next[index], next[index - 1]];
                      setFields(next);
                    }}
                  >
                    Move up
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={index === fields.length - 1}
                    onClick={() => {
                      const next = [...fields];
                      [next[index + 1], next[index]] = [next[index], next[index + 1]];
                      setFields(next);
                    }}
                  >
                    Move down
                  </Button>
                </div>
              </div>
            ))}

            <Button type="button" onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending}>
              {saveMutation.isPending ? "Saving…" : "Save form"}
            </Button>
          </CardContent>
        </Card>

        <Card className="h-fit xl:sticky xl:top-24">
          <CardHeader>
            <CardTitle>Live preview</CardTitle>
            <CardDescription>How custom fields appear on the registration page.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="rounded-md border border-border bg-muted/20 p-4">
              <p className="text-sm font-medium">Attendee details</p>
              <p className="mt-1 text-xs text-muted-foreground">
                Standard name and email fields are always included.
              </p>
            </div>
            {fields.map((field) => (
              <div key={field.key}>
                {field.type !== "checkbox" ? (
                  <Label htmlFor={`preview-${field.key}`} className="mb-2 block">
                    {field.label}
                    {field.required ? <span className="text-destructive"> *</span> : null}
                  </Label>
                ) : null}
                <RegistrationFieldPreview field={field} />
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
