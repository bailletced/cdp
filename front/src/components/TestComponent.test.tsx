import { render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { TestComponent } from "./TestComponent";

describe("TestComponent", () => {
  it("renders with default message", () => {
    render(<TestComponent />);
    expect(screen.getByText("Hello")).toBeInTheDocument();
  });

  it("renders with custom message", () => {
    render(<TestComponent message="Custom" />);
    expect(screen.getByText("Custom")).toBeInTheDocument();
  });

  it("fetches the current account from the API using MSW", async () => {
    render(<TestComponent message="API Test" />);

    await waitFor(() => {
      expect(screen.getByTestId("api-message")).toHaveTextContent(
        "thomas@bailletced.org",
      );
    });
  });

  it("has correct test id", () => {
    render(<TestComponent />);
    expect(screen.getByTestId("test-component")).toBeInTheDocument();
  });
});
