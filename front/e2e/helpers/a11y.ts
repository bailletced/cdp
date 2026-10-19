import { AxeBuilder, type AxeResults } from "@axe-core/playwright";
import { type Page, expect } from "@playwright/test";

function formatViolations(violations: AxeResults["violations"]): string {
  return violations
    .map((violation) => {
      const nodes = violation.nodes
        .map(
          (node) =>
            `    - ${node.target.join(" ")} (${node.html})\n      ${node.failureSummary}`,
        )
        .join("\n");
      return `  ${violation.id} [${violation.impact}] ${violation.help} (${violation.helpUrl})\n${nodes}`;
    })
    .join("\n");
}

/**
 * Runs axe-core on the current page and fails the test on any violation
 * (F01-T12, US-01.4): WCAG 2.x levels A and AA rules are all enabled, so
 * the audit is blocking in CI.
 */
export async function expectNoA11yViolations(page: Page): Promise<void> {
  const results = await new AxeBuilder({ page })
    .withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa", "wcag22aa"])
    .analyze();

  expect(
    results.violations,
    `axe-core violations (${results.violations.length}):\n${formatViolations(results.violations)}`,
  ).toEqual([]);
}
