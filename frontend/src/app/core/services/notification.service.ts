import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  /** Phase 3 will replace this with a real notifications endpoint. */
  unreadCount(): Observable<number> {
    return of(0);
  }
}
