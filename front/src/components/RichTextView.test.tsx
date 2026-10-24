import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { RichTextEditor } from "./RichTextEditor";
import { RichTextView } from "./RichTextView";

const PSALM =
  "<p>Le Seigneur est mon berger :<br>je ne manque de rien.</p><blockquote><p><strong>Sur des prés</strong> d'herbe fraîche, <em>il me fait reposer</em>.</p></blockquote>";

describe("RichTextView (F06-T11)", () => {
  it("renders the server-cleaned HTML with its line breaks, emphasis and quotes", () => {
    const { container } = render(<RichTextView html={PSALM} />);

    expect(container.querySelector("p br")).not.toBeNull();
    expect(
      screen.getByText("Sur des prés", { selector: "blockquote strong" }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("il me fait reposer", { selector: "em" }),
    ).toBeInTheDocument();
  });

  it("shares the reading typography of the editor preview", async () => {
    const { container } = render(<RichTextView html={PSALM} />);
    render(<RichTextEditor label="Corps" value={PSALM} onChange={vi.fn()} />);

    expect(container.firstElementChild).toHaveClass("rich-text");
    expect(await screen.findByRole("textbox", { name: "Corps" })).toHaveClass(
      "rich-text",
    );
  });
});
