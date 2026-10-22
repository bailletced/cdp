import { describe, expect, it } from "vitest";
import { contrastRatio } from "./contrast";

describe("contrastRatio (WCAG 2.x)", () => {
  it("is 21 for black on white and 1 for a color on itself", () => {
    expect(contrastRatio("#000000", "#ffffff")).toBeCloseTo(21, 5);
    expect(contrastRatio("#6b4226", "#6b4226")).toBeCloseTo(1, 5);
  });

  it("is symmetric in its arguments", () => {
    expect(contrastRatio("#2b2118", "#faf5ec")).toBeCloseTo(
      contrastRatio("#faf5ec", "#2b2118"),
      10,
    );
  });

  it("matches the theme's documented ratios", () => {
    // Ratios documented in index.css for the carmelite palette
    expect(contrastRatio("#2b2118", "#ffffff")).toBeCloseTo(15.75, 1);
    expect(contrastRatio("#6e6259", "#ffffff")).toBeCloseTo(5.91, 1);
    expect(contrastRatio("#6b4226", "#ffffff")).toBeCloseTo(8.63, 1);
  });
});
