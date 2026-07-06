"use client";

import { useEffect, useRef } from "react";
import { Bold, Italic, Link2, List, ListOrdered } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

interface AboutRichTextEditorProps {
  id?: string;
  value: string;
  onChange: (html: string) => void;
}

export function AboutRichTextEditor({ id, value, onChange }: AboutRichTextEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const editor = editorRef.current;
    if (!editor) return;
    if (editor.innerHTML !== value) {
      editor.innerHTML = value;
    }
  }, [value]);

  const applyCommand = (command: string, commandValue?: string) => {
    editorRef.current?.focus();
    document.execCommand(command, false, commandValue);
    onChange(editorRef.current?.innerHTML ?? "");
  };

  const handleLink = () => {
    const url = window.prompt("Enter link URL");
    if (!url) return;
    applyCommand("createLink", url);
  };

  return (
    <div className="space-y-2">
      <Label htmlFor={id}>Body</Label>
      <div className="overflow-hidden rounded-md border border-input">
        <div className="flex flex-wrap gap-1 border-b border-border bg-muted/40 p-2">
          <Button type="button" size="sm" variant="ghost" onClick={() => applyCommand("bold")}>
            <Bold className="h-4 w-4" aria-hidden />
            <span className="sr-only">Bold</span>
          </Button>
          <Button type="button" size="sm" variant="ghost" onClick={() => applyCommand("italic")}>
            <Italic className="h-4 w-4" aria-hidden />
            <span className="sr-only">Italic</span>
          </Button>
          <Button type="button" size="sm" variant="ghost" onClick={handleLink}>
            <Link2 className="h-4 w-4" aria-hidden />
            <span className="sr-only">Link</span>
          </Button>
          <Button
            type="button"
            size="sm"
            variant="ghost"
            onClick={() => applyCommand("insertUnorderedList")}
          >
            <List className="h-4 w-4" aria-hidden />
            <span className="sr-only">Bullet list</span>
          </Button>
          <Button
            type="button"
            size="sm"
            variant="ghost"
            onClick={() => applyCommand("insertOrderedList")}
          >
            <ListOrdered className="h-4 w-4" aria-hidden />
            <span className="sr-only">Numbered list</span>
          </Button>
        </div>
        <div
          id={id}
          ref={editorRef}
          contentEditable
          suppressContentEditableWarning
          className="prose prose-neutral dark:prose-invert min-h-[160px] max-w-none px-3 py-3 text-sm focus:outline-none"
          onInput={() => onChange(editorRef.current?.innerHTML ?? "")}
        />
      </div>
      <p className="text-xs text-muted-foreground">
        Basic formatting only: bold, italic, links, and lists.
      </p>
    </div>
  );
}
