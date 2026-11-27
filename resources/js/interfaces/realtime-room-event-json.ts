// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

import LegacyMatchEventJson, { LegacyMatchEventType } from './legacy-match-event-json';

export function eventFromLegacy(event: LegacyMatchEventJson): RealtimeRoomEventJson {
  return {
    created_at: event.timestamp,
    event_detail: null,
    event_type: eventTypeFromLegacy(event),
    id: event.id,
    playlist_item_id: event.game?.id ?? null,
    user_id: event.user_id,
  };
}

function eventTypeFromLegacy(event: LegacyMatchEventJson) {
  const map: Record<LegacyMatchEventType, Exclude<RealtimeRoomEventType, 'name_changed'>> = {
    'host-changed': 'host_changed',
    'match-created': 'room_created',
    'match-disbanded': 'room_disbanded',
    other: 'unknown',
    'player-joined': 'player_joined',
    'player-kicked': 'player_kicked',
    'player-left': 'player_left',
  };

  return event.detail.type === 'other' && event.game != null
    ? 'game_started'
    : map[event.detail.type];
}

export type RealtimeRoomEventType =
  | 'game_started'
  | 'game_aborted'
  | 'game_completed'
  | 'host_changed'
  | 'name_changed'
  | 'player_joined'
  | 'player_kicked'
  | 'player_left'
  | 'room_created'
  | 'room_disbanded'
  | 'unknown';

export interface RoomNameChangedEventDetail {
  new_name: string;
  old_name: string;
}

type RealtimeRoomEventDetail = {
  event_detail: null;
  event_type: Exclude<RealtimeRoomEventType, 'name_changed'>;
} | {
  event_detail: RoomNameChangedEventDetail;
  event_type: 'name_changed';
};

type RealtimeRoomEventJson = {
  created_at: string;
  id: number;
  playlist_item_id: null | number;
  user_id: null | number;
} & RealtimeRoomEventDetail;

export default RealtimeRoomEventJson;
