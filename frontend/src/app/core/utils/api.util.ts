import { Paginated } from '../models/api.model';
import { ListQuery } from '../models/organization.model';

export function unwrapList<T>(data: T[] | Paginated<T> | null | undefined): T[] {
  if (!data) {
    return [];
  }
  if (Array.isArray(data)) {
    return data;
  }
  return data.items ?? [];
}

export function unwrapPaginated<T>(data: T[] | Paginated<T> | null | undefined): Paginated<T> {
  if (!data) {
    return {
      items: [],
      pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
    };
  }
  if (Array.isArray(data)) {
    return {
      items: data,
      pagination: {
        current_page: 1,
        per_page: data.length || 15,
        total: data.length,
        last_page: 1,
      },
    };
  }
  return data;
}

export function toHttpParams(query?: ListQuery | Record<string, string | number | boolean | null | undefined>): Record<string, string | number | boolean> {
  if (!query) {
    return {};
  }
  const params: Record<string, string | number | boolean> = {};
  for (const [key, value] of Object.entries(query)) {
    if (value === null || value === undefined || value === '') {
      continue;
    }
    params[key] = value;
  }
  return params;
}
