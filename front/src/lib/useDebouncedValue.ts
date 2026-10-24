import { useEffect, useState } from "react";

/**
 * The value, once it has stopped changing for `delayMs`: a search waits for
 * a pause in the typing before querying the API.
 */
export function useDebouncedValue<T>(value: T, delayMs = 300): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timeout = setTimeout(() => setDebounced(value), delayMs);

    return () => clearTimeout(timeout);
  }, [value, delayMs]);

  return debounced;
}
