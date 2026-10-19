import {
  Toast,
  ToastCloseButton,
  ToastContent,
  ToastDescription,
  ToastProvider,
  ToastTitle,
  toast,
} from "@heroui/react";
import type { ReactNode } from "react";

/**
 * Auto-dismiss delay in milliseconds (F01-T10). Long enough to read the
 * message; React Aria pauses the timers while the region is hovered or
 * focused and the close button stops them (WCAG 2.2.1).
 */
export const NOTIFICATION_TIMEOUT = 8000;

export interface NotifyOptions {
  description?: ReactNode;
  variant?: "default" | "accent" | "success" | "warning" | "danger";
  /** Auto-dismiss delay in ms; 0 keeps the notification open. */
  timeout?: number;
}

/**
 * Shows a confirmation notification (F01-T10). The <Notifications /> region
 * renders it and announces it to screen readers (role="alert" content).
 */
export function notify(message: ReactNode, options: NotifyOptions = {}) {
  const { timeout = NOTIFICATION_TIMEOUT, ...rest } = options;
  return toast(message, { timeout, ...rest });
}

/**
 * Toast region (F01-T10): mounted once at the app root. React Aria's
 * ToastRegion announces toasts (polite live region, role="alert" content),
 * pauses auto-dismiss on hover and focus, and every toast carries a
 * keyboard-operable close button.
 */
export function Notifications() {
  return (
    <ToastProvider placement="bottom">
      {(renderProps) => {
        const content = renderProps.toast.content;
        return (
          <Toast toast={renderProps.toast} variant={content?.variant}>
            <ToastContent>
              {content?.title ? <ToastTitle>{content.title}</ToastTitle> : null}
              {content?.description ? (
                <ToastDescription>{content.description}</ToastDescription>
              ) : null}
            </ToastContent>
            <ToastCloseButton aria-label="Fermer la notification" />
          </Toast>
        );
      }}
    </ToastProvider>
  );
}
