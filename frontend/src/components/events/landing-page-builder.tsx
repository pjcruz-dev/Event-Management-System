"use client";

import {
  DndContext,
  KeyboardSensor,
  PointerSensor,
  closestCenter,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  SortableContext,
  arrayMove,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { Copy, Eye, EyeOff, GripVertical } from "lucide-react";
import { AboutRichTextEditor } from "@/components/events/about-rich-text-editor";
import { LANDING_BLOCK_LABELS } from "@/features/events/constants";
import type { LandingPageConfigFormValues } from "@/features/events/schemas";
import {
  blockSettingBool,
  blockSettingNumber,
  blockSettingString,
  defaultBlockForType,
  resolveTicketsConfig,
  updateBlockSetting,
} from "@/lib/landing-block-utils";
import type { LandingBlockType } from "@/types/event";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";

const BLOCK_TYPES = Object.keys(LANDING_BLOCK_LABELS) as LandingBlockType[];

interface LandingPageBuilderProps {
  value: LandingPageConfigFormValues;
  onChange: (value: LandingPageConfigFormValues) => void;
}

function blockSortId(index: number): string {
  return `landing-block-${index}`;
}

function parseBlockSortId(id: string | number): number {
  return Number(String(id).replace("landing-block-", ""));
}

export function LandingPageBuilder({ value, onChange }: LandingPageBuilderProps) {
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );

  const tickets = resolveTicketsConfig(value);
  const sortableIds = value.blocks.map((_, index) => blockSortId(index));

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const oldIndex = parseBlockSortId(active.id);
    const newIndex = parseBlockSortId(over.id);
    if (Number.isNaN(oldIndex) || Number.isNaN(newIndex)) return;

    onChange({ ...value, blocks: arrayMove(value.blocks, oldIndex, newIndex) });
  };

  const updateBlock = (
    index: number,
    patch: Partial<LandingPageConfigFormValues["blocks"][number]>,
  ) => {
    const blocks = value.blocks.map((block, i) =>
      i === index ? { ...block, ...patch } : block,
    );
    onChange({ ...value, blocks });
  };

  const updateSetting = (index: number, key: string, settingValue: string | boolean) => {
    const block = value.blocks[index];
    updateBlock(index, {
      settings: updateBlockSetting(block.settings, key, settingValue),
    });
  };

  const addBlock = (type: LandingBlockType) => {
    onChange({
      ...value,
      blocks: [...value.blocks, defaultBlockForType(type)],
    });
  };

  const duplicateBlock = (index: number) => {
    const source = value.blocks[index];
    const copy = {
      ...source,
      settings: { ...source.settings },
    };
    const blocks = [...value.blocks];
    blocks.splice(index + 1, 0, copy);
    onChange({ ...value, blocks });
  };

  const removeBlock = (index: number) => {
    onChange({ ...value, blocks: value.blocks.filter((_, i) => i !== index) });
  };

  const updateTickets = (patch: Partial<typeof tickets>) => {
    onChange({
      ...value,
      tickets: {
        ...tickets,
        ...patch,
      },
    });
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Drag blocks by the handle to reorder your public landing page.
      </p>
      <div className="flex flex-wrap gap-2">
        {BLOCK_TYPES.map((type) => (
          <Button key={type} type="button" variant="outline" size="sm" onClick={() => addBlock(type)}>
            Add {LANDING_BLOCK_LABELS[type]}
          </Button>
        ))}
      </div>

      <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
        <SortableContext items={sortableIds} strategy={verticalListSortingStrategy}>
          <div className="space-y-3">
            {value.blocks.map((block, index) => (
              <SortableBlockCard
                key={blockSortId(index)}
                id={blockSortId(index)}
                block={block}
                index={index}
                onUpdateBlock={updateBlock}
                onUpdateSetting={updateSetting}
                onDuplicate={() => duplicateBlock(index)}
                onRemove={() => removeBlock(index)}
              />
            ))}
          </div>
        </SortableContext>
      </DndContext>

      <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
        <p className="font-medium">Tickets section</p>
        <p className="mt-1 text-sm text-muted-foreground">
          Control the ticket list shown after your landing blocks on the public page.
        </p>
        <div className="mt-4 space-y-3">
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={tickets.visible}
              onChange={(e) => updateTickets({ visible: e.target.checked })}
            />
            Show tickets section
          </label>
          <div>
            <Label htmlFor="tickets-title">Section title</Label>
            <Input
              id="tickets-title"
              value={tickets.title}
              disabled={!tickets.visible}
              onChange={(e) => updateTickets({ title: e.target.value })}
            />
          </div>
        </div>
      </div>
    </div>
  );
}

