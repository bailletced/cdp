import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { RichTextEditor } from "./RichTextEditor";

function renderEditor(value = "<p>Bonjour</p>", onChange = vi.fn()) {
  render(<RichTextEditor label="Corps" value={value} onChange={onChange} />);

  return { onChange };
}

describe("RichTextEditor (F06-T10)", () => {
  it("exposes a labelled multiline text box", async () => {
    renderEditor();

    const box = await screen.findByRole("textbox", { name: "Corps" });
    expect(box).toHaveAttribute("aria-multiline", "true");
    expect(box).toHaveTextContent("Bonjour");
  });

  it("offers a labelled toolbar whose buttons announce their state", async () => {
    renderEditor("<p><strong>Bonjour</strong></p>");

    const toolbar = await screen.findByRole("toolbar", {
      name: "Mise en forme",
    });
    expect(toolbar).toBeInTheDocument();

    // The cursor sits in bold text: « Gras » is pressed, the others are not
    expect(screen.getByRole("button", { name: "Gras" })).toHaveAttribute(
      "aria-pressed",
      "true",
    );
    expect(screen.getByRole("button", { name: "Italique" })).toHaveAttribute(
      "aria-pressed",
      "false",
    );
    expect(screen.getByRole("button", { name: "Citation" })).toHaveAttribute(
      "aria-pressed",
      "false",
    );
  });

  it("turns the paragraph into a quote and reports the new HTML", async () => {
    const { onChange } = renderEditor();

    fireEvent.click(await screen.findByRole("button", { name: "Citation" }));

    await waitFor(() =>
      expect(onChange).toHaveBeenLastCalledWith(
        "<blockquote><p>Bonjour</p></blockquote>",
      ),
    );
    expect(screen.getByRole("button", { name: "Citation" })).toHaveAttribute(
      "aria-pressed",
      "true",
    );
  });

  it("undoes the last change", async () => {
    const { onChange } = renderEditor();

    fireEvent.click(await screen.findByRole("button", { name: "Citation" }));
    fireEvent.click(screen.getByRole("button", { name: "Annuler" }));

    await waitFor(() =>
      expect(onChange).toHaveBeenLastCalledWith("<p>Bonjour</p>"),
    );
  });

  it("moves the focus between the toolbar buttons with the arrow keys", async () => {
    renderEditor();

    const bold = await screen.findByRole("button", { name: "Gras" });
    const italic = screen.getByRole("button", { name: "Italique" });
    const redo = screen.getByRole("button", { name: "Rétablir" });

    // A single tab stop for the whole toolbar (roving tabindex)
    expect(bold).toHaveAttribute("tabindex", "0");
    expect(italic).toHaveAttribute("tabindex", "-1");

    bold.focus();
    fireEvent.keyDown(bold, { key: "ArrowRight" });
    expect(italic).toHaveFocus();
    expect(italic).toHaveAttribute("tabindex", "0");

    fireEvent.keyDown(italic, { key: "End" });
    expect(redo).toHaveFocus();

    fireEvent.keyDown(redo, { key: "ArrowRight" });
    expect(bold).toHaveFocus();
  });

  it("reports an empty body as an empty string", async () => {
    const { onChange } = renderEditor("");

    fireEvent.click(await screen.findByRole("button", { name: "Citation" }));
    fireEvent.click(screen.getByRole("button", { name: "Annuler" }));

    // Never "<p></p>": an empty editor means no body
    await waitFor(() => expect(onChange).toHaveBeenLastCalledWith(""));
  });

  it("links the description and the error message to the text box", async () => {
    render(
      <RichTextEditor
        label="Corps"
        value=""
        onChange={vi.fn()}
        description="Shift + Entrée pour un retour à la ligne."
        errorMessage="Le corps du texte est obligatoire."
      />,
    );

    const box = await screen.findByRole("textbox", { name: "Corps" });
    expect(box).toHaveAttribute("aria-invalid", "true");
    expect(box).toHaveAccessibleDescription(
      "Shift + Entrée pour un retour à la ligne. Le corps du texte est obligatoire.",
    );
  });

  it("follows a value set from outside (form pre-fill)", async () => {
    const onChange = vi.fn();
    const { rerender } = render(
      <RichTextEditor label="Corps" value="" onChange={onChange} />,
    );

    rerender(
      <RichTextEditor
        label="Corps"
        value="<p>Psaume 23</p>"
        onChange={onChange}
      />,
    );

    expect(
      await screen.findByRole("textbox", { name: "Corps" }),
    ).toHaveTextContent("Psaume 23");
    // A pre-fill is not a user edit
    expect(onChange).not.toHaveBeenCalled();
  });
});
