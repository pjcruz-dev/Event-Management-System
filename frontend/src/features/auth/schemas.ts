import { z } from "zod";

export const loginSchema = z.object({
  email: z.string().email("Enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

export const registerSchema = z
  .object({
    name: z.string().min(2, "Name must be at least 2 characters"),
    email: z.string().email("Enter a valid email address"),
    password: z
      .string()
      .min(8, "Password must be at least 8 characters")
      .regex(/[A-Z]/, "Include at least one uppercase letter")
      .regex(/[0-9]/, "Include at least one number"),
    password_confirmation: z.string(),
    invitation_token: z.string().optional(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });

export const forgotPasswordSchema = z.object({
  email: z.string().email("Enter a valid email address"),
});

export const resetPasswordSchema = z
  .object({
    email: z.string().email(),
    token: z.string().min(1),
    password: z
      .string()
      .min(8, "Password must be at least 8 characters")
      .regex(/[A-Z]/, "Include at least one uppercase letter")
      .regex(/[0-9]/, "Include at least one number"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });

export const profileSchema = z.object({
  name: z.string().min(2),
  email: z.string().email(),
  phone: z.string().optional(),
  timezone: z.string().min(1),
  locale: z.string().min(2),
});

export const organizationSchema = z.object({
  name: z.string().min(2, "Organization name is required"),
  slug: z.string().optional(),
});

export const inviteSchema = z.object({
  email: z.string().email("Enter a valid email address"),
  role: z.string().min(1, "Select a role"),
});

export type LoginFormValues = z.infer<typeof loginSchema>;
export type RegisterFormValues = z.infer<typeof registerSchema>;
export type ProfileFormValues = z.infer<typeof profileSchema>;
export type OrganizationFormValues = z.infer<typeof organizationSchema>;
export type InviteFormValues = z.infer<typeof inviteSchema>;
export type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>;