function SortableBlockCard({
  id,
  block,
  index,
  onUpdateBlock,
  onUpdateSetting,
  onDuplicate,
  onRemove,
}: {
  id: string;
  block: LandingPageConfigFormValues["blocks"][number];
  index: number;
  onUpdateBlock: (
    index: number,
    patch: Partial<LandingPageConfigFormValues["blocks"][number]>,
  ) => void;
  onUpdateSetting: (index: number, key: string, value: string | boolean) => void;
  onDuplicate: () => void;
  onRemove: () => void;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  const isVisible = block.visible !== false;

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        "rounded-lg border border-border bg-card p-4 shadow-sm",
        isDragging && "z-10 opacity-90 ring-2 ring-primary/30",
        !isVisible && "opacity-70",
      )}
    >
      <div className="mb-3 flex items-start gap-3">
        <button
          type="button"
          className="mt-0.5 flex h-9 w-9 shrink-0 cursor-grab items-center justify-center rounded-md border border-border text-muted-foreground hover:bg-accent hover:text-foreground active:cursor-grabbing"
          aria-label={`Drag to reorder ${LANDING_BLOCK_LABELS[block.type]}`}
          {...attributes}
          {...listeners}
        >
          <GripVertical className="h-4 w-4" aria-hidden />
        </button>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <p className="font-medium">{LANDING_BLOCK_LABELS[block.type]}</p>
              {!isVisible ? (
                <span className="text-xs text-muted-foreground">(hidden)</span>
              ) : null}
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                title={isVisible ? "Hide block on public page" : "Show block on public page"}
                onClick={() => onUpdateBlock(index, { visible: !isVisible })}
              >
                {isVisible ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                <span className="sr-only">{isVisible ? "Hide block" : "Show block"}</span>
              </Button>
              <Button type="button" variant="outline" size="sm" onClick={onDuplicate}>
                <Copy className="mr-1 h-4 w-4" aria-hidden />
                Duplicate
              </Button>
              <Button type="button" variant="outline" size="sm" onClick={onRemove}>
                Remove
              </Button>
            </div>
          </div>

          <div className="mt-3 space-y-3">
            <BlockSettingsFields
              block={block}
              index={index}
              onUpdateBlock={onUpdateBlock}
              onUpdateSetting={onUpdateSetting}
            />
          </div>
        </div>
      </div>
    </div>
  );
}

