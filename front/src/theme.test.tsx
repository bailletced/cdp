import { readFileSync } from "node:fs";
import { Button, Checkbox, Input, Label, TextField } from "@heroui/react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

// Read the stylesheet from disk: Vitest stubs CSS imports (css: false), so a
// `?raw` import would come back empty. Vitest runs with the front/ directory
// as working directory.
const css = readFileSync("src/index.css", "utf8");

// The carmelite theme and the accessibility overrides live in index.css.
// jsdom has no layout engine, so the visual sizes are locked here by contract
// on the stylesheet itself; the e2e/screenshot pass measures them for real.
function ruleBodies(selector: string): string[] {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const pattern = new RegExp(`${escaped}\\s*\\{([^}]*)\\}`, "g");
  return [...css.matchAll(pattern)].map((match) => match[1]);
}

describe("theme stylesheet (F01-T05)", () => {
  it("sets the body text to 18px", () => {
    expect(ruleBodies("body").join("\n")).toContain("font-size: 1.125rem");
  });

  it("self-hosts the Atkinson Hyperlegible font (CSP font-src 'self')", () => {
    expect(css).toContain("@font-face");
    expect(css).toContain("/fonts/AtkinsonHyperlegible-Regular.woff2");
    expect(css).not.toContain("fonts.gstatic.com");
    expect(css).not.toContain("fonts.googleapis.com");
  });

  it("self-hosts the Alegreya display face for the headings", () => {
    expect(css).toContain("/fonts/Alegreya-Medium.woff2");
    expect(css).toContain("/fonts/Alegreya-Bold.woff2");
    expect(ruleBodies("h1,\n  h2,\n  h3").join("\n")).toContain(
      'font-family: "Alegreya"',
    );
  });

  it("maps the dark carmelite palette (« la veille ») onto the HeroUI tokens", () => {
    expect(css).toContain("color-scheme: dark");
    expect(css).toContain("--background: #211913");
    expect(css).toContain("--surface: #2d231b");
    expect(css).toContain("--foreground: #f3ece0");
    expect(css).toContain("--accent: #e6b762");
    expect(css).toContain("--accent-foreground: #211913");
    expect(css).toContain("--danger: #f2958c");
  });

  it("brings every HeroUI label, field and message to the 18 px body size", () => {
    // The override group starting at .button must cover all text-bearing slots
    const group = css.match(/\.button,[\s\S]*?\n\}\n/);
    expect(group?.[0]).toContain("font-size: 1.125rem");
    expect(group?.[0]).toContain(".label");
    expect(group?.[0]).toContain(".description");
    expect(group?.[0]).toContain(".field-error");
    expect(group?.[0]).toContain(".checkbox__content");
  });
});

describe("touch targets ≥ 44 px (F01-T06)", () => {
  it("makes buttons 44 px tall and wide, lg 48 px", () => {
    const button = ruleBodies(".button").join("\n");
    expect(button).toContain("height: 2.75rem");
    expect(button).toContain("min-width: 2.75rem");
    expect(ruleBodies(".button--lg").join("\n")).toContain("height: 3rem");
  });

  it("makes inputs at least 44 px tall", () => {
    expect(css).toMatch(
      /\[data-slot="input"\][^{]*\{[^}]*min-height: 2\.75rem/s,
    );
  });

  it("makes the whole checkbox control at least 44 px tall, with a 24 px box", () => {
    expect(css).toMatch(
      /\.checkbox,\s*\.checkbox__content\s*\{[^}]*min-height: 2\.75rem/s,
    );
    expect(ruleBodies(".checkbox__control").join("\n")).toContain(
      "width: 1.5rem",
    );
  });

  it("extends the link tap area to 44 px without changing the line box", () => {
    const link = ruleBodies(".link").join("\n");
    expect(link).toContain("padding-block: 0.5rem");
    expect(link).toContain("margin-block: -0.5rem");
  });
});

describe("visible focus (F01-T06)", () => {
  it("widens the HeroUI focus ring to 3 px", () => {
    expect(css).toContain("--tw-ring-width: 3px");
  });

  it("outlines plain interactive elements with the primary brown", () => {
    expect(css).toMatch(
      /a:focus-visible[^{]*\{[^}]*outline: 3px solid var\(--focus\)/s,
    );
  });
});

describe("HeroUI variants used by the theme", () => {
  it("renders the button variants and sizes the overrides key on", () => {
    const { container, rerender } = render(
      <Button variant="primary">Principal</Button>,
    );
    expect(container.querySelector(".button--primary")).not.toBeNull();
    rerender(<Button variant="outline">Contour</Button>);
    expect(container.querySelector(".button--outline")).not.toBeNull();
    rerender(<Button size="sm">Petit</Button>);
    expect(container.querySelector(".button--sm")).not.toBeNull();
    rerender(<Button size="lg">Grand</Button>);
    expect(container.querySelector(".button--lg")).not.toBeNull();
  });

  it("associates the input with its label", () => {
    render(
      <TextField>
        <Label>Adresse e-mail</Label>
        <Input type="email" />
      </TextField>,
    );
    expect(screen.getByLabelText("Adresse e-mail")).toBeInTheDocument();
  });

  it("gives the checkbox an accessible name", () => {
    render(
      <Checkbox>
        <Checkbox.Content>
          <Checkbox.Control>
            <Checkbox.Indicator />
          </Checkbox.Control>
          {"J'accepte la politique de confidentialité"}
        </Checkbox.Content>
      </Checkbox>,
    );
    expect(
      screen.getByRole("checkbox", {
        name: "J'accepte la politique de confidentialité",
      }),
    ).toBeInTheDocument();
  });
});
