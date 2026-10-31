/** Contract v1. Providers never persist progress or decide completion. */
export type PlayerEvent =
  | { type: 'ready'; durationMs: number | null }
  | { type: 'play' | 'pause' | 'position' | 'seeking' | 'seeked' | 'ended'; positionMs: number; rate: number; monotonicMs: number }
  | { type: 'error'; code: string; retryable: boolean };
export type Capabilities = Readonly<{
  position: boolean; duration: boolean; seek: boolean; speed: boolean;
  captions: boolean; transcript: boolean; expiringPlayback: boolean; download: boolean;
}>;
export interface PlayerAdapter {
  mount(container: HTMLElement, descriptor: PlaybackDescriptor, signal: AbortSignal): Promise<void>;
  onEvent(listener: (event: PlayerEvent) => void): () => void;
  getPositionMs(): Promise<number>;
  getDurationMs(): Promise<number | null>;
  seek(positionMs: number): Promise<void>;
  pause(): Promise<void>;
  destroy(): Promise<void>;
}
export type PlaybackDescriptor = Readonly<{
  provider: string; source_revision: number; player_asset: string;
  player_options: Readonly<Record<string, string | number | boolean | null>>;
  capabilities: Capabilities; expires_at: string | null; playback_token?: string;
}>;