function BlockSettingsFields({
  block,
  index,
  onUpdateBlock,
  onUpdateSetting,
}: {
  block: LandingPageConfigFormValues["blocks"][number];
  index: number;
  onUpdateBlock: (
    index: number,
    patch: Partial<LandingPageConfigFormValues["blocks"][number]>,
  ) => void;
  onUpdateSetting: (index: number, key: string, value: string | boolean) => void;
}) {
  const settings = block.settings;

  switch (block.type) {
    case "hero":
      return (
        <div className="space-y-3">
          <div>
            <Label>Headline</Label>
            <Input
              value={blockSettingString(settings, "headline")}
              onChange={(e) => onUpdateSetting(index, "headline", e.target.value)}
            />
          </div>
          <div>
            <Label>Subheadline</Label>
            <Input
              value={blockSettingString(settings, "subheadline")}
              onChange={(e) => onUpdateSetting(index, "subheadline", e.target.value)}
            />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={blockSettingBool(settings, "show_register_button", true)}
              onChange={(e) => onUpdateSetting(index, "show_register_button", e.target.checked)}
            />
            Show register button in hero
          </label>
        </div>
      );
    case "about":
      return (
        <AboutRichTextEditor
          value={blockSettingString(settings, "body")}
          onChange={(html) => onUpdateSetting(index, "body", html)}
        />
      );
    case "image":
      return (
        <div className="space-y-3">
          <div>
            <Label>Image URL</Label>
            <Input
              value={blockSettingString(settings, "image_url")}
              onChange={(e) => onUpdateSetting(index, "image_url", e.target.value)}
              placeholder="https://example.com/image.jpg or upload via Theme tab"
            />
            <p className="mt-1 text-xs text-muted-foreground">
              Paste an image URL. Use the Theme tab to upload images and copy the URL.
            </p>
          </div>
          <div>
            <Label>Caption (optional)</Label>
            <Input
              value={blockSettingString(settings, "caption")}
              onChange={(e) => onUpdateSetting(index, "caption", e.target.value)}
              placeholder="Photo of the venue"
            />
          </div>
          <div>
            <Label>Alt text (for accessibility)</Label>
            <Input
              value={blockSettingString(settings, "alt_text")}
              onChange={(e) => onUpdateSetting(index, "alt_text", e.target.value)}
              placeholder="Conference main hall with seating"
            />
          </div>
        </div>
      );
    case "cta":
      return (
        <div className="space-y-3">
          <div>
            <Label>Headline</Label>
            <Input
              value={blockSettingString(settings, "headline")}
              onChange={(e) => onUpdateSetting(index, "headline", e.target.value)}
            />
          </div>
          <div>
            <Label>Subheadline</Label>
            <Input
              value={blockSettingString(settings, "subheadline")}
              onChange={(e) => onUpdateSetting(index, "subheadline", e.target.value)}
            />
          </div>
          <div>
            <Label>Button label</Label>
            <Input
              value={blockSettingString(settings, "button_label", "Register now")}
              onChange={(e) => onUpdateSetting(index, "button_label", e.target.value)}
            />
          </div>
        </div>
      );
    case "faq":
      return (
        <div className="space-y-3">
          <div>
            <Label>Section title</Label>
            <Input
              value={blockSettingString(settings, "title", "Frequently asked questions")}
              onChange={(e) => onUpdateSetting(index, "title", e.target.value)}
            />
          </div>
          <FaqBlockEditor
            settings={settings}
            onReplaceSettings={(nextSettings) =>
              onUpdateBlock(index, { settings: nextSettings })
            }
          />
        </div>
      );
    case "agenda-preview":
      return (
        <DynamicBlockFields
          settings={settings}
          index={index}
          onUpdateSetting={onUpdateSetting}
          defaultTitle="Conference agenda"
          limitMin={1}
          limitMax={20}
          limitDefault={4}
          showLayout={false}
          extraFields={
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={blockSettingBool(settings, "show_view_all_link", true)}
                onChange={(e) => onUpdateSetting(index, "show_view_all_link", e.target.checked)}
              />
              Show view-all agenda link
            </label>
          }
          hint="Sessions are pulled from the Agenda page (published sessions only)."
        />
      );
    case "speakers-preview":
      return (
        <DynamicBlockFields
          settings={settings}
          index={index}
          onUpdateSetting={onUpdateSetting}
          defaultTitle="Featured speakers"
          limitMin={1}
          limitMax={24}
          limitDefault={6}
          showLayout
          hint="Speakers are pulled from the Speakers page."
        />
      );
    case "sponsors":
      return (
        <DynamicBlockFields
          settings={settings}
          index={index}
          onUpdateSetting={onUpdateSetting}
          defaultTitle="Our sponsors"
          showLayout={false}
          extraFields={
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={blockSettingBool(settings, "group_by_tier", true)}
                onChange={(e) => onUpdateSetting(index, "group_by_tier", e.target.checked)}
              />
              Group sponsors by tier
            </label>
          }
          hint="Sponsors are pulled from the Sponsors page."
        />
      );
    case "exhibitors":
      return (
        <DynamicBlockFields
          settings={settings}
          index={index}
          onUpdateSetting={onUpdateSetting}
          defaultTitle="Exhibitors"
          limitMin={1}
          limitMax={48}
          limitDefault={12}
          showLayout
          hint="Exhibitors are pulled from the Exhibitors page."
        />
      );
    default:
      return null;
  }
}

