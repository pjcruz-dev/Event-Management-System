"use client";

import Image from "next/image";
import { CalendarDays, MapPin, Ticket } from "lucide-react";
import type { EventSessionRecord, ExhibitorRecord, SpeakerRecord, SponsorRecord } from "@/types/conference";
import type { EventRecord, LandingBlock } from "@/types/event";
import type { PublicEventPayload, TicketTypeRecord } from "@/types/ticketing";
import { EventLandingProvider, type EventLandingMode } from "@/components/public/event-landing-context";
import { EventLandingAnchor, EventLandingLink } from "@/components/public/event-landing-link";
import {
  getCardClassName,
  getCtaHeadingClassName,
  getCtaSectionClassName,
  getCtaSectionStyle,
  getDefaultCtaSubtitleClassName,
  getFaqItemClassName,
  getHeroHeadingClassName,
  getHeroImageFrameClassName,
  getHeroInnerPaddingClassName,
  getHeroPrimaryButtonClassName,
  getHeroSectionClassName,
  getHeroSectionStyle,
  getLayoutRootClassName,
  getLayoutVariantDataAttribute,
  getPrimaryButtonClassName,
  getSectionHeadingClassName,
  getSectionShellClassName,
  resolveHeroHeadline,
  showHeroPattern,
} from "@/lib/event-layout-variant";
import { getEventThemeFontClassName } from "@/lib/event-theme-fonts";
import {
  blockSettingBool,
  blockSettingNumber,
  blockSettingString,
  isLandingBlockVisible,
  resolveTicketsConfig,
  shouldShowTicketsSection,
  visibleLandingBlocks,
} from "@/lib/landing-block-utils";
import { parseFaqItems } from "@/lib/public-event-seo";
import {
  formatEventDateRange,
  formatSessionTime,
  formatTierLabel,
  formatTicketPrice,
} from "@/lib/public-event-format";
import { EventActionsBar } from "@/components/public/event-actions-bar";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";

const SPONSOR_TIER_ORDER = ["platinum", "gold", "silver", "bronze", "partner"] as const;

interface PublicEventLandingProps {
  payload: PublicEventPayload;
  speakers?: SpeakerRecord[];
  sponsors?: SponsorRecord[];
  exhibitors?: ExhibitorRecord[];
  agendaSessions?: EventSessionRecord[];
  invitationToken?: string | null;
  mode?: EventLandingMode;
}

function registerHref(slug: string, invitationToken?: string | null): string {
  const base = `/e/${slug}/register`;
  if (!invitationToken) return base;
  return `${base}?invitation_token=${encodeURIComponent(invitationToken)}`;
}

function showRegisterCta(event: EventRecord, invitationToken?: string | null): boolean {
  if (event.registration_mode === "open") return true;
  return Boolean(invitationToken);
}

export function PublicEventLanding({
  payload,
  speakers = [],
  sponsors = [],
  exhibitors = [],
  agendaSessions = [],
  invitationToken = null,
  mode = "public",
}: PublicEventLandingProps) {
  const { event, ticket_types: tickets } = payload;
  const theme = event.theme_config;
  const layoutVariant = theme.layout_variant ?? "classic";
  const allBlocks = event.landing_page_config?.blocks ?? [];
  const blocks = visibleLandingBlocks(allBlocks);
  const ticketsConfig = resolveTicketsConfig(event.landing_page_config);
  const showTickets = shouldShowTicketsSection(ticketsConfig);
  const dateLabel = formatEventDateRange(event.starts_at, event.ends_at, event.timezone);
  const hasCtaBlock = blocks.some((block) => block.type === "cta");
  const canRegister = showRegisterCta(event, invitationToken);

  return (
    <EventLandingProvider mode={mode} layoutVariant={layoutVariant}>
      <div
        className={cn(
          "@container min-h-screen bg-background",
          getLayoutRootClassName(layoutVariant),
          getEventThemeFontClassName(theme.font),
        )}
        {...getLayoutVariantDataAttribute(layoutVariant)}
        style={{
          ["--event-primary" as string]: theme.primary_color,
          ["--event-secondary" as string]: theme.secondary_color,
        }}
      >
        {blocks.map((block, index) => (
          <LandingBlockView
            key={`${block.type}-${index}`}
            block={block}
            event={event}
            slug={event.slug}
            themePrimary={theme.primary_color}
            speakers={speakers}
            sponsors={sponsors}
            exhibitors={exhibitors}
            agendaSessions={agendaSessions}
            sectionIndex={index}
            dateLabel={dateLabel}
            invitationToken={invitationToken}
            canRegister={canRegister}
            layoutVariant={layoutVariant}
          />
        ))}

        {mode === "public" && (
          <div className="flex justify-center border-b border-border px-6 py-4">
            <EventActionsBar event={event} />
          </div>
        )}

        {showTickets ? (
          <TicketsSection
            event={event}
            tickets={tickets}
            title={ticketsConfig.title}
            themePrimary={theme.primary_color}
            muted={blocks.length % 2 === 0}
            invitationToken={invitationToken}
            canRegister={canRegister}
            layoutVariant={layoutVariant}
          />
        ) : null}

        {!hasCtaBlock && canRegister ? (
          <section
            className={getCtaSectionClassName(layoutVariant)}
            style={getCtaSectionStyle(layoutVariant)}
          >
            <div className="mx-auto max-w-2xl space-y-4">
              <h2 className={getCtaHeadingClassName(layoutVariant)}>Ready to join?</h2>
              <p className={getDefaultCtaSubtitleClassName(layoutVariant)}>
                Secure your spot at {event.name}
                {dateLabel ? ` on ${dateLabel.split("·")[0]?.trim()}` : ""}.
              </p>
              <EventLandingLink
                href={registerHref(event.slug, invitationToken)}
                size="lg"
                className={getPrimaryButtonClassName(layoutVariant)}
              >
                Register now
              </EventLandingLink>
            </div>
          </section>
        ) : null}

        <footer className="border-t border-border bg-muted/20 px-6 py-8">
          <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">{event.name}</p>
            <div className="flex flex-wrap gap-3">
              {agendaSessions.length > 0 ? (
                <EventLandingLink
                  href={`/e/${event.slug}/agenda`}
                  variant="outline"
                  size="sm"
                >
                  Full agenda
                </EventLandingLink>
              ) : null}
              <EventLandingLink href="/discover" variant="outline" size="sm">
                Discover events
              </EventLandingLink>
            </div>
          </div>
        </footer>
      </div>
    </EventLandingProvider>
  );
}

function SectionShell({
  children,
  muted = false,
  layoutVariant,
  className = "",
}: {
  children: React.ReactNode;
  muted?: boolean;
  layoutVariant: EventRecord["theme_config"]["layout_variant"];
  className?: string;
}) {
  return (
    <section className={cn(getSectionShellClassName(layoutVariant, muted), className)}>
      <div className="mx-auto max-w-5xl">{children}</div>
    </section>
  );
}

function SectionHeading({
  title,
  description,
  layoutVariant,
}: {
  title: string;
  description?: string;
  layoutVariant: EventRecord["theme_config"]["layout_variant"];
}) {
  return (
    <div className="mb-8 max-w-2xl">
      <h2 className={getSectionHeadingClassName(layoutVariant)}>{title}</h2>
      {description ? (
        <p className="mt-2 text-muted-foreground leading-relaxed">{description}</p>
      ) : null}
    </div>
  );
}