function DynamicBlockFields({
  settings,
  index,
  onUpdateSetting,
  defaultTitle,
  limitMin,
  limitMax,
  limitDefault,
  showLayout,
  extraFields,
  hint,
}: {
  settings: Record<string, string | boolean>;
  index: number;
  onUpdateSetting: (index: number, key: string, value: string | boolean) => void;
  defaultTitle: string;
  limitMin?: number;
  limitMax?: number;
  limitDefault?: number;
  showLayout: boolean;
  extraFields?: React.ReactNode;
  hint: string;
}) {
  return (
    <div className="space-y-3">
      <div>
        <Label>Section title</Label>
        <Input
          value={blockSettingString(settings, "title", defaultTitle)}
          onChange={(e) => onUpdateSetting(index, "title", e.target.value)}
        />
      </div>
      <div>
        <Label>Subtitle</Label>
        <Textarea
          value={blockSettingString(settings, "subtitle")}
          onChange={(e) => onUpdateSetting(index, "subtitle", e.target.value)}
        />
      </div>
      {limitMin !== undefined && limitMax !== undefined ? (
        <div>
          <Label>Item limit</Label>
          <Input
            type="number"
            min={limitMin}
            max={limitMax}
            value={blockSettingNumber(settings, "limit", limitDefault ?? limitMin)}
            onChange={(e) => onUpdateSetting(index, "limit", e.target.value)}
          />
        </div>
      ) : null}
      {showLayout ? (
        <div>
          <Label htmlFor={`layout-${index}`}>Layout</Label>
          <select
            id={`layout-${index}`}
            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            value={blockSettingString(settings, "layout", "grid")}
            onChange={(e) => onUpdateSetting(index, "layout", e.target.value)}
          >
            <option value="grid">Grid</option>
            <option value="list">List</option>
          </select>
        </div>
      ) : null}
      {extraFields}
      <p className="text-sm text-muted-foreground">{hint}</p>
    </div>
  );
}

function FaqBlockEditor({
  settings,
  onReplaceSettings,
}: {
  settings: Record<string, string | boolean>;
  onReplaceSettings: (settings: Record<string, string | boolean>) => void;
}) {
  const count = Number(blockSettingString(settings, "item_count", "0"));

  const updateItem = (itemIndex: number, field: "question" | "answer", value: string) => {
    onReplaceSettings({
      ...settings,
      [`${field}_${itemIndex}`]: value,
    });
  };

  const addItem = () => {
    onReplaceSettings({ ...settings, item_count: String(count + 1) });
  };

  const removeItem = (itemIndex: number) => {
    const nextSettings = { ...settings };
    delete nextSettings[`question_${itemIndex}`];
    delete nextSettings[`answer_${itemIndex}`];
    const items = Array.from({ length: count }, (_, i) => i).filter((i) => i !== itemIndex);
    const rebuilt: Record<string, string | boolean> = { item_count: String(items.length) };
    items.forEach((currentIndex, newIndex) => {
      rebuilt[`question_${newIndex}`] = blockSettingString(nextSettings, `question_${currentIndex}`);
      rebuilt[`answer_${newIndex}`] = blockSettingString(nextSettings, `answer_${currentIndex}`);
    });
    onReplaceSettings(rebuilt);
  };

  return (
    <div className="space-y-3">
      {Array.from({ length: count }).map((_, itemIndex) => (
        <div key={itemIndex} className="space-y-2 rounded-md border border-border p-3">
          <Input
            placeholder="Question"
            value={blockSettingString(settings, `question_${itemIndex}`)}
            onChange={(e) => updateItem(itemIndex, "question", e.target.value)}
          />
          <Textarea
            placeholder="Answer"
            value={blockSettingString(settings, `answer_${itemIndex}`)}
            onChange={(e) => updateItem(itemIndex, "answer", e.target.value)}
          />
          <Button type="button" variant="outline" size="sm" onClick={() => removeItem(itemIndex)}>
            Remove FAQ item
          </Button>
        </div>
      ))}
      <Button type="button" variant="secondary" size="sm" onClick={addItem}>
        Add FAQ item
      </Button>
    </div>
  );
}