function LandingBlockView({
  block,
  event,
  slug,
  themePrimary,
  speakers,
  sponsors,
  exhibitors,
  agendaSessions,
  sectionIndex,
  dateLabel,
  invitationToken,
  canRegister,
  layoutVariant,
}: {
  block: LandingBlock;
  event: EventRecord;
  slug: string;
  themePrimary: string;
  speakers: SpeakerRecord[];
  sponsors: SponsorRecord[];
  exhibitors: ExhibitorRecord[];
  agendaSessions: EventSessionRecord[];
  sectionIndex: number;
  dateLabel: string | null;
  invitationToken?: string | null;
  canRegister: boolean;
  layoutVariant: EventRecord["theme_config"]["layout_variant"];
}) {
  const settings = block.settings;
  const muted = sectionIndex % 2 === 1;
  const cardClassName = getCardClassName(layoutVariant);

  if (!isLandingBlockVisible(block)) {
    return null;
  }

  switch (block.type) {
    case "hero":
      return (
        <section
          className={getHeroSectionClassName(layoutVariant)}
          style={getHeroSectionStyle(layoutVariant)}
        >
          {showHeroPattern(layoutVariant) ? (
            <div
              className="pointer-events-none absolute inset-0 opacity-[0.07]"
              style={{
                backgroundImage:
                  "radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 80%, white 1px, transparent 1px)",
                backgroundSize: "48px 48px",
              }}
              aria-hidden
            />
          ) : null}
          <div
            className={cn(
              "relative mx-auto grid max-w-6xl gap-10 px-6 @md:grid-cols-2 @md:items-center",
              getHeroInnerPaddingClassName(layoutVariant),
            )}
          >
            <div className="space-y-6">
              {event.category ? (
                <span
                  className={cn(
                    "inline-block px-3 py-1 text-xs font-medium uppercase tracking-wider",
                    layoutVariant === "minimal"
                      ? "rounded-sm border border-white/30"
                      : "rounded-full bg-white/15 backdrop-blur-sm",
                  )}
                >
                  {event.category.replace("-", " ")}
                </span>
              ) : null}
              <h1 className={getHeroHeadingClassName(layoutVariant)}>
                {resolveHeroHeadline(block, event)}
              </h1>
              <p
                className={cn(
                  "max-w-xl @md:text-xl",
                  layoutVariant === "minimal" ? "text-lg text-white/95" : "text-lg text-white/90",
                )}
              >
                {blockSettingString(settings, "subheadline") || event.description}
              </p>
              <div className="flex flex-wrap gap-4 text-sm text-white/85">
                {dateLabel ? (
                  <span className="inline-flex items-center gap-2">
                    <CalendarDays className="h-4 w-4 shrink-0" aria-hidden />
                    {dateLabel}
                  </span>
                ) : null}
                {event.venue ? (
                  <span className="inline-flex items-center gap-2">
                    <MapPin className="h-4 w-4 shrink-0" aria-hidden />
                    {event.venue}
                  </span>
                ) : null}
              </div>
              <div className="flex flex-wrap gap-3 pt-2">
                {canRegister && blockSettingBool(settings, "show_register_button", true) ? (
                  <EventLandingLink
                    href={registerHref(slug, invitationToken)}
                    size="lg"
                    className={getHeroPrimaryButtonClassName(layoutVariant)}
                  >
                    Register
                  </EventLandingLink>
                ) : null}
                {agendaSessions.length > 0 ? (
                  <EventLandingLink
                    href={`/e/${slug}/agenda`}
                    size="lg"
                    variant="outline"
                    className="border-white/40 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                  >
                    View agenda
                  </EventLandingLink>
                ) : null}
              </div>
            </div>
            {event.theme_config.hero_background_type === "video" &&
            event.theme_config.hero_video_url ? (
              <>
                <video
                  src={event.theme_config.hero_video_url}
                  autoPlay
                  muted
                  loop
                  playsInline
                  className="absolute inset-0 h-full w-full object-cover"
                  aria-hidden
                />
                <div className="absolute inset-0 bg-black/50" aria-hidden />
              </>
            ) : event.theme_config.hero_image_url ? (
              <div className={getHeroImageFrameClassName(layoutVariant)}>
                <Image
                  src={event.theme_config.hero_image_url}
                  alt=""
                  fill
                  className="object-cover"
                  priority
                  sizes="(max-width: 768px) 100vw, 50vw"
                />
              </div>
            ) : (
              <div
                className={cn(
                  "hidden @md:block",
                  getHeroImageFrameClassName(layoutVariant),
                  layoutVariant === "minimal" ? "bg-white/10" : "bg-white/10",
                )}
              />
            )}
          </div>
        </section>
      );
    case "about": {
      const body = blockSettingString(settings, "body").trim() || event.description?.trim();
      if (!body) return null;
      const isHtml = /<[^>]+>/.test(body);
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading title="About this event" layoutVariant={layoutVariant} />
          <div className="prose prose-neutral dark:prose-invert max-w-3xl">
            {isHtml ? (
              <div
                className="text-base leading-relaxed text-muted-foreground @md:text-lg [&_a]:text-primary [&_a]:underline"
                dangerouslySetInnerHTML={{ __html: body }}
              />
            ) : (
              <p className="whitespace-pre-wrap text-base leading-relaxed text-muted-foreground @md:text-lg">
                {body}
              </p>
            )}
          </div>
        </SectionShell>
      );
    }
    case "image": {
      const imageUrl = blockSettingString(settings, "image_url");
      const caption = blockSettingString(settings, "caption");
      const altText = blockSettingString(settings, "alt_text") || caption || "Event image";
      if (!imageUrl) return null;
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <div className="mx-auto max-w-4xl">
            <div className="relative overflow-hidden rounded-lg">
              <Image
                src={imageUrl}
                alt={altText}
                width={1200}
                height={675}
                className="h-auto w-full object-cover"
                sizes="(max-width: 768px) 100vw, 800px"
              />
            </div>
            {caption && (
              <p className="mt-3 text-center text-sm text-muted-foreground">
                {caption}
              </p>
            )}
          </div>
        </SectionShell>
      );
    }
    case "faq": {
      const items = parseFaqItems(settings);
      if (items.length === 0) return null;
      const faqTitle = blockSettingString(settings, "title", "Frequently asked questions");
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading title={faqTitle} layoutVariant={layoutVariant} />
          <div className="grid max-w-3xl gap-3">
            {items.map((item) => (
              <details key={item.question} className={getFaqItemClassName(layoutVariant)}>
                <summary className="cursor-pointer list-none font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                  <span className="flex items-center justify-between gap-4">
                    {item.question}
                    <span className="text-muted-foreground transition group-open:rotate-45">+</span>
                  </span>
                </summary>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">{item.answer}</p>
              </details>
            ))}
          </div>
        </SectionShell>
      );
    }
    case "cta": {
      const ctaHeadline =
        blockSettingString(settings, "headline") || "Ready to join?";
      const ctaSubheadline =
        blockSettingString(settings, "subheadline") || `Don't miss ${event.name}.`;
      const ctaButtonLabel = blockSettingString(settings, "button_label", "Register now");
      return (
        <section
          className={getCtaSectionClassName(layoutVariant)}
          style={getCtaSectionStyle(layoutVariant)}
        >
          <div className="mx-auto max-w-2xl space-y-5">
            <h2 className={getCtaHeadingClassName(layoutVariant)}>{ctaHeadline}</h2>
            <p className={getDefaultCtaSubtitleClassName(layoutVariant)}>{ctaSubheadline}</p>
            {canRegister ? (
              <EventLandingLink
                href={registerHref(slug, invitationToken)}
                size="lg"
                className={getPrimaryButtonClassName(layoutVariant)}
              >
                {ctaButtonLabel}
              </EventLandingLink>
            ) : (
              <p
                className={cn(
                  "text-sm",
                  layoutVariant === "minimal" ? "text-muted-foreground" : "text-white/80",
                )}
              >
                Registration is by invitation only.
              </p>
            )}
          </div>
        </section>
      );
    }
    case "agenda-preview": {
      const limit = blockSettingNumber(settings, "limit", 4);
      const previewSessions = agendaSessions.slice(0, limit);
      const agendaTitle = blockSettingString(settings, "title", "Conference agenda");
      const agendaSubtitle = blockSettingString(
        settings,
        "subtitle",
        "Explore sessions, keynotes, and workshops. Reserve your seat for limited-capacity sessions on the full agenda.",
      );
      const showViewAll = blockSettingBool(settings, "show_view_all_link", true);
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading
            title={agendaTitle}
            description={agendaSubtitle || undefined}
            layoutVariant={layoutVariant}
          />
          {previewSessions.length === 0 ? (
            <p className="text-muted-foreground">The schedule will be published soon.</p>
          ) : (
            <div className="grid gap-3">
              {previewSessions.map((session) => (
                <div
                  key={session.id}
                  className={cn(
                    cardClassName,
                    "flex flex-col gap-2 p-5 @sm:flex-row @sm:items-center @sm:justify-between",
                  )}
                >
                  <div>
                    <p className="font-semibold">{session.title}</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {session.track?.name ? `${session.track.name} · ` : ""}
                      {formatSessionTime(session.starts_at, session.ends_at)}
                      {session.room ? ` · ${session.room}` : ""}
                    </p>
                  </div>
                  {session.speakers && session.speakers.length > 0 ? (
                    <p className="text-sm text-muted-foreground">
                      {session.speakers.map((s) => s.name).join(", ")}
                    </p>
                  ) : null}
                </div>
              ))}
            </div>
          )}
          {showViewAll ? (
            <EventLandingLink href={`/e/${slug}/agenda`} className="mt-6" variant="outline">
              View full agenda
            </EventLandingLink>
          ) : null}
        </SectionShell>
      );
    }
    case "speakers-preview": {
      const speakerLimit = blockSettingNumber(settings, "limit", 6);
      const speakerLayout = blockSettingString(settings, "layout", "grid");
      const previewSpeakers = speakers.slice(0, speakerLimit);
      const speakersTitle = blockSettingString(settings, "title", "Featured speakers");
      const speakersSubtitle = blockSettingString(
        settings,
        "subtitle",
        "Meet the experts leading sessions throughout the event.",
      );
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading
            title={speakersTitle}
            description={speakersSubtitle || undefined}
            layoutVariant={layoutVariant}
          />
          {previewSpeakers.length === 0 ? (
            <p className="text-muted-foreground">Speaker lineup coming soon.</p>
          ) : (
            <div
              className={cn(
                speakerLayout === "list" ? "flex flex-col gap-4" : "grid gap-5 @sm:grid-cols-2 @lg:grid-cols-3",
              )}
            >
              {previewSpeakers.map((speaker) => (
                <Card
                  key={speaker.id}
                  className={cn(
                    cardClassName,
                    "overflow-hidden",
                    speakerLayout === "list" && "@sm:flex-row",
                  )}
                >
                  <CardContent
                    className={cn(
                      "p-0",
                      speakerLayout === "list"
                        ? "flex w-full flex-col items-center gap-4 px-6 py-5 @sm:flex-row @sm:items-start @sm:text-left"
                        : "",
                    )}
                  >
                    <div
                      className={cn(
                        speakerLayout === "list"
                          ? "flex shrink-0 flex-col items-center @sm:items-start"
                          : "flex flex-col items-center px-6 pb-6 pt-8 text-center",
                      )}
                    >
                      {speaker.photo_url ? (
                        <Image
                          src={speaker.photo_url}
                          alt=""
                          width={96}
                          height={96}
                          className="h-24 w-24 rounded-full object-cover ring-4 ring-background shadow-md"
                        />
                      ) : (
                        <div
                          className="flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold text-white shadow-md ring-4 ring-background"
                          style={{ backgroundColor: themePrimary }}
                          aria-hidden
                        >
                          {speaker.name.charAt(0)}
                        </div>
                      )}
                      <p className="mt-4 text-lg font-semibold">{speaker.name}</p>
                      {speaker.title ? (
                        <p className="mt-1 text-sm font-medium text-primary">{speaker.title}</p>
                      ) : null}
                    </div>
                    {speaker.bio ? (
                      <p
                        className={cn(
                          "text-sm leading-relaxed text-muted-foreground",
                          speakerLayout === "list"
                            ? "line-clamp-3 flex-1 px-6 pb-5 @sm:px-0 @sm:pb-0"
                            : "mt-3 line-clamp-4 px-6 pb-6",
                        )}
                      >
                        {speaker.bio}
                      </p>
                    ) : null}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </SectionShell>
      );
    }
    case "sponsors": {
      const sponsorsTitle = blockSettingString(settings, "title", "Our sponsors");
      const sponsorsSubtitle = blockSettingString(
        settings,
        "subtitle",
        "Thank you to the partners making this event possible.",
      );
      const groupByTier = blockSettingBool(settings, "group_by_tier", true);
      const grouped = groupByTier ? groupSponsorsByTier(sponsors) : [["all", sponsors] as [string, SponsorRecord[]]];
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading
            title={sponsorsTitle}
            description={sponsorsSubtitle || undefined}
            layoutVariant={layoutVariant}
          />
          {sponsors.length === 0 ? (
            <p className="text-muted-foreground">Sponsor announcements coming soon.</p>
          ) : groupByTier ? (
            <div className="space-y-10">
              {grouped.map(([tier, tierSponsors]) => (
                <div key={tier}>
                  <p className="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                    {formatTierLabel(tier)}
                  </p>
                  <div
                    className={`grid gap-4 ${
                      tier === "platinum"
                        ? "@sm:grid-cols-2"
                        : "@sm:grid-cols-2 @lg:grid-cols-3 @xl:grid-cols-4"
                    }`}
                  >
                    {tierSponsors.map((sponsor) => (
                      <SponsorCard
                        key={sponsor.id}
                        sponsor={sponsor}
                        featured={tier === "platinum"}
                        cardClassName={cardClassName}
                      />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="grid gap-4 @sm:grid-cols-2 @lg:grid-cols-3 @xl:grid-cols-4">
              {sponsors.map((sponsor) => (
                <SponsorCard
                  key={sponsor.id}
                  sponsor={sponsor}
                  featured={false}
                  cardClassName={cardClassName}
                />
              ))}
            </div>
          )}
        </SectionShell>
      );
    }
    case "exhibitors": {
      const exhibitorLimit = blockSettingNumber(settings, "limit", 12);
      const exhibitorLayout = blockSettingString(settings, "layout", "grid");
      const previewExhibitors = exhibitors.slice(0, exhibitorLimit);
      const exhibitorsTitle = blockSettingString(settings, "title", "Exhibitors");
      const exhibitorsSubtitle = blockSettingString(
        settings,
        "subtitle",
        "Meet the organizations showcasing products and services at this event.",
      );
      return (
        <SectionShell muted={muted} layoutVariant={layoutVariant}>
          <SectionHeading
            title={exhibitorsTitle}
            description={exhibitorsSubtitle || undefined}
            layoutVariant={layoutVariant}
          />
          {previewExhibitors.length === 0 ? (
            <p className="text-muted-foreground">Exhibitor listings coming soon.</p>
          ) : (
            <div
              className={cn(
                exhibitorLayout === "list"
                  ? "flex flex-col gap-4"
                  : "grid gap-4 @sm:grid-cols-2 @lg:grid-cols-3 @xl:grid-cols-4",
              )}
            >
              {previewExhibitors.map((exhibitor) => (
                <ExhibitorCard
                  key={exhibitor.id}
                  exhibitor={exhibitor}
                  layout={exhibitorLayout}
                  cardClassName={cardClassName}
                />
              ))}
            </div>
          )}
        </SectionShell>
      );
    }
  }
}

function SponsorCard({
  sponsor,
  featured,
  cardClassName,
}: {
  sponsor: SponsorRecord;
  featured: boolean;
  cardClassName: string;
}) {
  const content = (
    <Card
      className={cn(
        cardClassName,
        "flex h-full items-center justify-center transition hover:shadow-md",
        featured ? "min-h-[120px] p-8" : "min-h-[100px] p-6",
      )}
    >
      <CardContent className="flex flex-col items-center justify-center p-0 text-center">
        {sponsor.logo_url ? (
          <Image
            src={sponsor.logo_url}
            alt={sponsor.name}
            width={featured ? 200 : 140}
            height={featured ? 80 : 56}
            className={`w-auto max-w-full object-contain ${featured ? "h-16" : "h-12"}`}
          />
        ) : (
          <p className={`font-semibold ${featured ? "text-xl" : "text-base"}`}>{sponsor.name}</p>
        )}
      </CardContent>
    </Card>
  );

  if (sponsor.website_url) {
    return (
      <EventLandingAnchor
        href={sponsor.website_url}
        className="block transition-opacity hover:opacity-90"
      >
        {content}
      </EventLandingAnchor>
    );
  }

  return content;
}

function ExhibitorCard({
  exhibitor,
  layout,
  cardClassName,
}: {
  exhibitor: ExhibitorRecord;
  layout: string;
  cardClassName: string;
}) {
  const logoUrl = exhibitor.logo_url ?? exhibitor.logo_path;
  const content = (
    <Card
      className={cn(
        cardClassName,
        "flex h-full transition hover:shadow-md",
        layout === "list" ? "flex-row items-center gap-4 p-5" : "items-center justify-center p-6",
      )}
    >
      <CardContent
        className={cn(
          "flex p-0",
          layout === "list"
            ? "w-full flex-row items-center gap-4 text-left"
            : "flex-col items-center justify-center text-center",
        )}
      >
        {logoUrl ? (
          <Image
            src={logoUrl}
            alt={exhibitor.name}
            width={layout === "list" ? 80 : 140}
            height={layout === "list" ? 48 : 56}
            className={cn(
              "w-auto max-w-full shrink-0 object-contain",
              layout === "list" ? "h-12" : "h-14",
            )}
          />
        ) : (
          <div
            className={cn(
              "flex shrink-0 items-center justify-center rounded-md bg-muted font-semibold",
              layout === "list" ? "h-12 w-12 text-sm" : "h-14 w-14 text-base",
            )}
            aria-hidden
          >
            {exhibitor.name.charAt(0)}
          </div>
        )}
        <div className={layout === "list" ? "min-w-0 flex-1" : "mt-3"}>
          <p className="font-semibold">{exhibitor.name}</p>
          {exhibitor.description ? (
            <p
              className={cn(
                "mt-1 text-sm text-muted-foreground",
                layout === "list" ? "line-clamp-2" : "line-clamp-3",
              )}
            >
              {exhibitor.description}
            </p>
          ) : null}
        </div>
      </CardContent>
    </Card>
  );

  if (exhibitor.website_url) {
    return (
      <EventLandingAnchor
        href={exhibitor.website_url}
        className="block transition-opacity hover:opacity-90"
      >
        {content}
      </EventLandingAnchor>
    );
  }

  return content;
}

function TicketsSection({
  event,
  tickets,
  title,
  themePrimary,
  muted,
  invitationToken,
  canRegister,
  layoutVariant,
}: {
  event: EventRecord;
  tickets: TicketTypeRecord[];
  title: string;
  themePrimary: string;
  muted: boolean;
  invitationToken?: string | null;
  canRegister: boolean;
  layoutVariant: EventRecord["theme_config"]["layout_variant"];
}) {
  const onSaleTickets = tickets.filter((ticket) => ticket.is_on_sale);
  const cardClassName = getCardClassName(layoutVariant);

  return (
    <SectionShell muted={muted} layoutVariant={layoutVariant}>
      <SectionHeading
        title={title}
        description={
          onSaleTickets.length > 0
            ? "Choose the pass that fits you and complete registration in minutes."
            : "Ticket types are being finalized. Check back soon or contact the organizer."
        }
        layoutVariant={layoutVariant}
      />
      {onSaleTickets.length === 0 ? (
        <div
          className={cn(
            cardClassName,
            "border-dashed bg-card/50 p-8 text-center",
          )}
        >
          <Ticket className="mx-auto h-10 w-10 text-muted-foreground" aria-hidden />
          <p className="mt-3 text-muted-foreground">
            {canRegister ? "Registration opens soon." : "Registration is by invitation only."}
          </p>
          {canRegister ? (
            <EventLandingLink
              href={registerHref(event.slug, invitationToken)}
              className="mt-4"
              variant="outline"
            >
              Go to registration
            </EventLandingLink>
          ) : null}
        </div>
      ) : (
        <div className="grid min-w-0 grid-cols-1 gap-4 @md:grid-cols-2 @lg:grid-cols-3">
          {onSaleTickets.map((ticket) => (
            <Card
              key={ticket.id}
              className={cn(cardClassName, "flex min-w-0 flex-col transition hover:shadow-md")}
            >
              <CardContent className="flex flex-1 flex-col p-6">
                <p className="text-lg font-semibold">{ticket.name}</p>
                {ticket.description ? (
                  <p className="mt-2 flex-1 text-sm leading-relaxed text-muted-foreground">
                    {ticket.description}
                  </p>
                ) : (
                  <div className="flex-1" />
                )}
                <div className="mt-6 flex flex-col gap-3 @sm:flex-row @sm:items-end @sm:justify-between">
                  <p className="text-2xl font-bold" style={{ color: themePrimary }}>
                    {formatTicketPrice(ticket.price, ticket.currency)}
                  </p>
                  <EventLandingLink
                    href={`${registerHref(event.slug, invitationToken)}?ticket=${ticket.id}`}
                    size="sm"
                    className="w-full shrink-0 @sm:w-auto"
                    style={{ backgroundColor: themePrimary }}
                  >
                    Select
                  </EventLandingLink>
                </div>
                {ticket.remaining_quantity !== null ? (
                  <p className="mt-2 text-xs text-muted-foreground">
                    {ticket.remaining_quantity} remaining
                  </p>
                ) : null}
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </SectionShell>
  );
}

function groupSponsorsByTier(sponsors: SponsorRecord[]): Array<[string, SponsorRecord[]]> {
  const map = new Map<string, SponsorRecord[]>();
  for (const sponsor of sponsors) {
    const list = map.get(sponsor.tier) ?? [];
    list.push(sponsor);
    map.set(sponsor.tier, list);
  }

  const ordered: Array<[string, SponsorRecord[]]> = [];
  for (const tier of SPONSOR_TIER_ORDER) {
    const list = map.get(tier);
    if (list?.length) ordered.push([tier, list]);
  }
  for (const [tier, list] of map) {
    if (!SPONSOR_TIER_ORDER.includes(tier as (typeof SPONSOR_TIER_ORDER)[number])) {
      ordered.push([tier, list]);
    }
  }
  return ordered;
}
